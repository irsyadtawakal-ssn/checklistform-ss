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

// ─── GET /api/admin/outlets ───────────────────────────────────────────────
if ($method === 'GET') {
    $rows = $pdo->query(
        "SELECT id, code, name, type, address, daily_sales_target, active, created_at
         FROM outlets ORDER BY code"
    )->fetchAll();

    foreach ($rows as &$r) {
        $r['active']             = (bool) $r['active'];
        $r['daily_sales_target'] = (int)  $r['daily_sales_target'];
    }
    jsonOk($rows);
}

// ─── POST /api/admin/outlets  (create) ───────────────────────────────────
if ($method === 'POST') {
    csrfValidate();
    $body = json_decode(file_get_contents('php://input'), true);

    $code   = trim($body['code']   ?? '');
    $name   = trim($body['name']   ?? '');
    $type   = $body['type']  ?? 'internal';
    $addr   = trim($body['address'] ?? '');
    $target = (int) ($body['daily_sales_target'] ?? 0);

    if (!$code || !$name)                            jsonError('Kode dan nama outlet wajib diisi', 422);
    if (!preg_match('/^[\w\-]{2,20}$/', $code))     jsonError('Kode outlet hanya boleh huruf, angka, dan tanda hubung (2-20 karakter)', 422);
    if (!in_array($type, ['internal', 'mitra'], true)) jsonError('Tipe outlet tidak valid', 422);

    // Cek duplikat kode
    $dup = $pdo->prepare('SELECT id FROM outlets WHERE code = ? LIMIT 1');
    $dup->execute([$code]);
    if ($dup->fetch()) jsonError('Kode outlet sudah digunakan', 409);

    $ins = $pdo->prepare(
        'INSERT INTO outlets (code, name, type, address, daily_sales_target) VALUES (?, ?, ?, ?, ?)'
    );
    $ins->execute([$code, $name, $type, $addr ?: null, $target]);
    $newId = (int) $pdo->lastInsertId();

    auditLog($pdo, (int)$user['id'], 'outlet_create', 'outlet', $newId);

    $row = $pdo->prepare('SELECT id, code, name, type, address, daily_sales_target, active, created_at FROM outlets WHERE id = ?');
    $row->execute([$newId]);
    $outlet = $row->fetch();
    $outlet['active']             = (bool) $outlet['active'];
    $outlet['daily_sales_target'] = (int)  $outlet['daily_sales_target'];
    jsonOk($outlet, 201);
}

// ─── PUT /api/admin/outlets?id= (update) ─────────────────────────────────
if ($method === 'PUT') {
    csrfValidate();
    $id   = (int) ($_GET['id'] ?? 0);
    $body = json_decode(file_get_contents('php://input'), true);

    if (!$id) jsonError('id outlet diperlukan', 400);

    $chk = $pdo->prepare('SELECT id FROM outlets WHERE id = ? LIMIT 1');
    $chk->execute([$id]);
    if (!$chk->fetch()) jsonError('Outlet tidak ditemukan', 404);

    $fields = [];
    $params = [];

    if (isset($body['name'])) {
        $name = trim($body['name']);
        if (!$name) jsonError('Nama tidak boleh kosong', 422);
        $fields[] = 'name = ?'; $params[] = $name;
    }
    if (isset($body['type'])) {
        if (!in_array($body['type'], ['internal', 'mitra'], true)) jsonError('Tipe tidak valid', 422);
        $fields[] = 'type = ?'; $params[] = $body['type'];
    }
    if (isset($body['address'])) {
        $fields[] = 'address = ?'; $params[] = trim($body['address']) ?: null;
    }
    if (isset($body['daily_sales_target'])) {
        $fields[] = 'daily_sales_target = ?'; $params[] = max(0, (int)$body['daily_sales_target']);
    }
    if (isset($body['active'])) {
        $fields[] = 'active = ?'; $params[] = $body['active'] ? 1 : 0;
    }
    if (!$fields) jsonError('Tidak ada field yang diubah', 400);

    $params[] = $id;
    $pdo->prepare('UPDATE outlets SET ' . implode(', ', $fields) . ' WHERE id = ?')->execute($params);

    auditLog($pdo, (int)$user['id'], 'outlet_update', 'outlet', $id, ['fields' => array_map(fn($f) => explode(' =', $f)[0], $fields)]);

    $row = $pdo->prepare('SELECT id, code, name, type, address, daily_sales_target, active, created_at FROM outlets WHERE id = ?');
    $row->execute([$id]);
    $outlet = $row->fetch();
    $outlet['active']             = (bool) $outlet['active'];
    $outlet['daily_sales_target'] = (int)  $outlet['daily_sales_target'];
    jsonOk($outlet);
}

jsonError('Method tidak didukung', 405);

// ── Helpers ────────────────────────────────────────────────────────────────
function auditLog(\PDO $pdo, int $userId, string $action, string $targetType, int $targetId, array $payload = []): void
{
    $pdo->prepare(
        "INSERT INTO audit_log (user_id, action, target_type, target_id, payload_json, ip)
         VALUES (?, ?, ?, ?, ?, ?)"
    )->execute([
        $userId, $action, $targetType, $targetId,
        $payload ? json_encode($payload, JSON_UNESCAPED_UNICODE) : null,
        $_SERVER['REMOTE_ADDR'] ?? null,
    ]);
}
