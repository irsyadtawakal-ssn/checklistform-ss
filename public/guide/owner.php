<?php
declare(strict_types=1);
require_once __DIR__ . '/../../src/bootstrap.php';
require_once ROOT_PATH . '/src/helpers/auth.php';
require_once ROOT_PATH . '/src/middleware/page.php';
pageRequireRole('owner', 'admin');
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Panduan Owner · SS Operations</title>
<style>
@import url('https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,600;9..144,700&family=Inter:wght@400;500;600&display=swap');
:root{--purple:#4F46E5;--ink:#1C1917;--ink2:#57534E;--ink3:#A8A29E;--ok:#16A34A;--danger:#DC2626;--saffron:#E8942A}
*{box-sizing:border-box;margin:0;padding:0}
body{font-family:'Inter',sans-serif;color:var(--ink);background:#fff;padding:32px;max-width:700px;margin:0 auto;font-size:14px;line-height:1.6}
.header{display:flex;align-items:center;gap:16px;border-bottom:3px solid var(--purple);padding-bottom:16px;margin-bottom:24px}
.logo{width:48px;height:48px;background:var(--purple);border-radius:10px;display:flex;align-items:center;justify-content:center;font-family:'Fraunces',serif;font-weight:700;font-size:18px;color:#fff;flex-shrink:0}
.title{font-family:'Fraunces',serif;font-size:22px;font-weight:700}
.subtitle{font-size:12px;color:var(--ink3);margin-top:2px}
.role-tag{background:var(--purple);color:#fff;font-size:10px;font-weight:600;padding:2px 8px;border-radius:99px;letter-spacing:.04em;margin-left:auto;align-self:flex-start;flex-shrink:0}
h2{font-family:'Fraunces',serif;font-size:16px;font-weight:600;margin:24px 0 10px;padding-bottom:5px;border-bottom:1px solid #eee}
ol,ul{padding-left:20px;margin:8px 0}
li{margin-bottom:6px}
.kpi-grid{display:grid;grid-template-columns:repeat(2,1fr);gap:10px;margin:12px 0}
.kpi-card{border:1px solid #eee;border-radius:10px;padding:14px;text-align:center}
.kpi-num{font-family:'Fraunces',serif;font-size:28px;font-weight:700;color:var(--purple)}
.kpi-lbl{font-size:11px;color:var(--ink3);margin-top:2px}
.tip{background:#EEF2FF;border:1px solid #C7D2FE;border-radius:8px;padding:12px 14px;margin:12px 0;font-size:13px;color:var(--purple)}
.warning{background:#FEF2F2;border:1px solid #FECACA;border-radius:8px;padding:12px 14px;margin:12px 0;font-size:13px;color:var(--danger)}
table{width:100%;border-collapse:collapse;margin:10px 0;font-size:12px}
th{background:#F7F3ED;padding:7px 10px;text-align:left;border:1px solid #eee;font-weight:600}
td{padding:7px 10px;border:1px solid #eee}
.print-btn{position:fixed;bottom:24px;right:24px;background:var(--purple);color:#fff;border:none;border-radius:10px;padding:10px 18px;font-family:'Fraunces',serif;font-size:14px;font-weight:600;cursor:pointer;box-shadow:0 4px 12px rgba(79,70,229,.35)}
@media print{.print-btn{display:none}body{padding:20px}@page{margin:15mm}}
</style>
</head>
<body>

<div class="header">
  <div class="logo">SS</div>
  <div>
    <div class="title">Panduan Owner</div>
    <div class="subtitle">SS Operations · Monitoring Bisnis & KPI</div>
  </div>
  <div class="role-tag">ROLE: OWNER</div>
</div>

<h2>🏆 Apa yang Bisa Dipantau Owner?</h2>
<div class="kpi-grid">
  <div class="kpi-card"><div class="kpi-num" style="color:var(--ok)">%</div><div class="kpi-lbl">Rata-rata Kepatuhan Semua Outlet</div></div>
  <div class="kpi-card"><div class="kpi-num" style="color:var(--danger)">0</div><div class="kpi-lbl">Submission Berbahaya (Kritikal Terlewat)</div></div>
  <div class="kpi-card"><div class="kpi-num" style="color:var(--saffron)">0</div><div class="kpi-lbl">Submission Terlambat</div></div>
  <div class="kpi-card"><div class="kpi-num">0</div><div class="kpi-lbl">Kunjungan SPV dalam Periode</div></div>
</div>

<h2>📊 Cara Membaca Dashboard</h2>
<p>Login ke aplikasi → Anda langsung masuk ke <strong>Dashboard Kepatuhan</strong> yang menampilkan seluruh 19 outlet.</p>
<ul>
  <li><strong>Grid kepatuhan:</strong> Setiap baris = satu outlet. Setiap kolom = satu hari. Tiga kotak kecil = 3 shift (Open/Ops/Close).</li>
  <li><strong>Warna kotak:</strong> Hijau = sempurna, Kuning = ada yang kurang tapi kritikal aman, Merah = BAHAYA, Abu = belum submit.</li>
  <li><strong>Ranking:</strong> Scroll ke bawah untuk melihat 5 outlet terbaik dan 5 outlet yang perlu perhatian.</li>
</ul>

<div class="tip">💡 <strong>Rutinitas rekomendasi:</strong> Cek dashboard setiap pagi sebelum jam 10.00. Fokus pada cell merah — hubungi SPV untuk tindak lanjut hari itu juga.</div>

<h2>📅 Filter Periode</h2>
<p>Di sidebar kiri, Anda bisa mengatur:</p>
<ul>
  <li><strong>Periode:</strong> Pilih "Bulan ini" untuk KPI bulanan, atau atur tanggal custom.</li>
  <li><strong>Outlet:</strong> Filter satu outlet spesifik jika ingin fokus.</li>
  <li><strong>Shift:</strong> Lihat hanya shift tertentu jika ada pola masalah di jam tertentu.</li>
</ul>

<h2>📤 Export Laporan Excel</h2>
<p>Klik <strong>"Export Excel"</strong> di sidebar kiri untuk mengunduh file <code>.xlsx</code> berisi seluruh data kepatuhan periode yang dipilih. File ini bisa dibuka di Excel atau Google Sheets untuk analisis lebih lanjut.</p>

<h2>🚨 Apa yang Harus Dilakukan Jika Ada Merah?</h2>
<table>
  <tr><th>Situasi</th><th>Tindakan</th></tr>
  <tr><td>1–2 cell merah sporadis</td><td>Monitor; tanyakan ke SPV penyebabnya</td></tr>
  <tr><td>1 outlet merah konsisten 3+ hari</td><td>Hubungi SPV untuk kunjungan segera</td></tr>
  <tr><td>Banyak outlet merah di shift yang sama</td><td>Cek apakah ada masalah sistem (koneksi internet, dll)</td></tr>
  <tr><td>Outlet tidak pernah submit (semua abu)</td><td>Cek apakah akun PIC bermasalah → hubungi Admin</td></tr>
</table>

<div class="warning">⚠️ Sebagai Owner, Anda tidak bisa mengubah data submission. Untuk koreksi data, minta SPV melakukan unlock. Untuk masalah teknis, hubungi Admin.</div>

<button class="print-btn" onclick="window.print()">🖨 Cetak / Simpan PDF</button>
</body>
</html>
