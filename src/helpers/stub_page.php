<?php
declare(strict_types=1);

/**
 * Render stub page — placeholder untuk halaman yang belum dibangun.
 * Dipakai task 1.4; akan diganti template nyata di Phase 2–5.
 */
function renderStubPage(array $user, string $title, string $icon, string $phase, string $desc): never
{
    $roleLabel = match ($user['role']) {
        'outlet' => 'PIC Outlet',
        'spv'    => 'Supervisor',
        'owner'  => 'Owner',
        'admin'  => 'Admin',
        default  => $user['role'],
    };

    $navLinks = [];
    if (in_array($user['role'], ['spv', 'owner', 'admin'])) {
        $navLinks[] = ['href' => '/dashboard',  'label' => 'Dashboard'];
    }
    if ($user['role'] === 'spv') {
        $navLinks[] = ['href' => '/spv-visit',  'label' => 'Visit Report'];
    }
    if ($user['role'] === 'outlet') {
        $navLinks[] = ['href' => '/checklist',  'label' => 'Checklist Harian'];
    }
    if ($user['role'] === 'admin') {
        $navLinks[] = ['href' => '/admin',      'label' => 'Admin Panel'];
    }

    ob_start();
    ?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= htmlspecialchars($title) ?> · SS Operations</title>
<link href="https://fonts.googleapis.com/css2?family=Fraunces:ital,opsz,wght,SOFT,WONK@0,9..144,300..900,0..100,0..1;1,9..144,300..900,0..100,0..1&family=Geist:wght@300;400;500;600&family=Geist+Mono:wght@400;500;600&display=swap" rel="stylesheet">
<style>
:root{--paper:#f5f1e8;--paper-2:#ede7d6;--ink:#1a1611;--ink-2:#3d352a;--ink-3:#6b6155;--ink-4:#9a8f7f;--ink-5:#c0b7a5;--line:rgba(26,22,17,0.10);--line-2:rgba(26,22,17,0.18);--saffron:#c45a1a;--saffron-edge:rgba(196,90,26,0.32);--ok:#5c6b2e;--display:'Fraunces',serif;--body:'Geist',system-ui,sans-serif;--mono:'Geist Mono',monospace;--ease:cubic-bezier(0.22,0.61,0.36,1)}
*{box-sizing:border-box;margin:0;padding:0}
body{background:var(--paper);color:var(--ink);font-family:var(--body);font-size:14px;-webkit-font-smoothing:antialiased;min-height:100vh;display:flex;flex-direction:column}
/* NAV */
nav{background:var(--ink);padding:0 32px;display:flex;align-items:center;justify-content:space-between;height:52px;flex-shrink:0}
.nav-brand{display:flex;align-items:center;gap:10px}
.nav-logo{width:32px;height:32px;background:var(--saffron);display:flex;align-items:center;justify-content:center;font-family:var(--display);font-weight:700;font-size:15px;color:#fff;font-variation-settings:"opsz" 16,"SOFT" 0}
.nav-name{font-family:var(--mono);font-size:11px;letter-spacing:0.16em;text-transform:uppercase;color:rgba(245,241,232,0.6)}
.nav-links{display:flex;align-items:center;gap:24px}
.nav-links a{font-family:var(--mono);font-size:11px;letter-spacing:0.12em;text-transform:uppercase;color:rgba(245,241,232,0.5);text-decoration:none;transition:color 0.15s}
.nav-links a:hover,.nav-links a.active{color:#f5f1e8}
.nav-right{display:flex;align-items:center;gap:16px}
.nav-user{font-family:var(--mono);font-size:10px;letter-spacing:0.12em;text-transform:uppercase;color:rgba(245,241,232,0.4)}
.nav-user strong{color:rgba(245,241,232,0.75);font-weight:500}
.btn-logout{background:transparent;border:1px solid rgba(245,241,232,0.2);color:rgba(245,241,232,0.5);padding:6px 14px;font-family:var(--mono);font-size:10px;letter-spacing:0.14em;text-transform:uppercase;cursor:pointer;transition:all 0.15s var(--ease)}
.btn-logout:hover{border-color:var(--saffron);color:var(--saffron)}
/* MAIN */
main{flex:1;display:flex;align-items:center;justify-content:center;padding:48px 24px}
.stub{max-width:520px;width:100%;text-align:center}
.stub-icon{font-size:64px;margin-bottom:24px;line-height:1}
.stub-phase{font-family:var(--mono);font-size:10px;letter-spacing:0.22em;text-transform:uppercase;color:var(--saffron);margin-bottom:16px}
.stub-title{font-family:var(--display);font-weight:400;font-size:clamp(36px,6vw,56px);line-height:1;letter-spacing:-0.025em;font-variation-settings:"opsz" 56,"SOFT" 30;margin-bottom:16px}
.stub-desc{font-family:var(--display);font-style:italic;font-size:18px;font-variation-settings:"opsz" 18,"SOFT" 100;color:var(--ink-3);line-height:1.5;margin-bottom:40px}
.stub-badge{display:inline-flex;align-items:center;gap:8px;background:var(--paper-2);border:1px solid var(--line-2);padding:10px 20px;font-family:var(--mono);font-size:11px;letter-spacing:0.12em;text-transform:uppercase;color:var(--ink-3)}
.stub-badge::before{content:'';width:8px;height:8px;background:var(--saffron);border-radius:50%;animation:pulse 2s infinite}
@keyframes pulse{0%,100%{opacity:1}50%{opacity:0.3}}
/* FOOTER */
footer{padding:16px 32px;border-top:1px solid var(--line);display:flex;justify-content:space-between;font-family:var(--mono);font-size:10px;letter-spacing:0.12em;text-transform:uppercase;color:var(--ink-5)}
</style>
</head>
<body>

<nav>
  <div class="nav-brand">
    <div class="nav-logo">SS</div>
    <span class="nav-name">Operations</span>
  </div>
  <div class="nav-links">
    <?php foreach ($navLinks as $link): ?>
    <a href="<?= htmlspecialchars($link['href']) ?>"><?= htmlspecialchars($link['label']) ?></a>
    <?php endforeach; ?>
  </div>
  <div class="nav-right">
    <div class="nav-user"><?= htmlspecialchars($roleLabel) ?> · <strong><?= htmlspecialchars($user['username']) ?></strong></div>
    <button class="btn-logout" onclick="logout()">Keluar</button>
  </div>
</nav>

<main>
  <div class="stub">
    <div class="stub-icon"><?= $icon ?></div>
    <div class="stub-phase"><?= htmlspecialchars($phase) ?></div>
    <h1 class="stub-title"><?= htmlspecialchars($title) ?></h1>
    <p class="stub-desc"><?= htmlspecialchars($desc) ?></p>
    <div class="stub-badge">Halaman ini sedang dibangun</div>
  </div>
</main>

<footer>
  <span>SS Operations · v1.0</span>
  <span><?= htmlspecialchars($roleLabel) ?> · <?= htmlspecialchars($user['full_name']) ?></span>
</footer>

<script src="/assets/js/toast.js"></script>
<script src="/assets/js/idle-logout.js"></script>
<script>
async function logout() {
  await fetch('/api/auth/logout', {method:'POST', credentials:'same-origin'});
  window.location.href = '/login';
}
</script>
</body>
</html>
<?php
    echo ob_get_clean();
    exit;
}
