<?php
/**
 * Shared App Header — DS v2 (mockup.html style)
 *
 * Params (set sebelum include):
 *   $headerSubtitle string  — teks di bawah brand (nama outlet, nama user, dll)
 *   $headerSubInfo  string  — baris kedua (tanggal, shift, dll) — opsional
 *   $headerExtra    string  — HTML tambahan di bawah subtitle (progress box, stats, dll) — opsional
 *   $showLogout     bool    — tampilkan tombol logout di avatar (default: false)
 *   $avatarEmoji    string  — emoji avatar (default: 👤)
 */

$headerSubtitle = $headerSubtitle ?? '';
$headerSubInfo  = $headerSubInfo  ?? '';
$headerExtra    = $headerExtra    ?? '';
$showLogout     = $showLogout     ?? false;
$avatarEmoji    = $avatarEmoji    ?? '👤';
?>
<header class="ds-app-header">
  <div class="ds-header-row">
    <div class="ds-header-brand">
      <div class="ds-header-logo">
        <img src="/assets/logo.png" alt="SS"
             onerror="this.style.display='none'; this.nextElementSibling.style.display='block'">
        <span class="ds-header-logo-fallback" style="display:none">SS</span>
      </div>
      <span class="ds-header-brand-text">SS OPS</span>
    </div>

    <div class="ds-header-actions">
      <?php if ($showLogout): ?>
      <button class="ds-logout-btn" onclick="handleLogout()" title="Keluar">
        <span style="font-size:13px;">⎋</span>
        <span>Keluar</span>
      </button>
      <?php else: ?>
      <div class="ds-header-avatar"><?= htmlspecialchars($avatarEmoji) ?></div>
      <?php endif; ?>
    </div>
  </div>

  <?php if ($headerSubtitle): ?>
  <div class="ds-header-subtitle"><?= htmlspecialchars($headerSubtitle) ?></div>
  <?php endif; ?>

  <?php if ($headerSubInfo): ?>
  <div class="ds-header-subinfo"><?= htmlspecialchars($headerSubInfo) ?></div>
  <?php endif; ?>

  <?php if ($headerExtra): ?>
  <?= $headerExtra ?>
  <?php endif; ?>
</header>

<style>
.ds-header-subtitle {
  font-size: 17px;
  font-weight: 900;
  color: white;
  margin-bottom: 2px;
}

.ds-header-subinfo {
  font-size: 11px;
  color: rgba(255,255,255,0.62);
  margin-bottom: 12px;
}

.ds-logout-btn {
  display: flex;
  align-items: center;
  gap: 5px;
  background: rgba(255,255,255,0.15);
  border: 1px solid rgba(255,255,255,0.22);
  border-radius: 10px;
  color: rgba(255,255,255,0.85);
  font-family: var(--font-body);
  font-size: 11px;
  font-weight: 800;
  padding: 6px 11px;
  cursor: pointer;
  transition: background 0.15s;
}

.ds-logout-btn:active { background: rgba(255,255,255,0.25); }
</style>
