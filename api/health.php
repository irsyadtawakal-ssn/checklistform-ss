<?php
declare(strict_types=1);

require_once __DIR__ . '/../src/bootstrap.php';
require_once ROOT_PATH . '/src/helpers/db.php';

header('Content-Type: application/json; charset=UTF-8');

try {
    db()->query('SELECT 1');
    http_response_code(200);
    echo json_encode(['ok' => true, 'db' => 'connected'], JSON_UNESCAPED_UNICODE);
} catch (PDOException $e) {
    error_log('Health check DB error: ' . $e->getMessage());
    http_response_code(503);
    echo json_encode(['ok' => false, 'db' => 'disconnected'], JSON_UNESCAPED_UNICODE);
}
