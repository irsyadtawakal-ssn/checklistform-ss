<?php
declare(strict_types=1);

require_once __DIR__ . '/../../src/bootstrap.php';
require_once ROOT_PATH . '/src/helpers/db.php';
require_once ROOT_PATH . '/src/helpers/response.php';
require_once ROOT_PATH . '/src/helpers/csrf.php';
require_once ROOT_PATH . '/src/middleware/role.php';

$user   = requireRole('admin');
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$pdo    = db();

// ─── GET /api/admin/users ─────────────────────────────────────────────────
if ($method === 'GET') {
    $rows = $pdo->query(
        "SELECT u.id, u.username, u.full_name, u.role, u.outlet_id, u.active,
                u.last_login_at, u.created_at,
                o.code AS outlet_code, o.name AS outlet_name
         FROM users u
         LEFT JOIN outlets o ON o.id = u.outlet_id
         ORDER BY u.role, u.username"
    )->fetchAll();

    foreach ($rows as &$r) {
        $r['active'] = (bool) $r['active'];
    }
    jsonOk($rows);
}

// ─── POST /api/admin/users  (create) ─────────────────────────────────────
if ($method === 'POST') {
    csrfValidate();
    $body = json_decode(file_get_contents('php://input'), true);

    $username  = trim(strtolower($body['username'] ?? ''));
    $fullName  = trim($body['full_name'] ?? '');
    $role      = $body['role'] ?? '';
    $outletId  = isset($body['outlet_id']) ? (int)$body['outlet_id'] : null;

    if (!$username || !$fullName)                              jsonError('Username dan nama lengkap wajib diisi', 422);
    if (!preg_match('/^[a-z0-9_\.]{3,30}$/', $username))     jsonError('Username hanya boleh huruf kecil, angka, titik, underscore (3-30 karakter)', 422);
    if (!in_array($role, ['outlet', 'spv', 'owner', 'admin'], true)) jsonError('Role tidak valid', 422);
    if ($role === 'outlet' && !$outletId)                     jsonError('Outlet wajib dipilih untuk user role outlet', 422);

    // Cek duplikat username
    $dup = $pdo->prepare('SELECT id FROM users WHERE username = ? LIMIT 1');
    $dup->execute([$username]);
    if ($dup->fetch()) jsonError('Username sudah digunakan', 409);

    // Jika outlet dipilih, pastikan ada
    if ($outletId) {
        $outChk = $pdo->prepare('SELECT id FROM outlets WHERE id = ? AND active = 1 LIMIT 1');
        $outChk->execute([$outletId]);
        if (!$outChk->fetch()) jsonError('Outlet tidak ditemukan atau tidak aktif', 404);
    }

    // Generate password random 8 karakter
    $plain = generatePassword();
    $hash  = password_hash($plain, PASSWORD_BCRYPT);

    $ins = $pdo->prepare(
        'INSERT INTO users (username, password_hash, full_name, role, outlet_id) VALUES (?, ?, ?, ?, ?)'
    );
    $ins->execute([$username, $hash, $fullName, $role, $role === 'outlet' ? $outletId : null]);
    $newId = (int) $pdo->lastInsertId();

    auditLog($pdo, (int)$user['id'], 'user_create', 'user', $newId, ['role' => $role]);

    jsonOk([
        'id'            => $newId,
        'username'      => $username,
        'full_name'     => $fullName,
        'role'          => $role,
        'outlet_id'     => $role === 'outlet' ? $outletId : null,
        'active'        => true,
        'plain_password' => $plain, // ditampilkan sekali, tidak disimpan plain
    ], 201);
}

