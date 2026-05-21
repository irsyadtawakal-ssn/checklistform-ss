<?php
// ─── GitHub Webhook Auto-Deploy ───────────────────────────────────────────
// URL: https://ops.sukashawarma.com/deploy.php

define('WEBHOOK_SECRET', '2da5950d3a93d951b28bcbac364a8efb1517ba52fc9ef0a546b39cf42d5a357a');
define('REPO_PATH',      '/home/sukashaw/public_html/ops');

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

// ─── Git pull ─────────────────────────────────────────────────────────────
$timestamp = date('Y-m-d H:i:s');
$output    = [];

exec('cd ' . escapeshellarg(REPO_PATH) . ' && git pull origin main 2>&1', $output, $exitCode);

$log = "[{$timestamp}] git pull exit={$exitCode} | " . implode(' | ', $output) . "\n";
@file_put_contents(REPO_PATH . '/logs/deploy.log', $log, FILE_APPEND | LOCK_EX);

if ($exitCode === 0) {
    http_response_code(200);
    echo 'Deploy OK — ' . implode(' ', $output);
} else {
    http_response_code(500);
    echo 'Deploy FAILED — ' . implode(' ', $output);
}
