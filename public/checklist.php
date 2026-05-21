<?php
declare(strict_types=1);

require_once __DIR__ . '/../src/bootstrap.php';
require_once ROOT_PATH . '/src/helpers/auth.php';
require_once ROOT_PATH . '/src/helpers/db.php';
require_once ROOT_PATH . '/src/middleware/page.php';
require_once ROOT_PATH . '/src/helpers/csrf.php';

$user = pageRequireRole('outlet');

$outletStmt = db()->prepare('SELECT id, code, name FROM outlets WHERE id = ? LIMIT 1');
$outletStmt->execute([$user['outlet_id']]);
$outlet = $outletStmt->fetch();

$outletName = $outlet['name'] ?? 'Outlet';
$outletCode = $outlet['code'] ?? '-';

$checklistJson = file_get_contents(ROOT_PATH . '/assets/data/checklist.json');
$checklistData = json_decode($checklistJson, true);
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="theme-color" content="#200500">
<meta name="csrf-token" content="<?= htmlspecialchars(csrfToken()) ?>">
<title><?= htmlspecialchars($outletName) ?> · Checklist · SS Ops</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=Nunito:wght@400;600;700;800;900&display=swap" rel="stylesheet">
<link rel="stylesheet" href="/assets/css/ds.css">
<style>
/* ── Page-specific overrides ── */
body { margin: 0; background: var(--bg); }

/* Status bar placeholder (mobile) */
.status-bar {
  height: 44px;
  background: #200500;
  display: flex;
  align-items: flex-end;
  justify-content: space-between;
  padding: 0 18px 8px;
  font-size: 11px;
  font-weight: 800;
  color: rgba(255,255,255,0.80);
  flex-shrink: 0;
}

/* Shift tabs */
.shift-tabs {
  display: grid;
  grid-template-columns: repeat(3,1fr);
  background: rgba(255,255,255,0.10);
  border-radius: 10px;
  padding: 3px;
  margin-top: 12px;
}

.shift-tab {
  padding: 8px 4px;
  border: none;
  background: transparent;
  color: rgba(255,255,255,0.55);
  font-family: var(--font-body);
  font-size: 11px;
  font-weight: 800;
  cursor: pointer;
  border-radius: 8px;
  letter-spacing: 0.3px;
  transition: all 0.18s;
  white-space: nowrap;
}

.shift-tab.active {
  background: white;
  color: var(--red);
}

/* Checklist body */
.checklist-body {
  flex: 1;
  overflow-y: auto;
  padding: 14px 14px 110px;
  -webkit-overflow-scrolling: touch;
}
.checklist-body::-webkit-scrollbar { display: none; }

