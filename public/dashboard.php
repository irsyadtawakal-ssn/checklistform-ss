<?php
declare(strict_types=1);

require_once __DIR__ . '/../src/bootstrap.php';
require_once ROOT_PATH . '/src/helpers/auth.php';
require_once ROOT_PATH . '/src/helpers/db.php';
require_once ROOT_PATH . '/src/middleware/page.php';
require_once ROOT_PATH . '/src/helpers/csrf.php';

$user = pageRequireRole('spv', 'owner', 'admin');

// Ambil semua outlet aktif untuk filter dropdown
$outletRows = db()->query("SELECT id, code, name, type FROM outlets WHERE active = 1 ORDER BY type, code")->fetchAll();
$outletsByType = ['internal' => [], 'mitra' => []];
foreach ($outletRows as $o) { $outletsByType[$o['type']][] = $o; }

// Load critical items dari JSON untuk drill-down modal
$checklistData = json_decode(file_get_contents(ROOT_PATH . '/assets/data/checklist.json'), true);
$criticalItems = ['open' => [], 'ops' => [], 'close' => []];
foreach (['open', 'ops', 'close'] as $sh) {
    foreach ($checklistData['checklist'][$sh] as $section) {
        foreach ($section['items'] as $item) {
            if (($item['badge'] ?? '') === 'ibadge-crit') {
                $criticalItems[$sh][] = ['id' => $item['id'], 'text' => $item['text']];
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="csrf-token" content="<?= htmlspecialchars(csrfToken()) ?>">
<title>Dashboard Kepatuhan · SS Operations</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=Nunito:wght@400;600;700;800;900&family=Fraunces:opsz,wght@9..144,400;9..144,600;9..144,700&family=Inter:wght@400;500;600&family=Geist+Mono:wght@400;500&display=swap" rel="stylesheet">
<link rel="stylesheet" href="/assets/css/ds.css">
<style>
@import url('https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,400;9..144,600;9..144,700&family=Inter:wght@400;500;600&family=Geist+Mono:wght@400;500&display=swap');

:root {
  --bg:       #F7F3ED;
  --surface:  #FFFFFF;
  --border:   rgba(28,25,23,0.10);
  --border2:  rgba(28,25,23,0.16);
  --ink:      #1C1917;
  --ink2:     #57534E;
  --ink3:     #A8A29E;
  --saffron:  #E8942A;
  --saf-bg:   #FEF3E2;
  --saf-border:rgba(232,148,42,0.30);
  --ok:       #16A34A;
  --ok-bg:    #F0FDF4;
  --ok-border:#BBF7D0;
  --warn:     #D97706;
  --warn-bg:  #FFFBEB;
  --warn-border:#FDE68A;
  --danger:   #DC2626;
  --danger-bg:#FEF2F2;
  --danger-border:#FECACA;
  --idle:     #A8A29E;
  --idle-bg:  #F5F5F4;
  --idle-border:#E7E5E4;
  --radius:   10px;
  --radius-lg:14px;
  --shadow:   0 1px 3px rgba(0,0,0,0.08), 0 1px 2px rgba(0,0,0,0.05);
  --shadow-lg:0 4px 16px rgba(0,0,0,0.10), 0 2px 6px rgba(0,0,0,0.06);
}
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
html { font-size: 14px; }
body { background: var(--bg); color: var(--ink); font-family: 'Inter', sans-serif; min-height: 100vh; }

/* ── Header ──────────────────────────────────────────────────────────── */
.app-header {
  position: sticky; top: 0; z-index: 200;
  background: var(--surface); border-bottom: 1px solid var(--border);
  height: 60px; display: flex; align-items: center;
  padding: 0 24px; gap: 16px;
}
.brand { display: flex; align-items: center; gap: 10px; text-decoration: none; color: inherit; }
.brand-mark {
  width: 36px; height: 36px; background: var(--saffron); border-radius: 9px;
  display: flex; align-items: center; justify-content: center;
  font-family: 'Fraunces', serif; font-weight: 700; font-size: 14px; color: #fff; letter-spacing: -0.5px;
}
.brand-title { font-family: 'Fraunces', serif; font-weight: 600; font-size: 16px; line-height: 1; }
.brand-sub { font-size: 10px; color: var(--ink3); font-family: 'Geist Mono', monospace; }
.header-spacer { flex: 1; }
.header-user {
  display: flex; align-items: center; gap: 8px;
  font-size: 12px; color: var(--ink2);
}
.role-badge {
  font-size: 10px; font-family: 'Geist Mono', monospace; padding: 2px 8px;
  border-radius: 99px; border: 1px solid var(--border2); background: var(--bg);
  text-transform: uppercase; letter-spacing: 0.05em;
}
.btn-logout {
  display: flex; align-items: center; gap: 5px; padding: 6px 12px;
  border: 1px solid var(--border2); border-radius: 7px; background: transparent;
  color: var(--ink2); font-size: 12px; cursor: pointer; font-family: inherit;
  transition: all 0.15s;
}
.btn-logout:hover { background: var(--bg); color: var(--ink); }

/* ── Layout ──────────────────────────────────────────────────────────── */
.layout { display: flex; min-height: calc(100vh - 60px); }
.sidebar {
  width: 260px; flex-shrink: 0; background: var(--surface);
  border-right: 1px solid var(--border); padding: 20px 16px;
  position: sticky; top: 60px; height: calc(100vh - 60px); overflow-y: auto;
}
.main-content { flex: 1; padding: 24px; overflow-x: auto; min-width: 0; }

/* ── Sidebar filters ─────────────────────────────────────────────────── */
.sidebar-section { margin-bottom: 20px; }
.sidebar-label {
  font-size: 10px; font-family: 'Geist Mono', monospace; color: var(--ink3);
  text-transform: uppercase; letter-spacing: 0.08em; margin-bottom: 8px; display: block;
}
.filter-control {
  width: 100%; padding: 8px 10px; border: 1px solid var(--border2);
  border-radius: 8px; background: var(--bg); color: var(--ink);
  font-family: inherit; font-size: 13px; outline: none; transition: border-color 0.15s;
}
.filter-control:focus { border-color: var(--saffron); }
.filter-row { display: flex; gap: 6px; }
.filter-row .filter-control { flex: 1; min-width: 0; }
.shift-btns { display: flex; gap: 4px; flex-wrap: wrap; }
.shift-btn {
  flex: 1; padding: 6px 4px; border: 1px solid var(--border2); border-radius: 7px;
  background: var(--bg); color: var(--ink2); font-size: 11px; font-family: 'Geist Mono', monospace;
  cursor: pointer; text-align: center; transition: all 0.15s; font-weight: 500;
  text-transform: uppercase; letter-spacing: 0.04em;
}
.shift-btn.active { background: var(--saffron); border-color: var(--saffron); color: #fff; }
.btn-apply {
  width: 100%; padding: 9px; border: none; border-radius: 8px;
  background: var(--saffron); color: #fff; font-family: 'Fraunces', serif;
  font-size: 14px; font-weight: 600; cursor: pointer; transition: opacity 0.15s;
}
.btn-apply:hover { opacity: 0.88; }
.sidebar-divider { border: none; border-top: 1px solid var(--border); margin: 16px 0; }
.btn-export {
  width: 100%; padding: 8px; border: 1px solid var(--border2); border-radius: 8px;
  background: transparent; color: var(--ink2); font-family: inherit; font-size: 12px;
  cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 6px;
  transition: all 0.15s;
}
.btn-export:hover { background: var(--bg); color: var(--ink); }

/* ── KPI Cards ───────────────────────────────────────────────────────── */
.kpi-strip { display: grid; grid-template-columns: repeat(4, 1fr); gap: 12px; margin-bottom: 24px; }
.kpi-card {
  background: var(--surface); border: 1px solid var(--border);
  border-radius: var(--radius-lg); padding: 16px; box-shadow: var(--shadow);
}
.kpi-label { font-size: 10px; font-family: 'Geist Mono', monospace; color: var(--ink3); text-transform: uppercase; letter-spacing: 0.08em; margin-bottom: 8px; }
.kpi-value { font-family: 'Fraunces', serif; font-size: 32px; font-weight: 700; line-height: 1; color: var(--ink); }
.kpi-value.ok-color { color: var(--ok); }
.kpi-value.danger-color { color: var(--danger); }
.kpi-value.warn-color { color: var(--warn); }
.kpi-sub { font-size: 11px; color: var(--ink3); margin-top: 4px; }

/* ── Section header ──────────────────────────────────────────────────── */
.section-header {
  display: flex; align-items: baseline; gap: 10px; margin-bottom: 14px;
}
.section-title { font-family: 'Fraunces', serif; font-size: 18px; font-weight: 600; }
.section-meta { font-size: 11px; color: var(--ink3); font-family: 'Geist Mono', monospace; }

/* ── Compliance Grid ─────────────────────────────────────────────────── */
.grid-wrap {
  background: var(--surface); border: 1px solid var(--border);
  border-radius: var(--radius-lg); overflow: auto; box-shadow: var(--shadow);
  margin-bottom: 28px; max-height: 600px;
}
.grid-table { border-collapse: collapse; width: max-content; min-width: 100%; }
.grid-table thead th {
  background: var(--bg); border-bottom: 1px solid var(--border2);
  padding: 0; font-weight: 500; position: sticky; top: 0; z-index: 10;
}
.grid-table thead th.outlet-th {
  width: 180px; min-width: 180px; text-align: left;
  padding: 10px 14px; font-size: 11px; color: var(--ink3);
  font-family: 'Geist Mono', monospace; text-transform: uppercase; letter-spacing: 0.05em;
  position: sticky; left: 0; z-index: 20; border-right: 1px solid var(--border2);
}
.date-th-group { border-left: 1px solid var(--border); }
.date-th-label {
  font-size: 10px; color: var(--ink2); text-align: center;
  padding: 6px 4px 2px; font-family: 'Geist Mono', monospace; font-weight: 500;
  border-bottom: 1px solid var(--border);
}
.shift-th-row { display: flex; }
.shift-sub-th {
  width: 42px; text-align: center; font-size: 9px; color: var(--ink3);
  padding: 3px 0 5px; font-family: 'Geist Mono', monospace; text-transform: uppercase;
}
.grid-table tbody tr:hover td { background: rgba(232,148,42,0.04); }
.grid-table tbody td {
  border-bottom: 1px solid var(--border); padding: 0; vertical-align: middle;
}
.grid-table tbody td.outlet-td {
  position: sticky; left: 0; z-index: 5; background: var(--surface);
  border-right: 1px solid var(--border2); padding: 8px 14px; width: 180px; min-width: 180px;
}
.grid-table tbody tr:hover td.outlet-td { background: var(--bg); }
.outlet-code { font-size: 10px; font-family: 'Geist Mono', monospace; color: var(--ink3); }
.outlet-name { font-size: 12px; font-weight: 500; color: var(--ink); margin-top: 1px; }
.date-cell-group { display: flex; border-left: 1px solid var(--border); }
.shift-td { padding: 0; border-left: 1px solid var(--border); text-align: center; }
.shift-td:first-of-type { border-left: 1px solid var(--border2); }
.shift-cell {
  width: 42px; height: 52px; display: flex; align-items: center; justify-content: center;
  cursor: pointer; transition: opacity 0.15s; border: none; background: transparent;
  position: relative;
}
.shift-cell:hover { opacity: 0.75; }
.shift-cell.idle { cursor: default; }
.status-dot {
  width: 18px; height: 18px; border-radius: 5px;
  display: flex; align-items: center; justify-content: center; font-size: 8px;
}
.status-dot.ok      { background: var(--ok-bg); border: 1.5px solid var(--ok-border); }
.status-dot.warn    { background: var(--warn-bg); border: 1.5px solid var(--warn-border); }
.status-dot.danger  { background: var(--danger-bg); border: 1.5px solid var(--danger-border); }
.status-dot.idle    { background: var(--idle-bg); border: 1.5px solid var(--idle-border); }
.status-dot.ok::after    { content:''; width:6px; height:6px; border-radius:50%; background:var(--ok); }
.status-dot.warn::after  { content:''; width:6px; height:6px; border-radius:50%; background:var(--warn); }
.status-dot.danger::after{ content:''; width:6px; height:6px; border-radius:50%; background:var(--danger); }

/* ── Grid legend ─────────────────────────────────────────────────────── */
.grid-legend {
  display: flex; gap: 16px; margin-bottom: 14px; flex-wrap: wrap; align-items: center;
}
.legend-item { display: flex; align-items: center; gap: 5px; font-size: 11px; color: var(--ink2); }
.legend-dot { width: 10px; height: 10px; border-radius: 3px; }
.legend-dot.ok { background: var(--ok-bg); border: 1.5px solid var(--ok-border); }
.legend-dot.warn { background: var(--warn-bg); border: 1.5px solid var(--warn-border); }
.legend-dot.danger { background: var(--danger-bg); border: 1.5px solid var(--danger-border); }
.legend-dot.idle { background: var(--idle-bg); border: 1.5px solid var(--idle-border); }

/* ── Rankings ────────────────────────────────────────────────────────── */
.rankings-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 28px; }
.ranking-card {
  background: var(--surface); border: 1px solid var(--border);
  border-radius: var(--radius-lg); overflow: hidden; box-shadow: var(--shadow);
}
.ranking-card-header {
  padding: 12px 16px; border-bottom: 1px solid var(--border);
  display: flex; align-items: center; gap: 8px;
}
.ranking-card-title { font-family: 'Fraunces', serif; font-size: 14px; font-weight: 600; }
.ranking-badge { font-size: 10px; padding: 2px 8px; border-radius: 99px; font-family: 'Geist Mono', monospace; }
.ranking-badge.top  { background: var(--ok-bg); color: var(--ok); border: 1px solid var(--ok-border); }
.ranking-badge.bot  { background: var(--danger-bg); color: var(--danger); border: 1px solid var(--danger-border); }
.ranking-row {
  display: flex; align-items: center; padding: 10px 16px;
  border-bottom: 1px solid var(--border); gap: 10px;
}
.ranking-row:last-child { border-bottom: none; }
.rank-num { font-family: 'Geist Mono', monospace; font-size: 11px; color: var(--ink3); width: 18px; flex-shrink: 0; }
.rank-outlet { flex: 1; min-width: 0; }
.rank-code { font-size: 10px; font-family: 'Geist Mono', monospace; color: var(--ink3); }
.rank-name { font-size: 12px; font-weight: 500; color: var(--ink); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.rank-pct {
  font-family: 'Fraunces', serif; font-size: 18px; font-weight: 700;
  flex-shrink: 0; min-width: 48px; text-align: right;
}
.rank-bar { width: 60px; height: 4px; background: var(--idle-bg); border-radius: 99px; flex-shrink: 0; overflow: hidden; }
.rank-bar-fill { height: 100%; border-radius: 99px; }
.rank-pct.ok-color { color: var(--ok); }
.rank-pct.warn-color { color: var(--warn); }
.rank-pct.danger-color { color: var(--danger); }
.rank-pct.none-color { color: var(--ink3); }

/* ── Loading / Empty ─────────────────────────────────────────────────── */
.loading-state {
  display: flex; align-items: center; justify-content: center;
  min-height: 200px; color: var(--ink3);
  font-family: 'Geist Mono', monospace; font-size: 12px;
}
.empty-state { text-align: center; padding: 40px 20px; color: var(--ink3); }
.empty-state p { font-size: 13px; margin-top: 6px; }

/* ── Modal ───────────────────────────────────────────────────────────── */
.modal-overlay {
  position: fixed; inset: 0; background: rgba(28,25,23,0.50);
  z-index: 500; display: none; align-items: center; justify-content: center;
  padding: 20px;
}
.modal-overlay.open { display: flex; }
.modal {
  background: var(--surface); border-radius: var(--radius-lg); box-shadow: var(--shadow-lg);
  width: 100%; max-width: 480px; max-height: 85vh; overflow-y: auto;
  animation: modal-in 0.18s ease;
}
@keyframes modal-in { from { opacity:0; transform:translateY(12px); } to { opacity:1; transform:translateY(0); } }
.modal-header {
  display: flex; align-items: flex-start; justify-content: space-between;
  padding: 18px 20px 14px; border-bottom: 1px solid var(--border);
}
.modal-title { font-family: 'Fraunces', serif; font-size: 17px; font-weight: 600; }
.modal-subtitle { font-size: 11px; color: var(--ink3); margin-top: 2px; font-family: 'Geist Mono', monospace; }
.modal-close {
  width: 28px; height: 28px; border: 1px solid var(--border2); border-radius: 6px;
  background: var(--bg); color: var(--ink2); cursor: pointer; font-size: 14px;
  display: flex; align-items: center; justify-content: center; flex-shrink: 0; margin-left: 12px;
}
.modal-body { padding: 18px 20px; }
.detail-row {
  display: flex; align-items: baseline; gap: 8px;
  padding: 7px 0; border-bottom: 1px solid var(--border);
}
.detail-row:last-child { border-bottom: none; }
.detail-key { font-size: 11px; color: var(--ink3); width: 100px; flex-shrink: 0; }
.detail-val { font-size: 13px; font-weight: 500; color: var(--ink); }
.detail-val.mono { font-family: 'Geist Mono', monospace; font-size: 12px; }
.status-tag {
  display: inline-flex; align-items: center; gap: 5px; padding: 3px 10px;
  border-radius: 99px; font-size: 11px; font-weight: 600; border: 1px solid;
}
.status-tag.ok    { background: var(--ok-bg);     color: var(--ok);     border-color: var(--ok-border);     }
.status-tag.warn  { background: var(--warn-bg);   color: var(--warn);   border-color: var(--warn-border);   }
.status-tag.danger{ background: var(--danger-bg); color: var(--danger); border-color: var(--danger-border); }
.compliance-bar { margin: 14px 0 8px; }
.cb-row { display: flex; justify-content: space-between; align-items: baseline; margin-bottom: 5px; }
.cb-label { font-size: 11px; color: var(--ink3); }
.cb-pct { font-family: 'Fraunces', serif; font-size: 16px; font-weight: 700; }
.cb-track { height: 6px; background: var(--idle-bg); border-radius: 99px; overflow: hidden; }
.cb-fill { height: 100%; border-radius: 99px; transition: width 0.4s; }
.missed-section { margin-top: 14px; }
.missed-title { font-size: 11px; font-weight: 600; color: var(--danger); margin-bottom: 8px; }
.missed-item {
  display: flex; align-items: flex-start; gap: 8px; padding: 6px 0;
  border-bottom: 1px solid var(--border); font-size: 12px; color: var(--ink2);
}
.missed-item:last-child { border-bottom: none; }
.missed-bullet { width: 16px; height: 16px; border-radius: 4px; background: var(--danger-bg); border: 1.5px solid var(--danger-border); flex-shrink: 0; margin-top: 1px; }
.modal-footer {
  padding: 14px 20px; border-top: 1px solid var(--border);
  display: flex; gap: 8px; justify-content: flex-end;
}
.btn-unlock {
  padding: 8px 16px; background: var(--saffron); color: #fff; border: none;
  border-radius: 8px; font-family: inherit; font-size: 13px; font-weight: 600;
  cursor: pointer; display: flex; align-items: center; gap: 6px; transition: opacity 0.15s;
}
.btn-unlock:hover { opacity: 0.85; }
.btn-unlock:disabled { opacity: 0.5; cursor: default; }
.btn-cancel {
  padding: 8px 14px; background: transparent; color: var(--ink2); border: 1px solid var(--border2);
  border-radius: 8px; font-family: inherit; font-size: 13px; cursor: pointer; transition: all 0.15s;
}
.btn-cancel:hover { background: var(--bg); }

/* ── Toast ───────────────────────────────────────────────────────────── */
.toast-wrap { position: fixed; bottom: 24px; left: 50%; transform: translateX(-50%); z-index: 9000; display: flex; flex-direction: column; gap: 8px; pointer-events: none; }
.toast {
  background: var(--ink); color: #fff; padding: 10px 18px; border-radius: 10px;
  font-size: 13px; box-shadow: var(--shadow-lg); animation: toast-in 0.2s ease;
  max-width: 360px; text-align: center;
}
.toast.success { background: var(--ok); }
.toast.error   { background: var(--danger); }
@keyframes toast-in { from { opacity:0; transform:translateY(10px); } }

/* ── Desktop header ──────────────────────────────────────────────────── */
@media (min-width: 768px) {
  .ds-app-header {
    padding: 0 32px 0;
    box-shadow: 0 2px 16px rgba(32,5,0,0.35);
  }
  .ds-header-row {
    height: 64px;
    padding: 0;
    align-items: center;
  }
  .ds-header-logo {
    width: 38px;
    height: 38px;
    border-radius: 10px;
    padding: 4px;
  }
  .ds-header-brand-text {
    font-size: 22px;
    letter-spacing: 4px;
  }
  .ds-header-actions {
    gap: 10px;
    align-items: center;
  }
  /* Tombol aksi di header desktop */
  .ds-header-actions a {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    background: rgba(255,255,255,0.13);
    border: 1px solid rgba(255,255,255,0.20);
    border-radius: 10px;
    color: rgba(255,255,255,0.90);
    font-family: var(--font-body);
    font-size: 12px;
    font-weight: 700;
    padding: 7px 14px;
    text-decoration: none;
    transition: background 0.15s;
    white-space: nowrap;
  }
  .ds-header-actions a:hover {
    background: rgba(255,255,255,0.22);
  }
  /* Nama user */
  .ds-header-actions > div {
    display: flex;
    align-items: center;
    gap: 7px;
    font-size: 12px;
    font-weight: 700;
    color: rgba(255,255,255,0.70);
  }
  /* Badge role */
  .ds-header-actions > div > span:last-child {
    font-size: 10px;
    background: rgba(232,146,74,0.25);
    border: 1px solid rgba(232,146,74,0.40);
    border-radius: 6px;
    padding: 3px 8px;
    color: #F4B07A;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 0.5px;
  }
  /* Tombol keluar */
  .ds-header-actions button {
    background: rgba(255,255,255,0.10);
    border: 1px solid rgba(255,255,255,0.18);
    border-radius: 10px;
    color: rgba(255,255,255,0.80);
    font-family: var(--font-body);
    font-size: 12px;
    font-weight: 700;
    padding: 7px 14px;
    cursor: pointer;
    transition: background 0.15s;
  }
  .ds-header-actions button:hover {
    background: rgba(255,255,255,0.18);
    color: #fff;
  }
}

/* ── Responsive ──────────────────────────────────────────────────────── */
@media (max-width: 900px) {
  .sidebar { width: 220px; }
  .kpi-strip { grid-template-columns: repeat(2, 1fr); }
  .rankings-grid { grid-template-columns: 1fr; }
}
@media (max-width: 640px) {
  .sidebar { display: none; }
  .main-content { padding: 16px; }
  .kpi-strip { grid-template-columns: repeat(2, 1fr); gap: 8px; }
}
@media (max-width: 767px) {
  #mobile-view { display: block !important; }
  .layout      { display: none !important; }

  /* ── Header mobile fix ── */
  .ds-app-header {
    padding: 10px 16px 12px;
  }
  .ds-header-row {
    flex-wrap: wrap;
    gap: 6px;
    padding: 0;
  }
  .ds-header-brand {
    flex: 1;
    min-width: 0;
    padding: 4px 0;
  }
  .ds-header-actions {
    width: 100%;
    gap: 6px;
    flex-wrap: nowrap;
    overflow-x: auto;
    -webkit-overflow-scrolling: touch;
    scrollbar-width: none;
    padding: 2px 0 4px 0;
  }
  .ds-header-actions::-webkit-scrollbar { display: none; }

  /* Sembunyikan nama lengkap — cukup role badge saja */
  .ds-header-actions > div > span:first-child { display: none !important; }

  /* Tombol lebih kecil & rapat di mobile */
  .ds-header-actions a,
  .ds-header-actions button {
    padding: 6px 11px !important;
    font-size: 11px !important;
    white-space: nowrap;
    flex-shrink: 0;
    border-radius: 8px !important;
  }
}
.mv-wrap { padding: 12px 14px 80px; max-width: 430px; margin: 0 auto; font-family: var(--font-body); }
.mv-mini-stats { display: flex; gap: 8px; margin-bottom: 14px; }
.mv-stat { background: rgba(122,18,0,0.07); border: 1px solid var(--border); border-radius: 12px; padding: 10px 8px; flex: 1; text-align: center; }
.mv-stat-num { font-family: var(--font-brand); font-size: 28px; color: var(--text); line-height: 1; }
.mv-stat-lbl { font-size: 9px; font-weight: 800; color: var(--muted); text-transform: uppercase; letter-spacing: 0.5px; margin-top: 2px; }
.mv-outlet-list { display: flex; flex-direction: column; gap: 9px; }
.mv-ol-card { background: white; border-radius: 16px; padding: 13px 14px; box-shadow: 0 2px 10px rgba(0,0,0,.06); border-left: 4px solid; }
.mv-ol-card.ok { border-left-color: #43A047; } .mv-ol-card.warn { border-left-color: #E8924A; } .mv-ol-card.crit { border-left-color: #7A1200; } .mv-ol-card.idle { border-left-color: #E8D5C8; }
.mv-ol-top { display: flex; align-items: flex-start; justify-content: space-between; margin-bottom: 9px; }
.mv-ol-name { font-size: 14px; font-weight: 800; color: #200500; }
.mv-ol-sub  { font-size: 11px; color: #6B4535; font-weight: 600; margin-top: 1px; }
.mv-badge { padding: 3px 9px; border-radius: 20px; font-size: 10px; font-weight: 900; white-space: nowrap; }
.mv-badge.ok { background:#E8F5E9; color:#2E7D32; } .mv-badge.warn { background:#FFFDE7; color:#E65100; } .mv-badge.crit { background:#FFEBEE; color:#7A1200; } .mv-badge.idle { background:#F5F5F5; color:#6B4535; }
.mv-ol-bar-row { display: flex; align-items: center; gap: 8px; }
.mv-ol-bar-bg { flex: 1; height: 6px; background: #F0F0F0; border-radius: 3px; overflow: hidden; }
.mv-ol-bar-fill { height: 100%; border-radius: 3px; }
.f-ok { background: linear-gradient(90deg,#43A047,#76D275); } .f-warn { background: linear-gradient(90deg,#FFB300,#FFD740); } .f-crit { background: linear-gradient(90deg,#E53935,#FF5252); } .f-idle { background:#E0E0E0; }
.mv-ol-pct { font-size: 14px; font-weight: 900; min-width: 36px; text-align: right; }
.p-ok{color:#2E7D32;} .p-warn{color:#E65100;} .p-crit{color:#7A1200;} .p-idle{color:#6B4535;}
.mv-ol-footer { margin-top: 7px; display: flex; gap: 6px; flex-wrap: wrap; }
.mv-chip { padding: 2px 8px; border-radius: 20px; font-size: 9px; font-weight: 800; }
.mv-chip.done{background:#E8F5E9;color:#2E7D32;} .mv-chip.miss{background:#FFEBEE;color:#7A1200;} .mv-chip.pend{background:#FFF8E1;color:#E65100;}
.mv-big-stats { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 8px; margin-bottom: 12px; }
.mv-bs-card { border-radius: 16px; padding: 14px 8px; text-align: center; position: relative; overflow: hidden; }
.mv-bs-card::before { content:''; position:absolute; top:-26px; right:-26px; width:72px; height:72px; border-radius:50%; background:rgba(255,255,255,.07); }
.mv-bs-aman{background:linear-gradient(145deg,#1B5E20,#2E7D32);} .mv-bs-warn{background:linear-gradient(145deg,#C47030,#E8924A);} .mv-bs-krit{background:linear-gradient(145deg,#200500,#7A1200);}
.mv-bs-num{font-family:var(--font-brand);font-size:44px;color:white;line-height:1;margin-bottom:2px;} .mv-bs-lbl{font-size:9px;font-weight:900;color:rgba(255,255,255,.70);text-transform:uppercase;letter-spacing:.5px;}
.mv-chart-wrap{display:flex;align-items:flex-end;gap:5px;height:82px;}
.mv-c-col{flex:1;display:flex;flex-direction:column;align-items:center;height:100%;justify-content:flex-end;gap:4px;}
.mv-c-val{font-size:7px;font-weight:900;color:#6B4535;} .mv-c-bar{width:100%;border-radius:4px 4px 0 0;background:linear-gradient(180deg,#8B1500,#7A1200);}
.mv-c-bar.today{background:linear-gradient(180deg,#F4B07A,#E8924A);} .mv-c-day{font-size:8px;font-weight:800;color:#6B4535;}
.mv-top-row{display:flex;align-items:center;gap:10px;padding:7px 0;border-bottom:1px solid #F5F5F5;}
.mv-top-row:last-child{border-bottom:none;}
.mv-rank-badge{width:22px;height:22px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:10px;font-weight:900;flex-shrink:0;}
.mv-r1{background:#E8924A;color:#5D3200;} .mv-r2{background:#9E9E9E;color:white;} .mv-r3{background:#A1887F;color:white;} .mv-rn{background:#F5F5F5;color:#6B4535;}
.mv-rank-name{flex:1;font-size:12px;font-weight:700;color:#200500;} .mv-rank-pct{font-size:13px;font-weight:900;color:#2E7D32;}
.mv-alert-row{display:flex;align-items:center;gap:10px;background:#FFEBEE;border-radius:10px;padding:10px 12px;margin-bottom:7px;}
.mv-alert-row:last-child{margin-bottom:0;} .mv-alert-dot{width:7px;height:7px;border-radius:50%;background:#7A1200;flex-shrink:0;}
.mv-alert-txt{flex:1;font-size:12px;font-weight:700;color:#B71C1C;}
.mv-comp-row{display:flex;align-items:flex-end;justify-content:space-between;}
.mv-comp-big{font-family:var(--font-brand);font-size:56px;color:#200500;line-height:1;}
.mv-comp-pct-sign{font-size:22px;font-weight:900;color:#6B4535;}
.mv-comp-trend{font-size:13px;font-weight:800;color:#2E7D32;} .mv-comp-sub{font-size:10px;color:#6B4535;font-weight:600;}
.mv-comp-bar-bg{height:6px;background:#F0F0F0;border-radius:3px;overflow:hidden;margin-top:8px;}
.mv-comp-bar-fill{height:100%;background:linear-gradient(90deg,#2E7D32,#43A047);border-radius:3px;transition:width .4s;}
</style>
</head>
<body>

<!-- ── Header DS v2 ── -->
<header class="ds-app-header" style="position:sticky;top:0;z-index:200;">
  <div class="ds-header-row">
    <div class="ds-header-brand">
      <div class="ds-header-logo">
        <img src="/assets/logo.png" alt="SS"
             onerror="this.style.display='none'; this.nextElementSibling.style.display='block'">
        <span class="ds-header-logo-fallback" style="display:none">SS</span>
      </div>
      <div>
        <div class="ds-header-brand-text">SS OPS</div>
        <div style="font-size:10px;color:rgba(255,255,255,0.50);font-family:var(--font-body);font-weight:700;letter-spacing:1px;line-height:1;margin-top:1px;">DASHBOARD</div>
      </div>
    </div>
    <div class="ds-header-actions">
      <?php if ($user['role'] === 'spv' || $user['role'] === 'admin'): ?>
      <a href="/spv-visits-history" style="display:flex;align-items:center;gap:5px;background:rgba(255,255,255,0.15);border:1px solid rgba(255,255,255,0.22);border-radius:10px;color:rgba(255,255,255,0.90);font-family:var(--font-body);font-size:11px;font-weight:800;padding:6px 12px;text-decoration:none;">
        📋 Rekap Kunjungan
      </a>
      <?php endif; ?>
      <?php if ($user['role'] === 'spv'): ?>
      <a href="/spv-visit" style="display:flex;align-items:center;gap:5px;background:rgba(255,255,255,0.15);border:1px solid rgba(255,255,255,0.22);border-radius:10px;color:rgba(255,255,255,0.90);font-family:var(--font-body);font-size:11px;font-weight:800;padding:6px 12px;text-decoration:none;">
        + Kunjungan
      </a>
      <?php endif; ?>
      <div style="display:flex;align-items:center;gap:6px;">
        <span style="font-size:11px;color:rgba(255,255,255,0.65);font-weight:700;"><?= htmlspecialchars($user['full_name']) ?></span>
        <span style="font-size:9px;background:rgba(255,255,255,0.15);border:1px solid rgba(255,255,255,0.22);border-radius:6px;padding:2px 7px;color:rgba(255,255,255,0.80);font-weight:800;text-transform:uppercase;letter-spacing:0.5px;"><?= htmlspecialchars($user['role']) ?></span>
      </div>
      <button onclick="doLogout()" style="background:rgba(255,255,255,0.12);border:1px solid rgba(255,255,255,0.20);border-radius:8px;color:rgba(255,255,255,0.80);font-family:var(--font-body);font-size:11px;font-weight:800;padding:5px 10px;cursor:pointer;">Keluar</button>
    </div>
  </div>
</header>

<!-- ── Mobile View (< 768px) — outlet cards sesuai mockup ── -->
<div id="mobile-view" style="display:none;">
  <!-- diisi oleh renderMobileView() -->
</div>

<div class="layout">
  <!-- ── Sidebar ── -->
  <aside class="sidebar">
    <div class="sidebar-section">
      <label class="sidebar-label">Periode</label>
      <div class="filter-row" style="margin-bottom:6px">
        <input type="date" id="f-from" class="filter-control" value="">
        <span style="display:flex;align-items:center;font-size:11px;color:var(--ink3)">–</span>
        <input type="date" id="f-to" class="filter-control" value="">
      </div>
      <div style="display:flex;gap:4px;flex-wrap:wrap">
        <button class="shift-btn" onclick="setPreset(7)" id="pre-7">7 hari</button>
        <button class="shift-btn" onclick="setPreset(14)" id="pre-14">14 hari</button>
        <button class="shift-btn active" onclick="setPreset(30)" id="pre-30">Bulan ini</button>
      </div>
    </div>

    <div class="sidebar-section">
      <label class="sidebar-label">Outlet</label>
      <select id="f-outlet" class="filter-control">
        <option value="0">Semua Outlet</option>
        <?php if ($outletsByType['internal']): ?>
        <optgroup label="Outlet Internal">
          <?php foreach ($outletsByType['internal'] as $o): ?>
          <option value="<?= $o['id'] ?>"><?= htmlspecialchars($o['code']) ?> — <?= htmlspecialchars($o['name']) ?></option>
          <?php endforeach; ?>
        </optgroup>
        <?php endif; ?>
        <?php if ($outletsByType['mitra']): ?>
        <optgroup label="Outlet Mitra">
          <?php foreach ($outletsByType['mitra'] as $o): ?>
          <option value="<?= $o['id'] ?>"><?= htmlspecialchars($o['code']) ?> — <?= htmlspecialchars($o['name']) ?></option>
          <?php endforeach; ?>
        </optgroup>
        <?php endif; ?>
      </select>
    </div>

    <div class="sidebar-section">
      <label class="sidebar-label">Shift</label>
      <div class="shift-btns">
        <button class="shift-btn active" id="sb-all" onclick="setShift('all')">Semua</button>
        <button class="shift-btn" id="sb-open" onclick="setShift('open')">Open</button>
        <button class="shift-btn" id="sb-ops" onclick="setShift('ops')">Ops</button>
        <button class="shift-btn" id="sb-close" onclick="setShift('close')">Close</button>
      </div>
    </div>

    <button class="btn-apply" onclick="applyFilters()">Terapkan Filter</button>

    <hr class="sidebar-divider">
    <button class="btn-export" onclick="exportExcel()">
      <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
      Export Excel
    </button>
  </aside>

  <!-- ── Main Content ── -->
  <main class="main-content">
    <!-- KPI Strip -->
    <div class="kpi-strip" id="kpi-strip">
      <div class="kpi-card"><div class="kpi-label">Rata-rata Kepatuhan</div><div class="kpi-value" id="kpi-avg">—</div><div class="kpi-sub">dari semua outlet aktif</div></div>
      <div class="kpi-card"><div class="kpi-label">Submission Bahaya</div><div class="kpi-value danger-color" id="kpi-danger">—</div><div class="kpi-sub">item KRITIKAL terlewat</div></div>
      <div class="kpi-card"><div class="kpi-label">Submission Terlambat</div><div class="kpi-value warn-color" id="kpi-late">—</div><div class="kpi-sub">di luar window shift</div></div>
      <div class="kpi-card"><div class="kpi-label">Kunjungan SPV</div><div class="kpi-value" id="kpi-spv">—</div><div class="kpi-sub">dalam periode</div></div>
    </div>

    <!-- Compliance Grid -->
    <div class="section-header">
      <span class="section-title">Grid Kepatuhan</span>
      <span class="section-meta" id="grid-meta">Memuat…</span>
    </div>
    <div class="grid-legend">
      <div class="legend-item"><div class="legend-dot ok"></div> Lengkap</div>
      <div class="legend-item"><div class="legend-dot warn"></div> Kurang</div>
      <div class="legend-item"><div class="legend-dot danger"></div> Bahaya</div>
      <div class="legend-item"><div class="legend-dot idle"></div> Belum</div>
      <div style="margin-left:auto;font-size:10px;color:var(--ink3);font-family:'Geist Mono',monospace;">O=Open · P=Ops · C=Close</div>
    </div>
    <div class="grid-wrap" id="grid-wrap">
      <div class="loading-state">Memuat data…</div>
    </div>

    <!-- Trend Chart (7.3) -->
    <div class="section-header" style="margin-top:8px">
      <span class="section-title">Tren Bulanan</span>
      <span class="section-meta" id="trend-meta"></span>
      <div style="margin-left:auto;display:flex;gap:6px">
        <button class="shift-btn" id="tr-3"  onclick="setTrendMonths(3)"  style="font-size:11px;padding:4px 10px">3 bln</button>
        <button class="shift-btn active" id="tr-6" onclick="setTrendMonths(6)"  style="font-size:11px;padding:4px 10px">6 bln</button>
        <button class="shift-btn" id="tr-12" onclick="setTrendMonths(12)" style="font-size:11px;padding:4px 10px">12 bln</button>
      </div>
    </div>
    <div style="background:var(--surface);border:1px solid var(--border);border-radius:var(--radius-lg);padding:20px;box-shadow:var(--shadow);margin-bottom:28px">
      <canvas id="trend-chart" height="90"></canvas>
    </div>

    <!-- Heatmap (7.2) -->
    <div class="section-header">
      <span class="section-title">Heatmap 30 Hari</span>
      <span class="section-meta">Rata-rata kepatuhan per hari per outlet</span>
    </div>
    <div class="grid-wrap" id="heatmap-wrap" style="max-height:500px;margin-bottom:28px">
      <div class="loading-state">Memuat…</div>
    </div>

    <!-- Rankings -->
    <div class="section-header">
      <span class="section-title">Peringkat Outlet</span>
      <span class="section-meta" id="rank-meta"></span>
    </div>
    <div class="rankings-grid" id="rankings-wrap">
      <div class="loading-state">Memuat…</div>
      <div></div>
    </div>
  </main>
</div>

<!-- ── Modal ── -->
<div class="modal-overlay" id="modal-overlay" onclick="closeModal(event)">
  <div class="modal" id="modal-box">
    <div class="modal-header">
      <div>
        <div class="modal-title" id="modal-title">Detail Submission</div>
        <div class="modal-subtitle" id="modal-subtitle"></div>
      </div>
      <button class="modal-close" onclick="closeModal()">✕</button>
    </div>
    <div class="modal-body" id="modal-body">
      <div class="loading-state">Memuat…</div>
    </div>
    <div class="modal-footer" id="modal-footer"></div>
  </div>
</div>

<!-- ── Toast ── -->
<div class="toast-wrap" id="toast-wrap"></div>

<script src="https://cdn.sheetjs.com/xlsx-0.20.3/package/dist/xlsx.full.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>
<script>
// ── Constants from PHP ─────────────────────────────────────────────────────
const USER        = <?= json_encode(['id' => $user['id'], 'role' => $user['role'], 'name' => $user['full_name']], JSON_UNESCAPED_UNICODE) ?>;
const CRITICAL_ITEMS = <?= json_encode($criticalItems, JSON_UNESCAPED_UNICODE) ?>;

// ── State ──────────────────────────────────────────────────────────────────
let state = {
  from: '',
  to:   '',
  shift: 'all',
  outlet: 0,
};
let complianceData = null;
let summaryData    = null;
let trendMonths    = 6;
let trendChart     = null;
let heatmapData    = null; // separate 30-day fetch

// ── Override loadAll agar renderMobileView() selalu dipanggil ─────────────
const _origLoadAll = loadAll;
loadAll = async function () {
  /* Tampilkan skeleton loading di mobile sebelum API dipanggil */
  renderMobileSkeleton();
  await _origLoadAll.apply(this, arguments);
  renderMobileView();
};

function renderMobileSkeleton() {
  const mv = document.getElementById('mobile-view');
  if (!mv || window.innerWidth >= 768) return;

  const skCard = (wide) => `
    <div style="background:#fff;border-radius:16px;padding:14px;margin-bottom:10px;
                box-shadow:0 2px 8px rgba(32,5,0,.06);border-left:4px solid #E8D5C8;
                animation:skPulse 1.4s ease-in-out infinite">
      <div style="display:flex;justify-content:space-between;margin-bottom:10px">
        <div>
          <div style="height:14px;width:${wide}px;background:#F0EBE6;border-radius:6px;margin-bottom:6px"></div>
          <div style="height:10px;width:80px;background:#F0EBE6;border-radius:6px"></div>
        </div>
        <div style="height:22px;width:64px;background:#F0EBE6;border-radius:20px"></div>
      </div>
      <div style="height:6px;background:#F0EBE6;border-radius:3px"></div>
    </div>`;

  mv.innerHTML = `
    <style>
      @keyframes skPulse {
        0%,100%{opacity:1} 50%{opacity:.5}
      }
    </style>
    <div style="padding:14px 16px 80px">
      <div style="display:flex;gap:8px;margin-bottom:14px">
        ${['Outlet','On Track','Perlu Aksi'].map(lbl => `
          <div style="flex:1;background:#fff;border-radius:12px;padding:10px 8px;text-align:center;
                      border:1px solid #E8D5C8;animation:skPulse 1.4s ease-in-out infinite">
            <div style="height:28px;width:40px;background:#F0EBE6;border-radius:6px;margin:0 auto 6px"></div>
            <div style="font-size:9px;font-weight:800;color:#9E8B85;text-transform:uppercase">${lbl}</div>
          </div>`).join('')}
      </div>
      ${skCard(140)}
      ${skCard(110)}
      ${skCard(160)}
      ${skCard(120)}
      ${skCard(150)}
      <div style="text-align:center;padding:10px;font-size:11px;font-weight:700;color:#9E8B85">
        Memuat data outlet...
      </div>
    </div>`;
}

// ── Cache helpers (localStorage) ──────────────────────────────────────────
const CACHE_KEY = 'ss_dash_v1';
const CACHE_TTL = 5 * 60 * 1000; // 5 menit

function saveCache(comp, sum) {
  try {
    localStorage.setItem(CACHE_KEY, JSON.stringify({
      ts: Date.now(), comp, sum,
      from: state.from, to: state.to,
      shift: state.shift, outlet: state.outlet,
    }));
  } catch(e) {}
}

function loadCache() {
  try {
    const raw = localStorage.getItem(CACHE_KEY);
    if (!raw) return null;
    const c = JSON.parse(raw);
    // Cache hanya valid jika filter sama dan belum expired
    if (Date.now() - c.ts > CACHE_TTL) return null;
    if (c.from !== state.from || c.to !== state.to ||
        c.shift !== state.shift || String(c.outlet) !== String(state.outlet)) return null;
    return c;
  } catch(e) { return null; }
}

// ── Init ───────────────────────────────────────────────────────────────────
(function init() {
  const today = todayStr();
  const monthStart = today.slice(0, 8) + '01';
  const isSPVMobile = USER.role === 'spv' && window.innerWidth < 768;
  state.from = isSPVMobile ? today : monthStart;
  state.to   = today;
  document.getElementById('f-from').value = isSPVMobile ? today : monthStart;
  document.getElementById('f-to').value   = today;

  const isMobile = window.innerWidth < 768;

  /* Tampilkan cache lama dulu kalau ada — user langsung lihat data */
  const cached = loadCache();
  if (cached) {
    complianceData = cached.comp;
    summaryData    = cached.sum;
    renderKPI(summaryData);
    renderGrid(complianceData);
    renderRankings(summaryData.rankings);
    renderMobileView(); // langsung tampil pakai cache
  }

  /* Fetch data baru di background */
  loadAll();

  /* Desktop: load chart & heatmap. Mobile: skip — tidak ditampilkan */
  if (!isMobile) {
    loadTrend();
    loadHeatmap();
  }
})();

function todayStr() {
  return new Date().toISOString().slice(0, 10);
}

function setPreset(days) {
  const to = todayStr();
  let from;
  if (days === 30) {
    from = to.slice(0, 8) + '01';
  } else {
    const d = new Date(); d.setDate(d.getDate() - days + 1);
    from = d.toISOString().slice(0, 10);
  }
  document.getElementById('f-from').value = from;
  document.getElementById('f-to').value   = to;
  document.querySelectorAll('[id^="pre-"]').forEach(b => b.classList.remove('active'));
  document.getElementById('pre-' + days)?.classList.add('active');
}

function setShift(s) {
  state.shift = s;
  document.querySelectorAll('[id^="sb-"]').forEach(b => b.classList.remove('active'));
  document.getElementById('sb-' + s)?.classList.add('active');
}

function applyFilters() {
  state.from   = document.getElementById('f-from').value  || todayStr();
  state.to     = document.getElementById('f-to').value    || todayStr();
  state.outlet = parseInt(document.getElementById('f-outlet').value) || 0;
  loadAll();
}

// ── Data loading ───────────────────────────────────────────────────────────
async function loadAll() {
  renderGridLoading();
  renderRankingsLoading();

  const compParams = new URLSearchParams({
    from: state.from, to: state.to,
    shift: state.shift, outlet: state.outlet,
  });
  const sumParams = new URLSearchParams({ from: state.from, to: state.to });

  try {
    /* Fetch summary & compliance secara paralel.
       Di mobile: begitu summary selesai langsung render stats awal,
       tidak perlu nunggu compliance yang lebih berat. */
    const [compRes, sumRes] = await Promise.all([
      fetch('/api/dashboard/compliance?' + compParams),
      fetch('/api/dashboard/summary?' + sumParams).then(async r => {
        const json = await r.json();
        if (json.ok) {
          summaryData = json.data;
          renderKPI(summaryData);
          /* Render mobile awal dengan summary saja (compliance belum ada) */
          if (window.innerWidth < 768) renderMobileView();
        }
        return { ok: r.ok, data: json };
      }),
    ]);

    if (!compRes.ok) throw new Error('Fetch gagal');

    const comp   = await compRes.json();
    const sumJson = sumRes.data;

    if (!comp.ok || !sumJson.ok) throw new Error('API error');

    complianceData = comp.data;
    summaryData    = sumJson.data;

    renderKPI(summaryData);
    renderGrid(complianceData);
    renderRankings(summaryData.rankings);

    /* Simpan ke cache untuk kunjungan berikutnya */
    saveCache(complianceData, summaryData);

  } catch (e) {
    showToast('Gagal memuat data. Coba lagi.', 'error');
    renderGridError();
    renderRankingsLoading();
  }
}

// ── KPI ────────────────────────────────────────────────────────────────────
function renderKPI(s) {
  const avg = s.avg_compliance;
  const avgEl = document.getElementById('kpi-avg');
  avgEl.textContent = avg !== null ? avg + '%' : '—';
  avgEl.className = 'kpi-value ' + (avg >= 90 ? 'ok-color' : avg >= 70 ? 'warn-color' : 'danger-color');
  document.getElementById('kpi-danger').textContent = s.total_danger;
  document.getElementById('kpi-late').textContent   = s.total_late;
  document.getElementById('kpi-spv').textContent    = s.spv_visit_count;
}

// ── Grid ───────────────────────────────────────────────────────────────────
function renderGridLoading() {
  document.getElementById('grid-wrap').innerHTML = '<div class="loading-state">Memuat data…</div>';
  document.getElementById('grid-meta').textContent = 'Memuat…';
}
function renderGridError() {
  document.getElementById('grid-wrap').innerHTML = '<div class="loading-state" style="color:var(--danger)">Gagal memuat grid</div>';
}

function renderGrid(data) {
  const { dates, outlets } = data;
  const shifts = state.shift === 'all' ? ['open', 'ops', 'close'] : [state.shift];
  const shiftLabel = { open: 'O', ops: 'P', close: 'C' };

  document.getElementById('grid-meta').textContent = `${outlets.length} outlet · ${dates.length} hari`;

  if (!outlets.length || !dates.length) {
    document.getElementById('grid-wrap').innerHTML = '<div class="empty-state"><p>Tidak ada data dalam periode ini</p></div>';
    return;
  }

  // Format date label (e.g. "15 Mei")
  const fmtDate = d => {
    const dt = new Date(d + 'T00:00:00');
    return dt.toLocaleDateString('id-ID', { day: 'numeric', month: 'short' });
  };

  let html = '<table class="grid-table"><thead><tr>';
  html += '<th class="outlet-th">Outlet</th>';
  for (const date of dates) {
    html += `<th class="date-th-group" colspan="${shifts.length}">
      <div class="date-th-label">${fmtDate(date)}</div>
      <div class="shift-th-row">${shifts.map(s => `<div class="shift-sub-th">${shiftLabel[s]}</div>`).join('')}</div>
    </th>`;
  }
  html += '</tr></thead><tbody>';

  for (const outlet of outlets) {
    html += `<tr><td class="outlet-td">
      <div class="outlet-code">${esc(outlet.code)}</div>
      <div class="outlet-name">${esc(outlet.name)}</div>
    </td>`;

    for (const date of dates) {
      for (const s of shifts) {
        const cell = outlet.days[date]?.[s] ?? { status: 'idle' };
        const st = cell.status || 'idle';
        if (st === 'idle') {
          html += `<td class="shift-td"><div class="shift-cell idle"><div class="status-dot idle"></div></div></td>`;
        } else {
          const sid = cell.submission_id;
          html += `<td class="shift-td"><button class="shift-cell" title="${st.toUpperCase()} — ${esc(cell.pic_name||'')}"
            onclick="showDetail(${sid},${outlet.id},'${date}','${s}','${st}',${JSON.stringify(outlet.name)})">
            <div class="status-dot ${st}"></div>
          </button></td>`;
        }
      }
    }
    html += '</tr>';
  }

  html += '</tbody></table>';
  document.getElementById('grid-wrap').innerHTML = html;
}

// ── Rankings ───────────────────────────────────────────────────────────────
function renderRankingsLoading() {
  document.getElementById('rankings-wrap').innerHTML = '<div class="loading-state">Memuat…</div><div></div>';
  document.getElementById('rank-meta').textContent = '';
}

function renderRankings(rankings) {
  if (!rankings || !rankings.length) {
    document.getElementById('rankings-wrap').innerHTML = '<div class="empty-state"><p>Tidak ada data ranking</p></div><div></div>';
    return;
  }

  const top5 = rankings.slice(0, 5);
  const bot5 = [...rankings].reverse().filter(r => r.total_sub > 0).slice(0, 5);

  document.getElementById('rank-meta').textContent = `${rankings.length} outlet`;

  function pctColor(pct) {
    if (pct === null) return 'none-color';
    return pct >= 90 ? 'ok-color' : pct >= 70 ? 'warn-color' : 'danger-color';
  }
  function barColor(pct) {
    if (pct === null) return 'var(--idle)';
    return pct >= 90 ? 'var(--ok)' : pct >= 70 ? 'var(--warn)' : 'var(--danger)';
  }

  function renderCard(title, badge, badgeClass, items, startRank) {
    let html = `<div class="ranking-card">
      <div class="ranking-card-header">
        <span class="ranking-card-title">${title}</span>
        <span class="ranking-badge ${badgeClass}">${badge}</span>
      </div>`;
    items.forEach((r, i) => {
      const pct = r.compliance_pct;
      html += `<div class="ranking-row">
        <div class="rank-num">${startRank + i}</div>
        <div class="rank-outlet">
          <div class="rank-code">${esc(r.code)}</div>
          <div class="rank-name">${esc(r.name)}</div>
        </div>
        <div class="rank-bar"><div class="rank-bar-fill" style="width:${pct??0}%;background:${barColor(pct)}"></div></div>
        <div class="rank-pct ${pctColor(pct)}">${pct !== null ? pct + '%' : '—'}</div>
      </div>`;
    });
    html += '</div>';
    return html;
  }

  document.getElementById('rankings-wrap').innerHTML =
    renderCard('5 Terbaik', 'Top', 'top', top5, 1) +
    renderCard('5 Perlu Perhatian', 'Rendah', 'bot', bot5, 1);
}

// ── Detail Modal ───────────────────────────────────────────────────────────
async function showDetail(submissionId, outletId, date, shift, status, outletName) {
  if (!submissionId) return;

  const overlay = document.getElementById('modal-overlay');
  const shiftLabel = { open: 'Open (Pagi)', ops: 'Operasional', close: 'Close (Malam)' };
  const statusLabel = { ok: 'Lengkap', warn: 'Kurang', danger: 'Bahaya' };

  document.getElementById('modal-title').textContent = outletName;
  document.getElementById('modal-subtitle').textContent =
    `${fmtDateLong(date)} · Shift ${shiftLabel[shift] || shift}`;
  document.getElementById('modal-body').innerHTML = '<div class="loading-state">Memuat detail…</div>';
  document.getElementById('modal-footer').innerHTML = '';
  overlay.classList.add('open');

  try {
    const res = await fetch(`/api/checklists?outlet=${outletId}&date=${date}&shift=${shift}`);
    const json = await res.json();
    const sub = json?.data?.submission;
    if (!sub) throw new Error('no sub');

    const critIds   = (CRITICAL_ITEMS[shift] || []).map(x => x.id);
    const critMap   = Object.fromEntries((CRITICAL_ITEMS[shift] || []).map(x => [x.id, x.text]));
    const checks    = sub.checks || {};
    const missed    = critIds.filter(id => !checks[id]);

    const done      = Object.values(checks).filter(Boolean).length;
    const total     = Object.keys(checks).length;
    const pct       = total > 0 ? Math.round(done / total * 100) : 0;
    const pctColor  = pct >= 90 ? 'var(--ok)' : pct >= 70 ? 'var(--warn)' : 'var(--danger)';

    const lateTag   = sub.late ? '<span style="font-size:10px;padding:2px 6px;background:var(--warn-bg);color:var(--warn);border:1px solid var(--warn-border);border-radius:4px;margin-left:4px">TERLAMBAT</span>' : '';
    const lockedTag = sub.locked ? '<span style="font-size:10px;padding:2px 6px;background:var(--saf-bg);color:var(--saffron);border:1px solid var(--saf-border);border-radius:4px;margin-left:4px">TERKUNCI</span>' : '<span style="font-size:10px;padding:2px 6px;background:var(--bg);color:var(--ink3);border:1px solid var(--border2);border-radius:4px;margin-left:4px">TIDAK TERKUNCI</span>';

    let bodyHtml = `
      <div class="detail-row"><span class="detail-key">Status</span><span class="detail-val"><span class="status-tag ${status}">${statusLabel[status]||status}</span>${lateTag}</span></div>
      <div class="detail-row"><span class="detail-key">PIC Shift</span><span class="detail-val">${esc(sub.pic_name||'—')}</span></div>
      <div class="detail-row"><span class="detail-key">Waktu Submit</span><span class="detail-val mono">${sub.submitted_at ? sub.submitted_at.replace('T',' ').slice(0,16) : '—'}</span></div>
      <div class="detail-row"><span class="detail-key">Kunci</span><span class="detail-val">${lockedTag}</span></div>
      ${sub.spv_name ? `<div class="detail-row"><span class="detail-key">Nama SPV</span><span class="detail-val">${esc(sub.spv_name)}</span></div>` : ''}
      ${sub.handover_note ? `<div class="detail-row"><span class="detail-key">Catatan</span><span class="detail-val" style="font-size:12px">${esc(sub.handover_note)}</span></div>` : ''}
      <div class="compliance-bar">
        <div class="cb-row">
          <span class="cb-label">Tingkat Kepatuhan</span>
          <span class="cb-pct" style="color:${pctColor}">${pct}%</span>
        </div>
        <div class="cb-track"><div class="cb-fill" style="width:${pct}%;background:${pctColor}"></div></div>
        <div style="font-size:10px;color:var(--ink3);margin-top:4px;font-family:'Geist Mono',monospace">${done} / ${total} item selesai</div>
      </div>`;

    if (missed.length > 0) {
      bodyHtml += `<div class="missed-section">
        <div class="missed-title">Item KRITIKAL Terlewat (${missed.length})</div>`;
      missed.forEach(id => {
        bodyHtml += `<div class="missed-item">
          <div class="missed-bullet"></div>
          <span>${esc(critMap[id] || id)}</span>
        </div>`;
      });
      bodyHtml += '</div>';
    }

    document.getElementById('modal-body').innerHTML = bodyHtml;

    // Footer with unlock button
    let footerHtml = '<button class="btn-cancel" onclick="closeModal()">Tutup</button>';
    if (sub.locked && (USER.role === 'spv' || USER.role === 'admin')) {
      footerHtml = `<button class="btn-unlock" id="btn-unlock-${sub.id}" onclick="unlockSubmission(${sub.id})">🔓 Unlock Submission</button>` + footerHtml;
    }
    document.getElementById('modal-footer').innerHTML = footerHtml;

  } catch (e) {
    document.getElementById('modal-body').innerHTML = '<div class="empty-state"><p>Gagal memuat detail</p></div>';
    document.getElementById('modal-footer').innerHTML = '<button class="btn-cancel" onclick="closeModal()">Tutup</button>';
  }
}

async function unlockSubmission(submissionId) {
  const btn = document.getElementById('btn-unlock-' + submissionId);
  if (btn) { btn.disabled = true; btn.textContent = 'Memproses…'; }

  try {
    const res = await fetch(`/api/checklists/${submissionId}/unlock`, { method: 'POST', headers: {'X-CSRF-Token': getCsrfToken()} });
    const json = await res.json();
    if (!res.ok || !json.ok) throw new Error(json.message || 'Gagal unlock');

    showToast('Submission berhasil di-unlock', 'success');
    closeModal();
    loadAll();
  } catch (e) {
    showToast(e.message || 'Gagal unlock submission', 'error');
    if (btn) { btn.disabled = false; btn.textContent = '🔓 Unlock Submission'; }
  }
}

function closeModal(e) {
  if (e && e.target !== document.getElementById('modal-overlay')) return;
  document.getElementById('modal-overlay').classList.remove('open');
}

// ── Excel Export ───────────────────────────────────────────────────────────
function exportExcel() {
  if (!complianceData) { showToast('Muat data dahulu sebelum export', 'error'); return; }

  const { dates, outlets } = complianceData;
  const shifts = state.shift === 'all' ? ['open', 'ops', 'close'] : [state.shift];
  const rows = [['Tanggal', 'Kode Outlet', 'Nama Outlet', 'Shift', 'Status', '% Selesai', 'Kritikal Terlewat', 'PIC', 'Terlambat', 'Terkunci']];

  for (const outlet of outlets) {
    for (const date of dates) {
      for (const sh of shifts) {
        const cell = outlet.days[date]?.[sh];
        if (!cell || cell.status === 'idle') {
          rows.push([date, outlet.code, outlet.name, sh, 'idle', '', '', '', '', '']);
        } else {
          rows.push([
            date, outlet.code, outlet.name, sh,
            cell.status, cell.pct ?? '',
            cell.crit_missed ?? '', cell.pic_name || '',
            cell.late ? 'Ya' : 'Tidak',
            cell.locked ? 'Ya' : 'Tidak',
          ]);
        }
      }
    }
  }

  const ws = XLSX.utils.aoa_to_sheet(rows);
  ws['!cols'] = [10,12,25,8,10,10,14,20,10,10].map(w => ({ wch: w }));
  const wb = XLSX.utils.book_new();
  XLSX.utils.book_append_sheet(wb, ws, 'Kepatuhan');
  XLSX.writeFile(wb, `SS_Kepatuhan_${state.from}_${state.to}.xlsx`);
}

// ── Helpers ────────────────────────────────────────────────────────────────
function fmtDateLong(d) {
  return new Date(d + 'T00:00:00').toLocaleDateString('id-ID', { weekday:'long', day:'numeric', month:'long', year:'numeric' });
}

function esc(str) {
  return String(str || '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

function showToast(msg, type = '') {
  const wrap = document.getElementById('toast-wrap');
  const t = document.createElement('div');
  t.className = 'toast ' + type;
  t.textContent = msg;
  wrap.appendChild(t);
  setTimeout(() => t.remove(), 3200);
}

// ── Trend Chart (7.3) ─────────────────────────────────────────────────────
function setTrendMonths(m) {
  trendMonths = m;
  [3,6,12].forEach(n => document.getElementById('tr-'+n)?.classList.toggle('active', n === m));
  loadTrend();
}

async function loadTrend() {
  try {
    const res  = await fetch('/api/dashboard/trend?months=' + trendMonths);
    const json = await res.json();
    if (!json.ok) return;
    renderTrendChart(json.data);
  } catch {}
}

function renderTrendChart(data) {
  const { trend } = data;
  if (!trend.length) return;

  const labels  = trend.map(r => {
    const [y, m] = r.month.split('-');
    return new Date(+y, +m-1, 1).toLocaleDateString('id-ID', { month:'short', year:'2-digit' });
  });
  const avgData    = trend.map(r => r.avg_pct);
  const dangerData = trend.map(r => r.danger_count);

  document.getElementById('trend-meta').textContent = trend.length + ' bulan';

  if (trendChart) trendChart.destroy();
  const ctx = document.getElementById('trend-chart').getContext('2d');
  trendChart = new Chart(ctx, {
    data: {
      labels,
      datasets: [
        {
          type: 'line', label: 'Avg Kepatuhan (%)',
          data: avgData, yAxisID: 'yPct',
          borderColor: '#E8942A', backgroundColor: 'rgba(232,148,42,.12)',
          borderWidth: 2.5, pointRadius: 4, pointBackgroundColor: '#E8942A',
          fill: true, tension: 0.3,
        },
        {
          type: 'bar', label: 'Submission Bahaya',
          data: dangerData, yAxisID: 'yCount',
          backgroundColor: 'rgba(220,38,38,.25)', borderColor: '#DC2626',
          borderWidth: 1.5, borderRadius: 4,
        },
      ],
    },
    options: {
      responsive: true, interaction: { mode: 'index', intersect: false },
      plugins: { legend: { labels: { font: { family: 'Inter', size: 11 }, boxWidth: 12 } } },
      scales: {
        yPct:   { position: 'left',  min: 0, max: 100, ticks: { callback: v => v+'%', font: { size: 10 } }, grid: { color: 'rgba(0,0,0,.05)' } },
        yCount: { position: 'right', min: 0, ticks: { stepSize: 1, font: { size: 10 } }, grid: { display: false } },
        x:      { ticks: { font: { size: 10 } }, grid: { display: false } },
      },
    },
  });
}

// ── Heatmap (7.2) ─────────────────────────────────────────────────────────
async function loadHeatmap() {
  const heatFrom = date30DaysAgo();
  const heatTo   = todayStr();
  const params   = new URLSearchParams({ from: heatFrom, to: heatTo, shift: 'all', outlet: 0 });
  try {
    const res  = await fetch('/api/dashboard/compliance?' + params);
    const json = await res.json();
    if (!json.ok) return;
    heatmapData = json.data;
    renderHeatmap(heatmapData);
  } catch {
    document.getElementById('heatmap-wrap').innerHTML = '<div class="loading-state" style="color:var(--danger)">Gagal memuat heatmap</div>';
  }
}

function date30DaysAgo() {
  const d = new Date(); d.setDate(d.getDate() - 29);
  return d.toISOString().slice(0, 10);
}

function renderHeatmap(data) {
  const { dates, outlets } = data;
  if (!outlets.length) { document.getElementById('heatmap-wrap').innerHTML = '<div class="empty-state"><p>Tidak ada data</p></div>'; return; }

  const fmtShort = d => {
    const dt = new Date(d + 'T00:00:00');
    return dt.toLocaleDateString('id-ID', { day:'numeric', month:'short' });
  };

  // Compute day average compliance per outlet per day
  function dayAvg(outlet, date) {
    const shifts = ['open','ops','close'];
    const vals   = shifts.map(s => outlet.days[date]?.[s]?.pct).filter(v => v != null);
    return vals.length ? Math.round(vals.reduce((a,b) => a+b, 0) / vals.length) : null;
  }
  function avgColor(pct) {
    if (pct === null) return { bg: 'var(--idle-bg)', border: 'var(--idle-border)' };
    if (pct >= 90)   return { bg: 'var(--ok-bg)',   border: 'var(--ok-border)' };
    if (pct >= 70)   return { bg: 'var(--warn-bg)', border: 'var(--warn-border)' };
    return               { bg: 'var(--danger-bg)', border: 'var(--danger-border)' };
  }

  let html = '<table class="grid-table"><thead><tr><th class="outlet-th">Outlet</th>';
  dates.forEach(d => {
    html += `<th class="date-th-group"><div class="date-th-label" style="font-size:9px">${fmtShort(d)}</div></th>`;
  });
  html += '</tr></thead><tbody>';

  outlets.forEach(outlet => {
    html += `<tr><td class="outlet-td"><div class="outlet-code">${esc(outlet.code)}</div><div class="outlet-name">${esc(outlet.name)}</div></td>`;
    dates.forEach(date => {
      const avg  = dayAvg(outlet, date);
      const col  = avgColor(avg);
      const tip  = avg !== null ? avg + '%' : 'Belum submit';
      html += `<td style="padding:0;border-left:1px solid var(--border)">
        <div title="${tip}" style="width:100%;height:32px;background:${col.bg};border-bottom:2px solid ${col.border};display:flex;align-items:center;justify-content:center;font-size:9px;font-family:'Geist Mono',monospace;color:${avg===null?'var(--ink3)':'var(--ink2)'}">
          ${avg !== null ? avg+'%' : ''}
        </div></td>`;
    });
    html += '</tr>';
  });
  html += '</tbody></table>';
  document.getElementById('heatmap-wrap').innerHTML = html;
}

async function doLogout() {
  await fetch('/api/auth/logout', { method: 'POST' });
  location.href = '/login';
}

/* ══════════════════════════════════════════════════════════
   MOBILE VIEW — DS v2 (< 768px)
   Menggunakan data yang sama dari loadAll() / summaryData / complianceData
   ══════════════════════════════════════════════════════════ */

function renderMobileView() {
  const mv = document.getElementById('mobile-view');
  if (!mv || window.innerWidth >= 768) return;

  const role = USER.role;
  const s    = summaryData;
  const cd   = complianceData;

  if (!s) { mv.innerHTML = '<div style="padding:30px;text-align:center;color:#6B4535;font-family:Nunito,sans-serif;font-size:13px;">Memuat data...</div>'; return; }

  if (role === 'spv') {
    renderMobileSPV(mv, s, cd);
  } else {
    renderMobileOwner(mv, s, cd);
  }
}

function renderMobileSPV(mv, s, cd) {
  const outlets = (cd && cd.outlets) ? cd.outlets : [];
  const total   = outlets.length;
  const onTrack = outlets.filter(o => {
    const avg = outletAvgPct(o);
    return avg !== null && avg >= 70;
  }).length;
  const needAction = outlets.filter(o => {
    const avg = outletAvgPct(o);
    return avg === null || avg < 70;
  }).length;

  let html = `<div class="mv-wrap">
    <div class="mv-mini-stats">
      <div class="mv-stat"><div class="mv-stat-num">${total}</div><div class="mv-stat-lbl">Outlet</div></div>
      <div class="mv-stat"><div class="mv-stat-num" style="color:#43A047">${onTrack}</div><div class="mv-stat-lbl">On Track</div></div>
      <div class="mv-stat"><div class="mv-stat-num" style="color:#E53935">${needAction}</div><div class="mv-stat-lbl">Perlu Aksi</div></div>
    </div>
    <div class="mv-outlet-list">`;

  outlets.forEach(o => {
    const pct  = outletAvgPct(o);
    const cls  = pct === null ? 'idle' : pct >= 90 ? 'ok' : pct >= 60 ? 'warn' : 'crit';
    const fcls = pct === null ? 'f-idle' : pct >= 90 ? 'f-ok' : pct >= 60 ? 'f-warn' : 'f-crit';
    const pcls = pct === null ? 'p-idle' : pct >= 90 ? 'p-ok' : pct >= 60 ? 'p-warn' : 'p-crit';
    const badgeText = pct === null ? 'BELUM' : pct >= 90 ? '✅ SELESAI' : pct >= 60 ? '⚠ LAMBAT' : '🔴 KRITIS';
    const badgeCls  = pct === null ? 'idle' : pct >= 90 ? 'ok' : pct >= 60 ? 'warn' : 'crit';
    const done = pct !== null ? Math.round((pct / 100) * (o.total_items || 20)) : 0;
    const miss = (o.total_items || 20) - done;

    html += `<div class="mv-ol-card ${cls}">
      <div class="mv-ol-top">
        <div>
          <div class="mv-ol-name">${escH(o.name)}</div>
          <div class="mv-ol-sub">Kode: ${escH(o.code)}</div>
        </div>
        <span class="mv-badge ${badgeCls}">${badgeText}</span>
      </div>
      <div class="mv-ol-bar-row">
        <div class="mv-ol-bar-bg"><div class="mv-ol-bar-fill ${fcls}" style="width:${pct||0}%"></div></div>
        <div class="mv-ol-pct ${pcls}">${pct !== null ? pct + '%' : '—'}</div>
      </div>
      <div class="mv-ol-footer">
        ${done > 0 ? `<span class="mv-chip done">✓ ${done} selesai</span>` : ''}
        ${miss > 0 && pct !== null ? `<span class="mv-chip miss">✗ ${miss} belum</span>` : ''}
        ${pct === null ? `<span class="mv-chip pend">⏳ Belum submit</span>` : ''}
      </div>
    </div>`;
  });

  html += `</div></div>`;
  mv.innerHTML = html;
}

function renderMobileOwner(mv, s, cd) {
  const avg  = s.avg_compliance ?? 0;
  const top  = (s.rankings && s.rankings.top) ? s.rankings.top.slice(0, 5) : [];
  const bot  = (s.rankings && s.rankings.bottom) ? s.rankings.bottom.slice(0, 2) : [];

  /* Stats: hitung outlet aman / perhatian / kritis */
  const outlets = (cd && cd.outlets) ? cd.outlets : [];
  let aman = 0, perhatian = 0, kritis = 0;
  outlets.forEach(o => {
    const p = outletAvgPct(o);
    if (p === null || p < 60)      kritis++;
    else if (p < 90)               perhatian++;
    else                           aman++;
  });

  const rankClass = ['mv-r1','mv-r2','mv-r3','mv-rn','mv-rn'];

  /* Simple 7-day trend from summaryData if available */
  const trendDays = s.trend_7d || [];

  let html = `<div class="mv-wrap">
    <div class="mv-big-stats">
      <div class="mv-bs-card mv-bs-aman"><div class="mv-bs-num">${aman}</div><div class="mv-bs-lbl">Aman</div></div>
      <div class="mv-bs-card mv-bs-warn"><div class="mv-bs-num">${perhatian}</div><div class="mv-bs-lbl">Perhatian</div></div>
      <div class="mv-bs-card mv-bs-krit"><div class="mv-bs-num">${kritis}</div><div class="mv-bs-lbl">Kritis</div></div>
    </div>

    <div class="ds-sc" style="margin-bottom:10px;">
      <div class="ds-sc-title">Rata-rata Kepatuhan Hari Ini</div>
      <div class="mv-comp-row">
        <div style="display:flex;align-items:baseline;gap:2px;">
          <div class="mv-comp-big">${avg}</div>
          <div class="mv-comp-pct-sign">%</div>
        </div>
        <div style="text-align:right;padding-bottom:6px;">
          <div class="mv-comp-trend">Periode ini</div>
          <div class="mv-comp-sub">${s.total_submissions || 0} submission</div>
        </div>
      </div>
      <div class="mv-comp-bar-bg"><div class="mv-comp-bar-fill" style="width:${avg}%"></div></div>
    </div>`;

  if (trendDays.length > 0) {
    const maxVal = Math.max(...trendDays.map(d => d.avg || 0), 1);
    html += `<div class="ds-sc" style="margin-bottom:10px;">
      <div class="ds-sc-title">Tren ${trendDays.length} Hari Terakhir</div>
      <div class="mv-chart-wrap">`;
    trendDays.forEach((d, i) => {
      const h = Math.round(((d.avg || 0) / maxVal) * 70);
      const isToday = i === trendDays.length - 1;
      const dayLabel = new Date(d.date).toLocaleDateString('id-ID',{weekday:'short'}).slice(0,3).toUpperCase();
      html += `<div class="mv-c-col">
        <div class="mv-c-val">${d.avg || 0}%</div>
        <div class="mv-c-bar${isToday?' today':''}" style="height:${h}px;"></div>
        <div class="mv-c-day" style="${isToday?'color:#E8924A;font-weight:900;':''}">${isToday?'HARI':dayLabel}</div>
      </div>`;
    });
    html += `</div></div>`;
  }

  if (top.length > 0) {
    html += `<div class="ds-sc" style="margin-bottom:10px;"><div class="ds-sc-title">Top Outlet Hari Ini</div>`;
    top.forEach((o, i) => {
      const pct = o.avg_compliance ?? 0;
      html += `<div class="mv-top-row">
        <div class="mv-rank-badge ${rankClass[i]}">${i+1}</div>
        <div class="mv-rank-name">${escH(o.outlet_name)}</div>
        <div class="mv-rank-pct">${pct}%</div>
      </div>`;
    });
    html += `</div>`;
  }

  if (bot.length > 0) {
    html += `<div class="ds-sc" style="margin-bottom:10px;"><div class="ds-sc-title">🔔 Perlu Perhatian</div>`;
    bot.forEach(o => {
      const pct = o.avg_compliance ?? 0;
      html += `<div class="mv-alert-row">
        <div class="mv-alert-dot"></div>
        <div class="mv-alert-txt">${escH(o.outlet_name)} — Kepatuhan ${pct}%</div>
      </div>`;
    });
    html += `</div>`;
  }

  html += `</div>`;
  mv.innerHTML = html;
}

/* helpers */
function outletAvgPct(o) {
  if (!o.days) return null;
  const vals = [];
  Object.values(o.days).forEach(day => {
    Object.values(day).forEach(cell => {
      if (cell && cell.pct != null && cell.status !== 'idle') vals.push(cell.pct);
    });
  });
  if (!vals.length) return null;
  return Math.round(vals.reduce((a, b) => a + b, 0) / vals.length);
}

function escH(str) {
  return String(str || '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
}

/* Hook renderMobileView setelah loadAll() selesai */
// (dipanggil dari applyFilters() & init() melalui override ini)
</script>
</body>
</html>
