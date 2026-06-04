<?php
declare(strict_types=1);

function repo_listUsers(): array
{
    $rows = db()->query(
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
    return $rows;
}

function repo_findUserById(int $id): ?array
{
    $stmt = db()->prepare(
        "SELECT u.id, u.username, u.full_name, u.role, u.outlet_id, u.active,
                u.last_login_at, u.created_at,
                o.code AS outlet_code, o.name AS outlet_name
         FROM users u LEFT JOIN outlets o ON o.id = u.outlet_id WHERE u.id = ?"
    );
    $stmt->execute([$id]);
    $row = $stmt->fetch() ?: null;
    if ($row) $row['active'] = (bool) $row['active'];
    return $row;
}

function repo_findUserByUsername(string $username): ?array
{
    $stmt = db()->prepare('SELECT id FROM users WHERE username = ? LIMIT 1');
    $stmt->execute([$username]);
    return $stmt->fetch() ?: null;
}

function repo_findOutletActive(int $outletId): ?array
{
    $stmt = db()->prepare('SELECT id FROM outlets WHERE id = ? AND active = 1 LIMIT 1');
    $stmt->execute([$outletId]);
    return $stmt->fetch() ?: null;
}

function repo_createUser(string $username, string $hash, string $fullName, string $role, ?int $outletId): int
{
    $pdo = db();
    $pdo->prepare(
        'INSERT INTO users (username, password_hash, full_name, role, outlet_id) VALUES (?, ?, ?, ?, ?)'
    )->execute([$username, $hash, $fullName, $role, $outletId]);
    return (int) $pdo->lastInsertId();
}

function repo_updateUser(int $id, array $fields, array $params): void
{
    $params[] = $id;
    db()->prepare('UPDATE users SET ' . implode(', ', $fields) . ' WHERE id = ?')->execute($params);
}

function repo_resetPassword(int $id, string $hash): void
{
    db()->prepare('UPDATE users SET password_hash = ? WHERE id = ?')->execute([$hash, $id]);
}

function repo_listUsersForReset(string $role): array
{
    $pdo = db();
    if ($role === 'all') {
        return $pdo->query(
            "SELECT u.id, u.username, u.full_name, u.role, o.name AS outlet_name
             FROM users u LEFT JOIN outlets o ON o.id = u.outlet_id
             WHERE u.active = 1 AND u.role != 'admin'
             ORDER BY u.role, u.username"
        )->fetchAll();
    }
    $stmt = $pdo->prepare(
        "SELECT u.id, u.username, u.full_name, u.role, o.name AS outlet_name
         FROM users u LEFT JOIN outlets o ON o.id = u.outlet_id
         WHERE u.active = 1 AND u.role = ?
         ORDER BY u.username"
    );
    $stmt->execute([$role]);
    return $stmt->fetchAll();
}
