/**
 * SS Operations — Stress Test Skenario B
 * Submit Checklist Concurrent (Beban Utama)
 *
 * Cara jalankan:
 *   k6 run test-B-checklist.js
 *
 * Skenario: 19 outlet login lalu submit checklist shift Open
 * dalam rentang waktu ~30 detik (simulasi jam buka pagi)
 */

import http from 'k6/http';
import { check, sleep } from 'k6';
import { CookieJar } from 'k6/http';

// ── KONFIGURASI ───────────────────────────────────────────────────────────────
const BASE_URL = 'https://ops.sukashawarma.com';
const SHIFT    = 'open'; // shift yang ditest: open / ops / close

// ── AKUN OUTLET (19 akun) ─────────────────────────────────────────────────────
const ACCOUNTS = [
  { username: 'ss.beji',         password: '4KhqQDEY' },
  { username: 'ss.bekasi',       password: 'uZiLWyXU' },
  { username: 'ss.cibinong',     password: 'YQEXCmDv' },
  { username: 'ss.cimanggu',     password: 'f5vShSQc' },
  { username: 'ss.cirendeu',     password: 'F9utv725' },
  { username: 'ss.ciseeng',      password: '9CYH8HY3' },
  { username: 'ss.citayam',      password: 'HPyXZEsk' },
  { username: 'ss.dramaga',      password: 'S5SKwKGp' },
  { username: 'ss.empang',       password: 'AxaDfCAg' },
  { username: 'ss.jagakarsa',    password: 'pFSZU3hY' },
  { username: 'ss.jatiasih',     password: 'dTLXqbsT' },
  { username: 'ss.jatiwaringin', password: 'KHhYFSxg' },
  { username: 'ss.kalisari',     password: 'rmZgjY8Q' },
  { username: 'ss.pajajaran',    password: '3J8HeMi3' },
  { username: 'ss.paledang',     password: 'EF4YkMX9' },
  { username: 'ss.pekayon',      password: '269dvb2w' },
  { username: 'ss.sawangan',     password: 'GvXrt5sP' },
  { username: 'ss.sukmajaya',    password: 'PsPBf5ib' },
  { username: 'ss.tebet',        password: '9ULrUuD3' },
];

// ── SEMUA ITEM CHECKLIST SHIFT OPEN (49 item) ─────────────────────────────────
// Semua di-centang = simulasi PIC yang menyelesaikan semua checklist
const ALL_OPEN_ITEMS = {
  // Seksi 1: Keamanan & Akses
  o1a: true, o1b: true, o1c: true, o1d: true, o1e: true, o1f: true, o1g: true,
  // Seksi 2: Stok & Bahan Baku
  o2a: true, o2b: true, o2c: true, o2d: true, o2e: true, o2f: true,
  o2g: true, o2h: true, o2i: true,
  // Seksi 3: Peralatan Masak
  o3a: true, o3b: true, o3c: true, o3d: true, o3e: true, o3f: true,
  o3g: true, o3h: true, o3i: true, o3j: true, o3k: true,
  // Seksi 4: Kebersihan Pre-Opening
  o4a: true, o4b: true, o4c: true, o4d: true, o4e: true, o4f: true, o4g: true,
  // Seksi 5: Kasir, Digital & Signage
  o5a: true, o5b: true, o5c: true, o5d: true, o5e: true, o5f: true,
  o5g: true, o5h: true, o5i: true, o5j: true, o5k: true,
  // Seksi 6: ATK & Administrasi
  o6a: true, o6b: true, o6c: true, o6d: true,
};

// ── OPSI TEST ─────────────────────────────────────────────────────────────────
export const options = {
  scenarios: {
    submit_concurrent: {
      executor: 'per-vu-iterations',
      vus: ACCOUNTS.length,   // 19 virtual user = 19 outlet
      iterations: 1,          // masing-masing submit 1x
      maxDuration: '5m',
    },
  },
  thresholds: {
    'http_req_duration{step:login}':  ['p(95)<5000'],
    'http_req_duration{step:submit}': ['p(95)<8000'],
    'http_req_failed':                ['rate<0.05'],
    'checks':                         ['rate>0.90'],
  },
};

