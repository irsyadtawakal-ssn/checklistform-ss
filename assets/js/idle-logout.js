/**
 * SS Operations — Idle Auto-Logout
 * Deteksi 12 jam tidak ada aktivitas → logout otomatis.
 * Include di semua halaman yang butuh auth.
 *
 * Config (opsional, set sebelum include):
 *   window.SS_IDLE_LIMIT_MS  = 43200000  // default 12 jam
 *   window.SS_IDLE_WARN_MS   = 300000    // default 5 menit sebelum logout
 *   window.SS_LOGIN_URL      = '/login'
 */
(function () {
  'use strict';

  const IDLE_LIMIT  = window.SS_IDLE_LIMIT_MS  ?? 43200000; // 12 jam
  const WARN_BEFORE = window.SS_IDLE_WARN_MS   ?? 300000;   // 5 menit
  const LOGIN_URL   = window.SS_LOGIN_URL       ?? '/login';
  const EVENTS      = ['mousemove','keydown','click','scroll','touchstart','visibilitychange'];

  let idleTimer, warnTimer;

  function resetTimers() {
    clearTimeout(idleTimer);
    clearTimeout(warnTimer);
    dismissWarning();
    warnTimer = setTimeout(showWarning, IDLE_LIMIT - WARN_BEFORE);
    idleTimer = setTimeout(performLogout, IDLE_LIMIT);
  }

  function showWarning() {
    if (window.ssShowToast) {
      window.ssShowToast(
        'Sesi Anda akan berakhir dalam 5 menit karena tidak ada aktivitas.',
        'warn',
        0 // jangan auto-dismiss — tampilkan sampai ada aktivitas
      );
    }
  }

  function dismissWarning() {
    const el = document.getElementById('ss-toast-idle');
    if (el) el.remove();
  }

  async function performLogout() {
    try {
      await fetch('/api/auth/logout', {
        method: 'POST',
        credentials: 'same-origin',
      });
    } catch (_) { /* abaikan error network */ }
    window.location.href = LOGIN_URL + '?reason=idle';
  }

  // ─── Pasang event listener ────────────────────────────────────────────────
  EVENTS.forEach(ev =>
    document.addEventListener(ev, resetTimers, { passive: true })
  );

  // ─── Intercept fetch 401 global → redirect ke login ──────────────────────
  const _fetch = window.fetch;
  window.fetch = async function (...args) {
    const res = await _fetch(...args);
    if (res.status === 401) {
      const url = new URL(args[0], location.href);
      const isAuthEndpoint = url.pathname.startsWith('/api/auth/');
      if (!isAuthEndpoint) {
        window.location.href = LOGIN_URL + '?reason=session';
      }
    }
    return res;
  };

  // ─── Mulai timer ─────────────────────────────────────────────────────────
  resetTimers();
})();
