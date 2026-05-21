<?php
declare(strict_types=1);

require_once __DIR__ . '/../../src/bootstrap.php';
require_once ROOT_PATH . '/src/helpers/db.php';
require_once ROOT_PATH . '/src/helpers/response.php';
require_once ROOT_PATH . '/src/helpers/csrf.php';
require_once ROOT_PATH . '/src/middleware/role.php';

$user   = requireRole('admin');
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$pdo    = db();

// ─── GET /api/admin/spv-areas?spv_id= ─────────────────────────────────────
if ($method === 'GET') {
    // Daftar SPV dengan outlet yang di-assign
    $spvRows = $pdo->query(
        "SELECT u.id, u.username, u.full_name
         FROM users u WHERE u.role = 'spv' AND u.active = 1 ORDER BY u.full_name"
    )->fetchAll();

    $assignments = $pdo->query(
        "SELECT a.spv_id, a.outlet_id, o.code AS outlet_code, o.name AS outlet_name
         FROM spv_outlet_assignments a
         JOIN outlets o ON o.id = a.outlet_id
         ORDER BY a.spv_id, o.code"
    )->fetchAll();

    // Group assignments by spv_id
    $assignMap = [];
    foreach ($assignments as $a) {
        $assignMap[(int)$a['spv_id']][] = [
            'outlet_id'   => (int) $a['outlet_id'],
            'outlet_code' => $a['outlet_code'],
            'outlet_name' => $a['outlet_name'],
        ];
    }

    $result = [];
    foreach ($spvRows as $s) {
        $sid = (int)$s['id'];
        $result[] = [
            'spv_id'    => $sid,
            'username'  => $s['username'],
            'full_name' => $s['full_name'],
            'outlets'   => $assignMap[$sid] ?? [],   // kosong = akses semua
            'global'    => empty($assignMap[$sid]),   // true = lihat semua outlet
        ];
    }

    jsonOk($result);
}

// ─── POST /api/admin/spv-areas  (set assignments untuk satu SPV) ──────────
// Body: { spv_id: int, outlet_ids: [int, ...] }
// outlet_ids kosong = hapus semua assignment (SPV jadi global)
if ($method === 'POST') {
    csrfValidate();
    $body     = json_decode(file_get_contents('php://input'), true);
    $spvId    = (int) ($body['spv_id']    ?? 0);
    $outletIds = array_map('intval', $body['outlet_ids'] ?? []);

    if (!$spvId) jsonError('spv_id diperlukan', 400);

    // Pastikan user adalah SPV
    $spvChk = $pdo->prepare("SELECT id FROM users WHERE id = ? AND role = 'spv' LIMIT 1");
    $spvChk->execute([$spvId]);
    if (!$spvChk->fetch()) jsonError('SPV tidak ditemukan', 404);

    $pdo->beginTransaction();
    try {
        // Hapus semua assignment lama untuk SPV ini
        $pdo->prepare('DELETE FROM spv_outlet_assignments WHERE spv_id = ?')->execute([$spvId]);

        // Insert assignment baru
        if ($outletIds) {
            $ins = $pdo->prepare(
                'INSERT IGNORE INTO spv_outlet_assignments (spv_id, outlet_id, assigned_by) VALUES (?, ?, ?)'
            );
            foreach ($outletIds as $oid) {
                if ($oid > 0) $ins->execute([$spvId, $oid, (int)$user['id']]);
            }
        }

        $pdo->commit();
    } catch (\Throwable $e) {
        $pdo->rollBack();
        jsonError('Gagal menyimpan area assignment', 500);
    }

    $count = count($outletIds);
    jsonOk([
        'spv_id'      => $spvId,
        'outlet_count'=> $count,
        'global'      => $count === 0,
        'message'     => $count === 0
            ? 'Area dihapus — SPV sekarang bisa melihat semua outlet.'
            : "{$count} outlet di-assign ke SPV ini.",
    ]);
}

jsonError('Method tidak didukung', 405);
