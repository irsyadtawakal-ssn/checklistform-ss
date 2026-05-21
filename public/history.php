<?php
declare(strict_types=1);

require_once __DIR__ . '/../src/bootstrap.php';
require_once ROOT_PATH . '/src/helpers/auth.php';
require_once ROOT_PATH . '/src/helpers/db.php';
require_once ROOT_PATH . '/src/middleware/page.php';

$user = pageRequireRole('outlet');

$outletStmt = db()->prepare('SELECT id, code, name FROM outlets WHERE id = ? LIMIT 1');
$outletStmt->execute([$user['outlet_id']]);
$outlet = $outletStmt->fetch();

// Ambil 30 submission terakhir untuk outlet ini
$histStmt = db()->prepare(
    "SELECT id, shift, submission_date, status, compliance_status, compliance_pct,
            pic_name, late, locked, submitted_at
     FROM checklist_submissions
     WHERE outlet_id = ?
     ORDER BY submission_date DESC, FIELD(shift,'close','ops','open')
     LIMIT 30"
);
$histStmt->execute([$user['outlet_id']]);
$history = $histStmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0">
<title>Riwayat Saya · <?= htmlspecialchars($outlet['name'] ?? '') ?></title>
<style>
@import url('https://fonts.googleapis.com/css2?family=DM+Mono:wght@400;500&family=Syne:wght@700;800&family=DM+Sans:wght@400;500;600&display=swap');
:root{--bg:#fff;--bg2:#f7f6f3;--bg3:#eeede9;--border:rgba(0,0,0,.08);--border2:rgba(0,0,0,.14);--text:#111110;--text2:#4a4a47;--text3:#888882;--amber:#e8a020;--amber-bg:rgba(232,160,32,.10);--amber-border:rgba(232,160,32,.28);--red:#e05540;--red-bg:rgba(224,85,64,.08);--red-border:rgba(224,85,64,.25);--green:#3a9e50;--green-bg:rgba(58,158,80,.08);--green-border:rgba(58,158,80,.22);--radius:10px;--font-d:'Syne',sans-serif;--font-b:'DM Sans',sans-serif;--font-m:'DM Mono',monospace}
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0;-webkit-tap-highlight-color:transparent}
body{background:var(--bg);color:var(--text);font-family:var(--font-b);min-height:100vh}
.app-header{background:var(--bg);border-bottom:1px solid var(--border);padding:14px 16px;display:flex;align-items:center;justify-content:space-between}
.brand{display:flex;align-items:center;gap:10px}
.brand-logo{width:34px;height:34px;background:var(--amber);border-radius:8px;display:flex;align-items:center;justify-content:center;font-family:var(--font-d);font-weight:800;font-size:13px;color:#fff}
.brand-name{font-family:var(--font-d);font-weight:700;font-size:14px}
.brand-sub{font-size:10px;color:var(--text3);font-family:var(--font-m)}
.header-right{display:flex;align-items:center;gap:8px}
.outlet-tag{font-size:10px;font-family:var(--font-m);color:var(--amber);background:var(--amber-bg);border:1px solid var(--amber-border);padding:2px 8px;border-radius:99px}
.btn-back{padding:6px 12px;border:1px solid var(--border2);border-radius:7px;background:transparent;font-family:var(--font-b);font-size:12px;color:var(--text2);cursor:pointer;text-decoration:none;display:inline-flex;align-items:center;gap:4px}
.main{padding:20px 16px 80px}
.page-title{font-family:var(--font-d);font-size:20px;font-weight:800;margin-bottom:4px}
.page-sub{font-size:12px;color:var(--text3);font-family:var(--font-m);margin-bottom:20px}
.history-list{display:flex;flex-direction:column;gap:10px}
.history-card{background:var(--bg2);border:1px solid var(--border);border-radius:var(--radius);padding:14px 16px;display:flex;align-items:center;gap:14px}
.date-block{text-align:center;flex-shrink:0;width:46px}
.date-day{font-family:var(--font-d);font-size:22px;font-weight:800;line-height:1}
.date-mon{font-size:9px;font-family:var(--font-m);color:var(--text3);text-transform:uppercase;letter-spacing:.06em}
.shift-pill{display:inline-flex;align-items:center;padding:3px 10px;border-radius:99px;font-size:10px;font-family:var(--font-m);font-weight:500;text-transform:uppercase;border:1px solid;flex-shrink:0}
.shift-open{background:var(--amber-bg);color:var(--amber);border-color:var(--amber-border)}
.shift-ops{background:var(--green-bg);color:var(--green);border-color:var(--green-border)}
.shift-close{background:var(--bg3);color:var(--text3);border-color:var(--border2)}
.card-info{flex:1;min-width:0}
.pic-name{font-size:13px;font-weight:600;color:var(--text);white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.card-meta{display:flex;align-items:center;gap:6px;margin-top:3px;flex-wrap:wrap}
.meta-tag{font-size:10px;font-family:var(--font-m);padding:1px 6px;border-radius:4px;border:1px solid}
.meta-late{background:var(--amber-bg);color:var(--amber);border-color:var(--amber-border)}
.meta-locked{background:var(--bg3);color:var(--text3);border-color:var(--border2)}
.card-pct{text-align:right;flex-shrink:0}
.pct-num{font-family:var(--font-d);font-size:20px;font-weight:800}
.pct-num.ok{color:var(--green)}
.pct-num.warn{color:var(--amber)}
.pct-num.danger{color:var(--red)}
.pct-num.none{color:var(--text3)}
.pct-lbl{font-size:9px;color:var(--text3);font-family:var(--font-m)}
.status-dot{width:8px;height:8px;border-radius:50%;display:inline-block;margin-right:3px}
.dot-ok{background:var(--green)}
.dot-warn{background:var(--amber)}
.dot-danger{background:var(--red)}
.dot-none{background:var(--text3)}
.empty{text-align:center;padding:60px 20px;color:var(--text3)}
.empty p{font-size:13px;margin-top:8px}
.btn-checklist{display:block;margin:20px auto 0;padding:12px 24px;background:var(--amber);color:#fff;border:none;border-radius:10px;font-family:var(--font-d);font-size:14px;font-weight:700;cursor:pointer;text-align:center;text-decoration:none;width:fit-content}
</style>
</head>
<body>

<header class="app-header">
  <div class="brand">
    <div class="brand-logo">SS</div>
    <div>
      <div class="brand-name">SS Operations</div>
      <div class="brand-sub">Riwayat Submission</div>
    </div>
  </div>
  <div class="header-right">
    <span class="outlet-tag"><?= htmlspecialchars($outlet['code'] ?? '') ?></span>
    <a class="btn-back" href="/checklist">← Checklist</a>
  </div>
</header>

<main class="main">
  <div class="page-title">Riwayat Saya</div>
  <div class="page-sub"><?= htmlspecialchars($outlet['name'] ?? '') ?> · 30 submission terakhir</div>

  <?php if (empty($history)): ?>
    <div class="empty">
      <div style="font-size:32px">📋</div>
      <p>Belum ada submission. Mulai isi checklist hari ini!</p>
      <a class="btn-checklist" href="/checklist">Buka Checklist</a>
    </div>
  <?php else: ?>
    <div class="history-list">
      <?php foreach ($history as $row):
        $date    = $row['submission_date'];
        $dayNum  = date('d', strtotime($date));
        $monStr  = date('M', strtotime($date));
        $yearStr = date('Y', strtotime($date));
        $shift   = $row['shift'];
        $shiftLabel = ['open'=>'Open','ops'=>'Ops','close'=>'Close'][$shift] ?? $shift;
        $cs   = $row['compliance_status'];
        $pct  = $row['compliance_pct'];
        $pctClass = $cs === 'ok' ? 'ok' : ($cs === 'warn' ? 'warn' : ($cs === 'danger' ? 'danger' : 'none'));
        $dotClass = "dot-{$pctClass}";
        $submitTime = $row['submitted_at'] ? date('H:i', strtotime($row['submitted_at'])) : '—';
      ?>
        <div class="history-card">
          <div class="date-block">
            <div class="date-day"><?= $dayNum ?></div>
            <div class="date-mon"><?= $monStr ?> <?= $yearStr ?></div>
          </div>
          <span class="shift-pill shift-<?= $shift ?>"><?= $shiftLabel ?></span>
          <div class="card-info">
            <div class="pic-name"><?= htmlspecialchars($row['pic_name']) ?></div>
            <div class="card-meta">
              <span class="meta-tag meta-locked"><?= $row['locked'] ? '🔒 Terkunci' : '✎ Draft' ?></span>
              <?php if ($row['late']): ?><span class="meta-tag meta-late">Terlambat</span><?php endif; ?>
              <span style="font-size:10px;color:var(--text3);font-family:var(--font-m)"><?= $submitTime ?></span>
            </div>
          </div>
          <div class="card-pct">
            <?php if ($pct !== null): ?>
              <div class="pct-num <?= $pctClass ?>">
                <span class="status-dot <?= $dotClass ?>"></span><?= $pct ?>%
              </div>
              <div class="pct-lbl"><?= $cs ? strtoupper($cs) : '' ?></div>
            <?php else: ?>
              <div class="pct-num none">—</div>
              <div class="pct-lbl">Data lama</div>
            <?php endif; ?>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
    <a class="btn-checklist" href="/checklist">Buka Checklist Hari Ini</a>
  <?php endif; ?>
</main>

</body>
</html>
