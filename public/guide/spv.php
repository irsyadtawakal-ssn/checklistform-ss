<?php
declare(strict_types=1);
require_once __DIR__ . '/../../src/bootstrap.php';
require_once ROOT_PATH . '/src/helpers/auth.php';
require_once ROOT_PATH . '/src/middleware/page.php';
pageRequireRole('spv', 'owner', 'admin');
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Panduan SPV · SS Operations</title>
<style>
@import url('https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,600;9..144,700&family=Inter:wght@400;500;600&display=swap');
:root{--green:#16A34A;--ink:#1C1917;--ink2:#57534E;--ink3:#A8A29E;--saffron:#E8942A;--danger:#DC2626}
*{box-sizing:border-box;margin:0;padding:0}
body{font-family:'Inter',sans-serif;color:var(--ink);background:#fff;padding:32px;max-width:700px;margin:0 auto;font-size:14px;line-height:1.6}
.header{display:flex;align-items:center;gap:16px;border-bottom:3px solid var(--green);padding-bottom:16px;margin-bottom:24px}
.logo{width:48px;height:48px;background:var(--green);border-radius:10px;display:flex;align-items:center;justify-content:center;font-family:'Fraunces',serif;font-weight:700;font-size:18px;color:#fff;flex-shrink:0}
.title{font-family:'Fraunces',serif;font-size:22px;font-weight:700}
.subtitle{font-size:12px;color:var(--ink3);margin-top:2px}
.role-tag{background:var(--green);color:#fff;font-size:10px;font-weight:600;padding:2px 8px;border-radius:99px;letter-spacing:.04em;margin-left:auto;align-self:flex-start;flex-shrink:0}
h2{font-family:'Fraunces',serif;font-size:16px;font-weight:600;margin:24px 0 10px;padding-bottom:5px;border-bottom:1px solid #eee}
ol,ul{padding-left:20px;margin:8px 0}
li{margin-bottom:6px}
.step{display:flex;gap:12px;margin-bottom:12px;align-items:flex-start}
.step-num{width:26px;height:26px;border-radius:50%;background:var(--green);color:#fff;font-weight:700;font-size:12px;display:flex;align-items:center;justify-content:center;flex-shrink:0;margin-top:1px}
.step-body strong{display:block;font-size:13px;margin-bottom:2px}
.step-body span{font-size:12px;color:var(--ink2)}
.warning{background:#FEF2F2;border:1px solid #FECACA;border-radius:8px;padding:12px 14px;margin:12px 0;font-size:13px;color:var(--danger)}
.tip{background:#F0FDF4;border:1px solid #BBF7D0;border-radius:8px;padding:12px 14px;margin:12px 0;font-size:13px;color:var(--green)}
.grid{display:grid;grid-template-columns:1fr 1fr;gap:12px;margin:10px 0}
.card{border:1px solid #eee;border-radius:8px;padding:12px;font-size:12px}
.card strong{display:block;font-size:13px;margin-bottom:4px}
.print-btn{position:fixed;bottom:24px;right:24px;background:var(--green);color:#fff;border:none;border-radius:10px;padding:10px 18px;font-family:'Fraunces',serif;font-size:14px;font-weight:600;cursor:pointer;box-shadow:0 4px 12px rgba(22,163,74,.35)}
@media print{.print-btn{display:none}body{padding:20px}.grid{grid-template-columns:1fr 1fr}@page{margin:15mm}}
</style>
</head>
<body>

<div class="header">
  <div class="logo">SS</div>
  <div>
    <div class="title">Panduan Supervisor (SPV)</div>
    <div class="subtitle">SS Operations · Dashboard & Laporan Kunjungan</div>
  </div>
  <div class="role-tag">ROLE: SPV</div>
</div>

<h2>🎯 Tanggung Jawab SPV di Aplikasi</h2>
<div class="grid">
  <div class="card"><strong>📊 Monitoring Harian</strong>Pantau kepatuhan semua outlet via Dashboard. Cek cell merah / kuning setiap pagi.</div>
  <div class="card"><strong>🔓 Unlock Submission</strong>Jika PIC salah submit atau perlu koreksi, SPV bisa unlock agar bisa diisi ulang.</div>
  <div class="card"><strong>📝 Laporan Kunjungan</strong>Isi Laporan SPV setiap kali berkunjung ke outlet. Data masuk ke sistem otomatis.</div>
  <div class="card"><strong>📈 Export Data</strong>Export Excel kepatuhan harian untuk arsip atau laporan ke Owner.</div>
</div>

<h2>📊 Membaca Dashboard</h2>
<ul>
  <li><strong style="color:var(--green)">Hijau (Lengkap):</strong> Semua item terisi, tidak ada kritikal terlewat.</li>
  <li><strong style="color:var(--saffron)">Kuning (Kurang):</strong> Kritikal aman, tapi ada item lain yang belum diisi.</li>
  <li><strong style="color:var(--danger)">Merah (Bahaya):</strong> Ada item KRITIKAL yang terlewat — perlu tindakan!</li>
  <li><strong style="color:var(--ink3)">Abu-abu (Belum):</strong> Belum ada submission untuk shift tersebut.</li>
</ul>
<div class="tip">💡 Klik cell mana saja untuk melihat detail: siapa PIC-nya, jam berapa submit, dan item apa yang terlewat.</div>

<h2>🔓 Cara Unlock Submission</h2>
<div class="step"><div class="step-num">1</div><div class="step-body"><strong>Klik cell di grid kepatuhan</strong><span>Pilih outlet dan shift yang perlu di-unlock.</span></div></div>
<div class="step"><div class="step-num">2</div><div class="step-body"><strong>Klik "Unlock Submission" di modal</strong><span>Tombol hanya muncul jika submission berstatus terkunci dan Anda login sebagai SPV/Admin.</span></div></div>
<div class="step"><div class="step-num">3</div><div class="step-body"><strong>Informasikan ke PIC</strong><span>Beritahu PIC bahwa form sudah di-unlock dan bisa diisi ulang / dikoreksi.</span></div></div>
<div class="warning">⚠️ Setiap aksi unlock tercatat di Audit Log. Jangan unlock sembarangan tanpa alasan jelas.</div>

<h2>📝 Mengisi Laporan Kunjungan</h2>
<div class="step"><div class="step-num">1</div><div class="step-body"><strong>Buka menu "Laporan SPV"</strong><span>Dari halaman utama setelah login sebagai SPV.</span></div></div>
<div class="step"><div class="step-num">2</div><div class="step-body"><strong>Isi 7 tab secara berurutan</strong><span>Info → Inventaris → Penjualan → Stok → Karyawan → Foto → Ringkasan</span></div></div>
<div class="step"><div class="step-num">3</div><div class="step-body"><strong>Upload foto hanya jika ada temuan</strong><span>Foto tidak wajib. Tapi jika diupload, caption/alasan wajib diisi.</span></div></div>
<div class="step"><div class="step-num">4</div><div class="step-body"><strong>Submit & download PDF (opsional)</strong><span>Tap "Kirim Laporan". Laporan tersimpan di server. PDF bisa didownload dari tab Ringkasan.</span></div></div>

<h2>📤 Export Excel Kepatuhan</h2>
<p>Di halaman Dashboard, atur filter periode yang diinginkan, lalu klik <strong>"Export Excel"</strong> di sidebar kiri. File <code>.xlsx</code> akan terdownload otomatis.</p>

<button class="print-btn" onclick="window.print()">🖨 Cetak / Simpan PDF</button>
</body>
</html>