// ─── PUT /api/admin/users?id= (update / reset password) ──────────────────
if ($method === 'PUT') {
    csrfValidate();
    $id   = (int) ($_GET['id'] ?? 0);
    $body = json_decode(file_get_contents('php://input'), true);

    // ── Reset semua password sekaligus (tidak butuh ?id=) ─────────────────
    if (($body['action'] ?? '') === 'reset_all_passwords') {
        $role = $body['role'] ?? 'outlet';
        if (!in_array($role, ['outlet', 'spv', 'owner', 'all'], true)) {
            jsonError('Role tidak valid', 422);
        }

        if ($role === 'all') {
            $stmt = $pdo->query(
                "SELECT u.id, u.username, u.full_name, u.role, o.name AS outlet_name
                 FROM users u LEFT JOIN outlets o ON o.id = u.outlet_id
                 WHERE u.active = 1 AND u.role != 'admin'
                 ORDER BY u.role, u.username"
            );
        } else {
            $stmt = $pdo->prepare(
                "SELECT u.id, u.username, u.full_name, u.role, o.name AS outlet_name
                 FROM users u LEFT JOIN outlets o ON o.id = u.outlet_id
                 WHERE u.active = 1 AND u.role = ?
                 ORDER BY u.username"
            );
            $stmt->execute([$role]);
        }
        $rows = $stmt->fetchAll();

        $results = [];
        foreach ($rows as $row) {
            $plain = generatePassword();
            $hash  = password_hash($plain, PASSWORD_BCRYPT);
            $pdo->prepare('UPDATE users SET password_hash = ? WHERE id = ?')
                ->execute([$hash, $row['id']]);
            auditLog($pdo, (int)$user['id'], 'user_reset_password', 'user', (int)$row['id']);
            $results[] = [
                'username'    => $row['username'],
                'full_name'   => $row['full_name'],
                'role'        => $row['role'],
                'outlet_name' => $row['outlet_name'] ?? '—',
                'password'    => $plain,
            ];
        }

        jsonOk(['results' => $results, 'count' => count($results)]);
    }

    // ── Operasi single-user: wajib ada ?id= ───────────────────────────────
    if (!$id) jsonError('id user diperlukan', 400);

    $chk = $pdo->prepare('SELECT id, role FROM users WHERE id = ? LIMIT 1');
    $chk->execute([$id]);
    $target = $chk->fetch();
    if (!$target) jsonError('User tidak ditemukan', 404);

    // Admin tidak bisa mengubah akun admin lain kecuali dirinya sendiri
    if ($target['role'] === 'admin' && $id !== (int)$user['id']) {
        jsonError('Tidak dapat mengubah akun admin lain', 403);
    }

    // Reset password satu user
    if (($body['action'] ?? '') === 'reset_password') {
        $plain = generatePassword();
        $hash  = password_hash($plain, PASSWORD_BCRYPT);
        $pdo->prepare('UPDATE users SET password_hash = ? WHERE id = ?')->execute([$hash, $id]);
        auditLog($pdo, (int)$user['id'], 'user_reset_password', 'user', $id);
        jsonOk(['plain_password' => $plain]);
    }

    // Update fields
    $fields = [];
    $params = [];

    if (isset($body['full_name'])) {
        $fn = trim($body['full_name']);
        if (!$fn) jsonError('Nama tidak boleh kosong', 422);
        $fields[] = 'full_name = ?'; $params[] = $fn;
    }
    if (isset($body['active'])) {
        if ($id === (int)$user['id'] && !$body['active']) {
            jsonError('Tidak dapat menonaktifkan akun sendiri', 403);
        }
        $fields[] = 'active = ?'; $params[] = $body['active'] ? 1 : 0;
    }
    if (isset($body['outlet_id']) && $target['role'] === 'outlet') {
        $fields[] = 'outlet_id = ?'; $params[] = $body['outlet_id'] ? (int)$body['outlet_id'] : null;
    }
    if (!$fields) jsonError('Tidak ada field yang diubah', 400);

    $params[] = $id;
    $pdo->prepare('UPDATE users SET ' . implode(', ', $fields) . ' WHERE id = ?')->execute($params);

    auditLog($pdo, (int)$user['id'], 'user_update', 'user', $id);

    $row = $pdo->prepare(
        "SELECT u.id, u.username, u.full_name, u.role, u.outlet_id, u.active, u.last_login_at, u.created_at,
                o.code AS outlet_code, o.name AS outlet_name
         FROM users u LEFT JOIN outlets o ON o.id = u.outlet_id WHERE u.id = ?"
    );
    $row->execute([$id]);
    $updated = $row->fetch();
    $updated['active'] = (bool) $updated['active'];
    jsonOk($updated);
}

jsonError('Method tidak didukung', 405);

// ── Helpers ────────────────────────────────────────────────────────────────
function generatePassword(int $length = 8): string
{
    $chars = 'abcdefghijkmnpqrstuvwxyzABCDEFGHJKLMNPQRSTUVWXYZ23456789';
    $result = '';
    for ($i = 0; $i < $length; $i++) {
        $result .= $chars[random_int(0, strlen($chars) - 1)];
    }
    return $result;
}

function auditLog(\PDO $pdo, int $userId, string $action, string $targetType, int $targetId, array $payload = []): void
{
    $pdo->prepare(
        "INSERT INTO audit_log (user_id, action, target_type, target_id, payload_json, ip)
         VALUES (?, ?, ?, ?, ?, ?)"
    )->execute([
        $userId, $action, $targetType, $targetId,
        $payload ? json_encode($payload, JSON_UNESCAPED_UNICODE) : null,
        $_SERVER['REMOTE_ADDR'] ?? null,
    ]);
}
