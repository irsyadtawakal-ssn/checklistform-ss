<?php
declare(strict_types=1);

require_once __DIR__ . '/../helpers/auth.php';
require_once __DIR__ . '/../helpers/response.php';

function requireAuth(): array
{
    $user = currentUser();

    if ($user && isset($_SESSION['last_activity'])) {
        if (time() - $_SESSION['last_activity'] > SESSION_LIFETIME) {
            destroySession();
            jsonError('Sesi telah habis. Silakan login kembali.', 401);
        }
        $_SESSION['last_activity'] = time();
    }

    if (!$user) {
        jsonError('Unauthorized. Silakan login terlebih dahulu.', 401);
    }

    return $user;
}
