<?php
declare(strict_types=1);

require_once __DIR__ . '/../src/bootstrap.php';
require_once ROOT_PATH . '/src/helpers/auth.php';

startSession();

$user = currentUser();
if ($user) {
    $go = match ($user['role']) {
        'outlet'        => '/checklist',
        'spv', 'owner'  => '/dashboard',
        'admin'         => '/admin',
        default         => '/',
    };
    header("Location: {$go}");
    exit;
}

$reason = $_GET['reason'] ?? '';
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="theme-color" content="#200500">
<title>Masuk · SS Operations</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=Nunito:wght@400;600;700;800;900&display=swap" rel="stylesheet">
<style>
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

:root {
  --red:        #7A1200;
  --red-dark:   #200500;
  --orange:     #E8924A;
  --green:      #43A047;
  --green-mid:  #2E7D32;
  --text:       #200500;
  --muted:      #6B4535;
  --border:     #E8D5C8;
  --font-brand: 'Bebas Neue', cursive;
  --font-body:  'Nunito', sans-serif;
}

html, body {
  height: 100%;
  min-height: 100vh;
  font-family: var(--font-body);
  -webkit-font-smoothing: antialiased;
}

/* ════════════════════════════════
   MOBILE — clean centered card
   ════════════════════════════════ */
body {
  background: #F5F0EC;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  min-height: 100dvh;
  padding: 24px 20px;
  position: relative;
}

/* subtle warm dot pattern */
body::before {
  content: '';
  position: fixed;
  inset: 0;
  background-image:
    radial-gradient(circle at 15% 20%, rgba(122,18,0,0.06) 0%, transparent 50%),
    radial-gradient(circle at 85% 80%, rgba(232,146,74,0.08) 0%, transparent 50%);
  pointer-events: none;
  z-index: 0;
}

/* top section — mobile: compact brand header inside card */
.login-top {
  position: relative;
  z-index: 1;
  display: flex;
  flex-direction: column;
  align-items: center;
  padding: 32px 24px 20px;
  width: 100%;
}

.ss-logo {
  width: 76px;
  height: 76px;
  background: white;
  border-radius: 20px;
  display: flex;
  align-items: center;
  justify-content: center;
  margin-bottom: 14px;
  box-shadow: 0 6px 24px rgba(122,18,0,0.16);
  overflow: hidden;
  padding: 7px;
  flex-shrink: 0;
}
.ss-logo img { width: 100%; height: 100%; object-fit: contain; }
.ss-logo-fallback { font-family: var(--font-brand); font-size: 32px; color: var(--red); line-height: 1; }

.login-brand {
  font-family: var(--font-brand);
  font-size: 26px;
  color: var(--text);
  letter-spacing: 4px;
  text-align: center;
  line-height: 1;
}
.login-brand-sub {
  font-size: 9px;
  color: var(--muted);
  letter-spacing: 3px;
  text-transform: uppercase;
  font-weight: 700;
  margin-top: 5px;
  text-align: center;
}
.login-tagline { display: none; }
.ornament { display: none; }

/* login card — mobile: part of the same white card */
.login-card {
  position: relative;
  z-index: 1;
  background: white;
  border-radius: 0 0 24px 24px;
  padding: 4px 24px 28px;
  width: 100%;
  max-width: 430px;
  box-shadow: none;
}

/* wrap both top + card in one rounded card on mobile */
.login-shell {
  position: relative;
  z-index: 1;
  width: 100%;
  max-width: 420px;
  background: white;
  border-radius: 24px;
  box-shadow:
    0 0 0 1px rgba(232,213,200,0.9),
    0 16px 48px rgba(32,5,0,0.12),
    0 4px 16px rgba(32,5,0,0.06);
  overflow: hidden;
}

