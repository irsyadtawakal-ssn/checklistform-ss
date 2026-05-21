#!/usr/bin/env php
<?php
/**
 * SS Operations — Email Digest Harian
 *
 * Cron cPanel, setiap hari pukul 22:00 WIB (15:00 UTC):
 *   0 15 * * * /usr/local/bin/php /home/USERNAME/public_html/scripts/email-digest.php >> /home/USERNAME/public_html/logs/cron.log 2>&1
 *
 * Kirim ringkasan kepatuhan hari ini ke email Owner & SPV
 * yang memiliki kolom `email` terisi di tabel users.
 *
 * Prasyarat: PHP mail() berfungsi (cPanel biasanya aktif),
 *            atau set MAIL_FROM di .env.
 */

declare(strict_types=1);

define('ROOT_PATH', dirname(__DIR__));
require_once ROOT_PATH . '/src/helpers/env.php';
require_once ROOT_PATH . '/src/helpers/db.php';
loadEnv(ROOT_PATH . '/.env');

$today    = date('Y-m-d');
$logFile  = ROOT_PATH . '/logs/cron.log';

function dlog(string $msg): void {
    $ts = date('Y-m-d H:i:s');
    file_put_contents(ROOT_PATH . '/logs/cron.log', "[{$ts}] [digest] {$msg}\n", FILE_APPEND);
    echo "[{$ts}] [digest] {$msg}\n";
}

dlog("Mulai email digest untuk tanggal: {$today}");

$pdo = db();

// ─── Kumpulkan penerima ────────────────────────────────────────────────────
$recipients = $pdo->query(
    "SELECT full_name, email FROM users
     WHERE role IN ('owner','spv') AND active = 1 AND email IS NOT NULL AND email != ''
     ORDER BY role, full_name"
)->fetchAll();

// Juga dari MAIL_DIGEST_TO env (comma-separated)
$digestTo = getenv('MAIL_DIGEST_TO') ?: '';
$extraEmails = array_filter(array_map('trim', explode(',', $digestTo)));
foreach ($extraEmails as $em) {
    $recipients[] = ['full_name' => 'Tim SS', 'email' => $em];
}

if (!$recipients) {
    dlog("Tidak ada penerima email. Lewati.");
    exit(0);
}

// ─── Ambil data compliance hari ini ───────────────────────────────────────
$subRows = $pdo->prepare(
    "SELECT cs.outlet_id, cs.shift, cs.compliance_status, cs.compliance_pct,
            o.code AS outlet_code, o.name AS outlet_name
     FROM checklist_submissions cs
     JOIN outlets o ON o.id = cs.outlet_id
     WHERE cs.submission_date = ?
     ORDER BY o.code, cs.shift"
)->execute([$today]) ? $pdo->query(
    "SELECT cs.outlet_id, cs.shift, cs.compliance_status, cs.compliance_pct,
            o.code AS outlet_code, o.name AS outlet_name
     FROM checklist_submissions cs
     JOIN outlets o ON o.id = cs.outlet_id
     WHERE cs.submission_date = '{$today}'
     ORDER BY o.code, cs.shift"
)->fetchAll() : [];

$stmt = $pdo->prepare(
    "SELECT cs.outlet_id, cs.shift, cs.compliance_status, cs.compliance_pct,
            o.code AS outlet_code, o.name AS outlet_name
     FROM checklist_submissions cs
     JOIN outlets o ON o.id = cs.outlet_id
     WHERE cs.submission_date = ?
     ORDER BY o.code, cs.shift"
);
$stmt->execute([$today]);
$subRows = $stmt->fetchAll();

$totalOutlets    = (int) $pdo->query("SELECT COUNT(*) FROM outlets WHERE active = 1")->fetchColumn();
$totalExpected   = $totalOutlets * 3; // 3 shift
$totalSubmitted  = count($subRows);
$dangerCount     = count(array_filter($subRows, fn($r) => $r['compliance_status'] === 'danger'));
$warnCount       = count(array_filter($subRows, fn($r) => $r['compliance_status'] === 'warn'));
$okCount         = count(array_filter($subRows, fn($r) => $r['compliance_status'] === 'ok'));
$notSubmitted    = $totalExpected - $totalSubmitted;
$submitRate      = $totalExpected > 0 ? round($totalSubmitted / $totalExpected * 100) : 0;
$avgPct          = $totalSubmitted > 0 ? round(array_sum(array_column($subRows, 'compliance_pct')) / $totalSubmitted) : 0;

$tanggalFormatted = date('d F Y', strtotime($today));

