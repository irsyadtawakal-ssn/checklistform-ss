<?php
declare(strict_types=1);

require_once __DIR__ . '/../src/bootstrap.php';
require_once ROOT_PATH . '/src/helpers/db.php';
require_once ROOT_PATH . '/src/helpers/response.php';
require_once ROOT_PATH . '/src/helpers/compliance.php';
require_once ROOT_PATH . '/src/helpers/csrf.php';
require_once ROOT_PATH . '/src/middleware/role.php';

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

// ─── POST /api/checklists/{id}/unlock ────────────────────────────────────
$uri = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH);
if (preg_match('#/api/checklists/(\d+)/unlock$#', $uri, $m)) {
    if ($method !== 'POST') jsonError('Method tidak didukung', 405);

    csrfValidate();
    $user  = requireRole('spv', 'admin');
    $subId = (int) $m[1];

    $pdo  = db();
    $stmt = $pdo->prepare('SELECT id, locked FROM checklist_submissions WHERE id = ? LIMIT 1');
    $stmt->execute([$subId]);
    $sub = $stmt->fetch();

    if (!$sub) jsonError('Submission tidak ditemukan', 404);
    if (!(bool) $sub['locked']) jsonError('Submission sudah dalam status tidak terkunci', 409);

    $pdo->prepare(
        'UPDATE checklist_submissions
         SET locked = 0, status = "draft", unlocked_by = ?, unlocked_at = NOW()
         WHERE id = ?'
    )->execute([(int) $user['id'], $subId]);

    $pdo->prepare(
        "INSERT INTO audit_log (user_id, action, target_type, target_id, ip) VALUES (?, 'checklist_unlock', 'checklist_submission', ?, ?)"
    )->execute([(int) $user['id'], $subId, $_SERVER['REMOTE_ADDR'] ?? null]);

    jsonOk(['unlocked' => true, 'submission_id' => $subId]);
}

// ─── GET /api/checklists?outlet=&date=&shift= ────────────────────────────
if ($method === 'GET') {
    $user = requireRole('outlet', 'spv', 'admin', 'owner');

    $outletId = (int) ($_GET['outlet'] ?? $user['outlet_id'] ?? 0);
    $date     = $_GET['date']  ?? date('Y-m-d');
    $shift    = $_GET['shift'] ?? '';

    if (!$outletId) jsonError('outlet_id diperlukan', 400);
    if (!in_array($shift, ['open', 'ops', 'close'], true)) jsonError('shift tidak valid', 400);

    // Outlet role hanya bisa lihat miliknya sendiri
    if ($user['role'] === 'outlet' && $outletId !== (int) $user['outlet_id']) {
        jsonError('Akses ditolak', 403);
    }

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
    $submission = $stmt->fetch();

    if (!$submission) {
        jsonOk(['submission' => null]);
    }

    // Ambil item states
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

    jsonOk(['submission' => $submission]);
}

