<?php
declare(strict_types=1);

if (!defined('ROOT_PATH')) {
    require_once __DIR__ . '/../src/bootstrap.php';
    require_once ROOT_PATH . '/src/helpers/auth.php';
    startSession();
}

http_response_code(403);
$user = currentUser();
$back = match ($user['role'] ?? '') {
    'outlet' => '/checklist',
    'spv', 'owner' => '/dashboard',
    'admin'  => '/admin',
    default  => '/login',
};
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>403 · Akses Ditolak · SS Operations</title>
<link href="https://fonts.googleapis.com/css2?family=Fraunces:ital,opsz,wght,SOFT,WONK@0,9..144,300..900,0..100,0..1;1,9..144,300..900,0..100,0..1&family=Geist+Mono:wght@400;500&display=swap" rel="stylesheet">
<style>
:root{--paper:#f5f1e8;--ink:#1a1611;--ink-3:#6b6155;--ink-4:#9a8f7f;--saffron:#c45a1a;--danger:#a83621;--display:'Fraunces',serif;--mono:'Geist Mono',monospace;--ease:cubic-bezier(0.22,0.61,0.36,1)}
*{box-sizing:border-box;margin:0;padding:0}
body{background:var(--paper);color:var(--ink);font-family:var(--mono);min-height:100vh;display:flex;align-items:center;justify-content:center;padding:24px}
.card{max-width:480px;width:100%;text-align:center}
.code{font-family:var(--display);font-size:clamp(96px,20vw,160px);font-weight:400;line-height:1;letter-spacing:-0.04em;font-variation-settings:"opsz" 144,"SOFT" 0;color:var(--danger);margin-bottom:16px}
.title{font-family:var(--display);font-size:28px;font-weight:400;font-variation-settings:"opsz" 28,"SOFT" 30;margin-bottom:12px;color:var(--ink)}
.desc{font-size:12px;letter-spacing:0.08em;color:var(--ink-3);line-height:1.7;margin-bottom:40px}
.btn{display:inline-block;background:var(--ink);color:var(--paper);padding:14px 32px;font-family:var(--mono);font-size:11px;letter-spacing:0.2em;text-transform:uppercase;text-decoration:none;transition:background 0.2s var(--ease)}
.btn:hover{background:var(--saffron)}
.folio{margin-top:48px;font-size:10px;letter-spacing:0.14em;text-transform:uppercase;color:var(--ink-4)}
</style>
</head>
<body>
<div class="card">
  <div class="code">403</div>
  <h1 class="title">Akses Ditolak</h1>
  <p class="desc">
    Halaman ini tidak tersedia untuk akun Anda.<br>
    Role Anda: <strong><?= htmlspecialchars($user['role'] ?? 'tidak diketahui') ?></strong>
  </p>
  <a class="btn" href="<?= htmlspecialchars($back) ?>">← Kembali ke halaman saya</a>
  <div class="folio">SS Operations · Ops v1.0</div>
</div>
</body>
</html>
