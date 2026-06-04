<?php
declare(strict_types=1);

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
