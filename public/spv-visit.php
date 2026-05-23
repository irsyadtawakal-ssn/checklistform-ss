<?php
declare(strict_types=1);

require_once __DIR__ . '/../src/bootstrap.php';
require_once ROOT_PATH . '/src/helpers/auth.php';
require_once ROOT_PATH . '/src/helpers/db.php';
require_once ROOT_PATH . '/src/middleware/page.php';
require_once ROOT_PATH . '/src/helpers/csrf.php';

$user = pageRequireRole('spv', 'admin');

// Ambil semua outlet aktif, dikelompokkan per type
$outletRows = db()->query(
    'SELECT id, code, name, type, daily_sales_target FROM outlets WHERE active = 1 ORDER BY type, code'
)->fetchAll();

$outletsByType = ['internal' => [], 'mitra' => []];
foreach ($outletRows as $o) {
    $outletsByType[$o['type']][] = $o;
}

// Load master data SPV dari JSON
$spvMasterJson = file_get_contents(ROOT_PATH . '/assets/data/spv-master.json');
$spvMaster     = json_decode($spvMasterJson, true);
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="theme-color" content="#ffffff">
<meta name="csrf-token" content="<?= htmlspecialchars(csrfToken()) ?>">
<title>Laporan Kunjungan SPV · SS Operations</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=Nunito:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
<style>
:root{--bg:#F5F0EC;--bg2:#FFFFFF;--bg3:#F5F0EC;--bg4:#E8D5C8;--border:rgba(122,18,0,0.10);--border2:rgba(122,18,0,0.18);--text:#200500;--text2:#6B4535;--text3:#9E8B85;--amber:#E8924A;--amber-bg:rgba(232,146,74,0.12);--amber-border:rgba(232,146,74,0.30);--red:#DC2626;--red-bg:rgba(220,38,38,0.10);--red-border:rgba(220,38,38,0.28);--green:#43A047;--green-bg:rgba(67,160,71,0.10);--green-border:rgba(67,160,71,0.28);--blue:#5b9bd6;--blue-bg:rgba(91,155,214,0.10);--blue-border:rgba(91,155,214,0.25);--yellow:#D4940A;--yellow-bg:rgba(212,148,10,0.10);--yellow-border:rgba(212,148,10,0.28);--brand-red:#7A1200;--brand-dark:#200500;--radius:10px;--radius-lg:14px;--font-d:'Bebas Neue',sans-serif;--font-b:'Nunito',sans-serif;--font-m:'Nunito',sans-serif}
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0;-webkit-tap-highlight-color:transparent}
body{background:var(--bg);color:var(--text);font-family:var(--font-b);min-height:100vh;overscroll-behavior:none}
.app-header{background:var(--brand-dark);padding:12px 16px;position:sticky;top:0;z-index:100;box-shadow:0 2px 10px rgba(32,5,0,0.40)}
.header-row{display:flex;align-items:center;justify-content:space-between}
.brand{display:flex;align-items:center;gap:10px}
.brand-logo{width:34px;height:34px;border-radius:8px;overflow:hidden;object-fit:contain}
.brand-name{font-family:var(--font-d);font-size:18px;color:#fff;letter-spacing:0.04em;line-height:1}
.brand-sub{font-size:10px;color:#F4B07A;font-weight:700;text-transform:uppercase;letter-spacing:0.05em}
.header-actions{display:flex;gap:6px}
.pdf-btn,.logout-btn{padding:6px 11px;background:rgba(255,255,255,0.10);border:1px solid rgba(255,255,255,0.20);border-radius:var(--radius);font-size:11px;font-weight:700;color:rgba(255,255,255,0.85);cursor:pointer;font-family:var(--font-b)}
.pdf-btn:hover{background:rgba(255,255,255,0.18)}
.logout-btn{border-color:rgba(220,38,38,0.50);color:#FCA5A5}
.logout-btn:hover{background:rgba(220,38,38,0.15)}
.section-tabs{display:flex;overflow-x:auto;gap:0;border-bottom:3px solid rgba(32,5,0,0.40);scrollbar-width:none;margin-top:10px}
.section-tabs::-webkit-scrollbar{display:none}
.stab{padding:8px 14px;border:none;background:transparent;color:rgba(255,255,255,0.55);font-family:var(--font-b);font-size:12px;font-weight:700;cursor:pointer;border-bottom:3px solid transparent;margin-bottom:-3px;white-space:nowrap;transition:all 0.2s;flex-shrink:0;text-transform:uppercase;letter-spacing:0.04em}
.stab.active{color:#fff;border-bottom-color:var(--amber)}
.stab:hover:not(.active){color:rgba(255,255,255,0.80)}
.progress-bar-wrap{background:var(--bg2);padding:8px 16px;border-bottom:1px solid var(--border)}
.prog-row{display:flex;justify-content:space-between;align-items:center;margin-bottom:5px}
.prog-label{font-size:11px;color:var(--text3);font-weight:700}
.prog-pct{font-size:12px;font-weight:800;color:var(--brand-red)}
.prog-track{height:4px;background:var(--bg4);border-radius:99px;overflow:hidden}
.prog-fill{height:4px;background:linear-gradient(90deg,var(--brand-red),var(--amber));border-radius:99px;transition:width 0.3s ease;width:0%}
.main{padding:14px 16px 120px}
.section-page{display:none}
.section-page.active{display:block}
.card{background:var(--bg2);border:1px solid var(--border);border-radius:var(--radius-lg);padding:14px;margin-bottom:12px;box-shadow:0 1px 4px rgba(32,5,0,0.06)}
.card-title{font-family:var(--font-d);font-size:13px;letter-spacing:0.08em;color:var(--brand-red);margin-bottom:12px}
.card-title span{color:var(--text3);font-family:var(--font-b);font-size:11px;font-weight:600;letter-spacing:0}
.field{margin-bottom:10px}
.field label{font-size:10px;color:var(--text3);display:block;margin-bottom:4px;font-weight:800;text-transform:uppercase;letter-spacing:0.05em}
.field input[type=text],.field input[type=number],.field input[type=date],.field input[type=time],.field select,.field textarea{width:100%;background:var(--bg);border:1px solid var(--border2);color:var(--text);font-family:var(--font-b);font-size:13px;font-weight:600;border-radius:8px;padding:8px 11px;outline:none;-webkit-appearance:none;appearance:none}
.field input:focus,.field select:focus,.field textarea:focus{border-color:var(--amber);background:#fff}
.field textarea{resize:vertical;min-height:64px;font-size:12px;line-height:1.5}
.field select{background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='8' viewBox='0 0 12 8'%3E%3Cpath d='M1 1l5 5 5-5' stroke='%236B4535' stroke-width='1.5' fill='none' stroke-linecap='round'/%3E%3C/svg%3E");background-repeat:no-repeat;background-position:right 10px center;padding-right:30px}
.grid2{display:grid;grid-template-columns:1fr 1fr;gap:8px}
.status-row{display:flex;gap:6px;margin-top:4px}
.status-opt{flex:1;padding:7px 4px;border:1px solid var(--border2);border-radius:7px;font-size:11px;font-weight:700;text-align:center;cursor:pointer;transition:all 0.15s;color:var(--text3);background:var(--bg)}
.status-opt.s-ok{border-color:var(--green-border);color:var(--green);background:var(--green-bg)}
.status-opt.s-warn{border-color:var(--yellow-border);color:var(--yellow);background:var(--yellow-bg)}
.status-opt.s-broken{border-color:var(--red-border);color:var(--red);background:var(--red-bg)}
.inv-table{width:100%;border-collapse:collapse}
.inv-table th{font-size:10px;font-family:var(--font-b);font-weight:800;color:var(--brand-red);text-align:left;padding:6px 6px;border-bottom:2px solid var(--border2);text-transform:uppercase;letter-spacing:0.06em;white-space:nowrap}
.inv-table td{font-size:12px;padding:7px 6px;border-bottom:1px solid var(--border);vertical-align:middle}
.inv-table tr:last-child td{border-bottom:none}
.inv-item-name{font-size:12.5px;color:var(--text);font-weight:600;line-height:1.3}
.inv-cat{font-size:10px;color:var(--text3);font-weight:700;margin-top:1px}
.qty-input{width:48px;background:var(--bg);border:1px solid var(--border2);color:var(--text);font-family:var(--font-b);font-weight:700;font-size:12px;border-radius:6px;padding:4px 5px;text-align:center;outline:none}
.qty-input:focus{border-color:var(--amber)}
.status-pill{font-size:10px;font-weight:700;padding:3px 7px;border-radius:99px;border:1px solid;white-space:nowrap;cursor:pointer;display:inline-block;transition:all 0.15s}
.sp-ok{color:var(--green);border-color:var(--green-border);background:var(--green-bg)}
.sp-warn{color:var(--yellow);border-color:var(--yellow-border);background:var(--yellow-bg)}
.sp-broken{color:var(--red);border-color:var(--red-border);background:var(--red-bg)}
.score-grid{display:grid;grid-template-columns:1fr 1fr;gap:8px;margin-bottom:12px}
.score-card{background:var(--bg);border-radius:var(--radius);padding:10px 12px;border:1px solid var(--border)}
.score-label{font-size:10px;color:var(--text3);font-weight:800;text-transform:uppercase;letter-spacing:0.05em;margin-bottom:4px}
.score-val{font-size:22px;font-weight:900;font-family:var(--font-b);color:var(--amber)}
.score-sub{font-size:10px;color:var(--text3);font-weight:600;margin-top:2px}
.emp-card{background:var(--bg2);border:1px solid var(--border);border-radius:var(--radius);margin-bottom:10px;overflow:hidden}
.emp-header{display:flex;align-items:center;gap:10px;padding:10px 12px;cursor:pointer;background:var(--bg)}
.emp-avatar{width:32px;height:32px;border-radius:50%;background:var(--amber-bg);border:1px solid var(--amber-border);display:flex;align-items:center;justify-content:center;font-size:12px;font-weight:800;color:var(--brand-red);flex-shrink:0}
.emp-name{font-size:13px;font-weight:600;flex:1}
.emp-role-lbl{font-size:11px;color:var(--text3);font-weight:700}
.emp-body{padding:12px;display:none}
.emp-body.open{display:block}
.eval-row{display:flex;align-items:center;gap:8px;margin-bottom:8px}
.eval-label{font-size:12px;color:var(--text2);flex:1;line-height:1.4}
.star-row{display:flex;gap:3px}
.star{width:27px;height:27px;border-radius:5px;border:1px solid var(--border2);background:var(--bg);display:flex;align-items:center;justify-content:center;cursor:pointer;font-size:12px;font-weight:700;transition:all 0.15s;color:var(--text3)}
.star.lit{background:var(--amber-bg);border-color:var(--amber-border);color:var(--brand-red)}
.photo-slot{background:var(--bg);border:1px solid var(--border);border-radius:var(--radius);padding:12px;margin-bottom:10px}
.photo-slot-hdr{display:flex;align-items:center;justify-content:space-between;margin-bottom:8px}
.photo-slot-num{font-size:11px;color:var(--text3);font-weight:800;text-transform:uppercase;letter-spacing:0.05em}
.photo-rm-btn{background:var(--red-bg);border:1px solid var(--red-border);border-radius:5px;color:var(--red);font-size:10px;font-weight:700;padding:2px 8px;cursor:pointer}
.photo-zone{border:2px dashed var(--border2);border-radius:var(--radius);padding:20px;text-align:center;cursor:pointer;margin-bottom:8px;transition:border-color 0.15s}
.photo-zone:hover{border-color:var(--amber)}
.photo-zone-icon{font-size:22px;margin-bottom:6px;opacity:0.4}
.photo-zone-label{font-size:12px;font-weight:700;color:var(--text3)}
.photo-zone-sub{font-size:11px;color:var(--text3);font-weight:600;margin-top:2px}
.photo-thumb{width:100%;aspect-ratio:4/3;border-radius:8px;overflow:hidden;margin-bottom:8px}
.photo-thumb img{width:100%;height:100%;object-fit:cover}
.photo-tag{font-size:10px;font-weight:700;padding:2px 7px;border-radius:99px;border:1px solid;display:inline-block;cursor:pointer;margin:2px;transition:all 0.15s}
.ptag-normal{color:var(--text3);border-color:var(--border2)}
.ptag-selected{color:var(--brand-red);border-color:var(--amber-border);background:var(--amber-bg)}
.photo-caption{width:100%;background:var(--bg2);border:1px solid var(--border2);color:var(--text);font-family:var(--font-b);font-size:11px;font-weight:600;border-radius:5px;padding:6px 9px;outline:none;margin-top:6px}
.photo-caption:focus{border-color:var(--amber)}
.photo-compress-info{font-size:10px;color:var(--text3);font-weight:700;margin-top:4px}
.flag-item{display:flex;align-items:flex-start;gap:10px;padding:10px 0;border-bottom:1px solid var(--border)}
.flag-item:last-child{border-bottom:none}
.flag-dot{width:8px;height:8px;border-radius:50%;flex-shrink:0;margin-top:5px}
.flag-red{background:var(--red)}
.flag-amber{background:var(--amber)}
.flag-green{background:var(--green)}
.flag-text{font-size:12.5px;font-weight:600;line-height:1.5;flex:1}
.flag-source{font-size:10px;color:var(--text3);font-weight:700;margin-top:2px}
.divider{font-size:10px;font-weight:800;letter-spacing:0.08em;text-transform:uppercase;color:var(--brand-red);padding:12px 0 6px;border-top:1px solid var(--border);margin-top:4px}
.divider:first-child{border-top:none;margin-top:0;padding-top:0}
.add-btn{width:100%;padding:10px;background:var(--bg);border:1px dashed var(--border2);border-radius:var(--radius);color:var(--text3);font-size:13px;font-weight:700;cursor:pointer;font-family:var(--font-b)}
.submit-wrap{position:fixed;bottom:0;left:0;right:0;padding:12px 16px 28px;background:linear-gradient(to top,var(--bg) 70%,transparent);display:flex;gap:8px}
.submit-btn{flex:1;padding:14px;background:var(--brand-red);color:#fff;border:none;border-radius:var(--radius-lg);font-family:var(--font-d);font-size:18px;letter-spacing:0.05em;cursor:pointer;box-shadow:0 4px 20px rgba(122,18,0,0.35);-webkit-appearance:none;transition:all 0.18s}
.submit-btn:active{transform:scale(0.97)}
.submit-btn.done{background:var(--green);color:#fff;box-shadow:0 4px 20px rgba(67,160,71,0.30)}
.submit-btn:disabled{opacity:0.6;cursor:not-allowed}
.back-btn{padding:14px 16px;background:var(--bg2);border:1px solid var(--border2);border-radius:var(--radius-lg);font-family:var(--font-d);font-size:18px;letter-spacing:0.04em;color:var(--text2);cursor:pointer;white-space:nowrap;transition:all 0.15s;flex-shrink:0}
.back-btn:active{background:var(--bg4)}
.toast{position:fixed;bottom:90px;left:50%;transform:translateX(-50%) translateY(12px);background:var(--brand-dark);color:#fff;font-size:13px;font-weight:700;padding:9px 16px;border-radius:99px;z-index:999;opacity:0;transition:all 0.22s;white-space:nowrap;pointer-events:none;max-width:90vw;text-align:center}
.toast.show{opacity:1;transform:translateX(-50%) translateY(0)}
.summary-header{background:var(--brand-dark);border-radius:var(--radius-lg);padding:16px;margin-bottom:14px;text-align:center}
.summary-outlet{font-family:var(--font-d);font-size:22px;color:#F4B07A;letter-spacing:0.04em}
.summary-meta{font-size:11px;color:rgba(255,255,255,0.60);font-weight:700;margin-top:4px}
.summary-spv{font-size:13px;font-weight:700;color:rgba(255,255,255,0.85);margin-top:6px}
.note-textarea{width:100%;min-height:100px;background:var(--bg);border:1px solid var(--border2);color:var(--text);font-family:var(--font-b);font-size:13px;font-weight:600;border-radius:8px;padding:10px 12px;outline:none;resize:vertical;line-height:1.6}
.note-textarea:focus{border-color:var(--amber)}
@media print{
  body{background:#fff;color:#000}
  .app-header,.section-tabs,.progress-bar-wrap,.submit-wrap,.pdf-btn,.add-btn,.logout-btn{display:none!important}
  .section-page{display:block!important}
  .main{padding:0}
  .card{border:1px solid #ddd;background:#fff;margin-bottom:8px;break-inside:avoid}
  .card-title{color:#666}
  .inv-table td,.inv-table th{font-size:11px}
  .emp-body{display:block!important}
  .photo-zone{display:none}
}
</style>
</head>
<body>

<header class="app-header">
  <div class="header-row">
    <div class="brand">
      <img class="brand-logo" src="/assets/logo.png" alt="SS">
      <div>
        <div class="brand-name">SS Operations</div>
        <div class="brand-sub">Laporan Kunjungan SPV</div>
      </div>
    </div>
    <div class="header-actions">
      <button class="pdf-btn" onclick="window.location.href='/dashboard'">← Dashboard</button>
      <button class="pdf-btn" onclick="exportPDF()">Simpan PDF</button>
      <button class="logout-btn" onclick="doLogout()">Keluar</button>
    </div>
  </div>
  <div class="section-tabs">
    <button class="stab active" onclick="goTab(0)">Info</button>
    <button class="stab" onclick="goTab(1)">Inventaris</button>
    <button class="stab" onclick="goTab(2)">Penjualan</button>
    <button class="stab" onclick="goTab(3)">Stok</button>
    <button class="stab" onclick="goTab(4)">Karyawan</button>
    <button class="stab" onclick="goTab(5)">Foto</button>
    <button class="stab" onclick="goTab(6)">Ringkasan</button>
  </div>
</header>

<div class="progress-bar-wrap">
  <div class="prog-row">
    <span class="prog-label" id="progLabel">Lengkapi semua bagian</span>
    <span class="prog-pct" id="progPct">0%</span>
  </div>
  <div class="prog-track"><div class="prog-fill" id="progFill"></div></div>
</div>

<main class="main">

<!-- TAB 0: INFO -->
<div class="section-page active" id="tab-0">
  <div class="card">
    <div class="card-title">Info Kunjungan</div>
    <div class="grid2">
      <div class="field"><label>Nama SPV</label><input type="text" id="spv_name" placeholder="Nama lengkap" value="<?= htmlspecialchars($user['full_name'] ?? '') ?>" oninput="updateProgress()"></div>
      <div class="field"><label>Tanggal</label><input type="date" id="visit_date" oninput="updateProgress()"></div>
      <div class="field"><label>Jam Tiba</label><input type="time" id="time_arrive"></div>
      <div class="field"><label>Jam Keluar</label><input type="time" id="time_leave"></div>
    </div>
    <div class="field"><label>Outlet yang dikunjungi</label>
      <select id="outlet_id" onchange="onOutletChange();updateProgress()">
        <option value="">-- Pilih Outlet --</option>
        <?php if ($outletsByType['internal']): ?>
        <optgroup label="Outlet Internal">
          <?php foreach ($outletsByType['internal'] as $o): ?>
          <option value="<?= $o['id'] ?>" data-target="<?= $o['daily_sales_target'] ?>"><?= htmlspecialchars($o['name']) ?></option>
          <?php endforeach; ?>
        </optgroup>
        <?php endif; ?>
        <?php if ($outletsByType['mitra']): ?>
        <optgroup label="Outlet Mitra">
          <?php foreach ($outletsByType['mitra'] as $o): ?>
          <option value="<?= $o['id'] ?>" data-target="<?= $o['daily_sales_target'] ?>"><?= htmlspecialchars($o['name']) ?></option>
          <?php endforeach; ?>
        </optgroup>
        <?php endif; ?>
      </select>
    </div>
    <div class="field"><label>Shift dikunjungi</label>
      <select id="visit_shift">
        <option value="open">Pembukaan</option>
        <option value="ops" selected>Operasional</option>
        <option value="close">Penutupan</option>
        <option value="all">Full Day</option>
      </select>
    </div>
    <div class="field"><label>PIC / Kasir yang bertugas</label><input type="text" id="pic_on_duty" placeholder="Nama karyawan yang ditemui"></div>
  </div>
  <div class="card">
    <div class="card-title">Kondisi Umum Outlet</div>
    <div class="field"><label>Kebersihan area exterior</label>
      <div class="status-row">
        <div class="status-opt" onclick="setStatus(this,'cond_ext','ok')">OK</div>
        <div class="status-opt" onclick="setStatus(this,'cond_ext','warn')">Perlu Perhatian</div>
        <div class="status-opt" onclick="setStatus(this,'cond_ext','broken')">Buruk</div>
      </div>
    </div>
    <div class="field"><label>Kebersihan area dapur / interior</label>
      <div class="status-row">
        <div class="status-opt" onclick="setStatus(this,'cond_int','ok')">OK</div>
        <div class="status-opt" onclick="setStatus(this,'cond_int','warn')">Perlu Perhatian</div>
        <div class="status-opt" onclick="setStatus(this,'cond_int','broken')">Buruk</div>
      </div>
    </div>
    <div class="field"><label>Kebersihan kamar mandi</label>
      <div class="status-row">
        <div class="status-opt" onclick="setStatus(this,'cond_toilet','ok')">OK</div>
        <div class="status-opt" onclick="setStatus(this,'cond_toilet','warn')">Perlu Perhatian</div>
        <div class="status-opt" onclick="setStatus(this,'cond_toilet','broken')">Buruk</div>
      </div>
    </div>
    <div class="field"><label>Kepatuhan seragam & higienitas karyawan</label>
      <div class="status-row">
        <div class="status-opt" onclick="setStatus(this,'cond_uniform','ok')">OK</div>
        <div class="status-opt" onclick="setStatus(this,'cond_uniform','warn')">Tidak Lengkap</div>
        <div class="status-opt" onclick="setStatus(this,'cond_uniform','broken')">Tidak Pakai</div>
      </div>
    </div>
    <div class="field"><label>Catatan kondisi umum</label>
      <textarea id="cond_notes" placeholder="Deskripsikan temuan kondisi outlet..."></textarea>
    </div>
  </div>
</div>

<!-- TAB 1: INVENTARIS -->
<div class="section-page" id="tab-1">
  <div class="card">
    <div class="card-title">Inventaris Aset <span>— tap Status untuk ubah</span></div>
    <table class="inv-table">
      <thead><tr>
        <th style="width:38%">Aset</th>
        <th style="width:12%">Std</th>
        <th style="width:20%">Ada</th>
        <th style="width:30%">Status</th>
      </tr></thead>
      <tbody id="invBody"></tbody>
    </table>
  </div>
  <div class="card">
    <div class="card-title">Catatan Inventaris</div>
    <div class="field"><label>Aset rusak / hilang yang perlu ditindaklanjuti</label>
      <textarea id="inv_notes" placeholder="Contoh: Spatula bakar 2pcs rusak — perlu diganti sebelum weekend."></textarea>
    </div>
    <div class="field"><label>Estimasi biaya penggantian / perbaikan (Rp)</label>
      <input type="number" id="inv_repair_cost" placeholder="0">
    </div>
  </div>
</div>

<!-- TAB 2: PENJUALAN -->
<div class="section-page" id="tab-2">
  <div class="card">
    <div class="card-title">Data Penjualan</div>
    <div class="grid2">
      <div class="field"><label>Total penjualan (Rp)</label><input type="number" id="sales_total" placeholder="0" oninput="calcSales()"></div>
      <div class="field"><label>Jumlah transaksi</label><input type="number" id="sales_trx" placeholder="0" oninput="calcSales()"></div>
      <div class="field"><label>Penjualan tunai (Rp)</label><input type="number" id="sales_cash" placeholder="0" oninput="calcSales()"></div>
      <div class="field"><label>QRIS / transfer (Rp)</label><input type="number" id="sales_qris" placeholder="0" oninput="calcSales()"></div>
      <div class="field"><label>GoFood (Rp)</label><input type="number" id="sales_gofood" placeholder="0"></div>
      <div class="field"><label>GrabFood (Rp)</label><input type="number" id="sales_grab" placeholder="0"></div>
      <div class="field"><label>ShopeeFood (Rp)</label><input type="number" id="sales_shopee" placeholder="0"></div>
      <div class="field"><label>Kas fisik terhitung (Rp)</label><input type="number" id="cash_counted" placeholder="0" oninput="calcSales()"></div>
    </div>
    <div class="score-grid" style="margin-top:4px">
      <div class="score-card">
        <div class="score-label">Avg Transaction</div>
        <div class="score-val" id="avg_trx">Rp 0</div>
        <div class="score-sub">per transaksi</div>
      </div>
      <div class="score-card">
        <div class="score-label">Selisih Kas</div>
        <div class="score-val" id="cash_diff" style="color:var(--green)">Rp 0</div>
        <div class="score-sub">over / (short)</div>
      </div>
    </div>
  </div>
  <div class="card">
    <div class="card-title">Target vs Aktual</div>
    <div class="grid2">
      <div class="field"><label>Target harian (Rp)</label><input type="number" id="sales_target" placeholder="0" oninput="calcSales()"></div>
      <div class="field"><label>Pencapaian</label><input type="text" id="sales_ach" placeholder="—" readonly style="background:var(--bg4);color:var(--amber);font-weight:600"></div>
    </div>
    <div class="field"><label>Kondisi penjualan hari ini</label>
      <select id="sales_condition">
        <option value="">-- Pilih --</option>
        <option>Di atas ekspektasi</option>
        <option>Sesuai target</option>
        <option>Di bawah target</option>
        <option>Sangat di bawah target</option>
      </select>
    </div>
    <div class="field"><label>Faktor yang mempengaruhi</label>
      <textarea id="sales_notes" placeholder="Contoh: Hujan deras jam 11–14, penjualan drop. Online dominan hari ini."></textarea>
    </div>
  </div>
</div>

<!-- TAB 3: STOK -->
<div class="section-page" id="tab-3">
  <div class="card">
    <div class="card-title">Stok Bahan Baku</div>
    <div class="divider">Daging & Protein</div>
    <div class="grid2">
      <div class="field"><label>Stok daging spit (kg)</label><input type="number" id="s_daging" placeholder="0" step="0.1"></div>
      <div class="field"><label>Kiriman hari ini (kg)</label><input type="number" id="s_kiriman" placeholder="0" step="0.1"></div>
      <div class="field"><label>Spit digunakan hari ini</label><input type="number" id="s_spit_used" placeholder="0"></div>
      <div class="field"><label>Yield rata-rata (%)</label><input type="number" id="s_yield" placeholder="0" step="0.1"></div>
    </div>
    <div class="divider">Bahan Pendukung</div>
    <div class="grid2">
      <div class="field"><label>Roti pita (pcs)</label><input type="number" id="s_roti" placeholder="0"></div>
      <div class="field"><label>Saus (botol)</label><input type="number" id="s_saus" placeholder="0"></div>
      <div class="field"><label>Sayuran (hari)</label><input type="number" id="s_sayur" placeholder="0" step="0.5"></div>
      <div class="field"><label>Tabung gas</label><input type="number" id="s_gas" placeholder="0"></div>
    </div>
    <div class="divider">Packaging</div>
    <div class="grid2">
      <div class="field"><label>Wrapping paper (lembar)</label><input type="number" id="s_wrap" placeholder="0"></div>
      <div class="field"><label>Box kemasan (pcs)</label><input type="number" id="s_box" placeholder="0"></div>
      <div class="field"><label>Cup saus (pcs)</label><input type="number" id="s_cup" placeholder="0"></div>
      <div class="field"><label>Sendok plastik (pcs)</label><input type="number" id="s_sendok" placeholder="0"></div>
    </div>
  </div>
  <div class="card">
    <div class="card-title">Penilaian Stok</div>
    <div class="grid2">
      <div class="field"><label>Suhu chiller (°C)</label><input type="number" id="s_suhu_chiller" placeholder="0" step="0.1"></div>
      <div class="field"><label>Suhu freezer (°C)</label><input type="number" id="s_suhu_freezer" placeholder="0" step="0.1"></div>
    </div>
    <div class="field"><label>Bahan expired ditemukan?</label>
      <div class="status-row">
        <div class="status-opt" onclick="setStatus(this,'s_expired','ok')">Tidak ada</div>
        <div class="status-opt" onclick="setStatus(this,'s_expired','warn')">Ada — sudah dibuang</div>
        <div class="status-opt" onclick="setStatus(this,'s_expired','broken')">Ada — masih dipakai</div>
      </div>
    </div>
    <div class="field"><label>Status stok keseluruhan</label>
      <div class="status-row">
        <div class="status-opt" onclick="setStatus(this,'s_overall','ok')">Aman</div>
        <div class="status-opt" onclick="setStatus(this,'s_overall','warn')">Perlu restock</div>
        <div class="status-opt" onclick="setStatus(this,'s_overall','broken')">Kritis</div>
      </div>
    </div>
    <div class="field"><label>Total waste hari ini (Rp estimasi)</label><input type="number" id="s_waste" placeholder="0"></div>
    <div class="field"><label>Catatan stok</label>
      <textarea id="s_notes" placeholder="Bahan kritis, anomali yield, masalah supplier..."></textarea>
    </div>
  </div>
</div>

<!-- TAB 4: KARYAWAN -->
<div class="section-page" id="tab-4">
  <div class="card">
    <div class="card-title">Evaluasi Karyawan</div>
    <div id="empList"></div>
    <button class="add-btn" onclick="addEmployee()" style="margin-top:8px">+ Tambah Karyawan</button>
  </div>
  <div class="card">
    <div class="card-title">Catatan SDM</div>
    <div class="grid2">
      <div class="field"><label>Karyawan hadir</label><input type="number" id="emp_present" placeholder="0"></div>
      <div class="field"><label>Tidak hadir / izin</label><input type="text" id="emp_absent" placeholder="Nama & alasan"></div>
    </div>
    <div class="field"><label>Isu SDM yang perlu ditindaklanjuti</label>
      <textarea id="emp_issues" placeholder="Performa, kedisiplinan, konflik..."></textarea>
    </div>
    <div class="field"><label>Rekomendasi tindakan</label>
      <select id="emp_action">
        <option value="">-- Tidak ada --</option>
        <option>Peringatan lisan</option>
        <option>Surat peringatan SP1</option>
        <option>Surat peringatan SP2</option>
        <option>Rotasi outlet</option>
        <option>Promosi / kenaikan tanggung jawab</option>
        <option>Perlu training ulang</option>
      </select>
    </div>
  </div>
</div>

<!-- TAB 5: FOTO -->
<div class="section-page" id="tab-5">
  <div class="card">
    <div class="card-title">Dokumentasi Foto Kunjungan</div>
    <p style="font-size:12px;color:var(--text3);margin-bottom:12px;line-height:1.6">Foto bersifat opsional — hanya wajib ada caption jika foto diupload. Foto dikompres otomatis ke maks 800KB.</p>
    <div id="photoSections"></div>
    <button class="add-btn" onclick="addPhotoSlot()" style="margin-top:4px">+ Tambah Foto</button>
  </div>
</div>

<!-- TAB 6: RINGKASAN -->
<div class="section-page" id="tab-6">
  <div id="summaryContent">
    <div style="text-align:center;padding:40px 20px;color:var(--text3)">
      <div style="font-size:32px;opacity:0.3;margin-bottom:12px">📋</div>
      <div style="font-size:13px">Tap "Simpan & Generate Ringkasan"<br>untuk melihat report lengkap.</div>
    </div>
  </div>
</div>

</main>

<div class="submit-wrap">
  <button class="back-btn" id="backBtn" onclick="navBack()" style="display:none">←</button>
  <button class="submit-btn" id="submitBtn" onclick="navNext()">Selanjutnya →</button>
</div>
<div class="toast" id="toast"></div>

<script>
// ─── Data dari server ─────────────────────────────────────────────────────
const SPV_USER_ID = <?= (int) $user['id'] ?>;
const INVENTORY   = <?= json_encode($spvMaster['inventory'],    JSON_UNESCAPED_UNICODE) ?>;
const PHOTO_TAGS  = <?= json_encode($spvMaster['photoTags'],    JSON_UNESCAPED_UNICODE) ?>;
const EVAL_CRITERIA = <?= json_encode($spvMaster['evalCriteria'], JSON_UNESCAPED_UNICODE) ?>;

// ─── State ────────────────────────────────────────────────────────────────
let invState = {}, statusState = {}, employees = [], photos = [], photoCounter = 0;
let savedVisitId = null;

// ─── Init ─────────────────────────────────────────────────────────────────
function init() {
  document.getElementById('visit_date').value = new Date().toISOString().split('T')[0];
  renderInventory();
  renderEmployees();
  renderPhotoSlots();
  updateProgress();
  updateNavBtns();
}

// ─── Outlet select: auto-fill target penjualan ───────────────────────────
function onOutletChange() {
  const sel = document.getElementById('outlet_id');
  const opt = sel.options[sel.selectedIndex];
  const target = opt.dataset.target || '0';
  const targetField = document.getElementById('sales_target');
  if (targetField && parseInt(target) > 0) {
    targetField.value = target;
    calcSales();
  }
}

// ─── Inventaris ───────────────────────────────────────────────────────────
function renderInventory() {
  let html = '';
  INVENTORY.forEach(cat => {
    html += `<tr><td colspan="4" style="padding:10px 6px 4px;font-size:10px;font-family:var(--font-m);color:var(--text3);letter-spacing:0.06em;text-transform:uppercase;border-bottom:1px solid var(--border2)">${cat.cat}</td></tr>`;
    cat.items.forEach((item, i) => {
      const key = cat.cat + '_' + i;
      const qty = invState[key + '_qty'] !== undefined ? invState[key + '_qty'] : item.std;
      const st  = invState[key + '_st'] || 'ok';
      html += `<tr>
        <td><div class="inv-item-name">${item.name}</div><div class="inv-cat">${item.std} ${item.unit}</div></td>
        <td style="color:var(--text3);font-family:var(--font-m);font-size:11px">${item.std}</td>
        <td><input class="qty-input" type="number" value="${qty}" min="0" onchange="setInvQty('${key}',this.value)"></td>
        <td><span class="status-pill ${st==='ok'?'sp-ok':st==='warn'?'sp-warn':'sp-broken'}" onclick="cycleInvStatus('${key}',this)">${st==='ok'?'OK':st==='warn'?'Perlu Cek':'Rusak'}</span></td>
      </tr>`;
    });
  });
  document.getElementById('invBody').innerHTML = html;
}

function setInvQty(key, val) { invState[key + '_qty'] = parseInt(val) || 0; }

function cycleInvStatus(key, el) {
  const cur  = invState[key + '_st'] || 'ok';
  const next = cur === 'ok' ? 'warn' : cur === 'warn' ? 'broken' : 'ok';
  invState[key + '_st'] = next;
  el.className = 'status-pill ' + (next === 'ok' ? 'sp-ok' : next === 'warn' ? 'sp-warn' : 'sp-broken');
  el.textContent = next === 'ok' ? 'OK' : next === 'warn' ? 'Perlu Cek' : 'Rusak';
}

function setStatus(el, key, val) {
  statusState[key] = val;
  el.parentElement.querySelectorAll('.status-opt').forEach(o => {
    o.className = 'status-opt';
    if (o === el) o.classList.add(val === 'ok' ? 's-ok' : val === 'warn' ? 's-warn' : 's-broken');
  });
}

// ─── Penjualan ────────────────────────────────────────────────────────────
function calcSales() {
  const total   = parseFloat(document.getElementById('sales_total').value)   || 0;
  const trx     = parseFloat(document.getElementById('sales_trx').value)     || 0;
  const cash    = parseFloat(document.getElementById('sales_cash').value)     || 0;
  const counted = parseFloat(document.getElementById('cash_counted').value)   || 0;
  const target  = parseFloat(document.getElementById('sales_target').value)   || 0;
  const avg  = trx > 0 ? Math.round(total / trx) : 0;
  const diff = counted - cash;
  const ach  = target > 0 ? Math.round(total / target * 100) + '%' : '—';
  document.getElementById('avg_trx').textContent = 'Rp ' + avg.toLocaleString('id-ID');
  const de = document.getElementById('cash_diff');
  de.textContent = (diff >= 0 ? '+' : '') + 'Rp ' + Math.abs(diff).toLocaleString('id-ID');
  de.style.color = diff < 0 ? 'var(--red)' : diff > 0 ? 'var(--amber)' : 'var(--green)';
  document.getElementById('sales_ach').value = ach;
}

// ─── Karyawan ─────────────────────────────────────────────────────────────
function addEmployee() {
  const id = 'emp_' + Date.now();
  employees.push({ id, name: '', role: 'Crew', scores: {}, note: '' });
  renderEmployees();
  setTimeout(() => { const inp = document.getElementById('ename_' + id); if (inp) inp.focus(); }, 80);
}

function renderEmployees() {
  const container = document.getElementById('empList');
  if (!employees.length) {
    container.innerHTML = `<div style="text-align:center;padding:20px;color:var(--text3);font-size:12px">Belum ada karyawan. Tap tombol di bawah.</div>`;
    return;
  }
  container.innerHTML = employees.map(emp => {
    const total = EVAL_CRITERIA.reduce((s, c) => s + (emp.scores[c.key] || 0), 0);
    const max   = EVAL_CRITERIA.length * 5;
    const pct   = max > 0 ? Math.round(total / max * 100) : 0;
    const sc    = pct >= 80 ? 'var(--green)' : pct >= 60 ? 'var(--amber)' : 'var(--red)';
    const ini   = (emp.name || '?').split(' ').map(w => w[0] || '').slice(0, 2).join('').toUpperCase();
    return `<div class="emp-card">
      <div class="emp-header" onclick="toggleEmp('${emp.id}')">
        <div class="emp-avatar">${ini || '?'}</div>
        <div style="flex:1"><div class="emp-name">${emp.name || '(Nama belum diisi)'}</div><div class="emp-role-lbl">${emp.role}</div></div>
        <span style="font-size:13px;font-weight:700;font-family:var(--font-m);padding:3px 9px;border-radius:6px;color:${sc};background:${sc}22;border:1px solid ${sc}44">${pct}%</span>
      </div>
      <div class="emp-body" id="eb_${emp.id}">
        <div class="grid2" style="margin-bottom:10px">
          <div class="field"><label>Nama</label><input type="text" id="ename_${emp.id}" value="${emp.name}" placeholder="Nama lengkap" oninput="updateEmp('${emp.id}','name',this.value)"></div>
          <div class="field"><label>Posisi</label><select onchange="updateEmp('${emp.id}','role',this.value)">${['Crew','Kasir','Kepala Shift','Cook','Trainee'].map(r => `<option${r === emp.role ? ' selected' : ''}>${r}</option>`).join('')}</select></div>
        </div>
        ${EVAL_CRITERIA.map(c => `<div class="eval-row">
          <div class="eval-label">${c.label}</div>
          <div class="star-row">${[1,2,3,4,5].map(n => `<div class="star${(emp.scores[c.key]||0)>=n?' lit':''}" onclick="setScore('${emp.id}','${c.key}',${n})">${n}</div>`).join('')}</div>
        </div>`).join('')}
        <div class="field" style="margin-top:10px"><label>Catatan evaluasi</label><textarea placeholder="Kelebihan, kekurangan, rekomendasi..." oninput="updateEmp('${emp.id}','note',this.value)">${emp.note}</textarea></div>
        <button onclick="removeEmp('${emp.id}')" style="width:100%;padding:7px;background:var(--red-bg);border:1px solid var(--red-border);border-radius:7px;color:var(--red);font-size:12px;cursor:pointer;margin-top:6px">Hapus</button>
      </div>
    </div>`;
  }).join('');
}

function toggleEmp(id) { const b = document.getElementById('eb_' + id); if (b) b.classList.toggle('open'); }
function updateEmp(id, f, v) { const e = employees.find(e => e.id === id); if (e) { e[f] = v; if (f === 'name') renderEmployees(); } }
function setScore(eid, key, val) {
  const e = employees.find(e => e.id === eid);
  if (e) { e.scores[key] = val; renderEmployees(); setTimeout(() => { const b = document.getElementById('eb_' + eid); if (b) b.classList.add('open'); }, 10); }
}
function removeEmp(id) { employees = employees.filter(e => e.id !== id); renderEmployees(); }

// ─── Foto: upload + kompres client-side ──────────────────────────────────
const MAX_PHOTO_BYTES = 60 * 1024; // 60 KB — target payload base64 ~80 KB

function addPhotoSlot() {
  const id = 'ph_' + photoCounter++;
  photos.push({ id, blob: null, src: '', label: '', tags: [], sizeInfo: '' });
  renderPhotoSlots();
}

async function compressImage(file) {
  return new Promise(resolve => {
    const img = new Image();
    const url = URL.createObjectURL(file);
    img.onload = () => {
      URL.revokeObjectURL(url);
      const canvas  = document.createElement('canvas');
      let { width, height } = img;
      const maxDim = 1600;
      if (width > maxDim || height > maxDim) {
        if (width > height) { height = Math.round(height * maxDim / width); width = maxDim; }
        else                { width = Math.round(width * maxDim / height);  height = maxDim; }
      }
      canvas.width  = width;
      canvas.height = height;
      canvas.getContext('2d').drawImage(img, 0, 0, width, height);
      let quality = 0.85;
      const tryCompress = () => {
        canvas.toBlob(blob => {
          if (blob.size <= MAX_PHOTO_BYTES || quality <= 0.3) {
            resolve(blob);
          } else {
            quality -= 0.1;
            tryCompress();
          }
        }, 'image/jpeg', quality);
      };
      tryCompress();
    };
    img.src = url;
  });
}

async function loadPhoto(id, input) {
  const file = input.files[0];
  if (!file) return;
  const p = photos.find(p => p.id === id);
  if (!p) return;
  const blob = await compressImage(file);
  p.blob = blob;
  p.sizeInfo = (blob.size / 1024).toFixed(0) + ' KB';
  const reader = new FileReader();
  reader.onload = e => { p.src = e.target.result; renderPhotoSlots(); };
  reader.readAsDataURL(blob);
}

function renderPhotoSlots() {
  const c = document.getElementById('photoSections');
  if (!photos.length) {
    c.innerHTML = `<div style="text-align:center;padding:20px;color:var(--text3);font-size:12px">Belum ada foto. Tap tombol di bawah (opsional).</div>`;
    return;
  }
  c.innerHTML = photos.map((ph, pi) => `
    <div class="photo-slot">
      <div class="photo-slot-hdr">
        <span class="photo-slot-num">FOTO ${pi + 1}</span>
        <button class="photo-rm-btn" onclick="removePhoto('${ph.id}')">Hapus</button>
      </div>
      ${ph.src
        ? `<div class="photo-thumb"><img src="${ph.src}"></div><div class="photo-compress-info">Ukuran: ${ph.sizeInfo}</div>`
        : `<label style="display:block;cursor:pointer">
             <div class="photo-zone"><div class="photo-zone-icon">📷</div><div class="photo-zone-label">Ketuk untuk pilih foto</div><div class="photo-zone-sub">JPG, PNG, HEIC · dikompres otomatis ke ≤ 800KB</div></div>
             <input type="file" accept="image/*" style="display:none" onchange="loadPhoto('${ph.id}',this)">
           </label>`}
      <input class="photo-caption" type="text" placeholder="Keterangan foto (wajib jika ada foto) *" value="${ph.label}" oninput="updatePhoto('${ph.id}','label',this.value)">
      <div style="margin-top:6px">${PHOTO_TAGS.map(t => `<span class="photo-tag ${ph.tags.includes(t) ? 'ptag-selected' : 'ptag-normal'}" onclick="toggleTag('${ph.id}','${t}')">${t}</span>`).join('')}</div>
    </div>`).join('');
}

function updatePhoto(id, f, v) { const p = photos.find(p => p.id === id); if (p) p[f] = v; }
function removePhoto(id) { photos = photos.filter(p => p.id !== id); renderPhotoSlots(); }
function toggleTag(id, tag) {
  const p = photos.find(p => p.id === id);
  if (!p) return;
  const i = p.tags.indexOf(tag);
  i >= 0 ? p.tags.splice(i, 1) : p.tags.push(tag);
  renderPhotoSlots();
}

// ─── Progress bar ─────────────────────────────────────────────────────────
function updateProgress() {
  let filled = 0, total = 4;
  if (document.getElementById('spv_name')?.value)   filled++;
  if (document.getElementById('outlet_id')?.value)  filled++;
  if (document.getElementById('sales_total')?.value) filled++;
  if (employees.length > 0) filled++;
  const pct = Math.round(filled / total * 100);
  document.getElementById('progFill').style.width  = pct + '%';
  document.getElementById('progPct').textContent   = pct + '%';
  document.getElementById('progLabel').textContent = filled + ' dari ' + total + ' bagian utama terisi';
}

// ─── Tab navigation ───────────────────────────────────────────────────────
let currentTab = 0;
const TAB_COUNT = 7;

function goTab(i) {
  currentTab = i;
  document.querySelectorAll('.section-page').forEach((p, pi) => p.classList.toggle('active', pi === i));
  document.querySelectorAll('.stab').forEach((t, ti) => t.classList.toggle('active', ti === i));
  if (i === TAB_COUNT - 1) buildSummary();
  updateNavBtns();
  window.scrollTo({ top: 0, behavior: 'smooth' });
}

function updateNavBtns() {
  const btn     = document.getElementById('submitBtn');
  const backBtn = document.getElementById('backBtn');
  const isLast  = currentTab === TAB_COUNT - 1;
  const isDone  = btn.classList.contains('done');

  backBtn.style.display = currentTab > 0 ? '' : 'none';

  if (isDone) return; // sudah simpan, biarkan status akhir
  if (isLast) {
    btn.textContent = 'Simpan & Kirim Report';
    btn.onclick     = handleSubmit;
  } else {
    btn.textContent = 'Selanjutnya →';
    btn.onclick     = navNext;
  }
}

function navNext() {
  if (currentTab < TAB_COUNT - 1) goTab(currentTab + 1);
}

function navBack() {
  if (currentTab > 0) goTab(currentTab - 1);
}

// ─── Summary builder ──────────────────────────────────────────────────────
function buildSummary() {
  const outletSel  = document.getElementById('outlet_id');
  const outlet     = outletSel.options[outletSel.selectedIndex]?.text || '—';
  const spv        = document.getElementById('spv_name')?.value || '—';
  const date       = document.getElementById('visit_date')?.value || '—';
  const t1         = document.getElementById('time_arrive')?.value || '—';
  const t2         = document.getElementById('time_leave')?.value || '—';
  const salesTotal = parseFloat(document.getElementById('sales_total')?.value) || 0;
  const salesTarget= parseFloat(document.getElementById('sales_target')?.value) || 0;
  const ach        = salesTarget > 0 ? Math.round(salesTotal / salesTarget * 100) : null;
  const waste      = parseFloat(document.getElementById('s_waste')?.value) || 0;
  const cashDiff   = (parseFloat(document.getElementById('cash_counted')?.value)||0)
                   - (parseFloat(document.getElementById('sales_cash')?.value)||0);

  const broken = [];
  INVENTORY.forEach(cat => cat.items.forEach((item, i) => {
    const key = cat.cat + '_' + i;
    const st  = invState[key + '_st'] || 'ok';
    if (st === 'broken') broken.push(item.name);
    else if (st === 'warn') broken.push(item.name + ' (perlu cek)');
  }));

  let empAvg = 0;
  if (employees.length > 0) {
    const t = employees.reduce((s, e) => s + EVAL_CRITERIA.reduce((ss, c) => ss + (e.scores[c.key] || 0), 0), 0);
    empAvg = Math.round(t / (employees.length * EVAL_CRITERIA.length * 5) * 100);
  }

  const flags = [];
  if (statusState['cond_int']     === 'broken') flags.push({c:'red',   t:'Kebersihan interior buruk — tindakan segera', s:'Kondisi Umum'});
  if (statusState['cond_uniform'] === 'broken') flags.push({c:'red',   t:'Karyawan tidak memakai seragam / hairnet — pelanggaran SOP', s:'Kondisi Umum'});
  if (statusState['s_expired']    === 'broken') flags.push({c:'red',   t:'Bahan expired masih digunakan — food safety violation', s:'Stok'});
  if (broken.some(a => a.toLowerCase().includes('cctv interior'))) flags.push({c:'red', t:'CCTV interior rusak — aset keamanan tidak berfungsi', s:'Inventaris'});
  if (broken.some(a => a.toLowerCase().includes('timbangan')))     flags.push({c:'red', t:'Timbangan rusak — porsi tidak terkontrol, food cost bocor', s:'Inventaris'});
  const sc = parseFloat(document.getElementById('s_suhu_chiller')?.value);
  if (sc && sc > 4)  flags.push({c:'red',   t:`Suhu chiller ${sc}°C — di atas batas aman 4°C`, s:'Stok'});
  if (statusState['s_overall'] === 'broken') flags.push({c:'red', t:'Stok bahan baku dalam kondisi kritis', s:'Stok'});
  if (Math.abs(cashDiff) > 50000) flags.push({c:'amber', t:`Selisih kas Rp ${Math.abs(cashDiff).toLocaleString('id-ID')} — perlu investigasi`, s:'Penjualan'});
  if (ach && ach < 70) flags.push({c:'amber', t:`Pencapaian penjualan hanya ${ach}% dari target`, s:'Penjualan'});
  if (waste > 200000) flags.push({c:'amber', t:`Total waste Rp ${waste.toLocaleString('id-ID')} — di atas threshold`, s:'Stok'});
  if (employees.length > 0 && empAvg < 60) flags.push({c:'amber', t:`Rata-rata skor karyawan ${empAvg}% — perlu coaching`, s:'Karyawan'});
  if (broken.length > 3)   flags.push({c:'amber', t:`${broken.length} aset bermasalah ditemukan di outlet ini`, s:'Inventaris'});
  if (!employees.length)   flags.push({c:'amber', t:'Tidak ada evaluasi karyawan di-input', s:'Karyawan'});
  if (!photos.some(p => p.src)) flags.push({c:'amber', t:'Tidak ada foto dokumentasi kunjungan', s:'Foto'});
  if (!flags.length) flags.push({c:'green', t:'Tidak ada red flag signifikan ditemukan saat kunjungan.', s:''});

  const narrative = document.getElementById('spv_narrative')?.value || '';
  const followup  = document.getElementById('followup')?.value || '';

  document.getElementById('summaryContent').innerHTML = `
    <div class="summary-header">
      <div class="summary-outlet">${outlet}</div>
      <div class="summary-meta">${date} &nbsp;|&nbsp; ${t1} – ${t2}</div>
      <div class="summary-spv">Supervisor: ${spv}</div>
    </div>
    <div class="score-grid">
      <div class="score-card"><div class="score-label">Penjualan</div>
        <div class="score-val">Rp ${Math.round(salesTotal / 1000)}k</div>
        <div class="score-sub">${ach ? ach + '% dari target' : 'Target belum diisi'}</div>
      </div>
      <div class="score-card"><div class="score-label">Skor Karyawan</div>
        <div class="score-val" style="color:${empAvg>=80?'var(--green)':empAvg>=60?'var(--amber)':'var(--red)'}">${employees.length ? empAvg + '%' : '—'}</div>
        <div class="score-sub">${employees.length} karyawan dievaluasi</div>
      </div>
      <div class="score-card"><div class="score-label">Aset Bermasalah</div>
        <div class="score-val" style="color:${broken.length>0?'var(--red)':'var(--green)'}">${broken.length}</div>
        <div class="score-sub">dari total inventaris</div>
      </div>
      <div class="score-card"><div class="score-label">Total Waste</div>
        <div class="score-val" style="color:${waste>200000?'var(--red)':'var(--green)'}">Rp ${Math.round(waste/1000)}k</div>
        <div class="score-sub">hari kunjungan</div>
      </div>
    </div>
    <div class="card">
      <div class="card-title">Flag & Temuan Kunjungan</div>
      ${flags.map(f => `<div class="flag-item"><div class="flag-dot flag-${f.c}"></div><div><div class="flag-text">${f.t}</div>${f.s ? `<div class="flag-source">${f.s}</div>` : ''}</div></div>`).join('')}
    </div>
    ${broken.length ? `<div class="card"><div class="card-title">Aset Bermasalah</div>${broken.map(a => `<div style="font-size:12.5px;padding:6px 0;border-bottom:1px solid var(--border);color:var(--text2)">${a}</div>`).join('')}</div>` : ''}
    <div class="card">
      <div class="card-title">Narasi SPV</div>
      <textarea class="note-textarea" id="spv_narrative" placeholder="Tuliskan narasi singkat: temuan utama, tindakan di tempat, tindak lanjut yang diperlukan manajemen..." oninput="">${narrative}</textarea>
    </div>
    <div class="card" style="border-color:var(--amber-border)">
      <div class="card-title" style="color:var(--amber)">Tindak Lanjut Wajib</div>
      <textarea class="note-textarea" id="followup" placeholder="Apa yang HARUS dilakukan sebelum kunjungan berikutnya? Siapa PIC-nya? Kapan deadline-nya?" oninput="">${followup}</textarea>
    </div>`;
}

// ─── Submit ───────────────────────────────────────────────────────────────
async function handleSubmit() {
  const spvName  = document.getElementById('spv_name')?.value?.trim();
  const outletId = parseInt(document.getElementById('outlet_id')?.value);
  if (!spvName || !outletId) {
    showToast('Isi nama SPV dan pilih outlet terlebih dahulu.');
    return;
  }

  // Validasi foto: setiap foto yang sudah diupload wajib punya caption
  const uncaptioned = photos.filter(p => p.src && !p.label.trim());
  if (uncaptioned.length > 0) {
    showToast('Isi keterangan untuk semua foto yang sudah diupload.');
    goTab(5);
    return;
  }

  const btn = document.getElementById('submitBtn');
  btn.disabled    = true;
  btn.textContent = 'Menyimpan...';

  try {
    // Build payload
    const payload = {
      outlet_id:    outletId,
      spv_name:     spvName,
      visit_date:   document.getElementById('visit_date')?.value,
      time_arrive:  document.getElementById('time_arrive')?.value || null,
      time_leave:   document.getElementById('time_leave')?.value  || null,
      visit_shift:  document.getElementById('visit_shift')?.value,
      pic_on_duty:  document.getElementById('pic_on_duty')?.value || null,
      payload_json: {
        info: {
          cond_ext:     statusState['cond_ext']     || null,
          cond_int:     statusState['cond_int']     || null,
          cond_toilet:  statusState['cond_toilet']  || null,
          cond_uniform: statusState['cond_uniform'] || null,
          cond_notes:   document.getElementById('cond_notes')?.value || null,
        },
        inventory: { state: invState, notes: document.getElementById('inv_notes')?.value||null, repair_cost: parseFloat(document.getElementById('inv_repair_cost')?.value)||0 },
        sales: {
          total:     parseFloat(document.getElementById('sales_total')?.value)  || 0,
          trx:       parseFloat(document.getElementById('sales_trx')?.value)    || 0,
          cash:      parseFloat(document.getElementById('sales_cash')?.value)   || 0,
          qris:      parseFloat(document.getElementById('sales_qris')?.value)   || 0,
          gofood:    parseFloat(document.getElementById('sales_gofood')?.value) || 0,
          grab:      parseFloat(document.getElementById('sales_grab')?.value)   || 0,
          shopee:    parseFloat(document.getElementById('sales_shopee')?.value) || 0,
          cash_counted: parseFloat(document.getElementById('cash_counted')?.value) || 0,
          target:    parseFloat(document.getElementById('sales_target')?.value) || 0,
          condition: document.getElementById('sales_condition')?.value || null,
          notes:     document.getElementById('sales_notes')?.value || null,
        },
        stock: {
          daging:       parseFloat(document.getElementById('s_daging')?.value)       || 0,
          kiriman:      parseFloat(document.getElementById('s_kiriman')?.value)       || 0,
          spit_used:    parseFloat(document.getElementById('s_spit_used')?.value)     || 0,
          yield:        parseFloat(document.getElementById('s_yield')?.value)         || 0,
          roti:         parseFloat(document.getElementById('s_roti')?.value)          || 0,
          saus:         parseFloat(document.getElementById('s_saus')?.value)          || 0,
          sayur:        parseFloat(document.getElementById('s_sayur')?.value)         || 0,
          gas:          parseFloat(document.getElementById('s_gas')?.value)           || 0,
          wrap:         parseFloat(document.getElementById('s_wrap')?.value)          || 0,
          box:          parseFloat(document.getElementById('s_box')?.value)           || 0,
          cup:          parseFloat(document.getElementById('s_cup')?.value)           || 0,
          sendok:       parseFloat(document.getElementById('s_sendok')?.value)        || 0,
          suhu_chiller: parseFloat(document.getElementById('s_suhu_chiller')?.value)  || null,
          suhu_freezer: parseFloat(document.getElementById('s_suhu_freezer')?.value)  || null,
          expired:      statusState['s_expired']  || null,
          overall:      statusState['s_overall']  || null,
          waste:        parseFloat(document.getElementById('s_waste')?.value)         || 0,
          notes:        document.getElementById('s_notes')?.value || null,
        },
        hr: {
          present: parseInt(document.getElementById('emp_present')?.value) || 0,
          absent:  document.getElementById('emp_absent')?.value  || null,
          issues:  document.getElementById('emp_issues')?.value  || null,
          action:  document.getElementById('emp_action')?.value  || null,
        },
      },
      employees: employees.map(e => ({
        name:      e.name,
        role:      e.role,
        eval_json: e.scores,
        notes:     e.note || null,
      })),
    };

    // 3.7: Simpan visit utama
    const res = await fetch('/api/spv-visits', {
      method:  'POST',
      headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': getCsrfToken() },
      body:    JSON.stringify(payload),
    });
    const rawText = await res.text();
    console.log('API response:', res.status, rawText);
    let data;
    try { data = JSON.parse(rawText); }
    catch (_) { showToast('[' + res.status + '] ' + rawText.substring(0, 120)); btn.disabled = false; btn.textContent = 'Simpan & Kirim Report'; return; }
    if (!res.ok) { showToast(data.message || 'Gagal menyimpan visit.'); btn.disabled = false; btn.textContent = 'Simpan & Kirim Report'; return; }

    savedVisitId = data.data.visit_id;

    // 3.8: Upload foto — kirim sebagai urlencoded form biasa (bukan multipart)
    const photosWithData = photos.filter(p => p.blob);
    let photoFailed = 0;
    for (let i = 0; i < photosWithData.length; i++) {
      btn.textContent = `Upload foto ${i+1}/${photosWithData.length}...`;
      const ph = photosWithData[i];
      try {
        // Konversi blob → base64 data URI
        const base64 = await new Promise((resolve, reject) => {
          const reader = new FileReader();
          reader.onload  = e => resolve(e.target.result);
          reader.onerror = () => reject(new Error('FileReader error'));
          reader.readAsDataURL(ph.blob);
        });
        // Kirim sebagai application/x-www-form-urlencoded
        const params = new URLSearchParams();
        params.append('imgdata', base64);
        params.append('label',   ph.label);
        params.append('tags',    JSON.stringify(ph.tags ?? []));

        const ctrl  = new AbortController();
        const timer = setTimeout(() => ctrl.abort(), 35000);
        const upRes = await fetch('/api/spv-attach?vid=' + savedVisitId, {
          method:  'POST',
          headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-CSRF-Token': getCsrfToken() },
          body:    params.toString(),
          signal:  ctrl.signal,
        });
        clearTimeout(timer);
        if (!upRes.ok) photoFailed++;
      } catch(photoErr) {
        photoFailed++;
      }
    }

    // Selesai
    buildSummary();
    goTab(TAB_COUNT - 1);
    btn.classList.add('done');
    btn.textContent = 'Report Tersimpan ✓';
    btn.disabled    = true;
    document.getElementById('backBtn').style.display = 'none';
    if (photoFailed > 0) {
      showToast(`Report tersimpan. ${photoFailed} foto gagal upload — coba ulang via menu edit.`);
    } else {
      showToast('Report berhasil disimpan.');
    }

  } catch(e) {
    const msg = e?.name === 'TypeError' ? 'Koneksi gagal. Periksa internet dan coba lagi.' : 'Gagal menyimpan. Coba lagi.';
    showToast(msg);
    btn.disabled    = false;
    btn.textContent = 'Simpan & Kirim Report';
  }
}

// ─── PDF export (print) ───────────────────────────────────────────────────
function exportPDF() {
  buildSummary();
  const outlet = document.getElementById('outlet_id').options[document.getElementById('outlet_id').selectedIndex]?.text || 'outlet';
  const date   = document.getElementById('visit_date')?.value || 'tanggal';
  document.title = 'SS_Report_SPV_' + outlet + '_' + date;
  window.print();
}

// ─── Logout ───────────────────────────────────────────────────────────────
async function doLogout() {
  await fetch('/api/auth/logout', { method: 'POST' });
  window.location.href = '/login';
}

function showToast(msg) {
  const t = document.getElementById('toast');
  t.textContent = msg;
  t.classList.add('show');
  setTimeout(() => t.classList.remove('show'), 2800);
}

function getCsrfToken() {
  return document.querySelector('meta[name="csrf-token"]')?.content ?? '';
}

init();
</script>

<script src="/assets/js/idle-logout.js"></script>
</body>
</html>
