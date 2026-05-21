<?php
// ─── GitHub Webhook Auto-Deploy ───────────────────────────────────────────
// Taruh file ini di: public_html/ops/deploy.php
// Akses via: https://ops.sukashawarma.com/deploy.php

define('WEBHOOK_SECRET', '2da5950d3a93d951b28bcbac364a8efb1517ba52fc9ef0a546b39cf42d5a357a');
define('REPO_PATH',      '/home/sukashaw/repositories/checklistform-ss');
define('DEPLOY_PATH',    '/home/sukashaw/public_html/ops');
define('LOG_FILE',       DEPLOY_PATH . '/logs/deploy.log');

// ─── Hanya terima POST ───────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Method Not Allowed');
}

// ─── Verifikasi signature GitHub ─────────────────────────────────────────
$payload   = file_get_contents('php://input');
$signature = $_SERVER['HTTP_X_HUB_SIGNATURE_256'] ?? '';

if (empty($signature)) {
    http_response_code(401);
    exit('Missing signature');
}

$expected = 'sha256=' . hash_hmac('sha256', $payload, WEBHOOK_SECRET);
if (!hash_equals($expected, $signature)) {
    http_response_code(403);
    exit('Invalid signature');
}

// ─── Hanya proses push ke branch main ────────────────────────────────────
$data = json_decode($payload, true);
$ref  = $data['ref'] ?? '';
if ($ref !== 'refs/heads/main') {
    http_response_code(200);
    exit('Ignored: not main branch');
}

// ─── Cek exec() tersedia ──────────────────────────────────────────────────
$timestamp = date('Y-m-d H:i:s');
$debug     = [];

if (!function_exists('exec') || !is_callable('exec')) {
    http_response_code(500);
    exit("[{$timestamp}] ERROR: exec() dinonaktifkan di server ini");
}

$debug[] = "exec() tersedia";
$debug[] = "REPO_PATH=" . REPO_PATH;
$debug[] = "repo_exists=" . (is_dir(REPO_PATH) ? 'ya' : 'TIDAK ADA');
$debug[] = "DEPLOY_PATH=" . DEPLOY_PATH;
$debug[] = "deploy_exists=" . (is_dir(DEPLOY_PATH) ? 'ya' : 'TIDAK ADA');

// ─── Jalankan git pull ────────────────────────────────────────────────────
$pullOutput = [];
exec('cd ' . escapeshellarg(REPO_PATH) . ' && git pull origin main 2>&1', $pullOutput, $pullExit);
$debug[] = "git pull exit={$pullExit}";
$debug[] = implode(' | ', $pullOutput);

if ($pullExit === 0) {
    // Copy semua file ke public_html/ops
    $rsyncOutput = [];
    exec(
        'rsync -a --exclude=".git" --exclude="deploy.php" --exclude=".env" ' .
        escapeshellarg(REPO_PATH . '/') . ' ' . escapeshellarg(DEPLOY_PATH . '/') . ' 2>&1',
        $rsyncOutput,
        $rsyncExit
    );
    $debug[] = "rsync exit={$rsyncExit}";
    $debug[] = implode(' | ', $rsyncOutput);

    $log = "[{$timestamp}] SUCCESS\n" . implode("\n", $debug) . "\n---\n";
    @file_put_contents(LOG_FILE, $log, FILE_APPEND | LOCK_EX);
    http_response_code(200);
    echo "Deploy OK\n" . implode("\n", $debug);
} else {
    $log = "[{$timestamp}] FAILED\n" . implode("\n", $debug) . "\n---\n";
    @file_put_contents(LOG_FILE, $log, FILE_APPEND | LOCK_EX);
    http_response_code(500);
    echo "Deploy FAILED\n" . implode("\n", $debug);
}