// ─── POST /api/checklists ─────────────────────────────────────────────────
if ($method === 'POST') {
    csrfValidate();
    $user = requireRole('outlet');

    $body = json_decode(file_get_contents('php://input'), true);
    if (!$body) jsonError('Request body tidak valid', 400);

    $shift    = $body['shift']    ?? '';
    $picName  = trim($body['pic_name'] ?? '');
    $spvName  = trim($body['spv_name'] ?? '');
    $handover = trim($body['handover_note'] ?? '');
    $checks   = $body['checks']  ?? [];
    $inputs   = $body['inputs']  ?? [];

    // Validasi input
    if (!in_array($shift, ['open', 'ops', 'close'], true)) {
        jsonError('shift tidak valid', 400);
    }
    if (strlen($picName) < 3) {
        jsonError('Nama PIC shift wajib diisi (min. 3 karakter)', 422);
    }

    $outletId = (int) $user['outlet_id'];
    $userId   = (int) $user['id'];
    $today    = date('Y-m-d');

    // Cek duplikat
    $pdo  = db();
    $dup  = $pdo->prepare(
        'SELECT id, locked FROM checklist_submissions WHERE outlet_id = ? AND submission_date = ? AND shift = ? LIMIT 1'
    );
    $dup->execute([$outletId, $today, $shift]);
    $existing = $dup->fetch();

    if ($existing) {
        if ($existing['locked']) {
            jsonError('Submission sudah terkunci. Hubungi supervisor untuk unlock.', 409);
        }
        // Update existing draft
        $submissionId = (int) $existing['id'];
    } else {
        $submissionId = null;
    }

    // Hitung late flag berdasarkan window shift (Asia/Jakarta)
    $hour = (int) date('H');
    $lateWindows = [
        'open'  => [5,  10],
        'ops'   => [9,  16],
        'close' => [15, 23],
    ];
    [$openH, $closeH] = $lateWindows[$shift];
    $late = ($hour < $openH || $hour > $closeH) ? 1 : 0;

    // Pisahkan inputs menjadi data_fields (angka/teks) vs sign fields
    $dataFields = $inputs;
    unset($dataFields['pic_name'], $dataFields['spv_name'], $dataFields['handover']);

    // Hitung compliance dari checklist.json
    $checklistData = json_decode(file_get_contents(ROOT_PATH . '/assets/data/checklist.json'), true);
    $comp = computeCompliance($checklistData, $shift, $checks);

    $pdo->beginTransaction();
    try {
        if ($submissionId) {
            // Update draft yang sudah ada
            $upd = $pdo->prepare(
                'UPDATE checklist_submissions
                 SET status = "submitted", data_fields_json = ?, pic_name = ?, spv_name = ?,
                     handover_note = ?, late = ?, locked = 1,
                     compliance_status = ?, compliance_pct = ?,
                     submitted_at = NOW(), updated_at = NOW()
                 WHERE id = ?'
            );
            $upd->execute([
                json_encode($dataFields, JSON_UNESCAPED_UNICODE),
                $picName, $spvName ?: null, $handover ?: null, $late,
                $comp['status'], $comp['pct'],
                $submissionId,
            ]);
        } else {
            // Insert baru
            $ins = $pdo->prepare(
                'INSERT INTO checklist_submissions
                    (outlet_id, user_id, shift, submission_date, status, data_fields_json,
                     pic_name, spv_name, handover_note, late, locked,
                     compliance_status, compliance_pct, submitted_at)
                 VALUES (?, ?, ?, ?, "submitted", ?, ?, ?, ?, ?, 1, ?, ?, NOW())'
            );
            $ins->execute([
                $outletId, $userId, $shift, $today,
                json_encode($dataFields, JSON_UNESCAPED_UNICODE),
                $picName, $spvName ?: null, $handover ?: null, $late,
                $comp['status'], $comp['pct'],
            ]);
            $submissionId = (int) $pdo->lastInsertId();
        }

        // Upsert item states
        if ($checks) {
            $upsert = $pdo->prepare(
                'INSERT INTO checklist_items_state (submission_id, item_code, checked)
                 VALUES (?, ?, ?)
                 ON DUPLICATE KEY UPDATE checked = VALUES(checked)'
            );
            foreach ($checks as $code => $checked) {
                $upsert->execute([$submissionId, $code, $checked ? 1 : 0]);
            }
        }

        $pdo->commit();
    } catch (\Throwable $e) {
        $pdo->rollBack();
        jsonError('Gagal menyimpan submission', 500);
    }

    // Notifikasi WA jika ada kritikal terlewat (7.4)
    if ($comp['status'] === 'danger' && WA_SPV_NUMBER) {
        $outletNameRow = db()->prepare('SELECT name FROM outlets WHERE id = ? LIMIT 1');
        $outletNameRow->execute([$outletId]);
        $outletName = $outletNameRow->fetchColumn() ?: 'Outlet #' . $outletId;
        $shiftLabel = ['open' => 'Open', 'ops' => 'Operasional', 'close' => 'Close'][$shift] ?? $shift;
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
        'message'           => $late ? 'Submission berhasil disimpan (terlambat dari window shift).' : 'Submission berhasil.',
    ], 201);
}

jsonError('Method tidak didukung', 405);
