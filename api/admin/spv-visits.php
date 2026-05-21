<?php
declare(strict_types=1);

require_once __DIR__ . '/../../src/bootstrap.php';
require_once ROOT_PATH . '/src/helpers/db.php';
require_once ROOT_PATH . '/src/helpers/response.php';
require_once ROOT_PATH . '/src/middleware/role.php';

$user   = requireRole('spv', 'admin');
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$pdo    = db();

if ($method !== 'GET') jsonError('Method tidak didukung', 405);

// ── GET ?detail=1&id={n} — detail satu visit (query param, hindari WAF path block) ──
if (isset($_GET['detail']) && isset($_GET['id'])) {
    $visitId = (int) $_GET['id'];
    if (!$visitId) jsonError('id tidak valid', 400);

    $stmt = $pdo->prepare("
        SELECT v.*, o.name AS outlet_name, o.code AS outlet_code,
               u.full_name AS spv_name
        FROM   spv_visits v
        JOIN   outlets o ON o.id = v.outlet_id
        JOIN   users   u ON u.id = v.spv_id
        WHERE  v.id = ?
    ");
    $stmt->execute([$visitId]);
    $visit = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$visit) jsonError('Visit tidak ditemukan', 404);
    if ($user['role'] === 'spv' && (int) $visit['spv_id'] !== (int) $user['id']) {
        jsonError('Akses ditolak', 403);
    }

    $pStmt = $pdo->prepare("
        SELECT id, file_path, thumb_path, label, tag
        FROM   spv_visit_photos
        WHERE  visit_id = ?
        ORDER  BY id ASC
    ");
    $pStmt->execute([$visitId]);
    $photos = $pStmt->fetchAll(PDO::FETCH_ASSOC);

    $eStmt = $pdo->prepare("
        SELECT id, name, role, eval_json, notes
        FROM   spv_visit_employees
        WHERE  visit_id = ?
        ORDER  BY id ASC
    ");
    $eStmt->execute([$visitId]);
    $employees = $eStmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($employees as &$emp) {
        $emp['eval_json'] = json_decode($emp['eval_json'] ?? '[]', true);
    }
    unset($emp);

    $visit['payload_json'] = json_decode($visit['payload_json'] ?? '{}', true);
    $visit['photos']       = $photos;
    $visit['employees']    = $employees;

    jsonOk($visit);
}

// ── GET /api/admin/spv-visits — daftar kunjungan dengan filter ───────────────
$from     = $_GET['from']      ?? date('Y-m-01');
$to       = $_GET['to']        ?? date('Y-m-d');
$outletId = (int) ($_GET['outlet_id'] ?? 0);
$spvId    = (int) ($_GET['spv_id']    ?? 0);
$page     = max(1, (int) ($_GET['page'] ?? 1));
$limit    = min(100, max(10, (int) ($_GET['limit'] ?? 20)));
$offset   = ($page - 1) * $limit;

// SPV hanya boleh lihat kunjungannya sendiri
if ($user['role'] === 'spv') {
    $spvId = (int) $user['id'];
}

$where  = ['v.visit_date BETWEEN ? AND ?'];
$params = [$from, $to];

if ($outletId) { $where[] = 'v.outlet_id = ?'; $params[] = $outletId; }
if ($spvId)    { $where[] = 'v.spv_id = ?';    $params[] = $spvId;    }

$whereSQL = 'WHERE ' . implode(' AND ', $where);

// Total count
$countStmt = $pdo->prepare("
    SELECT COUNT(*) FROM spv_visits v $whereSQL
");
$countStmt->execute($params);
$total = (int) $countStmt->fetchColumn();

// Data
$dataStmt = $pdo->prepare("
    SELECT v.id, v.visit_date, v.time_arrive, v.time_leave, v.visit_shift,
           v.pic_on_duty, v.submitted_at,
           o.name AS outlet_name, o.code AS outlet_code,
           u.full_name AS spv_name,
           (SELECT COUNT(*) FROM spv_visit_photos p WHERE p.visit_id = v.id) AS photo_count
    FROM   spv_visits v
    JOIN   outlets o ON o.id = v.outlet_id
    JOIN   users   u ON u.id = v.spv_id
    $whereSQL
    ORDER  BY v.visit_date DESC, v.submitted_at DESC
    LIMIT  $limit OFFSET $offset
");
$dataStmt->execute($params);
$visits = $dataStmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($visits as &$v) {
    $v['photo_count'] = (int) $v['photo_count'];
}
unset($v);

jsonOk([
    'visits'     => $visits,
    'total'      => $total,
    'page'       => $page,
    'limit'      => $limit,
    'total_page' => (int) ceil($total / $limit),
]);
