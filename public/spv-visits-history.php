<?php
declare(strict_types=1);

require_once __DIR__ . '/../src/bootstrap.php';
require_once ROOT_PATH . '/src/helpers/auth.php';
require_once ROOT_PATH . '/src/helpers/db.php';
require_once ROOT_PATH . '/src/middleware/page.php';

$user = pageRequireRole('spv', 'admin');

$outlets  = [];
$spvUsers = [];
if ($user['role'] === 'admin') {
    $outlets  = db()->query("SELECT id, code, name FROM outlets WHERE active=1 ORDER BY name")->fetchAll();
    $spvUsers = db()->query("SELECT id, full_name AS name FROM users WHERE role='spv' AND active=1 ORDER BY full_name")->fetchAll();
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Rekap Kunjungan SPV · SS Operations</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=Nunito:wght@400;600;700;800;900&family=Fraunces:opsz,wght@9..144,400;9..144,600;9..144,700&family=Inter:wght@400;500;600&family=Geist+Mono:wght@400;500&display=swap" rel="stylesheet">
<link rel="stylesheet" href="/assets/css/ds.css">
<style>
:root {
  --bg:          #F7F3ED;
  --surface:     #FFFFFF;
  --border:      rgba(28,25,23,0.10);
  --border2:     rgba(28,25,23,0.16);
  --ink:         #1C1917;
  --ink2:        #57534E;
  --ink3:        #A8A29E;
  --saffron:     #E8942A;
  --saf-bg:      #FEF3E2;
  --saf-border:  rgba(232,148,42,0.30);
  --ok:          #16A34A;
  --ok-bg:       #F0FDF4;
  --ok-border:   #BBF7D0;
  --warn:        #D97706;
  --warn-bg:     #FFFBEB;
  --warn-border: #FDE68A;
  --danger:      #DC2626;
  --danger-bg:   #FEF2F2;
  --danger-border:#FECACA;
  --font-display:  'Bebas Neue', sans-serif;
  --font-serif:    'Fraunces', serif;
  --font-body:     'Nunito', sans-serif;
  --font-ui:       'Inter', sans-serif;
  --font-mono:     'Geist Mono', monospace;
}

* { box-sizing: border-box; margin: 0; padding: 0; }
body { background: var(--bg); font-family: var(--font-body); color: var(--ink); min-height: 100vh; }

/* Header */
.page-header {
  background: linear-gradient(135deg, #2C1A0E 0%, #4A2C14 50%, #1C1917 100%);
  padding: 0 24px;
  height: 56px;
  display: flex;
  align-items: center;
  justify-content: space-between;
  position: sticky;
  top: 0;
  z-index: 100;
  box-shadow: 0 2px 12px rgba(0,0,0,0.18);
}
.header-left { display: flex; align-items: center; gap: 14px; }
.header-logo { width: 32px; height: 32px; border-radius: 8px; background: rgba(255,255,255,0.12); display: flex; align-items: center; justify-content: center; overflow: hidden; }
.header-logo img { width: 100%; height: 100%; object-fit: cover; }
.header-brand { font-family: var(--font-display); font-size: 18px; color: #fff; letter-spacing: 1px; line-height: 1; }
.header-sub { font-size: 9px; color: rgba(255,255,255,0.50); font-family: var(--font-body); font-weight: 700; letter-spacing: 1px; }
.header-actions { display: flex; align-items: center; gap: 8px; }
.btn-header {
  background: rgba(255,255,255,0.12);
  border: 1px solid rgba(255,255,255,0.20);
  border-radius: 8px;
  color: rgba(255,255,255,0.85);
  font-family: var(--font-body);
  font-size: 11px;
  font-weight: 800;
  padding: 5px 11px;
  cursor: pointer;
  text-decoration: none;
  display: inline-flex;
  align-items: center;
  gap: 4px;
}
.btn-header:hover { background: rgba(255,255,255,0.20); }

/* Main content */
.main { max-width: 1100px; margin: 0 auto; padding: 28px 20px 60px; }

/* Page title */
.page-title { font-family: var(--font-serif); font-size: 26px; font-weight: 700; color: var(--ink); margin-bottom: 4px; }
.page-sub { font-size: 13px; color: var(--ink3); font-weight: 600; margin-bottom: 24px; }

/* Filter bar */
.filter-bar {
  background: var(--surface);
  border: 1px solid var(--border);
  border-radius: 14px;
  padding: 16px 20px;
  display: flex;
  flex-wrap: wrap;
  align-items: flex-end;
  gap: 14px;
  margin-bottom: 24px;
}
.filter-group { display: flex; flex-direction: column; gap: 5px; }
.filter-label { font-size: 11px; font-weight: 800; color: var(--ink2); letter-spacing: 0.3px; text-transform: uppercase; }
.filter-input {
  height: 36px;
  padding: 0 12px;
  border: 1px solid var(--border2);
  border-radius: 9px;
  font-family: var(--font-body);
  font-size: 13px;
  font-weight: 600;
  color: var(--ink);
  background: var(--bg);
  outline: none;
  min-width: 140px;
}
.filter-input:focus { border-color: var(--saffron); }
.btn-filter {
  height: 36px;
  padding: 0 18px;
  background: var(--saffron);
  border: none;
  border-radius: 9px;
  color: #fff;
  font-family: var(--font-body);
  font-size: 13px;
  font-weight: 800;
  cursor: pointer;
  align-self: flex-end;
}
.btn-filter:hover { opacity: 0.88; }

/* Visit cards */
.visits-list { display: flex; flex-direction: column; gap: 12px; }
.visit-card {
  background: var(--surface);
  border: 1px solid var(--border);
  border-radius: 14px;
  padding: 18px 20px;
  display: grid;
  grid-template-columns: 1fr auto;
  gap: 12px;
  align-items: center;
}
.visit-card:hover { border-color: var(--saf-border); box-shadow: 0 2px 12px rgba(232,148,42,0.08); }
.visit-meta { display: flex; flex-direction: column; gap: 6px; }
.visit-outlet { font-size: 15px; font-weight: 800; color: var(--ink); }
.visit-detail-row { display: flex; flex-wrap: wrap; gap: 12px; align-items: center; }
.visit-badge {
  font-size: 10px;
  font-weight: 800;
  letter-spacing: 0.5px;
  padding: 3px 9px;
  border-radius: 6px;
  text-transform: uppercase;
}
.badge-open  { background: var(--ok-bg);   color: var(--ok);   border: 1px solid var(--ok-border); }
.badge-ops   { background: var(--warn-bg); color: var(--warn); border: 1px solid var(--warn-border); }
.badge-close { background: var(--danger-bg); color: var(--danger); border: 1px solid var(--danger-border); }
.visit-info-text { font-size: 12px; color: var(--ink2); font-weight: 600; }
.visit-photo-chip {
  display: flex;
  align-items: center;
  gap: 4px;
  font-size: 11px;
  font-weight: 800;
  color: var(--ink3);
}
.btn-detail {
  background: var(--saf-bg);
  border: 1px solid var(--saf-border);
  border-radius: 9px;
  color: var(--saffron);
  font-family: var(--font-body);
  font-size: 12px;
  font-weight: 800;
  padding: 8px 16px;
  cursor: pointer;
  white-space: nowrap;
}
.btn-detail:hover { background: var(--saffron); color: #fff; }

/* Pagination */
.pagination { display: flex; justify-content: center; align-items: center; gap: 10px; margin-top: 24px; }
.btn-page {
  background: var(--surface);
  border: 1px solid var(--border2);
  border-radius: 8px;
  color: var(--ink);
  font-family: var(--font-body);
  font-size: 12px;
  font-weight: 700;
  padding: 7px 16px;
  cursor: pointer;
}
.btn-page:disabled { opacity: 0.38; cursor: not-allowed; }
.btn-page:not(:disabled):hover { border-color: var(--saffron); color: var(--saffron); }
.page-info { font-size: 12px; color: var(--ink3); font-weight: 700; }

/* Empty / loading */
.state-box {
  background: var(--surface);
  border: 1px solid var(--border);
  border-radius: 14px;
  padding: 48px 24px;
  text-align: center;
  color: var(--ink3);
}
.state-box .state-icon { font-size: 36px; margin-bottom: 10px; }
.state-box .state-text { font-size: 14px; font-weight: 700; }

/* Modal */
.modal-backdrop {
  position: fixed;
  inset: 0;
  background: rgba(0,0,0,0.45);
  z-index: 1000;
  display: none;
  align-items: flex-start;
  justify-content: center;
  padding: 24px 16px;
  overflow-y: auto;
}
.modal-backdrop.open { display: flex; }
.modal {
  background: var(--surface);
  border-radius: 18px;
  width: 100%;
  max-width: 700px;
  box-shadow: 0 20px 60px rgba(0,0,0,0.22);
  margin: auto;
  position: relative;
}
.modal-header {
  padding: 20px 24px 16px;
  border-bottom: 1px solid var(--border);
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 12px;
}
.modal-title { font-family: var(--font-serif); font-size: 19px; font-weight: 700; color: var(--ink); line-height: 1.2; }
.modal-subtitle { font-size: 12px; color: var(--ink3); font-weight: 600; margin-top: 3px; }
.modal-close {
  background: var(--bg);
  border: 1px solid var(--border);
  border-radius: 7px;
  width: 30px; height: 30px;
  display: flex; align-items: center; justify-content: center;
  cursor: pointer;
  font-size: 16px;
  color: var(--ink2);
  flex-shrink: 0;
}
.modal-close:hover { background: var(--danger-bg); color: var(--danger); border-color: var(--danger-border); }
.modal-body { padding: 20px 24px; display: flex; flex-direction: column; gap: 20px; }
.modal-section-title {
  font-size: 11px;
  font-weight: 800;
  letter-spacing: 0.8px;
  text-transform: uppercase;
  color: var(--ink3);
  margin-bottom: 10px;
}
.info-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(160px, 1fr));
  gap: 10px;
}
.info-item { display: flex; flex-direction: column; gap: 2px; }
.info-label { font-size: 10px; font-weight: 800; color: var(--ink3); text-transform: uppercase; letter-spacing: 0.3px; }
.info-value { font-size: 13px; font-weight: 700; color: var(--ink); }
.payload-item {
  background: var(--bg);
  border-radius: 8px;
  padding: 10px 14px;
  display: flex;
  flex-direction: column;
  gap: 3px;
}
.payload-key { font-size: 10px; font-weight: 800; color: var(--ink3); text-transform: uppercase; letter-spacing: 0.3px; }
.payload-val { font-size: 13px; font-weight: 600; color: var(--ink); }
.payload-empty { font-size: 12px; color: var(--ink3); font-style: italic; }
.photo-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(100px, 1fr)); gap: 10px; }
.photo-thumb {
  width: 100%;
  aspect-ratio: 1;
  object-fit: cover;
  border-radius: 9px;
  cursor: pointer;
  border: 2px solid transparent;
  transition: border-color 0.15s, transform 0.15s;
}
.photo-thumb:hover { border-color: var(--saffron); transform: scale(1.03); }
.photo-caption { font-size: 10px; color: var(--ink2); font-weight: 600; text-align: center; margin-top: 4px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.photo-wrapper { display: flex; flex-direction: column; }
.modal-footer { padding: 14px 24px 20px; border-top: 1px solid var(--border); text-align: right; }
.btn-close-modal {
  background: var(--bg);
  border: 1px solid var(--border2);
  border-radius: 9px;
  color: var(--ink2);
  font-family: var(--font-body);
  font-size: 13px;
  font-weight: 800;
  padding: 9px 20px;
  cursor: pointer;
}
.btn-close-modal:hover { background: var(--border); }
</style>
</head>
<body>

<!-- Header -->
<header class="page-header">
  <div class="header-left">
    <div class="header-logo">
      <img src="/assets/img/logo.png" alt="SS" onerror="this.style.display='none'">
    </div>
    <div>
      <div class="header-brand">SS OPS</div>
      <div class="header-sub">REKAP KUNJUNGAN SPV</div>
    </div>
  </div>
  <div class="header-actions">
    <a href="/dashboard" class="btn-header">← Dashboard</a>
    <?php if ($user['role'] === 'spv'): ?>
    <a href="/spv-visit" class="btn-header">+ Form Kunjungan</a>
    <?php endif; ?>
    <div style="display:flex;align-items:center;gap:6px;">
      <span style="font-size:11px;color:rgba(255,255,255,0.65);font-weight:700;"><?= htmlspecialchars($user['full_name'] ?? $user['name'] ?? '') ?></span>
      <span style="font-size:9px;background:rgba(255,255,255,0.15);border:1px solid rgba(255,255,255,0.22);border-radius:6px;padding:2px 7px;color:rgba(255,255,255,0.80);font-weight:800;text-transform:uppercase;letter-spacing:0.5px;"><?= htmlspecialchars($user['role']) ?></span>
    </div>
    <button onclick="doLogout()" class="btn-header">Keluar</button>
  </div>
</header>

<!-- Main -->
<div class="main">
  <div class="page-title">Rekap Kunjungan SPV</div>
  <div class="page-sub">Dokumentasi kunjungan supervisor ke outlet</div>

  <!-- Filter bar -->
  <div class="filter-bar">
    <div class="filter-group">
      <div class="filter-label">Dari Tanggal</div>
      <input type="date" id="filterFrom" class="filter-input" value="<?= date('Y-m-01') ?>">
    </div>
    <div class="filter-group">
      <div class="filter-label">Sampai</div>
      <input type="date" id="filterTo" class="filter-input" value="<?= date('Y-m-d') ?>">
    </div>
    <?php if ($user['role'] === 'admin'): ?>
    <div class="filter-group">
      <div class="filter-label">Outlet</div>
      <select id="filterOutlet" class="filter-input">
        <option value="">Semua Outlet</option>
        <?php foreach ($outlets as $o): ?>
        <option value="<?= $o['id'] ?>"><?= htmlspecialchars($o['name']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="filter-group">
      <div class="filter-label">SPV</div>
      <select id="filterSpv" class="filter-input">
        <option value="">Semua SPV</option>
        <?php foreach ($spvUsers as $s): ?>
        <option value="<?= $s['id'] ?>"><?= htmlspecialchars($s['name']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <?php endif; ?>
    <button class="btn-filter" onclick="loadVisits(1)">Tampilkan</button>
  </div>

  <!-- List area -->
  <div id="listArea">
    <div class="state-box">
      <div class="state-icon">⏳</div>
      <div class="state-text">Memuat data...</div>
    </div>
  </div>

  <!-- Pagination -->
  <div class="pagination" id="pagination" style="display:none;">
    <button class="btn-page" id="btnPrev" onclick="changePage(-1)" disabled>← Sebelumnya</button>
    <span class="page-info" id="pageInfo"></span>
    <button class="btn-page" id="btnNext" onclick="changePage(1)">Selanjutnya →</button>
  </div>
</div>

<!-- Modal Detail -->
<div class="modal-backdrop" id="modalBackdrop" onclick="handleBackdropClick(event)">
  <div class="modal" id="modalBox">
    <div class="modal-header">
      <div>
        <div class="modal-title" id="modalTitle">—</div>
        <div class="modal-subtitle" id="modalSubtitle">—</div>
      </div>
      <button class="modal-close" onclick="closeModal()">✕</button>
    </div>
    <div class="modal-body" id="modalBody">
      <div class="state-box"><div class="state-icon">⏳</div><div class="state-text">Memuat detail...</div></div>
    </div>
    <div class="modal-footer">
      <button class="btn-close-modal" onclick="closeModal()">Tutup</button>
    </div>
  </div>
</div>

<script>
const IS_ADMIN = <?= $user['role'] === 'admin' ? 'true' : 'false' ?>;
let currentPage = 1;
let totalPage   = 1;

function getFilters() {
  const from    = document.getElementById('filterFrom').value;
  const to      = document.getElementById('filterTo').value;
  const outlet  = IS_ADMIN ? (document.getElementById('filterOutlet')?.value || '') : '';
  const spv     = IS_ADMIN ? (document.getElementById('filterSpv')?.value || '')    : '';
  return { from, to, outlet, spv };
}

async function loadVisits(page = 1) {
  currentPage = page;
  const f = getFilters();
  const area = document.getElementById('listArea');
  const pg   = document.getElementById('pagination');

  area.innerHTML = '<div class="state-box"><div class="state-icon">⏳</div><div class="state-text">Memuat...</div></div>';
  pg.style.display = 'none';

  const params = new URLSearchParams({
    from: f.from, to: f.to, page, limit: 15,
    ...(f.outlet ? { outlet_id: f.outlet } : {}),
    ...(f.spv    ? { spv_id: f.spv }       : {}),
  });

  try {
    const res  = await fetch('/api/admin/spv-visits?' + params);
    const json = await res.json();
    if (!json.ok) throw new Error(json.message || 'Gagal memuat');

    const { visits, total, total_page } = json.data;
    totalPage = total_page;

    if (!visits || visits.length === 0) {
      area.innerHTML = '<div class="state-box"><div class="state-icon">📋</div><div class="state-text">Belum ada kunjungan dalam periode ini</div></div>';
      return;
    }

    area.innerHTML = '<div class="visits-list">' + visits.map(renderCard).join('') + '</div>';

    // Pagination
    document.getElementById('pageInfo').textContent = `Halaman ${page} dari ${total_page} (${total} kunjungan)`;
    document.getElementById('btnPrev').disabled = page <= 1;
    document.getElementById('btnNext').disabled = page >= total_page;
    pg.style.display = total_page > 1 ? 'flex' : 'none';

  } catch(e) {
    area.innerHTML = `<div class="state-box"><div class="state-icon">⚠️</div><div class="state-text">Gagal memuat data: ${e.message}</div></div>`;
    console.error('loadVisits error:', e);
  }
}

function changePage(delta) {
  const next = currentPage + delta;
  if (next < 1 || next > totalPage) return;
  loadVisits(next);
}

function shiftBadge(shift) {
  const map = { open: ['badge-open','Buka'], ops: ['badge-ops','Operasional'], close: ['badge-close','Tutup'] };
  const [cls, label] = map[shift] || ['badge-ops', shift || '—'];
  return `<span class="visit-badge ${cls}">${label}</span>`;
}

function formatTime(t) {
  if (!t) return '—';
  return t.substring(0, 5);
}

function formatDate(d) {
  if (!d) return '—';
  const dt = new Date(d + 'T00:00:00');
  return dt.toLocaleDateString('id-ID', { weekday:'short', day:'numeric', month:'short', year:'numeric' });
}

function renderCard(v) {
  const photoChip = v.photo_count > 0
    ? `<span class="visit-photo-chip">📷 ${v.photo_count} foto</span>`
    : `<span class="visit-photo-chip" style="color:var(--ink3);">Tanpa foto</span>`;

  const spvInfo = IS_ADMIN
    ? `<span class="visit-info-text">👤 ${escHtml(v.spv_name)}</span>`
    : '';

  return `
    <div class="visit-card">
      <div class="visit-meta">
        <div class="visit-outlet">${escHtml(v.outlet_name)} <span style="font-size:11px;color:var(--ink3);font-weight:700;">${escHtml(v.outlet_code)}</span></div>
        <div class="visit-detail-row">
          ${shiftBadge(v.visit_shift)}
          <span class="visit-info-text">📅 ${formatDate(v.visit_date)}</span>
          <span class="visit-info-text">🕐 ${formatTime(v.time_arrive)} – ${formatTime(v.time_leave)}</span>
          ${spvInfo}
          <span class="visit-info-text">PIC: ${escHtml(v.pic_on_duty || '—')}</span>
          ${photoChip}
        </div>
      </div>
      <button class="btn-detail" onclick="openReport(${v.id})">🖨️ Buka Laporan</button>
    </div>`;
}

async function openDetail(id) {
  document.getElementById('modalTitle').textContent    = 'Memuat...';
  document.getElementById('modalSubtitle').textContent = '';
  document.getElementById('modalBody').innerHTML =
    '<div class="state-box"><div class="state-icon">⏳</div><div class="state-text">Memuat detail kunjungan...</div></div>';
  document.getElementById('modalBackdrop').classList.add('open');
  document.body.style.overflow = 'hidden';

  try {
    const res  = await fetch('/api/admin/spv-visits?detail=1&id=' + id);
    const json = await res.json();
    if (!json.ok) throw new Error(json.message || 'Gagal');

    const v = json.data;
    document.getElementById('modalTitle').textContent    = v.outlet_name;
    document.getElementById('modalSubtitle').textContent = formatDate(v.visit_date) + ' · ' + (v.visit_shift || '—');

    document.getElementById('modalBody').innerHTML = buildModalBody(v);
  } catch(e) {
    document.getElementById('modalBody').innerHTML =
      '<div class="state-box"><div class="state-icon">⚠️</div><div class="state-text">Gagal memuat detail.</div></div>';
  }
}

function buildModalBody(v) {
  // Info section
  const spvRow = IS_ADMIN ? `<div class="info-item"><div class="info-label">SPV</div><div class="info-value">${escHtml(v.spv_name)}</div></div>` : '';
  const infoHtml = `
    <div>
      <div class="modal-section-title">Info Kunjungan</div>
      <div class="info-grid">
        <div class="info-item"><div class="info-label">Outlet</div><div class="info-value">${escHtml(v.outlet_name)}</div></div>
        <div class="info-item"><div class="info-label">Tanggal</div><div class="info-value">${formatDate(v.visit_date)}</div></div>
        <div class="info-item"><div class="info-label">Shift</div><div class="info-value">${shiftBadge(v.visit_shift)}</div></div>
        ${spvRow}
        <div class="info-item"><div class="info-label">Waktu Tiba</div><div class="info-value">${formatTime(v.time_arrive)}</div></div>
        <div class="info-item"><div class="info-label">Waktu Keluar</div><div class="info-value">${formatTime(v.time_leave)}</div></div>
        <div class="info-item"><div class="info-label">PIC Bertugas</div><div class="info-value">${escHtml(v.pic_on_duty || '—')}</div></div>
        <div class="info-item"><div class="info-label">Dikirim</div><div class="info-value" style="font-size:11px;">${v.submitted_at ? new Date(v.submitted_at).toLocaleString('id-ID') : '—'}</div></div>
      </div>
    </div>`;

  // Payload / catatan section
  let payloadHtml = '';
  const payload = v.payload_json || {};
  const payloadKeys = Object.keys(payload);
  if (payloadKeys.length > 0) {
    const items = payloadKeys.map(key => {
      const val = payload[key];
      const displayVal = typeof val === 'object' ? JSON.stringify(val) : String(val);
      const isNote = key.toLowerCase().includes('catatan') || key.toLowerCase().includes('note');
      return `<div class="payload-item" ${isNote ? 'style="grid-column:1/-1;background:var(--saf-bg);border:1px solid var(--saf-border);"' : ''}>
        <div class="payload-key">${escHtml(key.replace(/_/g,' '))}</div>
        <div class="payload-val">${escHtml(displayVal)}</div>
      </div>`;
    }).join('');
    payloadHtml = `
      <div>
        <div class="modal-section-title">Catatan & Isian Form</div>
        <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(200px,1fr));gap:8px;">${items}</div>
      </div>`;
  } else {
    payloadHtml = `
      <div>
        <div class="modal-section-title">Catatan & Isian Form</div>
        <div class="payload-item"><div class="payload-empty">Tidak ada catatan tambahan</div></div>
      </div>`;
  }

  // Photos section
  let photoHtml = '';
  if (v.photos && v.photos.length > 0) {
    const thumbs = v.photos.map(p => {
      const thumb = p.thumb_path || p.file_path;
      const full  = p.file_path;
      return `<div class="photo-wrapper">
        <img src="/${escHtml(thumb)}" class="photo-thumb" onclick="window.open('/${escHtml(full)}','_blank')"
             onerror="this.src='/${escHtml(full)}'" alt="${escHtml(p.label)}">
        <div class="photo-caption">${escHtml(p.label)}</div>
      </div>`;
    }).join('');
    photoHtml = `
      <div>
        <div class="modal-section-title">Foto Kunjungan (${v.photos.length})</div>
        <div class="photo-grid">${thumbs}</div>
      </div>`;
  } else {
    photoHtml = `
      <div>
        <div class="modal-section-title">Foto Kunjungan</div>
        <div class="state-box" style="padding:24px;"><div class="state-icon" style="font-size:24px;">📷</div><div class="state-text">Tidak ada foto dalam kunjungan ini</div></div>
      </div>`;
  }

  return infoHtml + payloadHtml + photoHtml;
}

function closeModal() {
  document.getElementById('modalBackdrop').classList.remove('open');
  document.body.style.overflow = '';
}

function handleBackdropClick(e) {
  if (e.target === document.getElementById('modalBackdrop')) closeModal();
}

function escHtml(str) {
  if (!str && str !== 0) return '';
  return String(str).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

async function doLogout() {
  await fetch('/api/auth/logout', { method: 'POST' });
  location.href = '/login';
}

// ── Master data inventaris & kriteria evaluasi (sama dengan spv-master.json) ──
const INVENTORY = [
  { cat:'Dapur — Utama', items:[
    {name:'Kompor bakar',std:2,unit:'unit'},{name:'Double fryer',std:1,unit:'unit'},
    {name:'Blender',std:1,unit:'unit'},{name:'Timbangan digital',std:2,unit:'pcs'},
    {name:'Stockpot besar',std:1,unit:'pcs'},{name:'Stockpot kecil',std:1,unit:'pcs'},
    {name:'MUG elektrik',std:1,unit:'unit'}
  ]},
  { cat:'Peralatan Masak', items:[
    {name:'Spatula bakar',std:4,unit:'pcs'},{name:'Spatula kulit',std:2,unit:'pcs'},
    {name:'Pisau',std:4,unit:'pcs'},{name:'Cutting board / talenan',std:2,unit:'pcs'},
    {name:'Stainless tray full',std:2,unit:'pcs'},{name:'Stainless tray ½',std:4,unit:'pcs'},
    {name:'Stainless tray ¼',std:4,unit:'pcs'},{name:'Service tong / jepitan',std:2,unit:'pcs'},
    {name:'Ladle',std:3,unit:'pcs'},{name:'Saringan minyak',std:2,unit:'pcs'},
    {name:'Keranjang sayur',std:1,unit:'unit'},{name:'Botol saus',std:6,unit:'pcs'},
    {name:'Kain lap',std:6,unit:'pcs'}
  ]},
  { cat:'Penyimpanan & Utilitas', items:[
    {name:'Freezer',std:1,unit:'unit'},{name:'Tabung gas',std:10,unit:'tabung'},
    {name:'Regulator set',std:4,unit:'pcs'},{name:'Tempat sampah',std:3,unit:'pcs'},
    {name:'Exhaust fan',std:2,unit:'unit'},{name:'Kipas angin',std:2,unit:'unit'},
    {name:'Fire extinguisher',std:1,unit:'unit'}
  ]},
  { cat:'Kasir & Teknologi', items:[
    {name:'Cash drawer',std:1,unit:'unit'},{name:'Printer struk',std:2,unit:'unit'},
    {name:'Tablet + stand',std:1,unit:'unit'},{name:'Sound box Pawoon',std:1,unit:'unit'},
    {name:'Mesin EDC',std:1,unit:'unit'},{name:'Handphone outlet',std:1,unit:'unit'},
    {name:'CCTV interior',std:2,unit:'unit'}
  ]},
  { cat:'Exterior & Signage', items:[
    {name:'Neon box',std:1,unit:'unit'},{name:'Lampu tembak exterior',std:4,unit:'unit'},
    {name:'CCTV exterior',std:1,unit:'unit'},{name:'Gembok',std:2,unit:'pcs'},
    {name:'Kursi plastik',std:6,unit:'pcs'}
  ]},
  { cat:'Kamar Mandi & Kebersihan', items:[
    {name:'Keran air',std:1,unit:'unit'},{name:'Sapu',std:2,unit:'pcs'},
    {name:'Kain pel',std:2,unit:'pcs'},{name:'Wiper',std:1,unit:'pcs'},
    {name:'Sikat WC',std:1,unit:'pcs'}
  ]}
];

const EVAL_CRITERIA = [
  {key:'punctual', label:'Ketepatan waktu & kehadiran'},
  {key:'uniform',  label:'Kerapian seragam & higienitas'},
  {key:'speed',    label:'Kecepatan & efisiensi kerja'},
  {key:'quality',  label:'Konsistensi kualitas produk'},
  {key:'attitude', label:'Sikap & pelayanan pelanggan'},
  {key:'sop',      label:'Kepatuhan SOP & prosedur'}
];

// ── Helpers ──────────────────────────────────────────────────────────────────
function rp(n) {
  if (!n && n !== 0) return '—';
  return 'Rp ' + Math.round(n).toLocaleString('id-ID');
}
function statusBadge(val) {
  const map = {
    ok:     ['#16A34A','#F0FDF4','#BBF7D0','✓ OK'],
    warn:   ['#D97706','#FFFBEB','#FDE68A','⚠ Perlu Cek'],
    broken: ['#DC2626','#FEF2F2','#FECACA','✕ Bermasalah'],
  };
  const [color, bg, border, label] = map[val] || ['#A8A29E','#F7F3ED','#E8E3DE', val || '—'];
  return `<span style="display:inline-block;padding:2px 9px;border-radius:6px;font-size:10px;font-weight:800;color:${color};background:${bg};border:1px solid ${border};">${label}</span>`;
}

// ── Generate laporan di browser (tidak butuh halaman PHP terpisah) ──────────
async function openReport(id) {
  const win = window.open('', '_blank');
  if (!win) { alert('Pop-up diblokir browser. Izinkan pop-up untuk situs ini.'); return; }
  win.document.write('<html><body style="font-family:sans-serif;padding:24px;color:#333">⏳ Memuat laporan...</body></html>');

  try {
    const res  = await fetch('/api/admin/spv-visits?detail=1&id=' + id);
    const json = await res.json();
    if (!json.ok) throw new Error(json.message || 'Gagal');
    const v = json.data;
    const p = v.payload_json || {};
    const info  = p.info  || {};
    const inv   = p.inventory || {};
    const sales = p.sales || {};
    const stock = p.stock || {};
    const hr    = p.hr    || {};
    const invState = inv.state || {};

    const shiftMap = { open:'Buka (Open)', ops:'Operasional', close:'Tutup (Close)' };
    const shiftStr = shiftMap[v.visit_shift] || v.visit_shift || '—';
    const dateStr  = v.visit_date
      ? new Date(v.visit_date + 'T00:00:00').toLocaleDateString('id-ID',{weekday:'long',day:'numeric',month:'long',year:'numeric'})
      : '—';

    // ── 1. KONDISI UMUM OUTLET ────────────────────────────────────────────
    const condRows = [
      ['Kebersihan Exterior',  info.cond_ext],
      ['Kebersihan Dapur / Interior', info.cond_int],
      ['Kebersihan Kamar Mandi',      info.cond_toilet],
      ['Kepatuhan Seragam & Hairnet', info.cond_uniform],
    ];
    let condHtml = condRows.map(([label, val]) =>
      `<tr>
        <td style="padding:7px 10px;font-size:12px;color:#1C1917;border-bottom:1px solid #f0ece7;">${label}</td>
        <td style="padding:7px 10px;border-bottom:1px solid #f0ece7;">${statusBadge(val)}</td>
      </tr>`
    ).join('');
    if (info.cond_notes) {
      condHtml += `<tr><td colspan="2" style="padding:8px 10px;font-size:12px;color:#57534E;font-style:italic;border-top:1px solid #e8e3de;">Catatan: ${escHtml(info.cond_notes)}</td></tr>`;
    }
    const condSection = `
      <div class="section">
        <div class="stitle">Kondisi Umum Outlet</div>
        <table style="width:100%;border-collapse:collapse;background:#f7f3ed;border-radius:8px;overflow:hidden;">
          <tbody>${condHtml}</tbody>
        </table>
      </div>`;

    // ── 2. INVENTARIS ASET ────────────────────────────────────────────────
    let invRows = '';
    INVENTORY.forEach(cat => {
      invRows += `<tr><td colspan="4" style="padding:8px 10px 4px;font-size:9px;font-weight:800;color:#A8A29E;text-transform:uppercase;letter-spacing:0.6px;background:#f0ece7;border-bottom:1px solid #e8e3de;">${cat.cat}</td></tr>`;
      cat.items.forEach((item, i) => {
        const key = cat.cat + '_' + i;
        const qty = invState[key + '_qty'] !== undefined ? invState[key + '_qty'] : '—';
        const st  = invState[key + '_st'] || 'ok';
        invRows += `<tr>
          <td style="padding:6px 10px;font-size:12px;color:#1C1917;border-bottom:1px solid #f0ece7;">${item.name}</td>
          <td style="padding:6px 10px;font-size:11px;color:#A8A29E;text-align:center;border-bottom:1px solid #f0ece7;">${item.std} ${item.unit}</td>
          <td style="padding:6px 10px;font-size:12px;font-weight:700;color:#1C1917;text-align:center;border-bottom:1px solid #f0ece7;">${qty !== '—' ? qty + ' ' + item.unit : '—'}</td>
          <td style="padding:6px 10px;text-align:center;border-bottom:1px solid #f0ece7;">${statusBadge(st)}</td>
        </tr>`;
      });
    });
    const invSection = `
      <div class="section">
        <div class="stitle">Inventaris Aset</div>
        <table style="width:100%;border-collapse:collapse;border-radius:8px;overflow:hidden;border:1px solid #e8e3de;">
          <thead>
            <tr style="background:#1C1917;">
              <th style="padding:7px 10px;font-size:10px;color:#fff;text-align:left;font-weight:700;">Aset</th>
              <th style="padding:7px 10px;font-size:10px;color:#fff;text-align:center;font-weight:700;">Std</th>
              <th style="padding:7px 10px;font-size:10px;color:#fff;text-align:center;font-weight:700;">Ada</th>
              <th style="padding:7px 10px;font-size:10px;color:#fff;text-align:center;font-weight:700;">Status</th>
            </tr>
          </thead>
          <tbody>${invRows}</tbody>
        </table>
        ${inv.notes || inv.repair_cost ? `
        <div style="margin-top:10px;background:#fef3e2;border:1px solid rgba(232,148,42,0.25);border-radius:8px;padding:10px 14px;">
          ${inv.notes ? `<div style="font-size:11px;color:#A8A29E;font-weight:800;text-transform:uppercase;margin-bottom:3px;">Catatan Inventaris</div><div style="font-size:12px;color:#1C1917;">${escHtml(inv.notes)}</div>` : ''}
          ${inv.repair_cost ? `<div style="font-size:11px;color:#A8A29E;font-weight:800;text-transform:uppercase;margin-top:6px;margin-bottom:3px;">Estimasi Biaya Perbaikan</div><div style="font-size:12px;font-weight:700;color:#E8942A;">${rp(inv.repair_cost)}</div>` : ''}
        </div>` : ''}
      </div>`;

    // ── 3. DATA PENJUALAN ─────────────────────────────────────────────────
    const avgTrx  = sales.trx > 0 ? Math.round(sales.total / sales.trx) : 0;
    const cashDiff = (sales.cash_counted || 0) - (sales.cash || 0);
    const ach      = sales.target > 0 ? Math.round(sales.total / sales.target * 100) : null;
    const salesRows = [
      ['Total Penjualan',      rp(sales.total),        true],
      ['Jumlah Transaksi',     (sales.trx || '—') + ' trx', false],
      ['Tunai',                rp(sales.cash),         false],
      ['QRIS',                 rp(sales.qris),         false],
      ['GoFood',               rp(sales.gofood),       false],
      ['GrabFood',             rp(sales.grab),         false],
      ['ShopeeFood',           rp(sales.shopee),       false],
      ['Kas Fisik (Hitung)',   rp(sales.cash_counted), false],
      ['Avg / Transaksi',      rp(avgTrx),             false],
      ['Selisih Kas',          (cashDiff >= 0 ? '+' : '') + rp(cashDiff), false],
    ];
    const salesHtml = salesRows.map(([label, val, bold]) =>
      `<tr>
        <td style="padding:6px 10px;font-size:12px;color:#57534E;border-bottom:1px solid #f0ece7;">${label}</td>
        <td style="padding:6px 10px;font-size:12px;font-weight:${bold?'800':'700'};color:${bold?'#E8942A':'#1C1917'};text-align:right;border-bottom:1px solid #f0ece7;">${val}</td>
      </tr>`
    ).join('');
    const salesSection = `
      <div class="section">
        <div class="stitle">Data Penjualan</div>
        <table style="width:100%;border-collapse:collapse;background:#f7f3ed;border-radius:8px;overflow:hidden;border:1px solid #e8e3de;">
          <tbody>
            ${salesHtml}
            <tr style="background:#1C1917;">
              <td style="padding:8px 10px;font-size:11px;font-weight:800;color:#fff;">Target Harian</td>
              <td style="padding:8px 10px;font-size:12px;font-weight:800;color:#E8942A;text-align:right;">${rp(sales.target)}${ach ? ' · <span style="color:#fff">'+ach+'% tercapai</span>' : ''}</td>
            </tr>
          </tbody>
        </table>
        ${sales.condition || sales.notes ? `
        <div style="margin-top:10px;display:grid;grid-template-columns:1fr 1fr;gap:8px;">
          ${sales.condition ? `<div style="background:#f7f3ed;border-radius:7px;padding:9px 12px;"><div style="font-size:9px;font-weight:800;color:#A8A29E;text-transform:uppercase;margin-bottom:3px;">Kondisi Penjualan</div><div style="font-size:12px;color:#1C1917;">${escHtml(sales.condition)}</div></div>` : ''}
          ${sales.notes    ? `<div style="background:#fef3e2;border:1px solid rgba(232,148,42,0.25);border-radius:7px;padding:9px 12px;grid-column:${sales.condition?'auto':'1/-1'};"><div style="font-size:9px;font-weight:800;color:#A8A29E;text-transform:uppercase;margin-bottom:3px;">Catatan Penjualan</div><div style="font-size:12px;color:#1C1917;">${escHtml(sales.notes)}</div></div>` : ''}
        </div>` : ''}
      </div>`;

    // ── 4. STOK BAHAN BAKU ────────────────────────────────────────────────
    const stockGroups = [
      { label:'Daging & Protein', rows:[
        ['Daging (stok awal)', stock.daging, 'kg'],
        ['Kiriman hari ini',   stock.kiriman, 'kg'],
        ['Spit dipakai',       stock.spit_used, 'spit'],
        ['Yield rata-rata',    stock.yield, 'kg/spit'],
      ]},
      { label:'Bahan Pendukung', rows:[
        ['Roti pita',  stock.roti, 'pcs'],
        ['Saus',       stock.saus, 'btl'],
        ['Sayur',      stock.sayur, 'pack'],
        ['Gas',        stock.gas, 'tabung'],
      ]},
      { label:'Packaging', rows:[
        ['Wrap',   stock.wrap, 'pcs'],
        ['Box',    stock.box,  'pcs'],
        ['Cup',    stock.cup,  'pcs'],
        ['Sendok', stock.sendok, 'pcs'],
      ]},
    ];
    let stockRows = '';
    stockGroups.forEach(g => {
      stockRows += `<tr><td colspan="3" style="padding:8px 10px 4px;font-size:9px;font-weight:800;color:#A8A29E;text-transform:uppercase;letter-spacing:0.6px;background:#f0ece7;border-bottom:1px solid #e8e3de;">${g.label}</td></tr>`;
      g.rows.forEach(([label, val, unit]) => {
        stockRows += `<tr>
          <td style="padding:6px 10px;font-size:12px;color:#57534E;border-bottom:1px solid #f0ece7;">${label}</td>
          <td style="padding:6px 10px;font-size:12px;font-weight:700;color:#1C1917;text-align:right;border-bottom:1px solid #f0ece7;">${val || val===0 ? val : '—'}</td>
          <td style="padding:6px 10px;font-size:11px;color:#A8A29E;border-bottom:1px solid #f0ece7;">${unit}</td>
        </tr>`;
      });
    });
    const stockSection = `
      <div class="section">
        <div class="stitle">Stok Bahan Baku</div>
        <table style="width:100%;border-collapse:collapse;border-radius:8px;overflow:hidden;border:1px solid #e8e3de;">
          <tbody>${stockRows}</tbody>
        </table>
        <div style="margin-top:10px;display:grid;grid-template-columns:repeat(3,1fr);gap:8px;">
          <div style="background:#f7f3ed;border-radius:7px;padding:9px 12px;">
            <div style="font-size:9px;font-weight:800;color:#A8A29E;text-transform:uppercase;margin-bottom:3px;">Suhu Chiller</div>
            <div style="font-size:13px;font-weight:700;color:${stock.suhu_chiller > 4 ? '#DC2626' : '#1C1917'};">${stock.suhu_chiller != null ? stock.suhu_chiller + '°C' : '—'}</div>
          </div>
          <div style="background:#f7f3ed;border-radius:7px;padding:9px 12px;">
            <div style="font-size:9px;font-weight:800;color:#A8A29E;text-transform:uppercase;margin-bottom:3px;">Suhu Freezer</div>
            <div style="font-size:13px;font-weight:700;color:#1C1917;">${stock.suhu_freezer != null ? stock.suhu_freezer + '°C' : '—'}</div>
          </div>
          <div style="background:#f7f3ed;border-radius:7px;padding:9px 12px;">
            <div style="font-size:9px;font-weight:800;color:#A8A29E;text-transform:uppercase;margin-bottom:3px;">Total Waste</div>
            <div style="font-size:13px;font-weight:700;color:${stock.waste > 200000 ? '#DC2626' : '#1C1917'};">${rp(stock.waste)}</div>
          </div>
        </div>
        <div style="margin-top:8px;display:grid;grid-template-columns:1fr 1fr;gap:8px;">
          <div style="background:#f7f3ed;border-radius:7px;padding:9px 12px;">
            <div style="font-size:9px;font-weight:800;color:#A8A29E;text-transform:uppercase;margin-bottom:4px;">Bahan Expired</div>
            ${statusBadge(stock.expired)}
          </div>
          <div style="background:#f7f3ed;border-radius:7px;padding:9px 12px;">
            <div style="font-size:9px;font-weight:800;color:#A8A29E;text-transform:uppercase;margin-bottom:4px;">Kondisi Stok</div>
            ${statusBadge(stock.overall)}
          </div>
        </div>
        ${stock.notes ? `<div style="margin-top:8px;background:#fef3e2;border:1px solid rgba(232,148,42,0.25);border-radius:7px;padding:9px 12px;"><div style="font-size:9px;font-weight:800;color:#A8A29E;text-transform:uppercase;margin-bottom:3px;">Catatan Stok</div><div style="font-size:12px;color:#1C1917;">${escHtml(stock.notes)}</div></div>` : ''}
      </div>`;

    // ── 5. EVALUASI KARYAWAN ─────────────────────────────────────────────
    let empSection = '';
    const emps = v.employees || [];
    if (emps.length > 0) {
      let empHtml = '';
      emps.forEach(emp => {
        const scores = emp.eval_json || {};
        const total  = EVAL_CRITERIA.reduce((s, c) => s + (scores[c.key] || 0), 0);
        const pct    = Math.round(total / (EVAL_CRITERIA.length * 5) * 100);
        const scoreColor = pct >= 80 ? '#16A34A' : pct >= 60 ? '#D97706' : '#DC2626';
        const critRows = EVAL_CRITERIA.map(c => {
          const score = scores[c.key] || 0;
          const dots  = '●'.repeat(score) + '○'.repeat(5 - score);
          return `<tr>
            <td style="padding:4px 8px;font-size:11px;color:#57534E;">${c.label}</td>
            <td style="padding:4px 8px;font-size:13px;color:#E8942A;letter-spacing:1px;">${dots}</td>
            <td style="padding:4px 8px;font-size:11px;font-weight:700;color:#1C1917;text-align:right;">${score}/5</td>
          </tr>`;
        }).join('');
        empHtml += `
          <div style="background:#f7f3ed;border-radius:10px;padding:12px 14px;margin-bottom:10px;">
            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:10px;">
              <div>
                <div style="font-size:13px;font-weight:800;color:#1C1917;">${escHtml(emp.name)}</div>
                <div style="font-size:10px;color:#A8A29E;font-weight:700;text-transform:uppercase;letter-spacing:0.3px;">${escHtml(emp.role || '—')}</div>
              </div>
              <div style="font-size:22px;font-weight:900;color:${scoreColor};">${pct}%</div>
            </div>
            <table style="width:100%;border-collapse:collapse;">
              <tbody>${critRows}</tbody>
            </table>
            ${emp.notes ? `<div style="margin-top:8px;font-size:11px;color:#57534E;font-style:italic;border-top:1px solid #e8e3de;padding-top:6px;">Catatan: ${escHtml(emp.notes)}</div>` : ''}
          </div>`;
      });

      const avgPct = Math.round(emps.reduce((s, emp) => {
        const scores = emp.eval_json || {};
        return s + EVAL_CRITERIA.reduce((ss, c) => ss + (scores[c.key] || 0), 0);
      }, 0) / (emps.length * EVAL_CRITERIA.length * 5) * 100);

      empSection = `
        <div class="section">
          <div class="stitle">Evaluasi Karyawan · Rata-rata ${avgPct}% · ${emps.length} karyawan</div>
          ${empHtml}
        </div>`;
    }

    // ── 6. CATATAN SDM ────────────────────────────────────────────────────
    const hrSection = `
      <div class="section">
        <div class="stitle">Catatan SDM</div>
        <div style="display:grid;grid-template-columns:repeat(2,1fr);gap:8px;">
          <div style="background:#f7f3ed;border-radius:7px;padding:9px 12px;">
            <div style="font-size:9px;font-weight:800;color:#A8A29E;text-transform:uppercase;margin-bottom:3px;">Karyawan Hadir</div>
            <div style="font-size:13px;font-weight:700;color:#1C1917;">${hr.present || '—'} orang</div>
          </div>
          ${hr.absent ? `<div style="background:#fef3e2;border:1px solid rgba(232,148,42,0.25);border-radius:7px;padding:9px 12px;"><div style="font-size:9px;font-weight:800;color:#A8A29E;text-transform:uppercase;margin-bottom:3px;">Tidak Hadir / Izin</div><div style="font-size:12px;color:#1C1917;">${escHtml(hr.absent)}</div></div>` : ''}
          ${hr.issues ? `<div style="background:#fef2f2;border:1px solid #FECACA;border-radius:7px;padding:9px 12px;grid-column:1/-1;"><div style="font-size:9px;font-weight:800;color:#A8A29E;text-transform:uppercase;margin-bottom:3px;">Isu SDM</div><div style="font-size:12px;color:#1C1917;">${escHtml(hr.issues)}</div></div>` : ''}
          ${hr.action ? `<div style="background:#f7f3ed;border-radius:7px;padding:9px 12px;grid-column:1/-1;"><div style="font-size:9px;font-weight:800;color:#A8A29E;text-transform:uppercase;margin-bottom:3px;">Rekomendasi Tindakan</div><div style="font-size:12px;color:#1C1917;">${escHtml(hr.action)}</div></div>` : ''}
        </div>
      </div>`;

    // ── 7. FOTO ───────────────────────────────────────────────────────────
    let photoHtml = '';
    if (v.photos && v.photos.length > 0) {
      const thumbs = v.photos.map(p => {
        const thumb = p.thumb_path || p.file_path;
        const full  = p.file_path;
        return `<div style="display:flex;flex-direction:column;gap:5px;">
          <img src="/${escHtml(thumb)}" onerror="this.src='/${escHtml(full)}'"
               style="width:100%;aspect-ratio:1;object-fit:cover;border-radius:8px;border:1px solid #e8e3de;cursor:pointer;"
               onclick="window.open('/${escHtml(full)}','_blank')" alt="${escHtml(p.label)}">
          <div style="font-size:10px;text-align:center;color:#57534E;">${escHtml(p.label)}</div>
        </div>`;
      }).join('');
      photoHtml = `<div style="display:grid;grid-template-columns:repeat(4,1fr);gap:10px;">${thumbs}</div>`;
    } else {
      photoHtml = '<p style="color:#A8A29E;font-style:italic;font-size:12px;">Tidak ada foto dalam kunjungan ini.</p>';
    }
    const photoSection = `
      <div class="section">
        <div class="stitle">Dokumentasi Foto Kunjungan (${v.photos ? v.photos.length : 0})</div>
        ${photoHtml}
      </div>`;

    // ── 8. FLAGS (auto-generate dari data) ───────────────────────────────
    const broken = [];
    INVENTORY.forEach(cat => cat.items.forEach((item, i) => {
      const key = cat.cat + '_' + i;
      const st  = invState[key + '_st'] || 'ok';
      if (st === 'broken') broken.push(item.name);
      else if (st === 'warn') broken.push(item.name + ' (perlu cek)');
    }));
    const flags = [];
    if (info.cond_int     === 'broken') flags.push({c:'#DC2626', t:'Kebersihan interior buruk — tindakan segera', s:'Kondisi Umum'});
    if (info.cond_uniform === 'broken') flags.push({c:'#DC2626', t:'Karyawan tidak memakai seragam / hairnet — pelanggaran SOP', s:'Kondisi Umum'});
    if (stock.expired     === 'broken') flags.push({c:'#DC2626', t:'Bahan expired masih digunakan — food safety violation', s:'Stok'});
    if (broken.some(a => a.toLowerCase().includes('cctv interior'))) flags.push({c:'#DC2626', t:'CCTV interior rusak — aset keamanan tidak berfungsi', s:'Inventaris'});
    if (broken.some(a => a.toLowerCase().includes('timbangan')))     flags.push({c:'#DC2626', t:'Timbangan rusak — porsi tidak terkontrol', s:'Inventaris'});
    if (stock.suhu_chiller > 4)  flags.push({c:'#DC2626', t:`Suhu chiller ${stock.suhu_chiller}°C — di atas batas aman 4°C`, s:'Stok'});
    if (stock.overall === 'broken') flags.push({c:'#DC2626', t:'Stok bahan baku dalam kondisi kritis', s:'Stok'});
    if (Math.abs(cashDiff) > 50000) flags.push({c:'#D97706', t:`Selisih kas ${rp(Math.abs(cashDiff))} — perlu investigasi`, s:'Penjualan'});
    if (ach && ach < 70)  flags.push({c:'#D97706', t:`Pencapaian penjualan hanya ${ach}% dari target`, s:'Penjualan'});
    if (stock.waste > 200000) flags.push({c:'#D97706', t:`Total waste ${rp(stock.waste)} — di atas threshold`, s:'Stok'});
    if (broken.length > 3) flags.push({c:'#D97706', t:`${broken.length} aset bermasalah ditemukan di outlet ini`, s:'Inventaris'});
    if (!flags.length) flags.push({c:'#16A34A', t:'Tidak ada red flag signifikan ditemukan saat kunjungan.', s:''});

    const flagItems = flags.map(f =>
      `<div style="display:flex;gap:10px;padding:8px 0;border-bottom:1px solid #f0ece7;">
        <div style="width:8px;height:8px;border-radius:50%;background:${f.c};flex-shrink:0;margin-top:5px;"></div>
        <div>
          <div style="font-size:12px;line-height:1.5;color:#1C1917;">${f.t}</div>
          ${f.s ? `<div style="font-size:10px;color:#A8A29E;margin-top:2px;">${f.s}</div>` : ''}
        </div>
      </div>`
    ).join('');
    const flagSection = `
      <div class="section">
        <div class="stitle">Flag & Temuan Kunjungan</div>
        ${flagItems}
      </div>`;

    // ── Rakit HTML lengkap ────────────────────────────────────────────────
    const html = `<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Laporan Kunjungan SPV — ${escHtml(v.outlet_name)} — ${escHtml(dateStr)}</title>
<style>
*{box-sizing:border-box;margin:0;padding:0;}
body{font-family:'Segoe UI',Arial,sans-serif;font-size:13px;color:#1C1917;background:#f5f5f0;padding:24px;}
.no-print{display:flex;gap:10px;justify-content:flex-end;margin-bottom:20px;}
.btn-print{background:#E8942A;border:none;border-radius:8px;color:#fff;font-size:13px;font-weight:700;padding:9px 20px;cursor:pointer;}
.btn-back{background:#fff;border:1px solid #d0ccc8;border-radius:8px;color:#57534E;font-size:13px;font-weight:700;padding:9px 20px;cursor:pointer;text-decoration:none;}
.report{background:#fff;max-width:800px;margin:0 auto;padding:36px 40px;border-radius:14px;box-shadow:0 2px 20px rgba(0,0,0,0.08);}
.rh{display:flex;justify-content:space-between;align-items:flex-start;padding-bottom:18px;border-bottom:2px solid #1C1917;margin-bottom:22px;gap:16px;}
.brand{font-size:18px;font-weight:900;color:#1C1917;}
.brand-sub{font-size:10px;color:#A8A29E;font-weight:700;letter-spacing:1px;text-transform:uppercase;margin-top:2px;}
.doctype{font-size:15px;font-weight:800;color:#E8942A;text-align:right;}
.docsub{font-size:11px;color:#A8A29E;text-align:right;margin-top:3px;}
.outlet-title{font-size:22px;font-weight:900;color:#1C1917;margin-bottom:4px;}
.outlet-sub{font-size:12px;color:#A8A29E;font-weight:600;margin-bottom:20px;}
.info-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:12px;background:#f7f3ed;border-radius:10px;padding:16px;margin-bottom:24px;}
.il{font-size:9px;font-weight:800;color:#A8A29E;text-transform:uppercase;letter-spacing:0.5px;}
.iv{font-size:13px;font-weight:700;color:#1C1917;}
.section{margin-bottom:22px;}
.stitle{font-size:10px;font-weight:800;text-transform:uppercase;letter-spacing:1px;color:#A8A29E;border-bottom:1px solid #e8e3de;padding-bottom:6px;margin-bottom:12px;}
.footer{margin-top:28px;padding-top:14px;border-top:1px solid #e8e3de;display:flex;justify-content:space-between;align-items:flex-end;font-size:10px;color:#A8A29E;}
.sig{text-align:center;}.sigline{width:140px;border-bottom:1px solid #1C1917;margin:40px auto 6px;}
.signame{font-size:11px;font-weight:700;color:#1C1917;}.sigrole{font-size:9px;color:#A8A29E;}
@media print{
  body{background:#fff;padding:0;}
  .no-print{display:none!important;}
  .report{box-shadow:none;border-radius:0;padding:20px 24px;max-width:100%;}
}
</style>
</head>
<body>
<div class="no-print">
  <button class="btn-back" onclick="window.close()">✕ Tutup</button>
  <button class="btn-print" onclick="window.print()">🖨️ Print / Save PDF</button>
</div>
<div class="report">
  <!-- Header -->
  <div class="rh">
    <div>
      <div style="display:flex;align-items:center;gap:10px;">
        <div style="width:40px;height:40px;border-radius:9px;background:#2C1A0E;display:flex;align-items:center;justify-content:center;color:#fff;font-weight:900;font-size:14px;flex-shrink:0;">SS</div>
        <div>
          <div class="brand">SS Operations</div>
          <div class="brand-sub">Suka Shawarma · F&amp;B Operations</div>
        </div>
      </div>
    </div>
    <div>
      <div class="doctype">Laporan Kunjungan SPV</div>
      <div class="docsub">ID #${id} · Dicetak: ${new Date().toLocaleString('id-ID')}</div>
    </div>
  </div>

  <!-- Outlet + Info -->
  <div class="outlet-title">${escHtml(v.outlet_name)}</div>
  <div class="outlet-sub">Kode: ${escHtml(v.outlet_code || '—')}</div>
  <div class="info-grid">
    <div><div class="il">Tanggal Kunjungan</div><div class="iv">${escHtml(dateStr)}</div></div>
    <div><div class="il">Shift</div><div class="iv">${escHtml(shiftStr)}</div></div>
    <div><div class="il">Supervisor</div><div class="iv">${escHtml(v.spv_name || '—')}</div></div>
    <div><div class="il">Waktu Tiba</div><div class="iv">${v.time_arrive ? v.time_arrive.substring(0,5) : '—'}</div></div>
    <div><div class="il">Waktu Keluar</div><div class="iv">${v.time_leave ? v.time_leave.substring(0,5) : '—'}</div></div>
    <div><div class="il">PIC Bertugas</div><div class="iv">${escHtml(v.pic_on_duty || '—')}</div></div>
  </div>

  ${condSection}
  ${invSection}
  ${salesSection}
  ${stockSection}
  ${empSection}
  ${hrSection}
  ${photoSection}
  ${flagSection}

  <!-- Footer -->
  <div class="footer">
    <div>
      <div>Dikirim: ${v.submitted_at ? new Date(v.submitted_at).toLocaleString('id-ID') : '—'}</div>
      <div style="margin-top:3px;">Dokumen ini dibuat otomatis oleh SS Operations.</div>
    </div>
    <div class="sig">
      <div class="sigline"></div>
      <div class="signame">${escHtml(v.spv_name || '—')}</div>
      <div class="sigrole">Supervisor</div>
    </div>
  </div>
</div>
</body></html>`;

    win.document.open();
    win.document.write(html);
    win.document.close();
    win.document.title = 'Laporan SPV — ' + v.outlet_name + ' — ' + dateStr;

  } catch(e) {
    win.document.open();
    win.document.write('<html><body style="font-family:sans-serif;padding:24px;color:#c00">Gagal memuat laporan: ' + e.message + '</body></html>');
    win.document.close();
  }
}

// Load on start
loadVisits(1);
</script>
</body>
</html>
