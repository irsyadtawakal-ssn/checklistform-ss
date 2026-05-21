<?php
declare(strict_types=1);
require_once __DIR__ . '/../../src/bootstrap.php';
require_once ROOT_PATH . '/src/helpers/auth.php';
require_once ROOT_PATH . '/src/middleware/page.php';
pageRequireRole('admin');
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Panduan Admin · SS Operations</title>
<style>
@import url('https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,600;9..144,700&family=Inter:wght@400;500;600&display=swap');
:root{--red:#DC2626;--ink:#1C1917;--ink2:#57534E;--ink3:#A8A29E;--ok:#16A34A;--saffron:#E8942A}
*{box-sizing:border-box;margin:0;padding:0}
body{font-family:'Inter',sans-serif;color:var(--ink);background:#fff;padding:32px;max-width:700px;margin:0 auto;font-size:14px;line-height:1.6}
.header{display:flex;align-items:center;gap:16px;border-bottom:3px solid var(--red);padding-bottom:16px;margin-bottom:24px}
.logo{width:48px;height:48px;background:var(--red);border-radius:10px;display:flex;align-items:center;justify-content:center;font-family:'Fraunces',serif;font-weight:700;font-size:18px;color:#fff;flex-shrink:0}
.title{font-family:'Fraunces',serif;font-size:22px;font-weight:700}
.subtitle{font-size:12px;color:var(--ink3);margin-top:2px}
.role-tag{background:var(--red);color:#fff;font-size:10px;font-weight:600;padding:2px 8px;border-radius:99px;letter-spacing:.04em;margin-left:auto;align-self:flex-start;flex-shrink:0}
h2{font-family:'Fraunces',serif;font-size:16px;font-weight:600;margin:24px 0 10px;padding-bottom:5px;border-bottom:1px solid #eee}
ol,ul{padding-left:20px;margin:8px 0}
li{margin-bottom:6px}
.step{display:flex;gap:12px;margin-bottom:12px;align-items:flex-start}
.step-num{width:26px;height:26px;border-radius:50%;background:var(--red);color:#fff;font-weight:700;font-size:12px;display:flex;align-items:center;justify-content:center;flex-shrink:0;margin-top:1px}
.step-body strong{display:block;font-size:13px;margin-bottom:2px}
.step-body span{font-size:12px;color:var(--ink2)}
.warning{background:#FEF2F2;border:1px solid #FECACA;border-radius:8px;padding:12px 14px;margin:12px 0;font-size:13px;color:var(--red)}
.tip{background:#F0FDF4;border:1px solid #BBF7D0;border-radius:8px;padding:12px 14px;margin:12px 0;font-size:13px;color:var(--ok)}
table{width:100%;border-collapse:collapse;margin:10px 0;font-size:12px}
th{background:#F7F3ED;padding:7px 10px;text-align:left;border:1px solid #eee;font-weight:600}
td{padding:7px 10px;border:1px solid #eee}
code{background:#F7F3ED;padding:1px 5px;border-radius:4px;font-size:12px;font-family:'Courier New',monospace}
.print-btn{position:fixed;bottom:24px;right:24px;background:var(--red);color:#fff;border:none;border-radius:10px;padding:10px 18px;font-family:'Fraunces',serif;font-size:14px;font-weight:600;cursor:pointer;box-shadow:0 4px 12px rgba(220,38,38,.35)}
@media print{.print-btn{display:none}body{padding:20px}@page{margin:15mm}}
</style>
</head>
<body>

<div class="header">
  <div class="logo">SS</div>
  <div>
    <div class="title">Panduan Admin</div>
    <div class="subtitle">SS Operations · Manajemen Sistem</div>
  </div>
  <div class="role-tag">ROLE: ADMIN</div>
</div>

<h2>⚙️ Akses Admin Panel</h2>
<p>Login sebagai Admin → Anda akan masuk ke <strong>Admin Panel</strong> (<code>/admin</code>) dengan 4 tab: Outlet, Pengguna, Audit Log, Error Log.</p>

<h2>🏪 Manajemen Outlet</h2>
<div class="step"><div class="step-num">1</div><div class="step-body"><strong>Tambah outlet baru</strong><span>Klik "+ Tambah Outlet" → isi Kode (unik, tidak bisa diubah), Nama, Tipe (Internal/Mitra), Alamat, Target Penjualan Harian.</span></div></div>
<div class="step"><div class="step-num">2</div><div class="step-body"><strong>Edit target penjualan</strong><span>Klik angka target di tabel → edit langsung → centang ✓ untuk simpan. Nilai 0 = tidak ada target.</span></div></div>
<div class="step"><div class="step-num">3</div><div class="step-body"><strong>Nonaktifkan outlet</strong><span>Klik "Nonaktifkan" di baris outlet. Outlet tidak akan muncul di form checklist / SPV visit.</span></div></div>
<div class="warning">⚠️ Kode outlet tidak bisa diubah setelah dibuat karena digunakan sebagai referensi di sistem penyimpanan foto.</div>

<h2>👤 Manajemen Pengguna</h2>
<table>
  <tr><th>Role</th><th>Akses</th><th>Catatan</th></tr>
  <tr><td><strong>outlet</strong></td><td>Form checklist saja</td><td>Wajib pilih outlet saat membuat akun</td></tr>
  <tr><td><strong>spv</strong></td><td>Dashboard + laporan kunjungan + unlock</td><td>Tidak terikat outlet tertentu</td></tr>
  <tr><td><strong>owner</strong></td><td>Dashboard (read-only)</td><td>Tidak bisa unlock atau edit data</td></tr>
  <tr><td><strong>admin</strong></td><td>Semua + Admin Panel</td><td>Tidak bisa hapus admin lain</td></tr>
</table>

<p style="margin-top:10px"><strong>Buat pengguna baru:</strong> Klik "+ Tambah Pengguna" → isi form → password 8 karakter akan di-generate otomatis. <strong>Catat / salin password tersebut</strong> — hanya ditampilkan sekali, tidak bisa dilihat kembali.</p>

<div class="tip">✅ <strong>Reset password:</strong> Klik "Reset PW" di baris user → password baru muncul sekali di modal. Sampaikan ke user yang bersangkutan.</div>

<h2>📋 Audit Log</h2>
<p>Tab "Audit Log" mencatat semua aksi penting: login, unlock submission, create/update outlet/user. Gunakan filter di kotak pencarian untuk mencari aksi tertentu (mis. ketik "unlock" untuk melihat semua aksi unlock).</p>

<h2>🔴 Error Log</h2>
<p>Tab "Error Log" menampilkan error PHP terbaru dari server. Berguna untuk troubleshooting. Jika ada error berulang, catat pesannya dan hubungi developer.</p>

<h2>💾 Backup & Cron (Setup Satu Kali)</h2>
<p>Backup otomatis dikonfigurasi via Cron Jobs di cPanel. Lihat file <code>DEPLOY.md</code> untuk instruksi lengkap. Perintah yang perlu dijalankan:</p>
<ul>
  <li><code>scripts/db-backup.php</code> — backup harian jam 03:00 WIB</li>
  <li><code>scripts/archive-data.php</code> — arsip data lama setiap Minggu jam 02:00 WIB</li>
</ul>
<p style="margin-top:8px">Hasil backup ada di folder <code>backup/hot/</code> (30 hari) dan <code>backup/archive/</code> (2 tahun).</p>

<div class="warning">⚠️ <strong>Keamanan:</strong> Jangan bagikan password akun Admin ke siapapun. Password default semua akun seed adalah <code>ss1234!</code> — reset semua sebelum distribusi ke pengguna nyata.</div>

<button class="print-btn" onclick="window.print()">🖨 Cetak / Simpan PDF</button>
</body>
</html>
