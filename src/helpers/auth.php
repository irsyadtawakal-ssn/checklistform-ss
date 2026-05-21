<?php
declare(strict_types=1);

function startSession(): void
{
    if (session_status() !== PHP_SESSION_NONE) {
        return;
    }

    session_name(SESSION_NAME);
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'secure'   => (defined('APP_ENV') && APP_ENV === 'production'),
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

function currentUser(): ?array
{
    startSession();
    return $_SESSION['user'] ?? null;
}

function setSessionUser(array $user): void
{
    startSession();
    session_regenerate_id(true);
    $_SESSION['user']          = $user;
    $_SESSION['last_activity'] = time();
}

function destroySession(): void
{
    startSession();
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params['path'], $params['domain'],
            $params['secure'], $params['httponly']
        );
    }
    session_destroy();
}

function hashPassword(string $password): string
{
    return password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
}

function verifyPassword(string $password, string $hash): bool
{
    return password_verify($password, $hash);
}
