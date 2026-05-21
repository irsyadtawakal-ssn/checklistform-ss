<?php
declare(strict_types=1);

require_once __DIR__ . '/../src/bootstrap.php';
require_once ROOT_PATH . '/src/helpers/auth.php';
require_once ROOT_PATH . '/src/helpers/db.php';
require_once ROOT_PATH . '/src/middleware/page.php';
require_once ROOT_PATH . '/src/helpers/csrf.php';

$user = pageRequireRole('admin');
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="csrf-token" content="<?= htmlspecialchars(csrfToken()) ?>">
<title>Admin Panel · SS Operations</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=Nunito:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<style>
:root {
  /* ── SS Brand Palette ── */
  --red:       #7A1200;
  --red-dark:  #200500;
  --red-mid:   #8B1500;
  --orange:    #E8924A;
  --orange-lt: #F4B07A;

  /* ── Surface & Text ── */
  --bg:      #F5F0EC;
  --surface: #FFFFFF;
  --text:    #200500;
  --muted:   #6B4535;
  --border:  rgba(122,18,0,.12);
  --border2: rgba(122,18,0,.20);

  /* ── Semantic colours ── */
  --ok:#16A34A; --ok-bg:#F0FDF4; --ok-bdr:#BBF7D0;
  --warn:#D97706; --warn-bg:#FFFBEB; --warn-bdr:#FDE68A;
  --danger:#DC2626; --danger-bg:#FEF2F2; --danger-bdr:#FECACA;
  --idle:#9E8B85; --idle-bg:#F5F0EC; --idle-bdr:#E8D5C8;

  /* ── Orange accent (replaces --saffron) ── */
  --saffron:  var(--orange);
  --saf-bg:   #FEF3E2;
  --saf-bdr:  rgba(232,146,74,.30);

  /* ── Ink aliases ── */
  --ink:  var(--text);
  --ink2: var(--muted);
  --ink3: #9E8B85;

  /* ── Shape & Shadow ── */
  --r:10px; --r-lg:14px;
  --shadow:0 1px 3px rgba(32,5,0,.08),0 1px 2px rgba(32,5,0,.05);
  --shadow-lg:0 4px 16px rgba(32,5,0,.14),0 2px 6px rgba(32,5,0,.07);
}
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
html{font-size:14px}
body{background:var(--bg);color:var(--text);font-family:'Nunito',sans-serif;min-height:100vh}

