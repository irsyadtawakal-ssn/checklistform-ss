<?php
declare(strict_types=1);
require_once __DIR__ . '/../../src/bootstrap.php';
require_once ROOT_PATH . '/src/helpers/auth.php';
require_once ROOT_PATH . '/src/middleware/page.php';
pageRequireRole('outlet', 'spv', 'owner', 'admin');
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Panduan PIC · SS Operations</title>
<style>
@import url('https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,600;9..144,700&family=Inter:wght@400;500;600&display=swap');
:root{--saffron:#E8942A;--ink:#1C1917;--ink2:#57534E;--ink3:#A8A29E;--ok:#16A34A;--danger:#DC2626}
*{box-sizing:border-box;margin:0;padding:0}
body{font-family:'Inter',sans-serif;color:var(--ink);background:#fff;padding:32px;max-width:700px;margin:0 auto;font-size:14px;line-height:1.6}
.header{display:flex;align-items:center;gap:16px;border-bottom:3px solid var(--saffron);padding-bottom:16px;margin-bottom:24px}
.logo{width:48px;height:48px;background:var(--saffron);border-radius:10px;display:flex;align-items:center;justify-content:center;font-family:'Fraunces',serif;font-weight:700;font-size:18px;color:#fff;flex-shrink:0}
.title{font-family:'Fraunces',serif;font-size:22px;font-weight:700}
.subtitle{font-size:12px;color:var(--ink3);margin-top:2px}
.role-tag{background:var(--saffron);color:#fff;font-size:10px;font-weight:600;padding:2px 8px;border-radius:99px;letter-spacing:.04em;margin-left:auto;align-self:flex-start;flex-shrink:0}
h2{font-family:'Fraunces',serif;font-size:16px;font-weight:600;margin:24px 0 10px;padding-bottom:5px;border-bottom:1px solid #eee}
ol,ul{padding-left:20px;margin:8px 0}
li{margin-bottom:6px}
.step{display:flex;gap:12px;margin-bottom:12px;align-items:flex-start}
.step-num{width:26px;height:26px;border-radius:50%;background:var(--saffron);color:#fff;font-weight:700;font-size:12px;display:flex;align-items:center;justify-content:center;flex-shrink:0;margin-top:1px}
.step-body strong{display:block;font-size:13px;margin-bottom:2px}
.step-body span{font-size:12px;color:var(--ink2)}
.warning{background:#FEF2F2;border:1px solid #FECACA;border-radius:8px;padding:12px 14px;margin:12px 0;font-size:13px;color:var(--danger)}
.tip{background:#F0FDF4;border:1px solid #BBF7D0;border-radius:8px;padding:12px 14px;margin:12px 0;font-size:13px;color:var(--ok)}
.shift-table{width:100%;border-collapse:collapse;margin:10px 0;font-size:12px}
.shift-table th{background:#F7F3ED;padding:7px 10px;text-align:left;border:1px solid #eee;font-weight:600}
.shift-table td{padding:7px 10px;border:1px solid #eee}
.print-btn{position:fixed;bottom:24px;right:24px;background:var(--saffron);color:#fff;border:none;border-radius:10px;padding:10px 18px;font-family:'Fraunces',serif;font-size:14px;font-weight:600;cursor:pointer;box-shadow:0 4px 12px rgba(232,148,42,.4)}
@media print{.print-btn{display:none}body{padding:20px}@page{margin:15mm}}
</style>
</head>
<body>

<div class="header">
  <div class="logo">SS</div>
  <div>
    <div class="title">Panduan PIC Shift</div>
    <div class="subtitle">SS Operations · Checklist Harian</div>
  </div>
  <div class="role-tag">ROLE: PIC / OUTLET</div>
</div>

<h2>📋 Apa itu Checklist Harian?</h2>
<p>Setiap shift (Open, Operasional, Close) wajib mengisi checklist kegiatan operasional di aplikasi ini. Tujuannya memastikan semua standar SS terpenuhi setiap hari.</p>

<h2>🕐 Window Waktu Per Shift</h2>
<table class="shift-table">
  <tr><th>Shift</th><th>Window Normal</th><th>Keterangan</th></tr>
  <tr><td><strong>Open (Pagi)</strong></td><td>05:00 – 10:00</td><td>Persiapan buka outlet</td></tr>
  <tr><td><strong>Operasional</strong></td><td>09:00 – 16:00</td><td>Pengecekan tengah hari</td></tr>
  <tr><td><strong>Close (Malam)</strong></td><td>15:00 – 23:00</td><td>Penutupan & serah terima</td></tr>
</table>
<div class="warning">⚠️ Submit di luar window = ditandai <strong>TERLAMBAT</strong> di sistem. Tetap submit meski terlambat — lebih baik terlambat daripada tidak sama sekali.</div>

<h2>📱 Cara Mengisi Checklist</h2>
<div class="step"><div class="step-num">1</div><div class="step-body"><strong>Login ke aplikasi</strong><span>Buka ops.sukashawarma.com → masuk dengan username & password outlet Anda.</span></div></div>
<div class="step"><div class="step-num">2</div><div class="step-body"><strong>Pilih shift yang sesuai</strong><span>Tap tab "Open", "Ops", atau "Close" di bagian atas.</span></div></div>
<div class="step"><div class="step-num">3</div><div class="step-body"><strong>Isi semua item</strong><span>Centang setiap item yang sudah selesai dikerjakan. Item bertanda <strong style="color:var(--danger)">KRITIKAL</strong> wajib dicentang semua sebelum bisa submit.</span></div></div>
<div class="step"><div class="step-num">4</div><div class="step-body"><strong>Isi Nama PIC Shift</strong><span>Tulis nama lengkap kamu (bukan nama akun outlet). Field ini wajib, minimal 3 huruf.</span></div></div>
<div class="step"><div class="step-num">5</div><div class="step-body"><strong>Tekan "Submit Checklist"</strong><span>Setelah submit, form akan terkunci. Jika ada yang salah, hubungi supervisor untuk unlock.</span></div></div>

<h2>❌ Item KRITIKAL</h2>
<p>Item berlabel <strong style="color:var(--danger)">KRITIKAL</strong> adalah standar yang <strong>tidak boleh terlewat</strong> dalam kondisi apapun. Contohnya: kebersihan area masak, suhu chiller, keamanan kas. Jika ada yang belum selesai, selesaikan dulu sebelum submit.</p>

<div class="tip">✅ <strong>Tips:</strong> Form otomatis tersimpan di browser. Jika browser tertutup atau koneksi putus, buka kembali — data tidak hilang. Saat koneksi kembali, lanjutkan dan submit.</div>

<h2>❓ Masalah Umum</h2>
<ul>
  <li><strong>Tidak bisa submit:</strong> Pastikan semua item KRITIKAL sudah dicentang dan Nama PIC sudah diisi.</li>
  <li><strong>Form sudah terkunci:</strong> Hubungi supervisor / SPV untuk minta unlock.</li>
  <li><strong>Lupa password:</strong> Hubungi admin untuk reset password.</li>
  <li><strong>Tidak ada koneksi:</strong> Isi dulu, simpan otomatis di browser. Submit saat koneksi kembali.</li>
</ul>

<button class="print-btn" onclick="window.print()">🖨 Cetak / Simpan PDF</button>
</body>
</html>
