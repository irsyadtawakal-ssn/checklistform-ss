<?php
declare(strict_types=1);

require_once __DIR__ . '/../../src/bootstrap.php';

$user   = requireRole('admin');
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

// ─── GET /api/admin/users ─────────────────────────────────────────────────
if ($method === 'GET') {
    jsonOk(repo_listUsers());
}

// ─── POST /api/admin/users ────────────────────────────────────────────────
if ($method === 'POST') {
    csrfValidate();
    $body = json_decode(file_get_contents('php://input'), true) ?: [];

    validateUserCreate($body);

    $username = trim(strtolower($body['username']));
    $fullName = trim($body['full_name']);
    $role     = $body['role'];
    $outletId = isset($body['outlet_id']) ? (int) $body['outlet_id'] : null;

    if (repo_findUserByUsername($username)) jsonError('Username sudah digunakan', 409);
    if ($outletId && !repo_findOutletActive($outletId)) jsonError('Outlet tidak ditemukan atau tidak aktif', 404);

    $plain = generatePassword();
    $hash  = password_hash($plain, PASSWORD_BCRYPT);
    $newId = repo_createUser($username, $hash, $fullName, $role, $role === 'outlet' ? $outletId : null);

    auditLog(db(), (int) $user['id'], 'user_create', 'user', $newId, ['role' => $role]);

    jsonOk([
        'id'             => $newId,
        'username'       => $username,
        'full_name'      => $fullName,
        'role'           => $role,
        'outlet_id'      => $role === 'outlet' ? $outletId : null,
        'active'         => true,
        'plain_password' => $plain,
    ], 201);
}

// ─── PUT /api/admin/users?id= ─────────────────────────────────────────────
if ($method === 'PUT') {
    csrfValidate();
    $id   = (int) ($_GET['id'] ?? 0);
    $body = json_decode(file_get_contents('php://input'), true) ?: [];

    // Reset semua password sekaligus
    if (($body['action'] ?? '') === 'reset_all_passwords') {
        validateResetAllRole($body['role'] ?? '');
        $rows    = repo_listUsersForReset($body['role']);
        $results = [];
        foreach ($rows as $row) {
            $plain = generatePassword();
            $hash  = password_hash($plain, PASSWORD_BCRYPT);
            repo_resetPassword((int) $row['id'], $hash);
            auditLog(db(), (int) $user['id'], 'user_reset_password', 'user', (int) $row['id']);
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

    if (!$id) jsonError('id user diperlukan', 400);

    $target = repo_findUserById($id);
    if (!$target) jsonError('User tidak ditemukan', 404);
    if ($target['role'] === 'admin' && $id !== (int) $user['id']) {
        jsonError('Tidak dapat mengubah akun admin lain', 403);
    }

    // Reset password satu user
    if (($body['action'] ?? '') === 'reset_password') {
        $plain = generatePassword();
        $hash  = password_hash($plain, PASSWORD_BCRYPT);
        repo_resetPassword($id, $hash);
        auditLog(db(), (int) $user['id'], 'user_reset_password', 'user', $id);
        jsonOk(['plain_password' => $plain]);
    }

    // Update fields
    validateUserUpdate($body);
    $fields = [];
    $params = [];

    if (isset($body['full_name'])) {
        $fn = trim($body['full_name']);
        if (!$fn) jsonError('Nama tidak boleh kosong', 422);
        $fields[] = 'full_name = ?'; $params[] = $fn;
    }
    if (isset($body['active'])) {
        if ($id === (int) $user['id'] && !$body['active']) {
            jsonError('Tidak dapat menonaktifkan akun sendiri', 403);
        }
        $fields[] = 'active = ?'; $params[] = $body['active'] ? 1 : 0;
    }
    if (isset($body['outlet_id']) && $target['role'] === 'outlet') {
        $fields[] = 'outlet_id = ?'; $params[] = $body['outlet_id'] ? (int) $body['outlet_id'] : null;
    }
    if (!$fields) jsonError('Tidak ada field yang diubah', 400);

    repo_updateUser($id, $fields, $params);
    auditLog(db(), (int) $user['id'], 'user_update', 'user', $id);

    jsonOk(repo_findUserById($id));
}

jsonError('Method tidak didukung', 405);