/* brand strip at top of card — mobile */
.login-top {
  background: linear-gradient(160deg, #200500 0%, #7A1200 60%, #A02000 100%);
  border-radius: 0;
  padding: 28px 24px 24px;
}

.login-brand       { color: white; }
.login-brand-sub   { color: rgba(255,255,255,0.50); }

/* mobile brand block */
.mobile-brand {
  display: flex;
  flex-direction: column;
  align-items: center;
}

/* desktop-only elements hidden on mobile */
.brand-top, .brand-mid, .brand-stats { display: none; }

/* ════════════════════════════════
   DESKTOP — split layout (≥ 680px)
   ════════════════════════════════ */
@media (min-width: 680px) {
  body {
    background: #F5F0EC;
    justify-content: center;
    align-items: center;
    padding: 24px;
  }

  /* outer wrapper */
  .login-shell {
    display: flex;
    width: 100%;
    max-width: 920px;
    min-height: 560px;
    border-radius: 24px;
    overflow: hidden;
    box-shadow:
      0 0 0 1px rgba(232,213,200,0.8),
      0 24px 80px rgba(32,5,0,0.18),
      0 8px 24px rgba(32,5,0,0.10);
  }

  /* show/hide per breakpoint */
  .mobile-brand { display: none; }
  .brand-top    { display: flex; }
  .brand-mid    { display: flex; }
  .brand-stats  { display: flex; }

  /* LEFT — brand panel */
  .login-top {
    flex: 1 1 42%;
    background: linear-gradient(155deg, #200500 0%, #7A1200 55%, #9A2000 100%);
    padding: 48px 44px;
    justify-content: space-between;
    align-items: flex-start;
    max-width: none;
    position: relative;
    overflow: hidden;
  }

  /* decorative ring */
  .login-top::after {
    content: '';
    position: absolute;
    width: 420px;
    height: 420px;
    right: -160px;
    bottom: -160px;
    border-radius: 50%;
    border: 60px solid rgba(232,146,74,0.10);
    pointer-events: none;
  }

  .login-top::before {
    content: '';
    position: absolute;
    width: 260px;
    height: 260px;
    right: -80px;
    bottom: -80px;
    border-radius: 50%;
    border: 40px solid rgba(232,146,74,0.08);
    pointer-events: none;
  }

  .brand-top {
    display: flex;
    align-items: center;
    gap: 12px;
    position: relative;
    z-index: 1;
  }

  .ss-logo {
    width: 48px;
    height: 48px;
    border-radius: 12px;
    margin-bottom: 0;
    box-shadow: 0 4px 16px rgba(0,0,0,0.24);
    padding: 5px;
  }

  .brand-text-group { display: flex; flex-direction: column; }

  .login-brand {
    font-size: 20px;
    letter-spacing: 3px;
    text-align: left;
    line-height: 1;
  }

  .login-brand-sub {
    font-size: 9px;
    letter-spacing: 2.5px;
    margin-top: 3px;
    text-align: left;
  }

  .brand-mid {
    position: relative;
    z-index: 1;
    flex: 1;
    display: flex;
    flex-direction: column;
    justify-content: center;
  }

  .brand-mid-logo {
    width: 100px;
    height: 100px;
    background: white;
    border-radius: 24px;
    display: flex;
    align-items: center;
    justify-content: center;
    overflow: hidden;
    padding: 10px;
    box-shadow: 0 12px 40px rgba(0,0,0,0.30);
    margin-bottom: 24px;
  }
  .brand-mid-logo img { width: 100%; height: 100%; object-fit: contain; }
  .brand-mid-logo-fallback { font-family: var(--font-brand); font-size: 42px; color: var(--red); line-height: 1; }

  .brand-headline {
    font-family: var(--font-brand);
    font-size: 46px;
    color: white;
    letter-spacing: 3px;
    line-height: 1.05;
    margin-bottom: 12px;
  }

  .brand-desc {
    font-size: 13px;
    color: rgba(255,255,255,0.55);
    font-weight: 600;
    line-height: 1.6;
    max-width: 280px;
  }

  .brand-stats {
    display: flex;
    gap: 20px;
    position: relative;
    z-index: 1;
  }

  .brand-stat { }
  .brand-stat-num {
    font-family: var(--font-brand);
    font-size: 28px;
    color: white;
    line-height: 1;
    letter-spacing: 1px;
  }
  .brand-stat-lbl {
    font-size: 9px;
    color: rgba(255,255,255,0.45);
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 1px;
    margin-top: 2px;
  }

  /* RIGHT — form panel */
  .login-card {
    flex: 1 1 58%;
    border-radius: 0;
    padding: 56px 56px;
    max-width: none;
    width: auto;
    box-shadow: none;
    display: flex;
    flex-direction: column;
    justify-content: center;
  }

  /* hide mobile-only elements */
  .login-tagline,
  .ornament { display: none; }
}

/* ════════════════════════════════
   SHARED FORM STYLES
   ════════════════════════════════ */
.login-card-title {
  font-size: 26px;
  font-weight: 900;
  color: var(--text);
  margin-bottom: 4px;
}
.login-card-sub {
  font-size: 13px;
  color: var(--muted);
  font-weight: 600;
  margin-bottom: 28px;
}

.form-group { margin-bottom: 14px; }

.form-label {
  display: block;
  font-size: 10px;
  font-weight: 900;
  color: var(--muted);
  text-transform: uppercase;
  letter-spacing: 1.2px;
  margin-bottom: 7px;
}

.form-input {
  width: 100%;
  padding: 14px 15px;
  border: 2px solid var(--border);
  border-radius: 12px;
  font-family: var(--font-body);
  font-size: 15px;
  font-weight: 600;
  color: var(--text);
  background: #FAFAFA;
  outline: none;
  transition: border-color 0.18s;
  -webkit-appearance: none;
}
.form-input:focus { border-color: var(--red); background: white; }

.pw-wrap { position: relative; }
.pw-toggle {
  position: absolute;
  right: 14px;
  top: 50%;
  transform: translateY(-50%);
  background: none;
  border: none;
  font-family: var(--font-body);
  font-size: 11px;
  font-weight: 800;
  color: var(--muted);
  cursor: pointer;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.btn-login {
  width: 100%;
  padding: 16px;
  background: linear-gradient(135deg, #7A1200, #E8924A);
  color: white;
  border: none;
  border-radius: 14px;
  font-family: var(--font-body);
  font-size: 16px;
  font-weight: 900;
  letter-spacing: 0.8px;
  cursor: pointer;
  margin-top: 8px;
  box-shadow: 0 6px 22px rgba(122,18,0,0.35);
  transition: opacity 0.18s, transform 0.14s;
  -webkit-appearance: none;
}
.btn-login:active:not(:disabled) { transform: scale(0.98); }
.btn-login:disabled { opacity: 0.7; cursor: not-allowed; }
.btn-login.success { background: linear-gradient(135deg, #2E7D32, #43A047); box-shadow: 0 6px 22px rgba(46,125,50,0.30); }

.login-help {
  text-align: center;
  margin-top: 18px;
  font-size: 12px;
  color: var(--muted);
  font-weight: 600;
}
.login-help a { color: var(--red); font-weight: 800; text-decoration: none; }

/* ── Toast ── */
.toast {
  position: fixed;
  bottom: 28px;
  left: 50%;
  transform: translateX(-50%) translateY(10px);
  background: var(--red-dark);
  color: white;
  font-size: 13px;
  font-weight: 700;
  padding: 10px 20px;
  border-radius: 99px;
  z-index: 999;
  opacity: 0;
  transition: all 0.22s;
  white-space: nowrap;
  pointer-events: none;
  max-width: 90vw;
  text-align: center;
}
.toast.show  { opacity: 1; transform: translateX(-50%) translateY(0); }
.toast.warn  { background: #E65100; }
.toast.error { background: #B71C1C; }
</style>
</head>
<body>

<div class="login-shell">

  <!-- LEFT / TOP: Brand panel -->
  <div class="login-top">

    <!-- Mobile: logo + brand centered -->
    <div class="mobile-brand">
      <div class="ss-logo">
        <img src="/assets/logo.png" alt="SS"
             onerror="this.style.display='none'; this.nextElementSibling.style.display='block'">
        <span class="ss-logo-fallback" style="display:none">SS</span>
      </div>
      <div class="login-brand">SUKA SHAWARMA</div>
      <div class="login-brand-sub">Sistem Operasional</div>
    </div>

    <!-- Desktop: brand top row (logo kecil + nama) -->
    <div class="brand-top">
      <div class="ss-logo">
        <img src="/assets/logo.png" alt="SS"
             onerror="this.style.display='none'; this.nextElementSibling.style.display='block'">
        <span class="ss-logo-fallback" style="display:none">SS</span>
      </div>
      <div class="brand-text-group">
        <div class="login-brand">SUKA SHAWARMA</div>
        <div class="login-brand-sub">Sistem Operasional</div>
      </div>
    </div>

    <!-- Desktop: center content -->
    <div class="brand-mid">
      <div class="brand-mid-logo">
        <img src="/assets/logo.png" alt="Suka Shawarma"
             onerror="this.style.display='none'; this.nextElementSibling.style.display='block'">
        <span class="brand-mid-logo-fallback" style="display:none">SS</span>
      </div>
      <div class="brand-headline">OPERASIONAL<br>19 OUTLET</div>
      <div class="brand-desc">Pantau kepatuhan, kelola kunjungan SPV, dan raih standar terbaik — setiap shift.</div>
    </div>

    <!-- Desktop: bottom stats -->
    <div class="brand-stats">
      <div class="brand-stat">
        <div class="brand-stat-num">19</div>
        <div class="brand-stat-lbl">Outlet Aktif</div>
      </div>
      <div class="brand-stat">
        <div class="brand-stat-num">3×</div>
        <div class="brand-stat-lbl">Shift / Hari</div>
      </div>
      <div class="brand-stat">
        <div class="brand-stat-num">365</div>
        <div class="brand-stat-lbl">Hari / Tahun</div>
      </div>
    </div>

    <!-- mobile-only placeholders (hidden) -->
    <div class="login-tagline"></div>
    <div class="ornament"></div>

  </div><!-- /login-top -->

  <!-- RIGHT / BOTTOM: Form -->
  <div class="login-card">
    <div class="login-card-title">Masuk ke Akun</div>
    <div class="login-card-sub">Gunakan akun yang diberikan oleh admin pusat</div>

    <form id="loginForm" novalidate>
      <div class="form-group">
        <label class="form-label">Username</label>
        <input class="form-input" id="username" type="text"
               placeholder="contoh: pic.depok"
               autocomplete="username" autocapitalize="none"
               inputmode="text" required>
      </div>

      <div class="form-group">
        <label class="form-label">Password</label>
        <div class="pw-wrap">
          <input class="form-input" id="pw" type="password"
                 placeholder="••••••••"
                 autocomplete="current-password" required>
          <button class="pw-toggle" type="button" id="pwToggle">Lihat</button>
        </div>
      </div>

      <button class="btn-login" id="btnLogin" type="submit">MASUK →</button>
    </form>

    <div class="login-help">
      Lupa password? <a href="mailto:admin@sukashawarma.com">Hubungi Admin</a>
    </div>
  </div><!-- /login-card -->

</div><!-- /login-shell -->

<!-- Toast -->
<div class="toast" id="toast"></div>

<script>
(function () {
  'use strict';

  const form     = document.getElementById('loginForm');
  const btnLogin = document.getElementById('btnLogin');
  const pwInput  = document.getElementById('pw');
  const pwToggle = document.getElementById('pwToggle');

  pwToggle.addEventListener('click', function () {
    const hidden = pwInput.type === 'password';
    pwInput.type = hidden ? 'text' : 'password';
    pwToggle.textContent = hidden ? 'Sembunyikan' : 'Lihat';
  });

  function showToast(msg, type) {
    const t = document.getElementById('toast');
    t.textContent = msg;
    t.className = 'toast show' + (type ? ' ' + type : '');
    clearTimeout(t._timer);
    t._timer = setTimeout(() => t.classList.remove('show'), 3500);
  }

  form.addEventListener('submit', async function (e) {
    e.preventDefault();

    const username = document.getElementById('username').value.trim();
    const password = pwInput.value;

    if (!username || !password) {
      showToast('Username dan password wajib diisi.', 'warn');
      return;
    }

    btnLogin.disabled    = true;
    btnLogin.textContent = 'Memverifikasi...';

    try {
      const res  = await fetch('/api/auth/login', {
        method:      'POST',
        headers:     { 'Content-Type': 'application/json' },
        credentials: 'same-origin',
        body:        JSON.stringify({ username, password }),
      });

      const data = await res.json();

      if (data.ok) {
        btnLogin.classList.add('success');
        btnLogin.textContent = 'Berhasil ✓';
        setTimeout(() => { window.location.href = data.data.redirect; }, 500);
      } else {
        showToast(data.message ?? 'Login gagal. Coba lagi.', 'error');
        btnLogin.disabled    = false;
        btnLogin.textContent = 'MASUK →';
      }
    } catch (_) {
      showToast('Gagal terhubung ke server. Cek koneksi internet Anda.', 'error');
      btnLogin.disabled    = false;
      btnLogin.textContent = 'MASUK →';
    }
  });

  <?php if ($reason === 'idle'): ?>
  window.addEventListener('load', function () {
    showToast('Sesi berakhir karena tidak ada aktivitas. Silakan login kembali.', 'warn');
  });
  <?php elseif ($reason === 'session'): ?>
  window.addEventListener('load', function () {
    showToast('Sesi Anda telah habis. Silakan login kembali.', 'warn');
  });
  <?php endif; ?>
})();
</script>
</body>
</html>
