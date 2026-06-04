<?php
declare(strict_types=1);

function validateUserCreate(array $body): void
{
    $username = trim(strtolower($body['username'] ?? ''));
    $fullName = trim($body['full_name'] ?? '');
    $role     = $body['role'] ?? '';
    $outletId = isset($body['outlet_id']) ? (int) $body['outlet_id'] : null;

    if (!$username || !$fullName) {
        jsonError('Username dan nama lengkap wajib diisi', 422);
    }
    if (!preg_match('/^[a-z0-9_\.]{3,30}$/', $username)) {
        jsonError('Username hanya boleh huruf kecil, angka, titik, underscore (3-30 karakter)', 422);
    }
    if (!in_array($role, ['outlet', 'spv', 'owner', 'admin'], true)) {
        jsonError('Role tidak valid', 422);
    }
    if ($role === 'outlet' && !$outletId) {
        jsonError('Outlet wajib dipilih untuk user role outlet', 422);
    }
}

function validateUserUpdate(array $body): void
{
    if (isset($body['full_name']) && !trim($body['full_name'])) {
        jsonError('Nama tidak boleh kosong', 422);
    }
    if (isset($body['type']) && !in_array($body['type'], ['internal', 'mitra'], true)) {
        jsonError('Tipe tidak valid', 422);
    }
}

function validateResetAllRole(string $role): void
{
    if (!in_array($role, ['outlet', 'spv', 'owner', 'all'], true)) {
        jsonError('Role tidak valid', 422);
    }
}