// ─── Bangun HTML email ─────────────────────────────────────────────────────
$dangerRows = array_filter($subRows, fn($r) => $r['compliance_status'] === 'danger');
$dangerList = '';
foreach ($dangerRows as $r) {
    $shiftLabel = ['open'=>'Open','ops'=>'Ops','close'=>'Close'][$r['shift']] ?? $r['shift'];
    $dangerList .= "<tr><td style='padding:6px 10px;border-bottom:1px solid #fee2e2'>{$r['outlet_code']}</td>"
                 . "<td style='padding:6px 10px;border-bottom:1px solid #fee2e2'>{$r['outlet_name']}</td>"
                 . "<td style='padding:6px 10px;border-bottom:1px solid #fee2e2;text-align:center'>{$shiftLabel}</td></tr>";
}

$html = <<<HTML
<!DOCTYPE html>
<html lang="id">
<head><meta charset="UTF-8"><style>
body{font-family:Arial,sans-serif;color:#1C1917;font-size:14px;line-height:1.5;margin:0;padding:0;background:#F7F3ED}
.wrap{max-width:600px;margin:24px auto;background:#fff;border-radius:12px;overflow:hidden;box-shadow:0 2px 8px rgba(0,0,0,.08)}
.hdr{background:#E8942A;padding:24px 28px;color:#fff}
.hdr h1{margin:0;font-size:20px;font-weight:700}
.hdr p{margin:4px 0 0;font-size:12px;opacity:.85}
.body{padding:24px 28px}
.kpi{display:flex;gap:12px;margin:16px 0;flex-wrap:wrap}
.kpi-item{flex:1;min-width:100px;border:1px solid #eee;border-radius:8px;padding:12px;text-align:center}
.kpi-num{font-size:28px;font-weight:700;line-height:1}
.kpi-lbl{font-size:11px;color:#A8A29E;margin-top:4px}
.ok{color:#16A34A}.warn{color:#D97706}.danger{color:#DC2626}
table{width:100%;border-collapse:collapse}
th{background:#F7F3ED;padding:8px 10px;text-align:left;font-size:12px;color:#57534E}
.ftr{padding:16px 28px;background:#F7F3ED;font-size:11px;color:#A8A29E;text-align:center}
</style></head>
<body>
<div class="wrap">
  <div class="hdr">
    <h1>📊 Digest Harian SS Operations</h1>
    <p>{$tanggalFormatted}</p>
  </div>
  <div class="body">
    <div class="kpi">
      <div class="kpi-item"><div class="kpi-num">{$submitRate}%</div><div class="kpi-lbl">Submission Rate</div></div>
      <div class="kpi-item"><div class="kpi-num ok">{$avgPct}%</div><div class="kpi-lbl">Avg Kepatuhan</div></div>
      <div class="kpi-item"><div class="kpi-num danger">{$dangerCount}</div><div class="kpi-lbl">Bahaya</div></div>
      <div class="kpi-item"><div class="kpi-num warn">{$warnCount}</div><div class="kpi-lbl">Kurang</div></div>
      <div class="kpi-item"><div class="kpi-num">{$notSubmitted}</div><div class="kpi-lbl">Belum Submit</div></div>
    </div>
    <p style="font-size:12px;color:#57534E">{$totalSubmitted} dari {$totalExpected} shift ter-submit hari ini ({$totalOutlets} outlet × 3 shift).</p>
HTML;

if ($dangerList) {
    $html .= <<<HTML
    <h3 style="margin:20px 0 10px;font-size:14px;color:#DC2626">⚠️ Outlet dengan Item KRITIKAL Terlewat</h3>
    <table>
      <tr><th>Kode</th><th>Nama Outlet</th><th>Shift</th></tr>
      {$dangerList}
    </table>
HTML;
}

$html .= <<<HTML
    <p style="margin-top:20px;font-size:12px;color:#A8A29E">
      Buka dashboard untuk detail lengkap: <a href="https://ops.sukashawarma.com/dashboard" style="color:#E8942A">ops.sukashawarma.com/dashboard</a>
    </p>
  </div>
  <div class="ftr">Email otomatis dari SS Operations. Jangan balas email ini.</div>
</div>
</body></html>
HTML;

// ─── Kirim email ───────────────────────────────────────────────────────────
$mailFrom    = getenv('MAIL_FROM') ?: 'noreply@sukashawarma.com';
$subject     = "📊 Digest Harian SS Ops — {$tanggalFormatted} | Bahaya: {$dangerCount}";
$headers     = implode("\r\n", [
    "MIME-Version: 1.0",
    "Content-Type: text/html; charset=UTF-8",
    "From: SS Operations <{$mailFrom}>",
    "X-Mailer: PHP/" . PHP_VERSION,
]);

$sent = 0;
foreach ($recipients as $r) {
    $to = "{$r['full_name']} <{$r['email']}>";
    if (mail($to, $subject, $html, $headers)) {
        $sent++;
        dlog("Terkirim ke: {$r['email']}");
    } else {
        dlog("GAGAL kirim ke: {$r['email']}");
    }
}

dlog("Selesai. {$sent} email terkirim dari " . count($recipients) . " penerima.");
exit(0);
