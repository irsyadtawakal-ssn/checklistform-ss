/* ============================================================
   SS OPS — Sistem Operasional Suka Shawarma
   app.js — Main JavaScript
   ============================================================ */

'use strict';

/* ── CSRF TOKEN ── */
function getCsrfToken() {
  return document.querySelector('meta[name="csrf-token"]')?.content ?? '';
}

/* ── SCREEN SWITCHER (mockup only) ── */
function sw(screenId, btn) {
  document.querySelectorAll('.screen').forEach(s => s.classList.remove('active'));
  document.querySelectorAll('.demo-btn').forEach(b => b.classList.remove('active'));
  document.getElementById(screenId).classList.add('active');
  btn.classList.add('active');
}


/* ── CHECKLIST TOGGLE ── */
function toggleCheck(item) {
  const circle = item.querySelector('.cl-check');
  const text   = item.querySelector('.cl-text');
  const timeEl = item.querySelector('.cl-time');
  const isDone = circle.classList.contains('done');

  if (isDone) {
    circle.classList.remove('done');
    circle.textContent = '';
    text.classList.remove('done');
    timeEl.textContent = '';
  } else {
    circle.classList.add('done');
    circle.textContent = '✓';
    text.classList.add('done');
    if (!timeEl.textContent) {
      const now = new Date();
      const h = String(now.getHours()).padStart(2, '0');
      const m = String(now.getMinutes()).padStart(2, '0');
      timeEl.textContent = `${h}:${m}`;
    }
  }

  updateProgress();

  /* Kirim ke server via AJAX (aktif saat PHP sudah siap) */
  const itemId   = item.dataset.itemId;
  const outletId = item.dataset.outletId;
  if (itemId && outletId) {
    saveCheckToServer(itemId, outletId, !isDone);
  }
}


/* ── UPDATE PROGRESS BAR (PIC page) ── */
function updateProgress() {
  const body = document.getElementById('pic-checklist-body');
  if (!body) return;

  const total   = body.querySelectorAll('.cl-item').length;
  const checked = body.querySelectorAll('.cl-check.done').length;
  const pct     = Math.round(checked / total * 100);

  const countEl = document.getElementById('pic-count');
  const barEl   = document.getElementById('pic-bar');

  if (countEl) countEl.innerHTML = `${checked}<span class="denom">/${total}</span>`;
  if (barEl)   barEl.style.width = pct + '%';

  /* update per-section count */
  body.querySelectorAll('.cl-section').forEach(sec => {
    const sTotal = sec.querySelectorAll('.cl-item').length;
    const sDone  = sec.querySelectorAll('.cl-check.done').length;
    const countEl = sec.querySelector('.cl-section-count');
    if (countEl) countEl.textContent = `${sDone}/${sTotal}`;
  });
}


/* ── AJAX: SIMPAN CHECK KE SERVER ── */
function saveCheckToServer(itemId, outletId, isChecked) {
  fetch('api/checklist_toggle.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({
      item_id:   itemId,
      outlet_id: outletId,
      checked:   isChecked
    })
  })
  .then(res => res.json())
  .then(data => {
    if (!data.success) {
      console.warn('Gagal simpan checklist:', data.message);
    }
  })
  .catch(err => console.error('Error AJAX checklist:', err));
}


/* ── AUTO REFRESH DASHBOARD (Supervisor & Owner) ── */
function startAutoRefresh(intervalMs = 120000) {
  setInterval(() => {
    const isDashboard = document.querySelector('.outlet-list') ||
                        document.querySelector('.owner-body');
    if (isDashboard) {
      location.reload();
    }
  }, intervalMs);
}


/* ── INIT ── */
document.addEventListener('DOMContentLoaded', () => {
  /* Auto refresh setiap 2 menit di halaman dashboard */
  if (document.body.dataset.page === 'supervisor' ||
      document.body.dataset.page === 'owner') {
    startAutoRefresh(120000);
  }

  /* Init progress saat halaman PIC dimuat */
  if (document.body.dataset.page === 'pic') {
    updateProgress();
  }
});
