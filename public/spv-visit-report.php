<?php
declare(strict_types=1);

require_once __DIR__ . '/../src/bootstrap.php';
require_once ROOT_PATH . '/src/helpers/auth.php';
require_once ROOT_PATH . '/src/helpers/db.php';
require_once ROOT_PATH . '/src/middleware/page.php';

$user    = pageRequireRole('spv', 'admin');
$visitId = (int) ($_GET['id'] ?? 0);
if (!$visitId) { http_response_code(400); die('ID kunjungan tidak valid.'); }

$pdo  = db();
$stmt = $pdo->prepare("
    SELECT v.*, o.name AS outlet_name, o.code AS outlet_code, o.type AS outlet_type,
           u.full_name AS spv_name, u.username AS spv_username
    FROM   spv_visits v
    JOIN   outlets o ON o.id = v.outlet_id
    JOIN   users   u ON u.id = v.spv_id
    WHERE  v.id = ?
");
$stmt->execute([$visitId]);
$visit = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$visit) { http_response_code(404); die('Kunjungan tidak ditemukan.'); }
if ($user['role'] === 'spv' && (int)$visit['spv_id'] !== (int)$user['id']) {
    http_response_code(403); die('Akses ditolak.');
}

// Paksa browser render sebagai HTML
header('Content-Type: text/html; charset=UTF-8');

// Foto
$photos = $pdo->prepare("SELECT id, file_path, thumb_path, label, tag FROM spv_visit_photos WHERE visit_id = ? ORDER BY id ASC");
$photos->execute([$visitId]);
$photos = $photos->fetchAll(PDO::FETCH_ASSOC);

// Payload
$payload = json_decode($visit['payload_json'] ?? '{}', true) ?: [];

