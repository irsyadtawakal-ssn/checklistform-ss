<?php
declare(strict_types=1);

require_once __DIR__ . '/../../src/bootstrap.php';
require_once ROOT_PATH . '/src/helpers/db.php';
require_once ROOT_PATH . '/src/helpers/response.php';
require_once ROOT_PATH . '/src/middleware/role.php';

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'GET') jsonError('Method tidak didukung', 405);

requireRole('admin');

$pdo    = db();
$limit  = min((int) ($_GET['limit'] ?? 100), 200);
$offset = max((int) ($_GET['offset'] ?? 0), 0);
$action = $_GET['action'] ?? '';

$where  = '';
$params = [];
if ($action) {
    $where    = 'WHERE al.action LIKE ?';
    $params[] = '%' . $action . '%';
}

$rows = $pdo->prepare(
    "SELECT al.id, al.action, al.target_type, al.target_id,
            al.payload_json, al.ip, al.created_at,
            u.username, u.full_name
     FROM audit_log al
     LEFT JOIN users u ON u.id = al.user_id
     {$where}
     ORDER BY al.created_at DESC
     LIMIT {$limit} OFFSET {$offset}"
);
$rows->execute($params);
$logs = $rows->fetchAll();

foreach ($logs as &$log) {
    $log['payload'] = $log['payload_json'] ? json_decode($log['payload_json'], true) : null;
    unset($log['payload_json']);
}

$total = (int) $pdo->query("SELECT COUNT(*) FROM audit_log")->fetchColumn();

jsonOk(['logs' => $logs, 'total' => $total, 'limit' => $limit, 'offset' => $offset]);
