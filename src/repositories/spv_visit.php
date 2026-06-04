<?php
declare(strict_types=1);

function repo_getVisit(int $visitId): ?array
{
    $stmt = db()->prepare('SELECT id, spv_id FROM spv_visits WHERE id = ? LIMIT 1');
    $stmt->execute([$visitId]);
    return $stmt->fetch() ?: null;
}

function repo_createVisit(int $outletId, int $spvId, array $data): int
{
    $pdo = db();
    $pdo->prepare(
        'INSERT INTO spv_visits
            (outlet_id, spv_id, visit_date, time_arrive, time_leave, visit_shift,
             pic_on_duty, payload_json, submitted_at)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())'
    )->execute([
        $outletId, $spvId,
        $data['visit_date'] ?? date('Y-m-d'),
        $data['time_arrive']  ?: null,
        $data['time_leave']   ?: null,
        $data['visit_shift']  ?: null,
        $data['pic_on_duty']  ?: null,
        json_encode($data['payload_json'] ?? [], JSON_UNESCAPED_UNICODE),
    ]);
    return (int) $pdo->lastInsertId();
}

function repo_insertEmployees(PDO $pdo, int $visitId, array $employees): void
{
    if (!$employees) return;
    $stmt = $pdo->prepare(
        'INSERT INTO spv_visit_employees (visit_id, name, role, eval_json, notes) VALUES (?, ?, ?, ?, ?)'
    );
    foreach ($employees as $emp) {
        $stmt->execute([
            $visitId,
            $emp['name'] ?? '',
            $emp['role'] ?? null,
            json_encode($emp['eval_json'] ?? [], JSON_UNESCAPED_UNICODE),
            $emp['notes'] ?? null,
        ]);
    }
}

function repo_getOutletCodeByVisit(int $visitId): string
{
    $pdo  = db();
    $stmt = $pdo->prepare(
        'SELECT code FROM outlets WHERE id = (SELECT outlet_id FROM spv_visits WHERE id = ?)'
    );
    $stmt->execute([$visitId]);
    return $stmt->fetchColumn() ?: 'unknown';
}

function repo_savePhoto(int $visitId, string $relPath, ?string $thumbRel, string $tags, string $label): int
{
    $pdo = db();
    $pdo->prepare(
        'INSERT INTO spv_visit_photos (visit_id, file_path, thumb_path, tag, label) VALUES (?, ?, ?, ?, ?)'
    )->execute([$visitId, $relPath, $thumbRel, $tags, $label]);
    return (int) $pdo->lastInsertId();
}
