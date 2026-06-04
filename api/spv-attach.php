<?php
declare(strict_types=1);

require_once __DIR__ . '/../src/bootstrap.php';

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    jsonError('Method tidak didukung', 405);
}

csrfValidate();
$user    = requireRole('spv', 'admin');
$visitId = (int) ($_GET['vid'] ?? 0);
if (!$visitId) jsonError('vid diperlukan', 400);

$visit = repo_getVisit($visitId);
if (!$visit) jsonError('Visit tidak ditemukan', 404);
if ($user['role'] !== 'admin' && (int) $visit['spv_id'] !== (int) $user['id']) {
    jsonError('Akses ditolak', 403);
}

$label = trim($_POST['label'] ?? '');
$tags  = json_decode($_POST['tags'] ?? '[]', true) ?: [];
$b64   = $_POST['imgdata'] ?? '';

if (empty($label)) jsonError('Label wajib diisi', 422);
if (empty($b64))   jsonError('Data gambar tidak ditemukan', 400);

if (str_contains($b64, ',')) {
    $b64 = explode(',', $b64, 2)[1];
}
$imageData = base64_decode($b64, true);
if ($imageData === false || strlen($imageData) < 100) {
    jsonError('Data gambar tidak valid', 400);
}

$outletCode = repo_getOutletCodeByVisit($visitId);
$visitDate  = date('Y-m-d');
$relDir     = 'uploads/spv/' . preg_replace('/[^a-z0-9\-]/i', '', $outletCode) . '/' . $visitDate;
$absDir     = ROOT_PATH . '/' . $relDir;
$thumbDir   = $absDir . '/thumb';

if (!is_dir($absDir))   mkdir($absDir,   0755, true);
if (!is_dir($thumbDir)) mkdir($thumbDir, 0755, true);

$uuid     = bin2hex(random_bytes(8));
$fileName = $uuid . '.jpg';
$absPath  = $absDir . '/' . $fileName;
$relPath  = $relDir . '/' . $fileName;
$thumbAbs = $thumbDir . '/' . $fileName;
$thumbRel = $relDir . '/thumb/' . $fileName;

if (file_put_contents($absPath, $imageData) === false) {
    jsonError('Gagal menyimpan gambar ke server', 500);
}

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

$photoId = repo_savePhoto($visitId, $relPath, $thumbRel, implode(',', $tags), $label);
jsonOk(['photo_id' => $photoId, 'path' => $relPath], 201);