/* ── Header ── */
.app-header{position:sticky;top:0;z-index:200;background:var(--red-dark);height:60px;display:flex;align-items:center;padding:0 20px;gap:14px;box-shadow:0 2px 8px rgba(32,5,0,.40)}
.brand{display:flex;align-items:center;gap:10px;text-decoration:none;color:#fff}
.header-logo{width:36px;height:36px;object-fit:contain;border-radius:8px}
.brand-title{font-family:'Bebas Neue',sans-serif;font-size:20px;color:#fff;letter-spacing:.04em;line-height:1}
.brand-sub{font-size:10px;color:var(--orange-lt);letter-spacing:.04em;font-weight:600;text-transform:uppercase}
.h-spacer{flex:1}
.role-badge{font-size:10px;font-weight:700;padding:3px 10px;border-radius:99px;background:rgba(232,146,74,.20);color:var(--orange-lt);text-transform:uppercase;letter-spacing:.06em;border:1px solid rgba(232,146,74,.35)}
.btn-logout{display:flex;align-items:center;gap:5px;padding:6px 12px;border:1px solid rgba(255,255,255,.20);border-radius:7px;background:transparent;color:rgba(255,255,255,.80);font-size:12px;font-weight:600;cursor:pointer;font-family:'Nunito',sans-serif;transition:all .15s}
.btn-logout:hover{background:rgba(255,255,255,.12);color:#fff}

/* ── Tabs ── */
.tab-bar{background:var(--red);border-bottom:3px solid var(--red-dark);padding:0 20px;display:flex;gap:0;overflow-x:auto;-webkit-overflow-scrolling:touch}
.tab-bar::-webkit-scrollbar{height:0}
.tab-btn{padding:11px 18px;border:none;background:transparent;color:rgba(255,255,255,.60);font-family:'Nunito',sans-serif;font-size:13px;font-weight:700;cursor:pointer;border-bottom:3px solid transparent;margin-bottom:-3px;transition:all .15s;white-space:nowrap}
.tab-btn.active{color:#fff;border-bottom-color:var(--orange)}
.tab-btn:hover:not(.active){color:rgba(255,255,255,.85);background:rgba(255,255,255,.08)}

/* ── Layout ── */
.page{padding:28px 28px 60px;max-width:1200px}

/* ── Section header ── */
.sec-header{display:flex;align-items:center;gap:12px;margin-bottom:18px;flex-wrap:wrap}
.sec-title{font-family:'Bebas Neue',sans-serif;font-size:24px;color:var(--red);letter-spacing:.03em}
.sec-count{font-size:11px;font-weight:700;color:var(--muted);background:var(--bg);border:1px solid var(--border2);padding:2px 10px;border-radius:99px}
.btn-primary{display:inline-flex;align-items:center;gap:6px;padding:8px 16px;background:var(--red);color:#fff;border:none;border-radius:8px;font-family:'Nunito',sans-serif;font-size:13px;font-weight:700;cursor:pointer;transition:opacity .15s;margin-left:auto}
.btn-primary:hover{opacity:.85}

/* ── Table ── */
.tbl-wrap{background:var(--surface);border:1px solid var(--border);border-radius:var(--r-lg);overflow:hidden;box-shadow:var(--shadow)}
table{width:100%;border-collapse:collapse}
thead th{background:var(--bg);border-bottom:2px solid var(--border2);padding:10px 14px;text-align:left;font-size:11px;font-family:'Nunito',sans-serif;font-weight:800;color:var(--red);text-transform:uppercase;letter-spacing:.07em;white-space:nowrap}
tbody tr{transition:background .1s}
tbody tr:hover{background:rgba(232,146,74,.06)}
tbody td{padding:11px 14px;border-bottom:1px solid var(--border);font-size:13px;vertical-align:middle}
tbody tr:last-child td{border-bottom:none}
.badge{display:inline-block;padding:2px 8px;border-radius:99px;font-size:10px;font-weight:700;border:1px solid;font-family:'Nunito',sans-serif}
.badge-internal{background:var(--ok-bg);color:var(--ok);border-color:var(--ok-bdr)}
.badge-mitra{background:var(--saf-bg);color:#C47030;border-color:var(--saf-bdr)}
.badge-active{background:var(--ok-bg);color:var(--ok);border-color:var(--ok-bdr)}
.badge-inactive{background:var(--idle-bg);color:var(--idle);border-color:var(--idle-bdr)}
.badge-outlet{background:var(--saf-bg);color:#C47030;border-color:var(--saf-bdr)}
.badge-spv{background:var(--ok-bg);color:var(--ok);border-color:var(--ok-bdr)}
.badge-owner{background:#EEF2FF;color:#4F46E5;border-color:#C7D2FE}
.badge-admin{background:#FFF0EE;color:var(--red);border-color:rgba(122,18,0,.25)}
.tbl-actions{display:flex;gap:6px}
.btn-tbl{padding:4px 10px;border:1px solid var(--border2);border-radius:6px;background:transparent;font-size:11px;font-weight:700;font-family:'Nunito',sans-serif;cursor:pointer;color:var(--muted);transition:all .15s;white-space:nowrap}
.btn-tbl:hover{background:var(--bg);color:var(--text)}
.btn-tbl.danger:hover{background:var(--danger-bg);color:var(--danger);border-color:var(--danger-bdr)}
.mono{font-family:'Nunito',sans-serif;font-size:12px;font-weight:700;letter-spacing:.02em}
.text-muted{color:var(--ink3)}

/* ── Inline target edit ── */
.target-cell{display:flex;align-items:center;gap:6px}
.target-display{font-size:12px;font-weight:700;cursor:pointer;padding:2px 6px;border-radius:4px;transition:background .15s}
.target-display:hover{background:var(--bg)}
.target-input{width:100px;padding:3px 7px;border:1px solid var(--orange);border-radius:6px;font-size:12px;outline:none;background:var(--surface);font-family:'Nunito',sans-serif}
.target-save{padding:3px 8px;background:var(--red);color:#fff;border:none;border-radius:5px;font-size:11px;font-weight:700;cursor:pointer}
.target-cancel{padding:3px 6px;background:transparent;border:1px solid var(--border2);border-radius:5px;font-size:11px;cursor:pointer;color:var(--ink3)}

/* ── Audit log ── */
.audit-wrap{background:var(--surface);border:1px solid var(--border);border-radius:var(--r-lg);overflow:hidden;box-shadow:var(--shadow)}
.audit-toolbar{padding:12px 16px;border-bottom:1px solid var(--border);display:flex;align-items:center;gap:10px;flex-wrap:wrap}
.audit-filter{padding:6px 10px;border:1px solid var(--border2);border-radius:7px;background:var(--bg);font-family:'Nunito',sans-serif;font-size:12px;outline:none;color:var(--text)}
.audit-filter:focus{border-color:var(--orange)}
.audit-refresh{padding:6px 12px;border:1px solid var(--border2);border-radius:7px;background:transparent;font-family:'Nunito',sans-serif;font-size:12px;font-weight:600;cursor:pointer;color:var(--muted)}
.audit-refresh:hover{background:var(--bg)}
.audit-row{display:grid;grid-template-columns:1fr 1fr 1fr auto;gap:12px;padding:10px 16px;border-bottom:1px solid var(--border);align-items:start;font-size:12px}
.audit-row:last-child{border-bottom:none}
.audit-row:hover{background:rgba(232,146,74,.04)}
.audit-action{font-size:11px;font-weight:700;color:var(--text)}
.audit-by{color:var(--muted);font-weight:600}
.audit-target{color:var(--ink3);font-size:11px}
.audit-time{color:var(--ink3);font-size:10px;white-space:nowrap}
.audit-pagination{display:flex;align-items:center;gap:8px;padding:12px 16px;border-top:1px solid var(--border);font-size:12px;color:var(--ink3)}
.pagination-btn{padding:4px 10px;border:1px solid var(--border2);border-radius:6px;background:transparent;font-size:11px;font-weight:700;cursor:pointer;color:var(--muted)}
.pagination-btn:disabled{opacity:.4;cursor:default}

/* ── Modal ── */
.modal-overlay{position:fixed;inset:0;background:rgba(32,5,0,.55);z-index:500;display:none;align-items:center;justify-content:center;padding:20px}
.modal-overlay.open{display:flex}
.modal{background:var(--surface);border-radius:var(--r-lg);box-shadow:var(--shadow-lg);width:100%;max-width:500px;max-height:88vh;overflow-y:auto;animation:modal-in .18s ease}
@keyframes modal-in{from{opacity:0;transform:translateY(12px)}}
.modal-header{display:flex;align-items:flex-start;justify-content:space-between;padding:18px 20px 14px;border-bottom:3px solid var(--orange)}
.modal-title{font-family:'Bebas Neue',sans-serif;font-size:20px;color:var(--red);letter-spacing:.03em}
.modal-subtitle{font-size:11px;color:var(--ink3);margin-top:2px;font-weight:600}
.modal-close{width:28px;height:28px;border:1px solid var(--border2);border-radius:6px;background:var(--bg);color:var(--muted);cursor:pointer;font-size:14px;display:flex;align-items:center;justify-content:center;flex-shrink:0;margin-left:12px}
.modal-body{padding:20px}
.modal-footer{padding:14px 20px;border-top:1px solid var(--border);display:flex;gap:8px;justify-content:flex-end}

/* ── Form ── */
.form-group{margin-bottom:16px}
.form-label{display:block;font-size:12px;font-weight:700;color:var(--muted);margin-bottom:5px;text-transform:uppercase;letter-spacing:.04em}
.form-label .req{color:var(--danger);margin-left:2px}
.form-control{width:100%;padding:9px 11px;border:1px solid var(--border2);border-radius:8px;background:var(--bg);color:var(--text);font-family:'Nunito',sans-serif;font-size:13px;outline:none;transition:border-color .15s}
.form-control:focus{border-color:var(--orange);background:var(--surface)}
.form-row{display:grid;grid-template-columns:1fr 1fr;gap:12px}
.form-hint{font-size:11px;color:var(--ink3);margin-top:4px;font-weight:600}
.form-error{font-size:11px;color:var(--danger);margin-top:4px;display:none;font-weight:600}

/* ── Password reveal box ── */
.pw-box{background:var(--saf-bg);border:2px solid var(--orange);border-radius:10px;padding:16px;text-align:center;margin-top:8px}
.pw-box-title{font-size:11px;font-weight:700;color:var(--muted);margin-bottom:8px;text-transform:uppercase;letter-spacing:.04em}
.pw-value{font-size:22px;font-weight:800;color:var(--red);letter-spacing:.08em;margin-bottom:10px;font-family:'Nunito',sans-serif}
.btn-copy{padding:6px 14px;background:var(--red);color:#fff;border:none;border-radius:7px;font-family:'Nunito',sans-serif;font-size:12px;font-weight:700;cursor:pointer}

/* ── Buttons ── */
.btn-ghost{padding:8px 14px;background:transparent;color:var(--muted);border:1px solid var(--border2);border-radius:8px;font-family:'Nunito',sans-serif;font-size:13px;font-weight:700;cursor:pointer;transition:all .15s}
.btn-ghost:hover{background:var(--bg)}
.btn-submit{padding:9px 20px;background:var(--red);color:#fff;border:none;border-radius:8px;font-family:'Bebas Neue',sans-serif;font-size:16px;letter-spacing:.05em;cursor:pointer;transition:opacity .15s}
.btn-submit:hover{opacity:.85}
.btn-submit:disabled{opacity:.5;cursor:default}
.btn-danger{padding:8px 14px;background:var(--danger);color:#fff;border:none;border-radius:8px;font-family:'Nunito',sans-serif;font-size:13px;font-weight:700;cursor:pointer;transition:opacity .15s}
.btn-danger:hover{opacity:.87}

/* ── Toast ── */
.toast-wrap{position:fixed;bottom:24px;left:50%;transform:translateX(-50%);z-index:9000;display:flex;flex-direction:column;gap:8px;pointer-events:none}
.toast{background:var(--red-dark);color:#fff;padding:10px 18px;border-radius:10px;font-size:13px;font-weight:700;box-shadow:var(--shadow-lg);animation:toast-in .2s ease;max-width:380px;text-align:center}
.toast.success{background:var(--ok)}
.toast.error{background:var(--danger)}
@keyframes toast-in{from{opacity:0;transform:translateY(10px)}}

/* ── Responsive ── */
@media(max-width:640px){
  .page{padding:16px}
  .form-row{grid-template-columns:1fr}
  .audit-row{grid-template-columns:1fr 1fr;gap:6px}
}
</style>
</head>
<body>

<header class="app-header">
  <a class="brand" href="/admin">
    <img class="header-logo" src="/assets/logo.png" alt="SS">
    <div>
      <div class="brand-title">SS Operations</div>
      <div class="brand-sub">Admin Panel</div>
    </div>
  </a>
  <div class="h-spacer"></div>
  <span class="role-badge">admin</span>
  <button class="btn-logout" onclick="doLogout()">Keluar</button>
</header>

<nav class="tab-bar">
  <button class="tab-btn active" onclick="switchTab('outlets')" id="tab-outlets">Outlet</button>
  <button class="tab-btn" onclick="switchTab('users')" id="tab-users">Pengguna</button>
  <button class="tab-btn" onclick="switchTab('audit')" id="tab-audit">Audit Log</button>
  <button class="tab-btn" onclick="switchTab('errorlog')" id="tab-errorlog">Error Log</button>
  <button class="tab-btn" onclick="switchTab('checklist')" id="tab-checklist">Master Checklist</button>
  <button class="tab-btn" onclick="switchTab('spvareas')" id="tab-spvareas">Area SPV</button>
</nav>

<!-- ══════════════════════════════════════════════════════ TAB: OUTLETS ══ -->
<div class="page" id="panel-outlets">
  <div class="sec-header">
    <span class="sec-title">Daftar Outlet</span>
    <span class="sec-count" id="outlets-count">—</span>
    <button class="btn-primary" onclick="openOutletModal()">+ Tambah Outlet</button>
  </div>
  <div class="tbl-wrap">
    <table>
      <thead><tr>
        <th>Kode</th><th>Nama</th><th>Tipe</th>
        <th>Target Harian</th><th>Status</th><th>Aksi</th>
      </tr></thead>
      <tbody id="outlets-tbody"><tr><td colspan="6" style="text-align:center;color:var(--ink3);padding:32px">Memuat…</td></tr></tbody>
    </table>
  </div>
</div>

<!-- ══════════════════════════════════════════════════════ TAB: USERS ══ -->
<div class="page" id="panel-users" style="display:none">
  <div class="sec-header">
    <span class="sec-title">Daftar Pengguna</span>
    <span class="sec-count" id="users-count">—</span>
    <div style="display:flex;gap:8px">
      <button class="btn-primary" style="background:var(--ok);border-color:var(--ok)" onclick="resetAllPasswords()">🔑 Reset Semua Password</button>
      <button class="btn-primary" onclick="openUserModal()">+ Tambah Pengguna</button>
    </div>
  </div>
  <div class="tbl-wrap">
    <table>
      <thead><tr>
        <th>Username</th><th>Nama Lengkap</th><th>Role</th>
        <th>Outlet</th><th>Terakhir Login</th><th>Status</th><th>Aksi</th>
      </tr></thead>
      <tbody id="users-tbody"><tr><td colspan="7" style="text-align:center;color:var(--ink3);padding:32px">Memuat…</td></tr></tbody>
    </table>
  </div>
</div>

<!-- ══════════════════════════════════════════════════════ TAB: AUDIT ══ -->
<div class="page" id="panel-audit" style="display:none">
  <div class="sec-header">
    <span class="sec-title">Audit Log</span>
    <span class="sec-count" id="audit-count">—</span>
  </div>
  <div class="audit-wrap">
    <div class="audit-toolbar">
      <input type="text" class="audit-filter" id="audit-search" placeholder="Filter aksi (mis. unlock, login)…" oninput="debounceAudit()">
      <button class="audit-refresh" onclick="loadAudit()">↺ Refresh</button>
      <span style="font-size:11px;color:var(--ink3);margin-left:auto" id="audit-ts">—</span>
    </div>
    <div id="audit-rows"><div style="text-align:center;padding:32px;color:var(--ink3)">Memuat…</div></div>
    <div class="audit-pagination">
      <button class="pagination-btn" id="audit-prev" onclick="auditPage(-1)" disabled>← Sebelumnya</button>
      <span id="audit-page-info">—</span>
      <button class="pagination-btn" id="audit-next" onclick="auditPage(1)">Berikutnya →</button>
    </div>
  </div>
</div>

<!-- ══════════════════════════════════════════════════════ TAB: ERROR LOG ══ -->
<div class="page" id="panel-errorlog" style="display:none">
  <div class="sec-header">
    <span class="sec-title">Error Log Aplikasi</span>
    <span class="sec-count" id="errlog-size">—</span>
    <button class="btn-primary" style="background:var(--ink2)" onclick="loadErrorLog()">↺ Refresh</button>
  </div>
  <div style="margin-bottom:12px;display:flex;align-items:center;gap:10px">
    <select class="filter-control" id="errlog-lines" style="width:auto;padding:6px 10px;border:1px solid var(--border2);border-radius:7px;background:var(--bg);font-family:inherit;font-size:12px">
      <option value="50">50 baris terakhir</option>
      <option value="100" selected>100 baris terakhir</option>
      <option value="250">250 baris terakhir</option>
      <option value="500">500 baris terakhir</option>
    </select>
    <span style="font-size:11px;color:var(--ink3)" id="errlog-ts">—</span>
  </div>
  <div class="tbl-wrap" style="font-family:'Nunito',sans-serif;font-size:11px;font-weight:600;line-height:1.6;max-height:600px;overflow-y:auto">
    <div id="errlog-rows" style="padding:16px">
      <span style="color:var(--ink3)">Memuat…</span>
    </div>
  </div>
</div>

<!-- ════════════════════════════════════════════ TAB: CHECKLIST MASTER ══ -->
<div class="page" id="panel-checklist" style="display:none">
  <div class="sec-header">
    <span class="sec-title">Master Item Checklist</span>
    <span class="sec-count" id="cl-count">—</span>
  </div>
  <div style="display:flex;gap:8px;margin-bottom:16px;flex-wrap:wrap">
    <select class="filter-control" id="cl-shift" style="width:auto;padding:7px 10px;border:1px solid var(--border2);border-radius:7px;background:var(--bg);font-family:inherit;font-size:13px" onchange="renderMasterChecklist()">
      <option value="open">Open (Pagi)</option>
      <option value="ops">Operasional</option>
      <option value="close">Close (Malam)</option>
    </select>
    <button class="btn-primary" onclick="openAddItemModal()">+ Tambah Item</button>
  </div>
  <div id="cl-sections"></div>
</div>

<!-- ════════════════════════════════════════════════ TAB: AREA SPV ══ -->
<div class="page" id="panel-spvareas" style="display:none">
  <div class="sec-header">
    <span class="sec-title">Area Assignment SPV</span>
    <span class="sec-count" id="spv-area-count">—</span>
  </div>
  <p style="font-size:12px;color:var(--ink3);margin-bottom:16px">
    SPV tanpa assignment = melihat semua outlet (global). Assign outlet spesifik agar SPV hanya melihat area-nya.
  </p>
  <div id="spv-area-list"></div>
</div>

<!-- ══════════════════════════════════════════════════════ MODALS ══ -->

<!-- Outlet Modal -->
<div class="modal-overlay" id="outlet-modal" onclick="closeModal('outlet-modal',event)">
  <div class="modal">
    <div class="modal-header">
      <div>
        <div class="modal-title" id="outlet-modal-title">Tambah Outlet</div>
        <div class="modal-subtitle">Outlet akan langsung aktif setelah disimpan</div>
      </div>
      <button class="modal-close" onclick="closeModal('outlet-modal')">✕</button>
    </div>
    <div class="modal-body">
      <input type="hidden" id="outlet-id">
      <div class="form-row">
        <div class="form-group">
          <label class="form-label">Kode Outlet<span class="req">*</span></label>
          <input type="text" class="form-control" id="outlet-code" placeholder="mis. SS-JKT-01" maxlength="20">
          <div class="form-hint">Huruf, angka, tanda hubung. Tidak bisa diubah setelah dibuat.</div>
        </div>
        <div class="form-group">
          <label class="form-label">Tipe<span class="req">*</span></label>
          <select class="form-control" id="outlet-type">
            <option value="internal">Internal</option>
            <option value="mitra">Mitra</option>
          </select>
        </div>
      </div>
      <div class="form-group">
        <label class="form-label">Nama Outlet<span class="req">*</span></label>
        <input type="text" class="form-control" id="outlet-name" placeholder="mis. SS Empang" maxlength="100">
      </div>
      <div class="form-group">
        <label class="form-label">Alamat</label>
        <input type="text" class="form-control" id="outlet-address" placeholder="Alamat lengkap (opsional)" maxlength="255">
      </div>
      <div class="form-group">
        <label class="form-label">Target Penjualan Harian (Rp)</label>
        <input type="number" class="form-control" id="outlet-target" placeholder="0 = tidak ada target" min="0" step="10000">
        <div class="form-hint">Digunakan untuk kalkulasi pencapaian di laporan SPV</div>
      </div>
    </div>
    <div class="modal-footer">
      <button class="btn-ghost" onclick="closeModal('outlet-modal')">Batal</button>
      <button class="btn-submit" id="outlet-save-btn" onclick="saveOutlet()">Simpan</button>
    </div>
  </div>
</div>

<!-- User Modal -->
<div class="modal-overlay" id="user-modal" onclick="closeModal('user-modal',event)">
  <div class="modal">
    <div class="modal-header">
      <div>
        <div class="modal-title" id="user-modal-title">Tambah Pengguna</div>
        <div class="modal-subtitle" id="user-modal-sub">Password akan di-generate otomatis</div>
      </div>
      <button class="modal-close" onclick="closeModal('user-modal')">✕</button>
    </div>
    <div class="modal-body">
      <input type="hidden" id="user-id">
      <div id="pw-reveal" style="display:none"></div>
      <div id="user-form-fields">
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Username<span class="req">*</span></label>
            <input type="text" class="form-control" id="user-username" placeholder="mis. ss.empang" maxlength="30">
            <div class="form-hint">Huruf kecil, angka, titik, underscore (3-30 karakter)</div>
          </div>
          <div class="form-group">
            <label class="form-label">Role<span class="req">*</span></label>
            <select class="form-control" id="user-role" onchange="onRoleChange()">
              <option value="outlet">Outlet (PIC)</option>
              <option value="spv">Supervisor</option>
              <option value="owner">Owner</option>
              <option value="admin">Admin</option>
            </select>
          </div>
        </div>
        <div class="form-group">
          <label class="form-label">Nama Lengkap<span class="req">*</span></label>
          <input type="text" class="form-control" id="user-fullname" placeholder="Nama lengkap pengguna" maxlength="100">
        </div>
        <div class="form-group" id="user-outlet-group">
          <label class="form-label">Outlet<span class="req">*</span></label>
          <select class="form-control" id="user-outlet">
            <option value="">— Pilih outlet —</option>
          </select>
        </div>
        <div class="form-group" id="user-active-group" style="display:none">
          <label class="form-label">Status</label>
          <select class="form-control" id="user-active">
            <option value="1">Aktif</option>
            <option value="0">Nonaktif</option>
          </select>
        </div>
      </div>
    </div>
    <div class="modal-footer" id="user-modal-footer">
      <button class="btn-ghost" onclick="closeModal('user-modal')">Batal</button>
      <button class="btn-submit" id="user-save-btn" onclick="saveUser()">Simpan</button>
    </div>
  </div>
</div>

<!-- Modal Reset All Passwords -->
<div class="modal-overlay" id="resetall-modal" onclick="closeModal('resetall-modal',event)">
  <div class="modal-box" style="max-width:700px;width:95vw" onclick="e=>e.stopPropagation()">
    <div class="modal-header">
      <div>
        <div class="modal-title">Reset Semua Password</div>
        <div class="modal-subtitle" id="resetall-sub">Pilih role yang akan direset</div>
      </div>
      <button class="modal-close" onclick="closeModal('resetall-modal')">✕</button>
    </div>
    <div class="modal-body" id="resetall-body">
      <div style="margin-bottom:16px">
        <label style="font-size:12px;color:var(--ink3);display:block;margin-bottom:6px">Reset password untuk role:</label>
        <div style="display:flex;gap:8px;flex-wrap:wrap">
          <button class="btn-tbl" id="role-btn-outlet" onclick="selectResetRole('outlet')" style="background:var(--ok-bg);border-color:var(--ok-bdr);color:var(--ok)">Outlet (19 akun)</button>
          <button class="btn-tbl" id="role-btn-spv" onclick="selectResetRole('spv')">SPV</button>
          <button class="btn-tbl" id="role-btn-owner" onclick="selectResetRole('owner')">Owner</button>
          <button class="btn-tbl" id="role-btn-all" onclick="selectResetRole('all')">Semua (kecuali Admin)</button>
        </div>
      </div>
      <div id="resetall-result" style="display:none">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:10px">
          <span style="font-size:13px;font-weight:600;color:var(--ok)" id="resetall-count-label"></span>
          <button class="btn-primary" onclick="printPasswords()" style="font-size:12px;padding:6px 14px">🖨️ Cetak / Export PDF</button>
        </div>
        <div style="max-height:400px;overflow-y:auto;border:1px solid var(--border);border-radius:var(--r)">
          <table style="width:100%;border-collapse:collapse;font-size:12px" id="resetall-table">
            <thead>
              <tr style="background:var(--bg);position:sticky;top:0">
                <th style="padding:8px 12px;text-align:left;font-size:11px;color:var(--ink3);font-weight:500;border-bottom:1px solid var(--border)">Username</th>
                <th style="padding:8px 12px;text-align:left;font-size:11px;color:var(--ink3);font-weight:500;border-bottom:1px solid var(--border)">Nama</th>
                <th style="padding:8px 12px;text-align:left;font-size:11px;color:var(--ink3);font-weight:500;border-bottom:1px solid var(--border)">Outlet</th>
                <th style="padding:8px 12px;text-align:left;font-size:11px;color:var(--ink3);font-weight:500;border-bottom:1px solid var(--border)">Password Baru</th>
              </tr>
            </thead>
            <tbody id="resetall-tbody"></tbody>
          </table>
        </div>
        <p style="font-size:11px;color:var(--danger);margin-top:10px">⚠️ Catat atau cetak password ini sekarang — tidak bisa dilihat lagi setelah modal ditutup.</p>
      </div>
    </div>
    <div class="modal-footer" id="resetall-footer">
      <button class="btn-ghost" onclick="closeModal('resetall-modal')">Batal</button>
      <button class="btn-submit" id="resetall-btn" onclick="doResetAll()" style="background:var(--ok)">Reset Sekarang</button>
    </div>
  </div>
</div>

<!-- Print area untuk PDF password -->
<div id="print-area" style="display:none">
  <style>
    @media print {
      body > *:not(#print-area) { display: none !important; }
      #print-area { display: block !important; font-family: monospace; padding: 20px; }
      #print-area h2 { font-size: 16px; margin-bottom: 4px; }
      #print-area p  { font-size: 11px; color: #666; margin-bottom: 16px; }
      #print-area table { width: 100%; border-collapse: collapse; font-size: 12px; }
      #print-area th { background: #f5f5f5; padding: 6px 10px; text-align: left; border: 1px solid #ddd; }
      #print-area td { padding: 6px 10px; border: 1px solid #ddd; }
      #print-area .pw { font-family: monospace; font-weight: bold; font-size: 13px; }
    }
  </style>
  <h2>SS Operations — Daftar Password Akun</h2>
  <p id="print-date"></p>
  <table>
    <thead><tr><th>#</th><th>Username</th><th>Nama</th><th>Outlet</th><th>Password Baru</th></tr></thead>
    <tbody id="print-tbody"></tbody>
  </table>
  <p style="margin-top:16px;font-size:11px">⚠️ Dokumen ini bersifat rahasia. Bagikan hanya kepada PIC masing-masing outlet. Minta segera ganti password setelah login pertama.</p>
</div>

<!-- Toast -->
<div class="toast-wrap" id="toast-wrap"></div>

<script>
// ── State ──────────────────────────────────────────────────────────────────
let outlets = [];  // cache
let users   = [];  // cache
let auditOffset = 0;
const AUDIT_LIMIT = 50;
let auditDebounce = null;

// ── Tab switching ──────────────────────────────────────────────────────────
let masterData = null; // checklist JSON cache
let spvAreas   = [];

function switchTab(tab) {
  ['outlets','users','audit','errorlog','checklist','spvareas'].forEach(t => {
    document.getElementById('panel-' + t).style.display = t === tab ? '' : 'none';
    document.getElementById('tab-' + t).classList.toggle('active', t === tab);
  });
  if (tab === 'outlets'   && outlets.length === 0) loadOutlets();
  if (tab === 'users'     && users.length === 0)   loadUsers();
  if (tab === 'audit')     loadAudit();
  if (tab === 'errorlog')  loadErrorLog();
  if (tab === 'checklist') loadMasterChecklist();
  if (tab === 'spvareas')  loadSpvAreas();
}

// ─────────────────────────────────────────────────────────────── OUTLETS ──
async function loadOutlets() {
  try {
    const res = await fetch('/api/admin/outlets');
    const json = await res.json();
    if (!json.ok) throw new Error();
    outlets = json.data;
    renderOutlets();
  } catch { showToast('Gagal memuat outlet', 'error'); }
}

function renderOutlets() {
  document.getElementById('outlets-count').textContent = outlets.length + ' outlet';
  const tbody = document.getElementById('outlets-tbody');
  if (!outlets.length) {
    tbody.innerHTML = '<tr><td colspan="6" style="text-align:center;color:var(--ink3);padding:32px">Belum ada outlet</td></tr>';
    return;
  }
  tbody.innerHTML = outlets.map(o => `
    <tr>
      <td><span class="mono">${esc(o.code)}</span></td>
      <td><strong>${esc(o.name)}</strong></td>
      <td><span class="badge badge-${o.type}">${o.type}</span></td>
      <td>
        <div class="target-cell" id="target-cell-${o.id}">
          <span class="target-display" title="Klik untuk edit" onclick="startTargetEdit(${o.id}, ${o.daily_sales_target})">
            ${o.daily_sales_target > 0 ? 'Rp ' + fmtRp(o.daily_sales_target) : '<span class="text-muted">—</span>'}
          </span>
        </div>
      </td>
      <td><span class="badge ${o.active ? 'badge-active' : 'badge-inactive'}">${o.active ? 'Aktif' : 'Nonaktif'}</span></td>
      <td>
        <div class="tbl-actions">
          <button class="btn-tbl" onclick="openOutletModal(${o.id})">Edit</button>
          <button class="btn-tbl danger" onclick="toggleOutletActive(${o.id}, ${o.active})">${o.active ? 'Nonaktifkan' : 'Aktifkan'}</button>
        </div>
      </td>
    </tr>`).join('');
}

function startTargetEdit(id, current) {
  const cell = document.getElementById('target-cell-' + id);
  cell.innerHTML = `
    <input type="number" class="target-input" id="te-${id}" value="${current}" min="0" step="10000">
    <button class="target-save" onclick="saveTarget(${id})">✓</button>
    <button class="target-cancel" onclick="renderOutlets()">✕</button>`;
  document.getElementById('te-' + id).focus();
}

async function saveTarget(id) {
  const val = parseInt(document.getElementById('te-' + id).value) || 0;
  try {
    const res = await fetch('/api/admin/outlets?id=' + id, {
      method: 'PUT', headers: {'Content-Type':'application/json'},
      body: JSON.stringify({ daily_sales_target: val }),
    });
    const json = await res.json();
    if (!json.ok) throw new Error(json.message || 'Gagal');
    const idx = outlets.findIndex(o => o.id === id);
    if (idx >= 0) outlets[idx] = json.data;
    renderOutlets();
    showToast('Target penjualan diperbarui', 'success');
  } catch (e) { showToast(e.message || 'Gagal simpan target', 'error'); }
}

async function toggleOutletActive(id, current) {
  const label = current ? 'nonaktifkan' : 'aktifkan';
  if (!confirm(`${label.charAt(0).toUpperCase() + label.slice(1)} outlet ini?`)) return;
  try {
    const res = await fetch('/api/admin/outlets?id=' + id, {
      method: 'PUT', headers: {'Content-Type':'application/json'},
      body: JSON.stringify({ active: !current }),
    });
    const json = await res.json();
    if (!json.ok) throw new Error(json.message || 'Gagal');
    const idx = outlets.findIndex(o => o.id === id);
    if (idx >= 0) outlets[idx] = json.data;
    renderOutlets();
    showToast('Status outlet diperbarui', 'success');
  } catch (e) { showToast(e.message || 'Gagal update outlet', 'error'); }
}

function openOutletModal(id = null) {
  const editing = id !== null;
  const o = editing ? outlets.find(x => x.id === id) : null;
  document.getElementById('outlet-modal-title').textContent = editing ? 'Edit Outlet' : 'Tambah Outlet';
  document.getElementById('outlet-id').value      = id ?? '';
  document.getElementById('outlet-code').value    = o?.code    ?? '';
  document.getElementById('outlet-code').disabled = editing;
  document.getElementById('outlet-name').value    = o?.name    ?? '';
  document.getElementById('outlet-type').value    = o?.type    ?? 'internal';
  document.getElementById('outlet-address').value = o?.address ?? '';
  document.getElementById('outlet-target').value  = o?.daily_sales_target ?? 0;
  document.getElementById('outlet-save-btn').textContent = editing ? 'Simpan Perubahan' : 'Simpan';
  document.getElementById('outlet-modal').classList.add('open');
}

async function saveOutlet() {
  const id      = document.getElementById('outlet-id').value;
  const editing = !!id;
  const btn     = document.getElementById('outlet-save-btn');
  btn.disabled  = true; btn.textContent = 'Menyimpan…';

  const payload = {
    code:   document.getElementById('outlet-code').value.trim(),
    name:   document.getElementById('outlet-name').value.trim(),
    type:   document.getElementById('outlet-type').value,
    address: document.getElementById('outlet-address').value.trim(),
    daily_sales_target: parseInt(document.getElementById('outlet-target').value) || 0,
  };

  try {
    const url = editing ? '/api/admin/outlets?id=' + id : '/api/admin/outlets';
    const res = await fetch(url, {
      method: editing ? 'PUT' : 'POST',
      headers: {'Content-Type':'application/json','X-CSRF-Token':getCsrfToken()},
      body: JSON.stringify(payload),
    });
    const json = await res.json();
    if (!json.ok) throw new Error(json.message || 'Gagal');

    if (editing) {
      const idx = outlets.findIndex(o => o.id === parseInt(id));
      if (idx >= 0) outlets[idx] = json.data;
    } else {
      outlets.push(json.data);
      outlets.sort((a,b) => a.code.localeCompare(b.code));
    }
    renderOutlets();
    closeModal('outlet-modal');
    showToast(editing ? 'Outlet diperbarui' : 'Outlet berhasil ditambahkan', 'success');
  } catch (e) {
    showToast(e.message || 'Gagal menyimpan outlet', 'error');
  } finally {
    btn.disabled = false; btn.textContent = editing ? 'Simpan Perubahan' : 'Simpan';
  }
}

// ──────────────────────────────────────────────────────────────── USERS ──
async function loadUsers() {
  try {
    const res = await fetch('/api/admin/users');
    const json = await res.json();
    if (!json.ok) throw new Error();
    users = json.data;
    renderUsers();
  } catch { showToast('Gagal memuat pengguna', 'error'); }
}

function renderUsers() {
  document.getElementById('users-count').textContent = users.length + ' pengguna';
  const tbody = document.getElementById('users-tbody');
  if (!users.length) {
    tbody.innerHTML = '<tr><td colspan="7" style="text-align:center;color:var(--ink3);padding:32px">Belum ada pengguna</td></tr>';
    return;
  }
  tbody.innerHTML = users.map(u => `
    <tr>
      <td><span class="mono">${esc(u.username)}</span></td>
      <td>${esc(u.full_name)}</td>
      <td><span class="badge badge-${u.role}">${u.role}</span></td>
      <td>${u.outlet_code ? `<span class="mono" style="font-size:11px">${esc(u.outlet_code)}</span>` : '<span class="text-muted">—</span>'}</td>
      <td class="text-muted" style="font-size:11px">${u.last_login_at ? fmtDateTime(u.last_login_at) : '—'}</td>
      <td><span class="badge ${u.active ? 'badge-active' : 'badge-inactive'}">${u.active ? 'Aktif' : 'Nonaktif'}</span></td>
      <td>
        <div class="tbl-actions">
          <button class="btn-tbl" onclick="openUserModal(${u.id})">Edit</button>
          <button class="btn-tbl" onclick="resetPassword(${u.id}, '${esc(u.username)}')">Reset PW</button>
          ${u.active
            ? `<button class="btn-tbl danger" onclick="toggleUserActive(${u.id}, true)">Nonaktifkan</button>`
            : `<button class="btn-tbl" onclick="toggleUserActive(${u.id}, false)">Aktifkan</button>`}
        </div>
      </td>
    </tr>`).join('');
}

function onRoleChange() {
  const role = document.getElementById('user-role').value;
  document.getElementById('user-outlet-group').style.display = role === 'outlet' ? '' : 'none';
}

function openUserModal(id = null) {
  const editing = id !== null;
  const u = editing ? users.find(x => x.id === id) : null;

  document.getElementById('user-modal-title').textContent = editing ? 'Edit Pengguna' : 'Tambah Pengguna';
  document.getElementById('user-modal-sub').textContent   = editing ? 'Ubah nama atau status pengguna' : 'Password acak akan di-generate otomatis';
  document.getElementById('user-id').value       = id ?? '';
  document.getElementById('user-username').value = u?.username ?? '';
  document.getElementById('user-username').disabled = editing;
  document.getElementById('user-role').value     = u?.role ?? 'outlet';
  document.getElementById('user-role').disabled  = editing;
  document.getElementById('user-fullname').value = u?.full_name ?? '';
  document.getElementById('user-active-group').style.display = editing ? '' : 'none';
  document.getElementById('user-active').value   = u?.active ? '1' : '0';

  // Populate outlet dropdown dari cache
  const sel = document.getElementById('user-outlet');
  sel.innerHTML = '<option value="">— Pilih outlet —</option>' +
    outlets.filter(o => o.active).map(o =>
      `<option value="${o.id}" ${u?.outlet_id === o.id ? 'selected' : ''}>${esc(o.code)} — ${esc(o.name)}</option>`
    ).join('');
  if (u?.outlet_id) sel.value = u.outlet_id;

  onRoleChange();
  document.getElementById('pw-reveal').style.display = 'none';
  document.getElementById('user-form-fields').style.display = '';
  document.getElementById('user-save-btn').textContent = editing ? 'Simpan Perubahan' : 'Buat Pengguna';
  document.getElementById('user-modal-footer').innerHTML = `
    <button class="btn-ghost" onclick="closeModal('user-modal')">Batal</button>
    <button class="btn-submit" id="user-save-btn" onclick="saveUser()">
      ${editing ? 'Simpan Perubahan' : 'Buat Pengguna'}
    </button>`;
  document.getElementById('user-modal').classList.add('open');
}

async function saveUser() {
  const id      = document.getElementById('user-id').value;
  const editing = !!id;
  const btn     = document.getElementById('user-save-btn');
  if (!btn) return;
  btn.disabled = true; btn.textContent = 'Menyimpan…';

  const payload = editing ? {
    full_name: document.getElementById('user-fullname').value.trim(),
    active:    document.getElementById('user-active').value === '1',
    outlet_id: document.getElementById('user-outlet').value || null,
  } : {
    username:  document.getElementById('user-username').value.trim(),
    full_name: document.getElementById('user-fullname').value.trim(),
    role:      document.getElementById('user-role').value,
    outlet_id: document.getElementById('user-outlet').value || null,
  };

  try {
    const url = editing ? '/api/admin/users?id=' + id : '/api/admin/users';
    const res = await fetch(url, {
      method: editing ? 'PUT' : 'POST',
      headers: {'Content-Type':'application/json','X-CSRF-Token':getCsrfToken()},
      body: JSON.stringify(payload),
    });
    const json = await res.json();
    if (!json.ok) throw new Error(json.message || 'Gagal');

    if (editing) {
      const idx = users.findIndex(u => u.id === parseInt(id));
      if (idx >= 0) users[idx] = json.data;
      renderUsers();
      closeModal('user-modal');
      showToast('Pengguna diperbarui', 'success');
    } else {
      // Show generated password
      users.push(json.data);
      users.sort((a,b) => a.username.localeCompare(b.username));
      renderUsers();
      document.getElementById('user-form-fields').style.display = 'none';
      document.getElementById('pw-reveal').style.display = '';
      document.getElementById('pw-reveal').innerHTML = `
        <div style="margin-bottom:12px;font-size:13px;color:var(--ink2)">
          Pengguna <strong>${esc(json.data.username)}</strong> berhasil dibuat. Catat password berikut — hanya ditampilkan sekali:
        </div>
        <div class="pw-box">
          <div class="pw-box-title">Password Sementara</div>
          <div class="pw-value" id="pw-display">${esc(json.data.plain_password)}</div>
          <button class="btn-copy" onclick="copyPw('${esc(json.data.plain_password)}')">Salin Password</button>
        </div>`;
      document.getElementById('user-modal-footer').innerHTML =
        '<button class="btn-submit" onclick="closeModal(\'user-modal\')">Selesai</button>';
    }
  } catch (e) {
    showToast(e.message || 'Gagal menyimpan pengguna', 'error');
    btn.disabled = false; btn.textContent = editing ? 'Simpan Perubahan' : 'Buat Pengguna';
  }
}

async function resetPassword(id, username) {
  if (!confirm(`Reset password untuk ${username}? Password baru akan di-generate dan ditampilkan sekali.`)) return;
  try {
    const res = await fetch('/api/admin/users?id=' + id, {
      method: 'PUT', headers: {'Content-Type':'application/json'},
      body: JSON.stringify({ action: 'reset_password' }),
    });
    const json = await res.json();
    if (!json.ok) throw new Error(json.message || 'Gagal');

    // Open user modal in password-reveal mode
    document.getElementById('user-id').value = id;
    document.getElementById('user-form-fields').style.display = 'none';
    document.getElementById('pw-reveal').style.display = '';
    document.getElementById('pw-reveal').innerHTML = `
      <div style="margin-bottom:12px;font-size:13px;color:var(--ink2)">
        Password untuk <strong>${esc(username)}</strong> berhasil direset. Catat password berikut:
      </div>
      <div class="pw-box">
        <div class="pw-box-title">Password Baru</div>
        <div class="pw-value">${esc(json.data.plain_password)}</div>
        <button class="btn-copy" onclick="copyPw('${esc(json.data.plain_password)}')">Salin Password</button>
      </div>`;
    document.getElementById('user-modal-title').textContent = 'Reset Password';
    document.getElementById('user-modal-sub').textContent   = 'Catat password ini sebelum menutup modal';
    document.getElementById('user-modal-footer').innerHTML  =
      '<button class="btn-submit" onclick="closeModal(\'user-modal\')">Selesai</button>';
    document.getElementById('user-modal').classList.add('open');
  } catch (e) { showToast(e.message || 'Gagal reset password', 'error'); }
}

async function toggleUserActive(id, current) {
  const label = current ? 'nonaktifkan' : 'aktifkan';
  if (!confirm(`${label.charAt(0).toUpperCase() + label.slice(1)} pengguna ini?`)) return;
  try {
    const res = await fetch('/api/admin/users?id=' + id, {
      method: 'PUT', headers: {'Content-Type':'application/json'},
      body: JSON.stringify({ active: !current }),
    });
    const json = await res.json();
    if (!json.ok) throw new Error(json.message || 'Gagal');
    const idx = users.findIndex(u => u.id === id);
    if (idx >= 0) users[idx] = json.data;
    renderUsers();
    showToast('Status pengguna diperbarui', 'success');
  } catch (e) { showToast(e.message || 'Gagal update pengguna', 'error'); }
}

function copyPw(pw) {
  navigator.clipboard.writeText(pw).then(() => showToast('Password disalin!', 'success'));
}

// ──────────────────────────────────────────────────────────────── AUDIT ──
async function loadAudit() {
  const q = document.getElementById('audit-search')?.value.trim() || '';
  const url = `/api/admin/audit-log?limit=${AUDIT_LIMIT}&offset=${auditOffset}` + (q ? '&action=' + encodeURIComponent(q) : '');
  try {
    const res = await fetch(url);
    const json = await res.json();
    if (!json.ok) throw new Error();
    const { logs, total } = json.data;

    document.getElementById('audit-count').textContent = total + ' log';
    document.getElementById('audit-ts').textContent = 'Diperbarui ' + new Date().toLocaleTimeString('id-ID');

    const wrap = document.getElementById('audit-rows');
    if (!logs.length) {
      wrap.innerHTML = '<div style="text-align:center;padding:32px;color:var(--ink3)">Tidak ada log</div>';
    } else {
      wrap.innerHTML = logs.map(l => `
        <div class="audit-row">
          <div>
            <div class="audit-action">${esc(l.action)}</div>
            <div class="audit-by">${l.full_name ? esc(l.full_name) : '<span class="text-muted">System</span>'}</div>
          </div>
          <div>
            <div class="audit-target">${l.target_type ? esc(l.target_type) + ' #' + l.target_id : '—'}</div>
            ${l.payload ? `<div class="audit-target" style="margin-top:2px">${esc(JSON.stringify(l.payload))}</div>` : ''}
          </div>
          <div class="audit-target" style="word-break:break-all">${l.ip || '—'}</div>
          <div class="audit-time">${fmtDateTime(l.created_at)}</div>
        </div>`).join('');
    }

    const totalPages = Math.ceil(total / AUDIT_LIMIT);
    const curPage    = Math.floor(auditOffset / AUDIT_LIMIT) + 1;
    document.getElementById('audit-page-info').textContent = `Hal ${curPage} / ${totalPages || 1}`;
    document.getElementById('audit-prev').disabled = auditOffset === 0;
    document.getElementById('audit-next').disabled = auditOffset + AUDIT_LIMIT >= total;
  } catch { showToast('Gagal memuat audit log', 'error'); }
}

function auditPage(dir) {
  auditOffset = Math.max(0, auditOffset + dir * AUDIT_LIMIT);
  loadAudit();
}

function debounceAudit() {
  clearTimeout(auditDebounce);
  auditDebounce = setTimeout(() => { auditOffset = 0; loadAudit(); }, 350);
}

// ── Helpers ────────────────────────────────────────────────────────────────
function closeModal(id, e) {
  if (e && e.target !== document.getElementById(id)) return;
  document.getElementById(id).classList.remove('open');
}

function showToast(msg, type = '') {
  const wrap = document.getElementById('toast-wrap');
  const t = document.createElement('div');
  t.className = 'toast ' + type;
  t.textContent = msg;
  wrap.appendChild(t);
  setTimeout(() => t.remove(), 3400);
}

function esc(str) {
  return String(str ?? '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

function fmtRp(n) {
  return Number(n).toLocaleString('id-ID');
}

function fmtDateTime(dt) {
  return new Date(dt).toLocaleString('id-ID', { day:'2-digit', month:'short', year:'numeric', hour:'2-digit', minute:'2-digit' });
}

async function doLogout() {
  await fetch('/api/auth/logout', { method: 'POST' });
  location.href = '/login';
}

// ──────────────────────────────────────────────────────────────── ERROR LOG ──
async function loadErrorLog() {
  const lines = document.getElementById('errlog-lines')?.value || 100;
  try {
    const res  = await fetch('/api/admin/error-log?lines=' + lines);
    const json = await res.json();
    if (!json.ok) throw new Error();
    const { entries, size_kb, message } = json.data;

    document.getElementById('errlog-size').textContent = size_kb + ' KB';
    document.getElementById('errlog-ts').textContent   = 'Diperbarui ' + new Date().toLocaleTimeString('id-ID');

    const wrap = document.getElementById('errlog-rows');
    if (message) { wrap.innerHTML = `<span style="color:var(--ink3)">${esc(message)}</span>`; return; }
    if (!entries.length) { wrap.innerHTML = '<span style="color:var(--ok)">✓ Tidak ada error log</span>'; return; }

    const colorMap = { error:'var(--danger)', warn:'var(--warn)', notice:'var(--saffron)', info:'var(--ink3)' };
    wrap.innerHTML = entries.map(e =>
      `<div style="color:${colorMap[e.level]||'var(--ink2)'};padding:2px 0;border-bottom:1px solid var(--border);word-break:break-all">${esc(e.text)}</div>`
    ).join('');
  } catch { showToast('Gagal memuat error log', 'error'); }
}

// ──────────────────────────────────────────────── CHECKLIST MASTER (7.1) ──
async function loadMasterChecklist() {
  try {
    const res = await fetch('/api/admin/checklist-master');
    const json = await res.json();
    if (!json.ok) throw new Error();
    masterData = json.data;
    renderMasterChecklist();
  } catch { showToast('Gagal memuat master checklist', 'error'); }
}

function renderMasterChecklist() {
  if (!masterData) return;
  const shift = document.getElementById('cl-shift').value;
  const sections = masterData.checklist?.[shift] || [];
  let totalItems = 0;
  sections.forEach(s => totalItems += s.items?.length || 0);
  document.getElementById('cl-count').textContent = totalItems + ' item';

  const wrap = document.getElementById('cl-sections');
  const badgeLabel = { 'ibadge-crit': '🔴 KRITIKAL', 'ibadge-imp': '🟡 Penting', '': '' };

  wrap.innerHTML = sections.map((sec, secIdx) => `
    <div style="margin-bottom:16px">
      <div style="display:flex;align-items:center;gap:8px;margin-bottom:8px">
        <div style="font-size:13px;font-weight:600;color:var(--ink2)">${esc(sec.title || 'Seksi ' + (secIdx+1))}</div>
        <span class="sec-count">${sec.items?.length || 0} item</span>
      </div>
      <div class="tbl-wrap">
        <table><thead><tr><th style="width:40px">#</th><th>Item</th><th>Badge</th><th>Aksi</th></tr></thead>
        <tbody>
          ${(sec.items || []).map((item, i) => `
            <tr>
              <td class="mono text-muted">${i+1}</td>
              <td style="font-size:13px">${esc(item.text)}<br>
                <span class="mono" style="font-size:10px;color:var(--ink3)">${esc(item.id)}</span>
                ${item.note ? `<br><span style="font-size:11px;color:var(--ink3)">📝 ${esc(item.note)}</span>` : ''}
              </td>
              <td>${item.badge ? `<span class="badge badge-${item.badge==='ibadge-crit'?'admin':'spv'}">${badgeLabel[item.badge]||item.badge}</span>` : '<span class="text-muted">—</span>'}</td>
              <td><div class="tbl-actions">
                <button class="btn-tbl" onclick="openEditItemModal('${esc(shift)}','${esc(item.id)}',${JSON.stringify(item.text)},${JSON.stringify(item.badge||'')})">Edit</button>
                <button class="btn-tbl danger" onclick="deleteItem('${esc(shift)}','${esc(item.id)}',${JSON.stringify(item.text)})">Hapus</button>
              </div></td>
            </tr>`).join('')}
        </tbody></table>
      </div>
    </div>`).join('');
}

function openAddItemModal() {
  const shift = document.getElementById('cl-shift').value;
  const sections = masterData?.checklist?.[shift] || [];
  const secOpts = sections.map((s,i) => `<option value="${i}">${esc(s.title||'Seksi '+(i+1))}</option>`).join('');
  openGenericModal('Tambah Item Checklist',
    `<div class="form-group"><label class="form-label">Seksi<span class="req">*</span></label>
      <select class="form-control" id="new-sec">${secOpts}</select></div>
     <div class="form-group"><label class="form-label">Teks Item<span class="req">*</span></label>
      <input type="text" class="form-control" id="new-text" placeholder="Deskripsi item..."></div>
     <div class="form-group"><label class="form-label">Badge</label>
      <select class="form-control" id="new-badge">
        <option value="">— Tidak ada —</option>
        <option value="ibadge-crit">🔴 KRITIKAL</option>
        <option value="ibadge-imp">🟡 Penting</option>
      </select></div>
     <div class="form-group"><label class="form-label">Catatan (opsional)</label>
      <input type="text" class="form-control" id="new-note" placeholder="Catatan tambahan..."></div>`,
    async () => {
      const body = {
        shift, section_index: parseInt(document.getElementById('new-sec').value),
        text:  document.getElementById('new-text').value.trim(),
        badge: document.getElementById('new-badge').value,
        note:  document.getElementById('new-note').value.trim(),
      };
      if (!body.text) { showToast('Teks item wajib diisi', 'error'); return false; }
      const res  = await fetch('/api/admin/checklist-master', { method:'POST', headers:{'Content-Type':'application/json','X-CSRF-Token':getCsrfToken()}, body: JSON.stringify(body) });
      const json = await res.json();
      if (!json.ok) throw new Error(json.message);
      masterData = null; await loadMasterChecklist();
      showToast('Item berhasil ditambahkan', 'success');
    });
}

function openEditItemModal(shift, itemId, text, badge) {
  openGenericModal('Edit Item Checklist',
    `<div class="form-group"><label class="form-label">Teks Item<span class="req">*</span></label>
      <input type="text" class="form-control" id="edit-text" value="${esc(text)}"></div>
     <div class="form-group"><label class="form-label">Badge</label>
      <select class="form-control" id="edit-badge">
        <option value="" ${!badge?'selected':''}>— Tidak ada —</option>
        <option value="ibadge-crit" ${badge==='ibadge-crit'?'selected':''}>🔴 KRITIKAL</option>
        <option value="ibadge-imp"  ${badge==='ibadge-imp'?'selected':''}>🟡 Penting</option>
      </select></div>`,
    async () => {
      const body = { shift, item_id: itemId, text: document.getElementById('edit-text').value.trim(), badge: document.getElementById('edit-badge').value };
      if (!body.text) { showToast('Teks item wajib diisi','error'); return false; }
      const res  = await fetch('/api/admin/checklist-master', { method:'PUT', headers:{'Content-Type':'application/json','X-CSRF-Token':getCsrfToken()}, body: JSON.stringify(body) });
      const json = await res.json();
      if (!json.ok) throw new Error(json.message);
      masterData = null; await loadMasterChecklist();
      showToast('Item diperbarui','success');
    });
}

async function deleteItem(shift, itemId, text) {
  if (!confirm(`Hapus item "${text}"?\nData checklist yang sudah diisi tidak terpengaruh, tapi item ini tidak akan muncul di form baru.`)) return;
  try {
    const res  = await fetch('/api/admin/checklist-master', { method:'DELETE', headers:{'Content-Type':'application/json','X-CSRF-Token':getCsrfToken()}, body: JSON.stringify({shift, item_id: itemId}) });
    const json = await res.json();
    if (!json.ok) throw new Error(json.message);
    masterData = null; await loadMasterChecklist();
    showToast('Item dihapus', 'success');
  } catch (e) { showToast(e.message||'Gagal hapus item','error'); }
}

// Generic modal helper
function openGenericModal(title, bodyHtml, onSave) {
  document.getElementById('user-modal-title').textContent = title;
  document.getElementById('user-modal-sub').textContent   = '';
  document.getElementById('user-id').value = '';
  document.getElementById('user-form-fields').style.display = '';
  document.getElementById('user-form-fields').innerHTML = bodyHtml;
  document.getElementById('pw-reveal').style.display = 'none';
  document.getElementById('user-modal-footer').innerHTML = `
    <button class="btn-ghost" onclick="closeModal('user-modal')">Batal</button>
    <button class="btn-submit" id="generic-save-btn" onclick="genericSave()">Simpan</button>`;
  window._genericSaveFn = onSave;
  document.getElementById('user-modal').classList.add('open');
}
async function genericSave() {
  const btn = document.getElementById('generic-save-btn');
  if (btn) { btn.disabled = true; btn.textContent = 'Menyimpan…'; }
  try {
    const result = await window._genericSaveFn();
    if (result !== false) closeModal('user-modal');
  } catch (e) { showToast(e.message||'Gagal menyimpan','error'); }
  finally { if (btn) { btn.disabled = false; btn.textContent = 'Simpan'; } }
}

// ──────────────────────────────────────────────────────── AREA SPV (7.7) ──
async function loadSpvAreas() {
  try {
    // Load SPV areas + outlets jika belum ada
    const [areaRes, outRes] = await Promise.all([
      fetch('/api/admin/spv-areas'),
      outlets.length ? Promise.resolve({ ok: true, json: async () => ({ ok: true, data: outlets }) }) : fetch('/api/admin/outlets'),
    ]);
    const areaJson = await areaRes.json();
    if (!areaJson.ok) throw new Error();
    if (outlets.length === 0) { const o = await outRes.json(); if (o.ok) outlets = o.data; }
    spvAreas = areaJson.data;
    renderSpvAreas();
  } catch { showToast('Gagal memuat area SPV','error'); }
}

function renderSpvAreas() {
  document.getElementById('spv-area-count').textContent = spvAreas.length + ' SPV';
  const wrap = document.getElementById('spv-area-list');
  if (!spvAreas.length) { wrap.innerHTML = '<div class="empty-state"><p>Tidak ada SPV aktif</p></div>'; return; }

  wrap.innerHTML = spvAreas.map(s => `
    <div style="background:var(--surface);border:1px solid var(--border);border-radius:var(--r-lg);padding:16px;margin-bottom:12px;box-shadow:var(--shadow)">
      <div style="display:flex;align-items:center;gap:10px;margin-bottom:12px">
        <div>
          <div style="font-weight:600;font-size:14px">${esc(s.full_name)}</div>
          <div class="mono text-muted" style="font-size:11px">${esc(s.username)}</div>
        </div>
        <span class="badge ${s.global ? 'badge-spv' : 'badge-outlet'}" style="margin-left:auto">
          ${s.global ? '🌐 Global' : s.outlets.length + ' outlet'}
        </span>
        <button class="btn-tbl" onclick="openSpvAreaModal(${s.spv_id})">Atur Area</button>
      </div>
      ${s.outlets.length ? `<div style="display:flex;gap:6px;flex-wrap:wrap">
        ${s.outlets.map(o => `<span class="badge badge-outlet" style="font-size:10px">${esc(o.outlet_code)}</span>`).join('')}
      </div>` : '<div style="font-size:12px;color:var(--ink3)">Akses ke semua outlet</div>'}
    </div>`).join('');
}

function openSpvAreaModal(spvId) {
  const spv = spvAreas.find(s => s.spv_id === spvId);
  if (!spv) return;
  const assignedIds = new Set(spv.outlets.map(o => o.outlet_id));
  const checkboxes = outlets.filter(o => o.active).map(o => `
    <label style="display:flex;align-items:center;gap:8px;padding:6px 0;border-bottom:1px solid var(--border);font-size:13px;cursor:pointer">
      <input type="checkbox" value="${o.id}" ${assignedIds.has(o.id)?'checked':''} style="accent-color:var(--saffron);width:15px;height:15px">
      <span class="mono" style="font-size:11px;color:var(--ink3);min-width:70px">${esc(o.code)}</span>
      ${esc(o.name)}
    </label>`).join('');

  openGenericModal(`Atur Area: ${spv.full_name}`,
    `<div style="font-size:12px;color:var(--ink3);margin-bottom:10px">Centang outlet yang boleh dilihat SPV ini. Kosongkan semua = akses global.</div>
     <div style="max-height:300px;overflow-y:auto">${checkboxes}</div>`,
    async () => {
      const checked = [...document.querySelectorAll('#user-form-fields input[type=checkbox]:checked')].map(el => parseInt(el.value));
      const res  = await fetch('/api/admin/spv-areas', { method:'POST', headers:{'Content-Type':'application/json','X-CSRF-Token':getCsrfToken()}, body: JSON.stringify({ spv_id: spvId, outlet_ids: checked }) });
      const json = await res.json();
      if (!json.ok) throw new Error(json.message);
      spvAreas = []; await loadSpvAreas();
      showToast(json.data.message, 'success');
    });
}

// ── Reset All Passwords ────────────────────────────────────────────────────
let selectedResetRole = 'outlet';

function resetAllPasswords() {
  selectedResetRole = 'outlet';
  document.getElementById('resetall-result').style.display = 'none';
  document.getElementById('resetall-footer').style.display = '';
  document.getElementById('resetall-sub').textContent = 'Pilih role yang akan direset';
  updateRoleButtons();
  document.getElementById('resetall-modal').classList.add('open');
}

function selectResetRole(role) {
  selectedResetRole = role;
  updateRoleButtons();
}

function updateRoleButtons() {
  ['outlet','spv','owner','all'].forEach(r => {
    const btn = document.getElementById('role-btn-' + r);
    if (!btn) return;
    btn.style.background   = r === selectedResetRole ? 'var(--ok-bg)'  : '';
    btn.style.borderColor  = r === selectedResetRole ? 'var(--ok-bdr)' : '';
    btn.style.color        = r === selectedResetRole ? 'var(--ok)'     : '';
  });
}

async function doResetAll() {
  const label = selectedResetRole === 'all' ? 'semua akun (kecuali admin)' : `akun role ${selectedResetRole}`;
  if (!confirm(`Reset password untuk ${label}? Semua password lama akan langsung tidak berlaku.`)) return;

  const btn = document.getElementById('resetall-btn');
  btn.disabled = true;
  btn.textContent = 'Mereset...';

  try {
    const res  = await fetch('/api/admin/users?id=0', {
      method: 'PUT',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ action: 'reset_all_passwords', role: selectedResetRole }),
    });
    const json = await res.json();
    if (!json.ok) throw new Error(json.message || 'Gagal');

    const results = json.data.results;
    document.getElementById('resetall-count-label').textContent =
      `✅ ${results.length} password berhasil direset`;

    document.getElementById('resetall-tbody').innerHTML = results.map((r, i) => `
      <tr style="background:${i%2===0?'#fff':'var(--bg)'}">
        <td style="padding:8px 12px;font-family:monospace">${esc(r.username)}</td>
        <td style="padding:8px 12px">${esc(r.full_name)}</td>
        <td style="padding:8px 12px;font-size:11px;color:var(--ink3)">${esc(r.outlet_name)}</td>
        <td style="padding:8px 12px;font-family:monospace;font-weight:700;color:var(--ok);font-size:13px">${esc(r.password)}</td>
      </tr>`).join('');

    document.getElementById('resetall-result').style.display = '';
    document.getElementById('resetall-footer').style.display = 'none';
    document.getElementById('resetall-sub').textContent = `${results.length} akun direset — catat atau cetak sebelum tutup`;

    // Siapkan print area
    document.getElementById('print-date').textContent =
      'Digenerate: ' + new Date().toLocaleString('id-ID') + ' oleh Admin';
    document.getElementById('print-tbody').innerHTML = results.map((r, i) => `
      <tr>
        <td>${i+1}</td>
        <td>${esc(r.username)}</td>
        <td>${esc(r.full_name)}</td>
        <td>${esc(r.outlet_name)}</td>
        <td class="pw">${esc(r.password)}</td>
      </tr>`).join('');

  } catch(e) {
    showToast(e.message || 'Gagal reset password', 'error');
  } finally {
    btn.disabled = false;
    btn.textContent = 'Reset Sekarang';
  }
}

function printPasswords() {
  document.getElementById('print-area').style.display = 'block';
  window.print();
  setTimeout(() => document.getElementById('print-area').style.display = 'none', 1000);
}

// ── Auto-load outlets on start ─────────────────────────────────────────────
loadOutlets();
</script>
</body>
</html>
