<?php
declare(strict_types=1);

require_once __DIR__ . '/../src/bootstrap.php';
require_once ROOT_PATH . '/src/helpers/auth.php';

startSession();
$user = currentUser();

if (!$user) {
    header('Location: /login');
    exit;
}

// Redirect ke halaman sesuai role
$go = match ($user['role']) {
    'outlet'        => '/checklist',
    'spv', 'owner'  => '/dashboard',
    'admin'         => '/admin',
    default         => '/login',
};

header("Location: {$go}");
exit;
