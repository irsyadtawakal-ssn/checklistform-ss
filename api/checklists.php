<?php
declare(strict_types=1);

require_once __DIR__ . '/../src/bootstrap.php';

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

// ─── POST /api/checklists/{id}/unlock ────────────────────────────────────
$uri = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH);
if (preg_match('#/api/checklists/(\d+)/unlock$#', $uri, $m)) {
    if ($method !== 'POST') jsonError('Method tidak didukung', 405);

    csrfValidate();
    $user  = requireRole('spv', 'admin');
    $subId = (int) $m[1];

    $sub = repo_getSubmissionById($subId);
    if (!$sub) jsonError('Submission tidak ditemukan', 404);
    if (!(bool) $sub['locked']) jsonError('Submission sudah dalam status tidak terkunci', 409);

    repo_unlockSubmission($subId, (int) $user['id']);
    auditLog(db(), (int) $user['id'], 'checklist_unlock', 'checklist_submission', $subId);

    jsonOk(['unlocked' => true, 'submission_id' => $subId]);
}

// ─── GET /api/checklists?outlet=&date=&shift= ────────────────────────────
if ($method === 'GET') {
    $user = requireRole('outlet', 'spv', 'admin', 'owner');

    $outletId = (int) ($_GET['outlet'] ?? $user['outlet_id'] ?? 0);
    $date     = $_GET['date']  ?? date('Y-m-d');
    $shift    = $_GET['shift'] ?? '';

    validateChecklistGet(['outlet' => $outletId, 'shift' => $shift]);

    if ($user['role'] === 'outlet' && $outletId !== (int) $user['outlet_id']) {
        jsonError('Akses ditolak', 403);
    }

    $submission = repo_getSubmission($outletId, $date, $shift);
    jsonOk(['submission' => $submission]);
}

// ─── POST /api/checklists ─────────────────────────────────────────────────
if ($method === 'POST') {
    csrfValidate();
    $user = requireRole('outlet');

    $body = json_decode(file_get_contents('php://input'), true) ?: [];
    validateChecklistPost($body);

    $shift    = $body['shift'];
    $picName  = trim($body['pic_name']);
    $spvName  = trim($body['spv_name'] ?? '');
    $handover = trim($body['handover_note'] ?? '');
    $checks   = $body['checks'] ?? [];
    $inputs   = $body['inputs'] ?? [];

    $outletId = (int) $user['outlet_id'];
    $userId   = (int) $user['id'];
    $today    = date('Y-m-d');

    $existing = repo_findSubmissionForUpsert($outletId, $today, $shift);
    if ($existing && $existing['locked']) {
        jsonError('Submission sudah terkunci. Hubungi supervisor untuk unlock.', 409);
    }

    // Hitung late flag
    $hour = (int) date('H');
    $lateWindows = ['open' => [5, 10], 'ops' => [9, 16], 'close' => [15, 23]];
    [$openH, $closeH] = $lateWindows[$shift];
    $late = ($hour < $openH || $hour > $closeH) ? 1 : 0;

    // Pisahkan data_fields
    $dataFields = $inputs;
    unset($dataFields['pic_name'], $dataFields['spv_name'], $dataFields['handover']);

    // Compliance
    $checklistData = json_decode(file_get_contents(ROOT_PATH . '/assets/data/checklist.json'), true);
    $comp = computeCompliance($checklistData, $shift, $checks);

    $rowData = [
        'picName'    => $picName,
        'spvName'    => $spvName,
        'handover'   => $handover,
        'late'       => $late,
        'dataFields' => $dataFields,
        'compStatus' => $comp['status'],
        'compPct'    => $comp['pct'],
    ];

    $pdo = db();
    $pdo->beginTransaction();
    try {
        if ($existing) {
            repo_updateSubmission($pdo, (int) $existing['id'], $rowData);
            $submissionId = (int) $existing['id'];
        } else {
            $submissionId = repo_insertSubmission($pdo, $outletId, $userId, $shift, $today, $rowData);
        }
        repo_upsertItemStates($pdo, $submissionId, $checks);
        $pdo->commit();
    } catch (\Throwable $e) {
        $pdo->rollBack();
        jsonError('Gagal menyimpan submission', 500);
    }

    if ($comp['status'] === 'danger' && WA_SPV_NUMBER) {
        $outletName  = repo_getOutletName($outletId);
        $shiftLabel  = ['open' => 'Open', 'ops' => 'Operasional', 'close' => 'Close'][$shift] ?? $shift;
        $msg = "⚠️ *KRITIKAL TERLEWAT*\n"
             . "Outlet: {$outletName}\n"
             . "Shift: {$shiftLabel} | Tanggal: {$today}\n"
             . "PIC: {$picName}\n"
             . "{$comp['crit_missed']} item KRITIKAL belum selesai.\n"
             . "Silakan cek dashboard segera.";
        sendWhatsApp(WA_SPV_NUMBER, $msg);
    }

    jsonOk([
        'submission_id'     => $submissionId,
        'late'              => (bool) $late,
        'compliance_status' => $comp['status'],
        'compliance_pct'    => $comp['pct'],
        'crit_missed'       => $comp['crit_missed'],
        'message'           => $late
            ? 'Submission berhasil disimpan (terlambat dari window shift).'
            : 'Submission berhasil.',
    ], 201);
}

jsonError('Method tidak didukung', 405);
