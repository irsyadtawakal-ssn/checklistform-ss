/**
 * SS Operations — Toast Notification
 * Dipakai di semua halaman.
 *
 * API:
 *   ssShowToast(message, type?, durationMs?)
 *   type: 'error' | 'warn' | 'ok' | 'info'   default: 'error'
 *   durationMs: 0 = tidak auto-dismiss
 */
(function () {
  'use strict';

  const STACK_ID = 'ss-toast-stack';

  function getStack() {
    let stack = document.getElementById(STACK_ID);
    if (!stack) {
      stack = document.createElement('div');
      stack.id = STACK_ID;
      stack.style.cssText =
        'position:fixed;bottom:24px;left:50%;transform:translateX(-50%);' +
        'z-index:9999;display:flex;flex-direction:column;gap:8px;align-items:center;' +
        'pointer-events:none;width:max-content;max-width:min(480px,90vw)';
      document.body.appendChild(stack);
    }
    return stack;
  }

  window.ssShowToast = function (msg, type, duration) {
    type     = type     ?? 'error';
    duration = duration ?? 4000;

    const colors = {
      error : { bg: '#a83621', text: '#f5f1e8' },
      warn  : { bg: '#b88412', text: '#f5f1e8' },
      ok    : { bg: '#5c6b2e', text: '#f5f1e8' },
      info  : { bg: '#3a5468', text: '#f5f1e8' },
    };
    const c = colors[type] ?? colors.error;

    const el = document.createElement('div');
    el.style.cssText =
      `background:${c.bg};color:${c.text};` +
      'padding:12px 20px;font-family:"Geist Mono",monospace;font-size:12px;' +
      'letter-spacing:0.06em;line-height:1.5;text-align:center;' +
      'opacity:0;transform:translateY(12px);transition:all 0.28s cubic-bezier(0.22,0.61,0.36,1);' +
      'pointer-events:auto;cursor:default;max-width:100%;';
    el.textContent = msg;
    el.addEventListener('click', () => dismiss(el));

    getStack().appendChild(el);
    requestAnimationFrame(() => {
      el.style.opacity = '1';
      el.style.transform = 'translateY(0)';
    });

    if (duration > 0) {
      setTimeout(() => dismiss(el), duration);
    }

    return el;
  };

  function dismiss(el) {
    el.style.opacity = '0';
    el.style.transform = 'translateY(8px)';
    setTimeout(() => el.remove(), 320);
  }
})();
