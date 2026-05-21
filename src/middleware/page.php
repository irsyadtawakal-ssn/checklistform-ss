<?php
declare(strict_types=1);

/**
 * Guard untuk halaman HTML (bukan API).
 * Kalau gagal → redirect ke login atau tampilkan 403,
 * bukan JSON error.
 */
function pageRequireAuth(): array
{
    startSession();
    $user = currentUser();

    if (!$user) {
        header('Location: /login?reason=session');
        exit;
    }

    if (isset($_SESSION['last_activity']) && time() - $_SESSION['last_activity'] > SESSION_LIFETIME) {
        destroySession();
        header('Location: /login?reason=idle');
        exit;
    }

    $_SESSION['last_activity'] = time();
    return $user;
}

function pageRequireRole(string ...$roles): array
{
    $user = pageRequireAuth();

    if (!in_array($user['role'], $roles, true)) {
        http_response_code(403);
        require ROOT_PATH . '/public/403.php';
        exit;
    }

    return $user;
}