/* Section icon colors */
.icon-opening { background: #FFF8E1; }
.icon-ops     { background: #FFF3E0; }
.icon-closing { background: #F3E5F5; }

/* Item badge (KRITIKAL) */
.ibadge {
  font-size: 9px;
  font-family: var(--font-body);
  font-weight: 800;
  padding: 2px 6px;
  border-radius: 5px;
  white-space: nowrap;
  flex-shrink: 0;
}
.ibadge-crit { background: #FFEBEE; color: var(--red); }
.ibadge-rec  { background: #E3F2FD; color: #1565C0; }
.ibadge-score{ background: #FFF8E1; color: #E65100; }

/* Data fields card */
.data-card {
  background: white;
  border-radius: var(--radius-lg);
  padding: 14px;
  margin-bottom: 14px;
  box-shadow: var(--shadow-card);
}

.data-card-title {
  font-size: 10px;
  font-weight: 900;
  text-transform: uppercase;
  letter-spacing: 1.3px;
  color: var(--muted);
  margin-bottom: 12px;
}

.data-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 10px;
}

.df label {
  display: block;
  font-size: 10px;
  font-weight: 900;
  color: var(--muted);
  text-transform: uppercase;
  letter-spacing: 0.8px;
  margin-bottom: 5px;
}

.df input[type=number],
.df input[type=text],
.df textarea,
.df select {
  width: 100%;
  background: #FAFAFA;
  border: 1.5px solid var(--border);
  color: var(--text);
  font-family: var(--font-body);
  font-size: 13px;
  font-weight: 600;
  border-radius: 10px;
  padding: 9px 11px;
  outline: none;
  -webkit-appearance: none;
  transition: border-color 0.15s;
}

.df input:focus,
.df textarea:focus { border-color: var(--red); }
.df textarea { resize: vertical; min-height: 60px; font-size: 12px; line-height: 1.5; }

/* Sign section */
.sign-card {
  background: white;
  border: 2px solid var(--border);
  border-radius: var(--radius-lg);
  padding: 14px;
  margin-top: 4px;
}

.sign-card-title {
  font-size: 12px;
  font-weight: 900;
  color: var(--red);
  margin-bottom: 12px;
  text-transform: uppercase;
  letter-spacing: 0.8px;
}

/* Submit bar */
.submit-wrap {
  position: fixed;
  bottom: 0;
  left: 0;
  right: 0;
  padding: 10px 14px calc(14px + env(safe-area-inset-bottom, 0px));
  background: linear-gradient(to top, var(--bg) 75%, transparent);
  z-index: 50;
}

.submit-btn {
  width: 100%;
  max-width: 430px;
  margin: 0 auto;
  display: block;
  padding: 15px;
  background: linear-gradient(135deg, var(--red), var(--orange));
  color: white;
  border: none;
  border-radius: var(--radius-lg);
  font-family: var(--font-body);
  font-size: 15px;
  font-weight: 900;
  cursor: pointer;
  box-shadow: 0 6px 22px rgba(122,18,0,0.30);
  transition: all 0.18s;
  -webkit-appearance: none;
}

.submit-btn:active:not(:disabled) { transform: scale(0.98); }
.submit-btn:disabled { opacity: 0.7; cursor: not-allowed; }
.submit-btn.success  { background: linear-gradient(135deg, var(--green-mid), var(--green)); box-shadow: 0 6px 22px rgba(46,125,50,0.30); }
.submit-btn.dim      { background: #D7CCC8; box-shadow: none; color: var(--muted); }

/* Locked banner */
.locked-banner {
  display: none;
  align-items: center;
  gap: 10px;
  background: #E8F5E9;
  border: 1.5px solid #A5D6A7;
  border-radius: var(--radius-md);
  padding: 10px 14px;
  margin-bottom: 14px;
  font-size: 13px;
  font-weight: 700;
  color: var(--green-mid);
}
.locked-banner.visible { display: flex; }
</style>
</head>
<body>

<div class="ds-screen" style="min-height:100dvh;">

  <!-- Header -->
  <div class="ds-app-header">
    <div class="ds-header-row">
      <div class="ds-header-brand">
        <div class="ds-header-logo">
          <img src="/assets/logo.png" alt="SS"
               onerror="this.style.display='none'; this.nextElementSibling.style.display='block'">
          <span class="ds-header-logo-fallback" style="display:none">SS</span>
        </div>
        <span class="ds-header-brand-text">SS OPS</span>
      </div>
      <button class="ds-logout-btn" onclick="handleLogout()">
        <span>Keluar</span>
      </button>
    </div>

    <div style="font-size:17px; font-weight:900; color:white; margin-bottom:2px;"
         id="outletLabel"><?= htmlspecialchars($outletName) ?></div>
    <div style="font-size:11px; color:rgba(255,255,255,0.62); margin-bottom:12px;"
         id="dateLabel"></div>

    <!-- Progress box -->
    <div class="ds-progress-box">
      <div class="ds-progress-top">
        <span class="ds-prog-label">Progress Hari Ini</span>
        <span class="ds-prog-number" id="progNum">0<span class="denom">/0</span></span>
      </div>
      <div class="ds-prog-bar-bg">
        <div class="ds-prog-bar-fill" id="progBar"></div>
      </div>
    </div>

    <!-- Shift tabs -->
    <div class="shift-tabs">
      <button class="shift-tab active" id="tab-open"  onclick="setShift('open')">Pembukaan</button>
      <button class="shift-tab"        id="tab-ops"   onclick="setShift('ops')">Operasional</button>
      <button class="shift-tab"        id="tab-close" onclick="setShift('close')">Penutupan</button>
    </div>
  </div>

  <!-- Checklist body -->
  <div class="checklist-body" id="mainArea"></div>

  <!-- Submit bar -->
  <div class="submit-wrap">
    <button class="submit-btn dim" id="submitBtn" onclick="handleSubmit()">
      Selesaikan Checklist
    </button>
  </div>

</div><!-- /ds-screen -->

<!-- Toast -->
<div class="ds-toast" id="toast"></div>

<style>
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
  padding: 6px 12px;
  cursor: pointer;
  transition: background 0.15s;
}
.ds-logout-btn:active { background: rgba(255,255,255,0.25); }
</style>

<script>
const OUTLET_ID   = <?= (int) ($outlet['id'] ?? 0) ?>;
const OUTLET_NAME = <?= json_encode($outletName) ?>;
const OUTLET_CODE = <?= json_encode($outletCode) ?>;
const USER_NAME   = <?= json_encode($user['username']) ?>;
const CHECKLIST   = <?= json_encode($checklistData['checklist'],  JSON_UNESCAPED_UNICODE) ?>;
const DATA_FIELDS = <?= json_encode($checklistData['dataFields'], JSON_UNESCAPED_UNICODE) ?>;

let state = { shift:'open', checks:{}, inputs:{}, locked:false };

/* ── localStorage ── */
function todayStr() {
  const d = new Date();
  return d.getFullYear()+'-'+String(d.getMonth()+1).padStart(2,'0')+'-'+String(d.getDate()).padStart(2,'0');
}
function lsKey(shift) { return 'ss_checklist_'+OUTLET_ID+'_'+todayStr()+'_'+shift; }
function lsSave() {
  try { localStorage.setItem(lsKey(state.shift), JSON.stringify({checks:state.checks,inputs:state.inputs,locked:state.locked})); } catch(e){}
}
function lsLoad(shift) {
  try { const r=localStorage.getItem(lsKey(shift)); return r?JSON.parse(r):null; } catch(e){return null;}
}

/* ── Init ── */
async function init() {
  const d = new Date();
  document.getElementById('dateLabel').textContent =
    d.toLocaleDateString('id-ID',{weekday:'long',day:'numeric',month:'long',year:'numeric'}) +
    ' · Shift Pagi';
  await loadFromServer(state.shift);
  render();
}

async function loadFromServer(shift) {
  try {
    const res = await fetch(`/api/checklists?outlet=${OUTLET_ID}&date=${todayStr()}&shift=${shift}`);
    if (!res.ok) throw new Error();
    const data = await res.json();
    const submission = data?.data?.submission ?? data?.submission ?? null;
    if (submission) {
      const s = submission;
      state.checks = s.checks || {};
      state.inputs = Object.assign({}, s.data_fields||{}, {
        pic_name: s.pic_name||'', spv_name: s.spv_name||'', handover: s.handover_note||''
      });
      state.locked = s.locked || false;
      lsSave(); return;
    }
  } catch(e){}
  const saved = lsLoad(shift);
  if (saved) { state.checks=saved.checks||{}; state.inputs=saved.inputs||{}; state.locked=saved.locked||false; }
  else        { state.checks={}; state.inputs={}; state.locked=false; }
}

async function setShift(s) {
  const prevPic = state.inputs['pic_name']||'';
  state.shift = s;
  ['open','ops','close'].forEach(t=>document.getElementById('tab-'+t).classList.toggle('active',t===s));
  await loadFromServer(s);
  if (!state.inputs['pic_name'] && prevPic) state.inputs['pic_name']=prevPic;
  render();
  window.scrollTo({top:0,behavior:'smooth'});
}

function toggleCheck(id) {
  if (state.locked) return;
  state.checks[id] = !state.checks[id];
  lsSave(); render();
}

function saveInput(id,v) {
  if (state.locked) return;
  state.inputs[id]=v; lsSave();
}

/* ── Stats ── */
function getStats() {
  const secs=CHECKLIST[state.shift];
  let total=0,done=0,critTotal=0,critDone=0;
  secs.forEach(sec=>sec.items.forEach(item=>{
    total++; if(state.checks[item.id])done++;
    if(item.badge==='ibadge-crit'){critTotal++;if(state.checks[item.id])critDone++;}
  }));
  return {total,done,critTotal,critDone};
}

/* ── Render ── */
function render() {
  const {total,done,critTotal,critDone}=getStats();
  const pct=total?Math.round(done/total*100):0;

  /* Update date label to include shift name */
  const shiftLabel={'open':'Pembukaan','ops':'Operasional','close':'Penutupan'}[state.shift];
  const d=new Date();
  document.getElementById('dateLabel').textContent=
    d.toLocaleDateString('id-ID',{weekday:'long',day:'numeric',month:'long'})+'  ·  Shift '+shiftLabel;

  /* Progress */
  document.getElementById('progNum').innerHTML=`${done}<span class="denom">/${total}</span>`;
  document.getElementById('progBar').style.width=pct+'%';

  /* Submit btn */
  const btn=document.getElementById('submitBtn');
  if (state.locked) {
    btn.textContent='Tersimpan ✓'; btn.className='submit-btn success'; btn.disabled=true;
  } else if (critDone<critTotal) {
    btn.textContent=`${critDone}/${critTotal} KRITIKAL — Belum Bisa Submit`; btn.className='submit-btn dim'; btn.disabled=false;
  } else if (done===total&&total>0) {
    btn.textContent='Submit & Kirim ke Supervisor ✓'; btn.className='submit-btn'; btn.disabled=false;
  } else {
    btn.textContent=`${done}/${total} Item — Selesaikan Checklist`; btn.className='submit-btn dim'; btn.disabled=false;
  }

  /* Build HTML */
  const sectionIcons={'open':'🌅','ops':'☀️','close':'🌙'};
  const sectionIconClass={'open':'icon-opening','ops':'icon-ops','close':'icon-closing'};
  const fields=DATA_FIELDS[state.shift];
  const secs=CHECKLIST[state.shift];
  let html='';

  /* Locked banner */
  if (state.locked) {
    html+=`<div class="locked-banner visible">✅ Checklist shift ini sudah dikirim dan dikunci.</div>`;
  }

  /* Data fields */
  html+=`<div class="data-card"><div class="data-card-title">Data & Angka Shift Ini</div><div class="data-grid">`;
  fields.forEach(f=>{
    const v=state.inputs[f.id]||'';
    const ro=state.locked?'readonly':'';
    if (f.type==='text') {
      html+=`<div class="df" style="grid-column:1/-1"><label>${f.label}</label><textarea oninput="saveInput('${f.id}',this.value)" placeholder="Tulis catatan..." ${ro}>${v}</textarea></div>`;
    } else {
      html+=`<div class="df"><label>${f.label}</label><input type="number" value="${v}" placeholder="0" oninput="saveInput('${f.id}',this.value)" ${ro}></div>`;
    }
  });
  html+=`</div></div>`;

  /* Checklist sections */
  secs.forEach(sec=>{
    const secItems=[...sec.items].sort((a,b)=>(a.badge==='ibadge-crit'?0:1)-(b.badge==='ibadge-crit'?0:1));
    const secDone=sec.items.filter(i=>state.checks[i.id]).length;
    const icon=sectionIcons[state.shift]||'📋';
    const iconClass=sectionIconClass[state.shift]||'';

    html+=`<div class="ds-section">
      <div class="ds-section-header">
        <div class="ds-section-icon ${iconClass}">${icon}</div>
        <div class="ds-section-title">${sec.title}</div>
        <div class="ds-section-count">${secDone}/${sec.items.length}</div>
      </div>
      <div class="ds-cl-card">`;

    secItems.forEach(item=>{
      const isDone=!!state.checks[item.id];
      html+=`<div class="ds-cl-item" onclick="toggleCheck('${item.id}')">
        <div class="ds-cl-check${isDone?' done':''}">
          ${isDone?'✓':''}
        </div>
        <div class="ds-cl-text${isDone?' done':''}">${item.text}</div>
        ${item.badge?`<span class="ibadge ${item.badge}">${item.btext}</span>`:''}
      </div>`;
    });

    html+=`</div></div>`;
  });

  /* Sign / verifikasi */
  const ro=state.locked?'readonly':'';
  html+=`<div class="sign-card">
    <div class="sign-card-title">Verifikasi Shift</div>
    <div class="data-grid">
      <div class="df">
        <label>Nama PIC Shift <span style="color:var(--red)">*</span></label>
        <input type="text" placeholder="Nama lengkap (wajib)"
               id="picNameInput"
               value="${state.inputs['pic_name']||''}"
               oninput="saveInput('pic_name',this.value)" ${ro}>
      </div>
      <div class="df">
        <label>Nama Supervisor</label>
        <input type="text" placeholder="Nama supervisor"
               value="${state.inputs['spv_name']||''}"
               oninput="saveInput('spv_name',this.value)" ${ro}>
      </div>
      <div class="df" style="grid-column:1/-1">
        <label>Catatan untuk shift berikutnya</label>
        <textarea placeholder="Stok kritis, alat bermasalah, info penting..."
                  oninput="saveInput('handover',this.value)" ${ro}>${state.inputs['handover']||''}</textarea>
      </div>
    </div>
  </div>`;

  document.getElementById('mainArea').innerHTML=html;
}

/* ── Submit ── */
async function handleSubmit() {
  if (state.locked) return;

  const {critTotal,critDone,total,done}=getStats();
  const critLeft=critTotal-critDone;

  if (critLeft>0) { showToast(critLeft+' item KRITIKAL belum selesai!','error'); return; }

  const picName=(state.inputs['pic_name']||'').trim();
  if (picName.length<3) {
    showToast('Isi nama PIC shift terlebih dahulu (min. 3 huruf).','warn');
    const inp=document.getElementById('picNameInput');
    if (inp) { inp.style.borderColor='var(--red)'; inp.focus(); inp.scrollIntoView({behavior:'smooth',block:'center'}); inp.addEventListener('input',()=>inp.style.borderColor='',{once:true}); }
    return;
  }

  if (done<total && !confirm((total-done)+' item belum dicentang. Tetap submit?')) return;

  const btn=document.getElementById('submitBtn');
  btn.disabled=true; btn.textContent='Menyimpan...';

  try {
    const res=await fetch('/api/checklists',{
      method:'POST', headers:{'Content-Type':'application/json','X-CSRF-Token':getCsrfToken()},
      body:JSON.stringify({shift:state.shift, pic_name:state.inputs['pic_name']||'',
        spv_name:state.inputs['spv_name']||'', handover_note:state.inputs['handover']||'',
        checks:state.checks, inputs:state.inputs})
    });
    const data=await res.json();
    if (!res.ok) { showToast(data.message||'Gagal menyimpan. Coba lagi.','error'); btn.disabled=false; render(); return; }
    state.locked=true; lsSave(); render();
    showToast(data.late?'Tersimpan ✓ (terlambat dari window shift)':'Checklist berhasil dikirim ke supervisor ✓','success');
  } catch(e) {
    showToast('Server tidak merespons. Coba lagi, atau hubungi admin.','error');
    btn.disabled=false; render();
  }
}

/* ── Logout ── */
async function handleLogout() {
  if (!confirm('Yakin mau keluar?')) return;
  try { await fetch('/api/auth/logout',{method:'POST'}); } catch(e){}
  window.location.href='/login';
}

/* ── Toast ── */
function showToast(msg, type) {
  const t=document.getElementById('toast');
  t.textContent=msg;
  t.className='ds-toast show'+(type?' '+type:'');
  clearTimeout(t._timer);
  t._timer=setTimeout(()=>t.classList.remove('show'),3000);
}

init();
</script>
<script src="/assets/js/idle-logout.js"></script>
</body>
</html>