// ── FUNGSI UTAMA ──────────────────────────────────────────────────────────────
export default function () {
  const akun = ACCOUNTS[(__VU - 1) % ACCOUNTS.length];

  // Jeda acak 0–20 detik — meniru outlet buka di waktu berbeda-beda
  sleep(Math.random() * 20);

  const jar    = new CookieJar();
  const params = { jar, headers: { 'Content-Type': 'application/json' }, timeout: '15s' };

  // ── Step 1: Login ──────────────────────────────────────────────────────────
  const loginRes = http.post(
    `${BASE_URL}/api/auth/login`,
    JSON.stringify({ username: akun.username, password: akun.password }),
    { ...params, tags: { step: 'login' } }
  );

  const loginBody = loginRes.body || '';
  const loginOk = check(loginRes, {
    '✅ Login: HTTP 200':     (r) => r.status === 200,
    '✅ Login: ok:true':      (r) => { try { return JSON.parse(r.body||'').ok === true; } catch { return false; } },
    '✅ Login: < 5 detik':    (r) => r.timings.duration < 5000,
  });

  if (!loginOk) {
    console.error(`❌ LOGIN GAGAL [VU${__VU}] ${akun.username} — HTTP ${loginRes.status} — ${loginBody.substring(0, 100)}`);
    return; // stop, tidak lanjut submit
  }

  console.log(`✅ Login OK   [VU${__VU}] ${akun.username} — ${Math.round(loginRes.timings.duration)}ms`);

  // Jeda singkat setelah login (meniru user membuka form)
  sleep(1 + Math.random() * 3);

  // ── Step 2: Submit Checklist ───────────────────────────────────────────────
  const submitPayload = JSON.stringify({
    shift:    SHIFT,
    pic_name: `PIC Test ${akun.username}`,
    checks:   ALL_OPEN_ITEMS,
    inputs:   {},
  });

  const submitRes = http.post(
    `${BASE_URL}/api/checklists`,
    submitPayload,
    { ...params, tags: { step: 'submit' } }
  );

  const submitBody = submitRes.body || '';
  let submissionId = null;
  try {
    const parsed = JSON.parse(submitBody);
    submissionId = parsed?.data?.submission_id || null;
  } catch {}

  const submitOk = check(submitRes, {
    '✅ Submit: HTTP 200/201':   (r) => r.status === 200 || r.status === 201,
    '✅ Submit: ok:true':        (r) => { try { return JSON.parse(r.body||'').ok === true; } catch { return false; } },
    '✅ Submit: ada submission_id': () => submissionId !== null,
    '✅ Submit: < 8 detik':      (r) => r.timings.duration < 8000,
  });

  if (!submitOk) {
    console.error(`❌ SUBMIT GAGAL [VU${__VU}] ${akun.username} — HTTP ${submitRes.status} — ${submitBody.substring(0, 150)}`);
  } else {
    console.log(`✅ Submit OK   [VU${__VU}] ${akun.username} — ID:${submissionId} — ${Math.round(submitRes.timings.duration)}ms`);
  }
}

// ── Ringkasan ─────────────────────────────────────────────────────────────────
export function handleSummary(data) {
  const loginDur  = Math.round(data.metrics['http_req_duration{step:login}']?.values?.['p(95)']  || 0);
  const submitDur = Math.round(data.metrics['http_req_duration{step:submit}']?.values?.['p(95)'] || 0);
  const failed    = ((data.metrics.http_req_failed?.values?.rate || 0) * 100).toFixed(1);
  const checks    = ((data.metrics.checks?.values?.rate || 0) * 100).toFixed(1);
  const reqs      = data.metrics.http_reqs?.values?.count || 0;

  const lulus = parseFloat(failed) < 5 && parseFloat(checks) > 90;

  return {
    stdout: `
╔══════════════════════════════════════════════════════════════╗
║     SS Operations — Hasil Skenario B: Submit Checklist       ║
╠══════════════════════════════════════════════════════════════╣
║  Total Request        : ${String(reqs).padEnd(35)}║
║  Error Rate           : ${String(failed + '%').padEnd(35)}║
║  Checks Lulus         : ${String(checks + '%').padEnd(35)}║
║  Login Response p95   : ${String(loginDur + ' ms').padEnd(35)}║
║  Submit Response p95  : ${String(submitDur + ' ms').padEnd(35)}║
╠══════════════════════════════════════════════════════════════╣
║  Status               : ${(lulus ? '✅ LULUS' : '❌ GAGAL').padEnd(35)}║
╚══════════════════════════════════════════════════════════════╝
`,
  };
}
