/**
 * SS Operations — Stress Test Skenario A
 * Login Serentak (Beban Ringan)
 *
 * Cara jalankan:
 *   k6 run test-A-login.js
 *
 * Isi bagian AKUN di bawah dengan username + password hasil Reset Semua Password
 */

import http from 'k6/http';
import { check, sleep } from 'k6';

// ── KONFIGURASI ──────────────────────────────────────────────────────────────
const BASE_URL = 'https://ops.sukashawarma.com';

// ── AKUN TEST ─────────────────────────────────────────────────────────────────
// Ganti password di bawah dengan hasil Reset Semua Password tadi
// Tambah/kurangi baris sesuai jumlah akun yang mau ditest
const ACCOUNTS = [
  { username: 'ss.beji',          password: '4KhqQDEY' },
  { username: 'ss.bekasi',        password: 'uZiLWyXU' },
  { username: 'ss.cibinong',      password: 'YQEXCmDv' },
  { username: 'ss.cimanggu',      password: 'f5vShSQc' },
  { username: 'ss.cirendeu',      password: 'F9utv725' },
  { username: 'ss.ciseeng',       password: '9CYH8HY3' },
  { username: 'ss.citayam',       password: 'HPyXZEsk' },
  { username: 'ss.dramaga',       password: 'S5SKwKGp' },
  { username: 'ss.empang',        password: 'AxaDfCAg' },
  { username: 'ss.jagakarsa',     password: 'pFSZU3hY' },
  { username: 'ss.jatiasih',      password: 'dTLXqbsT' },
  { username: 'ss.jatiwaringin',  password: 'KHhYFSxg' },
  { username: 'ss.kalisari',      password: 'rmZgjY8Q' },
  { username: 'ss.pajajaran',     password: '3J8HeMi3' },
  { username: 'ss.paledang',      password: 'EF4YkMX9' },
  { username: 'ss.pekayon',       password: '269dvb2w' },
  { username: 'ss.sawangan',      password: 'GvXrt5sP' },
  { username: 'ss.sukmajaya',     password: 'PsPBf5ib' },
  { username: 'ss.tebet',         password: '9ULrUuD3' },
  { username: 'spv_utama',        password: 'sW6piPzN' },
  { username: 'owner_ss',         password: 'prQqYKwv' },
];

// ── OPSI TEST ─────────────────────────────────────────────────────────────────
export const options = {
  // Mode sequential: 1 user, 21 iterasi, jeda 3 detik tiap login
  // Tidak trigger firewall, verifikasi semua akun bisa login
  scenarios: {
    login_sequential: {
      executor: 'per-vu-iterations',
      vus: 1,           // 1 virtual user
      iterations: ACCOUNTS.length,  // loop 21x (1 per akun)
      maxDuration: '5m',
    },
  },
  thresholds: {
    'http_req_duration': ['p(95)<5000'],
    'http_req_failed':   ['rate<0.05'],
    'checks':            ['rate>0.95'],
  },
};

// ── FUNGSI UTAMA ──────────────────────────────────────────────────────────────
export default function () {
  // Pilih akun berdasarkan iterasi ke-berapa (mulai dari 0)
  const akun = ACCOUNTS[__ITER % ACCOUNTS.length];

  const payload = JSON.stringify({
    username: akun.username,
    password: akun.password,
  });

  const params = {
    headers: { 'Content-Type': 'application/json' },
    timeout: '15s',
  };

  // ── Request Login ──────────────────────────────────────────────────────────
  const res = http.post(`${BASE_URL}/api/auth/login`, payload, params);

  // ── Cek Hasil ─────────────────────────────────────────────────────────────
  const body = res.body || '';
  const loginOk = check(res, {
    '✅ Status HTTP 200':      (r) => r.status === 200,
    '✅ Response JSON valid':  (r) => {
      try { JSON.parse(r.body || ''); return true; } catch { return false; }
    },
    '✅ Login berhasil (ok)':  (r) => {
      try { return JSON.parse(r.body || '').ok === true; } catch { return false; }
    },
    '✅ Response < 5 detik':   (r) => r.timings.duration < 5000,
  });

  // Log per akun di console
  const durasi = Math.round(res.timings.duration);
  if (!loginOk) {
    console.error(`❌ GAGAL  [VU${__VU}] ${akun.username} — HTTP ${res.status} — ${durasi}ms — ${body.substring(0, 120)}`);
  } else {
    console.log(`✅ OK     [VU${__VU}] ${akun.username} — ${durasi}ms`);
  }

  sleep(3); // jeda 3 detik antar login supaya tidak trigger firewall
}

// ── Ringkasan di Akhir ────────────────────────────────────────────────────────
export function handleSummary(data) {
  const req    = data.metrics.http_reqs?.values?.count || 0;
  const failed = data.metrics.http_req_failed?.values?.rate || 0;
  const p95    = Math.round(data.metrics.http_req_duration?.values?.['p(95)'] || 0);
  const avg    = Math.round(data.metrics.http_req_duration?.values?.avg || 0);

  const lulus = failed < 0.05 && p95 < 3000;

  return {
    stdout: `
╔══════════════════════════════════════════════════════════╗
║       SS Operations — Hasil Skenario A: Login Serentak       ║
╠══════════════════════════════════════════════════════════╣
║  Total Request   : ${String(req).padEnd(38)}║
║  Error Rate      : ${(failed * 100).toFixed(1).padEnd(37)}%║
║  Response Avg    : ${String(avg + ' ms').padEnd(38)}║
║  Response p95    : ${String(p95 + ' ms').padEnd(38)}║
╠══════════════════════════════════════════════════════════╣
║  Status          : ${(lulus ? '✅ LULUS' : '❌ GAGAL').padEnd(38)}║
╚══════════════════════════════════════════════════════════╝
`,
  };
}
