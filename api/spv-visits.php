<?php
declare(strict_types=1);

require_once __DIR__ . '/../src/bootstrap.php';
require_once ROOT_PATH . '/src/helpers/db.php';
require_once ROOT_PATH . '/src/helpers/response.php';
require_once ROOT_PATH . '/src/helpers/csrf.php';
require_once ROOT_PATH . '/src/middleware/role.php';

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

// ─── Photo upload: POST /api/spv-visits/{id}/photos ──────────────────────
// URL pattern: /api/spv-visits/123/photos
$uri = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH);
if (preg_match('#/api/spv-visits/(\d+)/photos$#', $uri, $m)) {
    if ($method !== 'POST') jsonError('Method tidak didukung', 405);

    csrfValidate();
    $user   = requireRole('spv', 'admin');
    $visitId = (int) $m[1];

    // Verifikasi visit ada dan milik SPV ini (atau admin)
    $pdo  = db();
    $stmt = $pdo->prepare('SELECT id, spv_id FROM spv_visits WHERE id = ? LIMIT 1');
    $stmt->execute([$visitId]);
    $visit = $stmt->fetch();

    if (!$visit) jsonError('Visit tidak ditemukan', 404);
    if ($user['role'] !== 'admin' && (int) $visit['spv_id'] !== (int) $user['id']) {
        jsonError('Akses ditolak', 403);
    }

    // Terima urlencoded form (imgdata = base64 foto, menghindari WAF file-upload rules)
    $label = trim($_POST['label'] ?? '');
    $tags  = json_decode($_POST['tags'] ?? '[]', true) ?: [];
    $b64   = $_POST['imgdata'] ?? '';

    if (empty($label)) jsonError('Caption foto wajib diisi', 422);
    if (empty($b64))   jsonError('Data foto tidak ditemukan', 400);

    // Strip "data:image/jpeg;base64," prefix jika ada
    if (str_contains($b64, ',')) {
        $b64 = explode(',', $b64, 2)[1];
    }
    $imageData = base64_decode($b64, true);
    if ($imageData === false || strlen($imageData) < 100) {
        jsonError('Data foto tidak valid', 400);
    }

    // Direktori simpan
    $outletStmt = $pdo->prepare('SELECT code FROM outlets WHERE id = (SELECT outlet_id FROM spv_visits WHERE id = ?)');
    $outletStmt->execute([$visitId]);
    $outletCode = $outletStmt->fetchColumn() ?: 'unknown';

    $visitDate = date('Y-m-d');
    $relDir    = 'uploads/spv/' . preg_replace('/[^a-z0-9\-]/i', '', $outletCode) . '/' . $visitDate;
    $absDir    = ROOT_PATH . '/' . $relDir;
    $thumbDir  = $absDir . '/thumb';

    if (!is_dir($absDir))   mkdir($absDir,   0755, true);
    if (!is_dir($thumbDir)) mkdir($thumbDir, 0755, true);

    $uuid     = bin2hex(random_bytes(8));
    $fileName = $uuid . '.jpg';
    $absPath  = $absDir  . '/' . $fileName;
    $relPath  = $relDir  . '/' . $fileName;
    $thumbAbs = $thumbDir . '/' . $fileName;
    $thumbRel = $relDir  . '/thumb/' . $fileName;

    if (file_put_contents($absPath, $imageData) === false) {
        jsonError('Gagal menyimpan foto ke server', 500);
    }

    // Generate thumbnail (max 300px lebar) jika GD tersedia
    if (function_exists('imagecreatefromjpeg')) {
        $src = @imagecreatefromjpeg($absPath);
        if ($src) {
            $ow = imagesx($src); $oh = imagesy($src);
            $tw = min(300, $ow);
            $th = (int) round($oh * $tw / $ow);
            $thumb = imagecreatetruecolor($tw, $th);
            imagecopyresampled($thumb, $src, 0, 0, 0, 0, $tw, $th, $ow, $oh);
            imagejpeg($thumb, $thumbAbs, 75);
            imagedestroy($src); imagedestroy($thumb);
        } else {
            $thumbRel = null;
        }
    } else {
        $thumbRel = null;
    }

    $ins = $pdo->prepare(
        'INSERT INTO spv_visit_photos (visit_id, file_path, thumb_path, tag, label) VALUES (?, ?, ?, ?, ?)'
    );
    $ins->execute([$visitId, $relPath, $thumbRel, implode(',', $tags), $label]);

    jsonOk(['photo_id' => (int) $pdo->lastInsertId(), 'path' => $relPath], 201);
}

// ─── POST /api/spv-visits ─────────────────────────────────────────────────
if ($method === 'POST') {
    csrfValidate();
    $user = requireRole('spv', 'admin');

    $body = json_decode(file_get_contents('php://input'), true);
    if (!$body) jsonError('Request body tidak valid', 400);

    $outletId   = (int) ($body['outlet_id']   ?? 0);
    $visitDate  = $body['visit_date']  ?? date('Y-m-d');
    $timeArrive = $body['time_arrive'] ?? null;
    $timeLeave  = $body['time_leave']  ?? null;
    $visitShift = $body['visit_shift'] ?? null;
    $picOnDuty  = $body['pic_on_duty'] ?? null;
    $payloadJson= $body['payload_json'] ?? [];
    $employees  = $body['employees']   ?? [];

    if (!$outletId) jsonError('outlet_id diperlukan', 400);

    $pdo = db();
    $pdo->beginTransaction();
    try {
        $ins = $pdo->prepare(
            'INSERT INTO spv_visits
                (outlet_id, spv_id, visit_date, time_arrive, time_leave, visit_shift,
                 pic_on_duty, payload_json, submitted_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())'
        );
        $ins->execute([
            $outletId,
            (int) $user['id'],
            $visitDate,
            $timeArrive ?: null,
            $timeLeave  ?: null,
            $visitShift ?: null,
            $picOnDuty  ?: null,
            json_encode($payloadJson, JSON_UNESCAPED_UNICODE),
        ]);
        $visitId = (int) $pdo->lastInsertId();

        // Insert employees
        if ($employees) {
            $empStmt = $pdo->prepare(
                'INSERT INTO spv_visit_employees (visit_id, name, role, eval_json, notes) VALUES (?, ?, ?, ?, ?)'
            );
            foreach ($employees as $emp) {
                $empStmt->execute([
                    $visitId,
                    $emp['name'] ?? '',
                    $emp['role'] ?? null,
                    json_encode($emp['eval_json'] ?? [], JSON_UNESCAPED_UNICODE),
                    $emp['notes'] ?? null,
                ]);
            }
        }

        $pdo->commit();
    } catch (\Throwable $e) {
        $pdo->rollBack();
        jsonError('Gagal menyimpan visit', 500);
    }

    jsonOk(['visit_id' => $visitId], 201);
}

jsonError('Method tidak didukung', 405);
