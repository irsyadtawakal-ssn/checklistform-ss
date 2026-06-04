<?php
declare(strict_types=1);

function repo_getSubmission(int $outletId, string $date, string $shift): ?array
{
    $pdo  = db();
    $stmt = $pdo->prepare(
        'SELECT id, outlet_id, user_id, shift, submission_date, status,
                data_fields_json, pic_name, spv_name, handover_note,
                late, locked, submitted_at
         FROM checklist_submissions
         WHERE outlet_id = ? AND submission_date = ? AND shift = ?
         LIMIT 1'
    );
    $stmt->execute([$outletId, $date, $shift]);
    $submission = $stmt->fetch() ?: null;

    if (!$submission) return null;

    $items = $pdo->prepare(
        'SELECT item_code, checked FROM checklist_items_state WHERE submission_id = ?'
    );
    $items->execute([$submission['id']]);
    $checks = [];
    foreach ($items->fetchAll() as $row) {
        $checks[$row['item_code']] = (bool) $row['checked'];
    }

    $submission['data_fields'] = $submission['data_fields_json']
        ? json_decode($submission['data_fields_json'], true)
        : [];
    unset($submission['data_fields_json']);
    $submission['checks'] = $checks;
    $submission['locked'] = (bool) $submission['locked'];
    $submission['late']   = (bool) $submission['late'];

    return $submission;
}

function repo_findSubmissionForUpsert(int $outletId, string $date, string $shift): ?array
{
    $pdo  = db();
    $stmt = $pdo->prepare(
        'SELECT id, locked FROM checklist_submissions
         WHERE outlet_id = ? AND submission_date = ? AND shift = ? LIMIT 1'
    );
    $stmt->execute([$outletId, $date, $shift]);
    return $stmt->fetch() ?: null;
}

function repo_insertSubmission(PDO $pdo, int $outletId, int $userId, string $shift, string $today, array $data): int
{
    $ins = $pdo->prepare(
        'INSERT INTO checklist_submissions
            (outlet_id, user_id, shift, submission_date, status, data_fields_json,
             pic_name, spv_name, handover_note, late, locked,
             compliance_status, compliance_pct, submitted_at)
         VALUES (?, ?, ?, ?, "submitted", ?, ?, ?, ?, ?, 1, ?, ?, NOW())'
    );
    $ins->execute([
        $outletId, $userId, $shift, $today,
        json_encode($data['dataFields'], JSON_UNESCAPED_UNICODE),
        $data['picName'], $data['spvName'] ?: null, $data['handover'] ?: null, $data['late'],
        $data['compStatus'], $data['compPct'],
    ]);
    return (int) $pdo->lastInsertId();
}

function repo_updateSubmission(PDO $pdo, int $submissionId, array $data): void
{
    $pdo->prepare(
        'UPDATE checklist_submissions
         SET status = "submitted", data_fields_json = ?, pic_name = ?, spv_name = ?,
             handover_note = ?, late = ?, locked = 1,
             compliance_status = ?, compliance_pct = ?,
             submitted_at = NOW(), updated_at = NOW()
         WHERE id = ?'
    )->execute([
        json_encode($data['dataFields'], JSON_UNESCAPED_UNICODE),
        $data['picName'], $data['spvName'] ?: null, $data['handover'] ?: null, $data['late'],
        $data['compStatus'], $data['compPct'],
        $submissionId,
    ]);
}

function repo_upsertItemStates(PDO $pdo, int $submissionId, array $checks): void
{
    if (!$checks) return;
    $upsert = $pdo->prepare(
        'INSERT INTO checklist_items_state (submission_id, item_code, checked)
         VALUES (?, ?, ?)
         ON DUPLICATE KEY UPDATE checked = VALUES(checked)'
    );
    foreach ($checks as $code => $checked) {
        $upsert->execute([$submissionId, $code, $checked ? 1 : 0]);
    }
}

function repo_unlockSubmission(int $submissionId, int $unlockedBy): void
{
    db()->prepare(
        'UPDATE checklist_submissions
         SET locked = 0, status = "draft", unlocked_by = ?, unlocked_at = NOW()
         WHERE id = ?'
    )->execute([$unlockedBy, $submissionId]);
}

function repo_getSubmissionById(int $submissionId): ?array
{
    $stmt = db()->prepare('SELECT id, locked FROM checklist_submissions WHERE id = ? LIMIT 1');
    $stmt->execute([$submissionId]);
    return $stmt->fetch() ?: null;
}

function repo_getOutletName(int $outletId): string
{
    $stmt = db()->prepare('SELECT name FROM outlets WHERE id = ? LIMIT 1');
    $stmt->execute([$outletId]);
    return $stmt->fetchColumn() ?: 'Outlet #' . $outletId;
}
