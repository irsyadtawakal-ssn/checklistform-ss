<?php
declare(strict_types=1);

require_once __DIR__ . '/auth.php';

function requireRole(string ...$roles): array
{
    $user = requireAuth();

    if (!in_array($user['role'], $roles, true)) {
        jsonError('Akses ditolak. Halaman ini tidak tersedia untuk role Anda.', 403);
    }

    return $user;
}
