<?php
declare(strict_types=1);

require_once __DIR__ . '/../../src/bootstrap.php';
require_once ROOT_PATH . '/src/helpers/db.php';
require_once ROOT_PATH . '/src/helpers/response.php';
require_once ROOT_PATH . '/src/helpers/auth.php';
require_once ROOT_PATH . '/src/helpers/rate_limit.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonError('Method tidak diizinkan', 405);
}

$body     = json_decode(file_get_contents('php://input'), true) ?? [];
$username = trim($body['username'] ?? '');
$password = $body['password'] ?? '';

if ($username === '' || $password === '') {
    jsonError('Username dan password wajib diisi', 400);
}

// Rate limit: maks 20 percobaan per 15 menit per IP
rateLimitCheck('login', clientIp(), 20, 900);

$stmt = db()->prepare(
    'SELECT id, username, password_hash, full_name, role, outlet_id, active
     FROM users WHERE username = ? LIMIT 1'
);
$stmt->execute([$username]);
$user = $stmt->fetch();

if (!$user || !verifyPassword($password, $user['password_hash'])) {
    jsonError('Username atau password salah', 401);
}

if (!(bool) $user['active']) {
    jsonError('Akun tidak aktif. Hubungi admin.', 403);
}

// Login sukses — reset rate limit & set session
rateLimitReset('login', clientIp());
unset($user['password_hash']);
setSessionUser($user);

// Update last_login_at
db()->prepare('UPDATE users SET last_login_at = NOW() WHERE id = ?')
    ->execute([$user['id']]);

$redirect = match ($user['role']) {
    'outlet' => '/checklist',
    'spv'    => '/dashboard',
    'owner'  => '/dashboard',
    'admin'  => '/admin',
    default  => '/',
};

jsonOk(['user' => $user, 'redirect' => $redirect]);