$shiftLabel = ['open' => 'Buka (Open)', 'ops' => 'Operasional', 'close' => 'Tutup (Close)'];
$shiftStr   = $shiftLabel[$visit['visit_shift']] ?? ($visit['visit_shift'] ?? '—');
$dateStr    = $visit['visit_date'] ? date('d F Y', strtotime($visit['visit_date'])) : '—';
$printTitle = 'Laporan Kunjungan SPV — ' . $visit['outlet_name'] . ' — ' . $dateStr;
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= htmlspecialchars($printTitle) ?></title>
<style>
  * { box-sizing: border-box; margin: 0; padding: 0; }
  body {
    font-family: 'Segoe UI', Arial, sans-serif;
    font-size: 13px;
    color: #1C1917;
    background: #f5f5f0;
    padding: 24px;
  }

  /* Tombol aksi — hilang saat print */
  .no-print {
    display: flex;
    gap: 10px;
    justify-content: flex-end;
    margin-bottom: 20px;
  }
  .btn-print {
    background: #E8942A;
    border: none;
    border-radius: 8px;
    color: #fff;
    font-size: 13px;
    font-weight: 700;
    padding: 9px 20px;
    cursor: pointer;
  }
  .btn-back {
    background: #fff;
    border: 1px solid #d0ccc8;
    border-radius: 8px;
    color: #57534E;
    font-size: 13px;
    font-weight: 700;
    padding: 9px 20px;
    cursor: pointer;
    text-decoration: none;
  }

  /* Dokumen laporan */
  .report {
    background: #fff;
    max-width: 800px;
    margin: 0 auto;
    padding: 36px 40px;
    border-radius: 14px;
    box-shadow: 0 2px 20px rgba(0,0,0,0.08);
  }

  /* Header laporan */
  .report-header {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    padding-bottom: 18px;
    border-bottom: 2px solid #1C1917;
    margin-bottom: 22px;
    gap: 16px;
  }
  .report-brand { display: flex; align-items: center; gap: 12px; }
  .report-logo {
    width: 44px; height: 44px;
    border-radius: 10px;
    background: #2C1A0E;
    display: flex; align-items: center; justify-content: center;
    color: #fff;
    font-size: 16px;
    font-weight: 900;
    letter-spacing: -1px;
    overflow: hidden;
  }
  .report-logo img { width: 100%; height: 100%; object-fit: cover; }
  .brand-name { font-size: 18px; font-weight: 900; color: #1C1917; letter-spacing: 0.5px; }
  .brand-sub  { font-size: 10px; color: #A8A29E; font-weight: 700; letter-spacing: 1px; text-transform: uppercase; margin-top: 1px; }
  .report-meta-right { text-align: right; }
  .report-doctype { font-size: 15px; font-weight: 800; color: #E8942A; }
  .report-docdate { font-size: 11px; color: #A8A29E; margin-top: 3px; }

  /* Judul outlet */
  .report-outlet-title {
    font-size: 22px;
    font-weight: 900;
    color: #1C1917;
    margin-bottom: 4px;
  }
  .report-outlet-sub { font-size: 12px; color: #A8A29E; font-weight: 600; margin-bottom: 20px; }

  /* Grid info */
  .info-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 12px;
    margin-bottom: 24px;
    background: #f7f3ed;
    border-radius: 10px;
    padding: 16px;
  }
  .info-item { display: flex; flex-direction: column; gap: 2px; }
  .info-label { font-size: 9px; font-weight: 800; color: #A8A29E; text-transform: uppercase; letter-spacing: 0.5px; }
  .info-value { font-size: 13px; font-weight: 700; color: #1C1917; }

  /* Section */
  .section { margin-bottom: 22px; }
  .section-title {
    font-size: 10px;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 1px;
    color: #A8A29E;
    border-bottom: 1px solid #e8e3de;
    padding-bottom: 6px;
    margin-bottom: 12px;
  }

  /* Payload catatan */
  .payload-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 8px;
  }
  .payload-item {
    background: #f7f3ed;
    border-radius: 7px;
    padding: 9px 12px;
  }
  .payload-key { font-size: 9px; font-weight: 800; color: #A8A29E; text-transform: uppercase; letter-spacing: 0.3px; margin-bottom: 2px; }
  .payload-val { font-size: 12px; font-weight: 600; color: #1C1917; }
  .payload-full { grid-column: 1 / -1; background: #fef3e2; border: 1px solid rgba(232,148,42,0.25); }

  /* Foto */
  .photo-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 10px;
  }
  .photo-item { display: flex; flex-direction: column; gap: 5px; }
  .photo-item img {
    width: 100%;
    aspect-ratio: 1;
    object-fit: cover;
    border-radius: 8px;
    border: 1px solid #e8e3de;
  }
  .photo-caption { font-size: 10px; color: #57534E; font-weight: 600; text-align: center; }

  /* Footer */
  .report-footer {
    margin-top: 28px;
    padding-top: 14px;
    border-top: 1px solid #e8e3de;
    display: flex;
    justify-content: space-between;
    align-items: flex-end;
    font-size: 10px;
    color: #A8A29E;
  }
  .signature-box {
    text-align: center;
  }
  .signature-line {
    width: 140px;
    border-bottom: 1px solid #1C1917;
    margin: 40px auto 6px;
  }
  .signature-name { font-size: 11px; font-weight: 700; color: #1C1917; }
  .signature-role { font-size: 9px; color: #A8A29E; }

  /* Print styles */
  @media print {
    body { background: #fff; padding: 0; }
    .no-print { display: none !important; }
    .report { box-shadow: none; border-radius: 0; padding: 20px 24px; max-width: 100%; }
    .photo-grid { grid-template-columns: repeat(4, 1fr); }
    a { color: inherit; text-decoration: none; }
  }
</style>
</head>
<body>

<!-- Tombol aksi -->
<div class="no-print">
  <a href="javascript:history.back()" class="btn-back">← Kembali</a>
  <button class="btn-print" onclick="window.print()">🖨️ Print / Save PDF</button>
</div>

<!-- Dokumen laporan -->
<div class="report">

  <!-- Header -->
  <div class="report-header">
    <div class="report-brand">
      <div class="report-logo">
        <img src="/assets/img/logo.png" alt="SS" onerror="this.style.display='none'; this.parentElement.textContent='SS'">
      </div>
      <div>
        <div class="brand-name">SS Operations</div>
        <div class="brand-sub">Suka Shawarma · F&B Operations</div>
      </div>
    </div>
    <div class="report-meta-right">
      <div class="report-doctype">Laporan Kunjungan SPV</div>
      <div class="report-docdate">ID Kunjungan: #<?= $visitId ?> · Dicetak: <?= date('d/m/Y H:i') ?></div>
    </div>
  </div>

  <!-- Judul outlet -->
  <div class="report-outlet-title"><?= htmlspecialchars($visit['outlet_name']) ?></div>
  <div class="report-outlet-sub">
    Kode: <?= htmlspecialchars($visit['outlet_code']) ?> ·
    Tipe: <?= ucfirst(htmlspecialchars($visit['outlet_type'] ?? '—')) ?>
  </div>

  <!-- Info grid -->
  <div class="info-grid">
    <div class="info-item">
      <div class="info-label">Tanggal Kunjungan</div>
      <div class="info-value"><?= htmlspecialchars($dateStr) ?></div>
    </div>
    <div class="info-item">
      <div class="info-label">Shift</div>
      <div class="info-value"><?= htmlspecialchars($shiftStr) ?></div>
    </div>
    <div class="info-item">
      <div class="info-label">SPV</div>
      <div class="info-value"><?= htmlspecialchars($visit['spv_name'] ?? '—') ?></div>
    </div>
    <div class="info-item">
      <div class="info-label">Waktu Tiba</div>
      <div class="info-value"><?= $visit['time_arrive'] ? substr($visit['time_arrive'], 0, 5) : '—' ?></div>
    </div>
    <div class="info-item">
      <div class="info-label">Waktu Keluar</div>
      <div class="info-value"><?= $visit['time_leave'] ? substr($visit['time_leave'], 0, 5) : '—' ?></div>
    </div>
    <div class="info-item">
      <div class="info-label">PIC Bertugas</div>
      <div class="info-value"><?= htmlspecialchars($visit['pic_on_duty'] ?? '—') ?></div>
    </div>
  </div>

  <!-- Catatan & Payload -->
  <?php if (!empty($payload)): ?>
  <div class="section">
    <div class="section-title">Catatan & Temuan</div>
    <div class="payload-grid">
      <?php foreach ($payload as $key => $val):
        $isNote  = stripos($key, 'catatan') !== false || stripos($key, 'note') !== false || stripos($key, 'temuan') !== false;
        $display = is_array($val) ? implode(', ', $val) : (string) $val;
        if (empty(trim($display))) continue;
      ?>
      <div class="payload-item <?= $isNote ? 'payload-full' : '' ?>">
        <div class="payload-key"><?= htmlspecialchars(str_replace('_', ' ', $key)) ?></div>
        <div class="payload-val"><?= htmlspecialchars($display) ?></div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
  <?php endif; ?>

  <!-- Foto -->
  <?php if (!empty($photos)): ?>
  <div class="section">
    <div class="section-title">Dokumentasi Foto (<?= count($photos) ?>)</div>
    <div class="photo-grid">
      <?php foreach ($photos as $p):
        $src = '/' . ltrim($p['file_path'], '/');
        $thumb = $p['thumb_path'] ? '/' . ltrim($p['thumb_path'], '/') : $src;
      ?>
      <div class="photo-item">
        <img src="<?= htmlspecialchars($thumb) ?>"
             onerror="this.src='<?= htmlspecialchars($src) ?>'"
             alt="<?= htmlspecialchars($p['label']) ?>">
        <div class="photo-caption"><?= htmlspecialchars($p['label']) ?></div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
  <?php else: ?>
  <div class="section">
    <div class="section-title">Dokumentasi Foto</div>
    <div style="color:#A8A29E;font-size:12px;font-style:italic;">Tidak ada foto dalam kunjungan ini.</div>
  </div>
  <?php endif; ?>

  <!-- Footer / tanda tangan -->
  <div class="report-footer">
    <div>
      <div>Dikirim: <?= $visit['submitted_at'] ? date('d/m/Y H:i', strtotime($visit['submitted_at'])) : '—' ?></div>
      <div style="margin-top:3px;">Dokumen ini dibuat otomatis oleh SS Operations.</div>
    </div>
    <div class="signature-box">
      <div class="signature-line"></div>
      <div class="signature-name"><?= htmlspecialchars($visit['spv_name'] ?? '—') ?></div>
      <div class="signature-role">Supervisor</div>
    </div>
  </div>

</div><!-- .report -->
</body>
</html>
