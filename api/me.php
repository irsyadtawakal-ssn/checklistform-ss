<?php
declare(strict_types=1);

require_once __DIR__ . '/../src/bootstrap.php';
require_once ROOT_PATH . '/src/helpers/response.php';
require_once ROOT_PATH . '/src/helpers/auth.php';
require_once ROOT_PATH . '/src/middleware/auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    jsonError('Method tidak diizinkan', 405);
}

$user = requireAuth();

jsonOk(['user' => $user]);
