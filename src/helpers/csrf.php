<?php
declare(strict_types=1);

function csrfToken(): string
{
    startSession();

    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

function csrfValidate(): void
{
    startSession();

    $token = $_SERVER['HTTP_X_CSRF_TOKEN']
        ?? $_POST['_csrf'] ?? '';

    if (!hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
        jsonError('CSRF token tidak valid.', 403);
    }
}

function csrfRotate(): void
{
    startSession();
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
