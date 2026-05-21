/**
 * SS Operations — Stress Test Skenario C
 * Upload Foto Concurrent
 *
 * Persiapan:
 *   Taruh 1 file foto (JPG, 1–3 MB) di folder stress-test ini,
 *   beri nama: test-photo.jpg
 *
 * Cara jalankan:
 *   k6 run test-C-photos.js
 *
 * Skenario: SPV login → buat visit → upload 3 foto berurutan
 * Simulasi SPV kunjungan outlet dan foto dokumentasi
 */

import http from 'k6/http';
import { check, sleep } from 'k6';
import { CookieJar } from 'k6/http';
import encoding from 'k6/encoding';

// ── KONFIGURASI ───────────────────────────────────────────────────────────────
const BASE_URL  = 'https://ops.sukashawarma.com';
const FOTO_PATH = './test-photo.jpg';  // file foto di folder stress-test

// ── Baca file foto sekali saat startup ───────────────────────────────────────
const fotoBytes = open(FOTO_PATH, 'b');

// ── AKUN SPV ──────────────────────────────────────────────────────────────────
const SPV = { username: 'spv_utama', password: 'sW6piPzN' };

// ── Outlet yang dikunjungi (pakai beberapa untuk variasi) ─────────────────────
// outlet_id sesuai database — kita pakai 3 outlet berbeda untuk 3 iterasi
// Ganti angka ini kalau outlet_id di DB kamu berbeda
const OUTLET_IDS = [1, 2, 3];

// ── OPSI TEST ─────────────────────────────────────────────────────────────────
export const options = {
  scenarios: {
    upload_foto: {
      executor: 'per-vu-iterations',
      vus: 3,           // 3 virtual user = 3 SPV "simulasi" upload bersamaan
      iterations: 1,    // masing-masing 1x
      maxDuration: '5m',
    },
  },
  thresholds: {
    'http_req_duration{step:login}':       ['p(95)<5000'],
    'http_req_duration{step:create_visit}':['p(95)<5000'],
    'http_req_duration{step:upload_foto}': ['p(95)<30000'], // max 30 detik per foto
    'http_req_failed':                     ['rate<0.10'],   // toleransi error 10%
    'checks':                              ['rate>0.85'],
  },
};

// ── FUNGSI UTAMA ──────────────────────────────────────────────────────────────
export default function () {
  const vuIdx    = (__VU - 1) % OUTLET_IDS.length;
  const outletId = OUTLET_IDS[vuIdx];

  // Jeda acak supaya tidak semua mulai bersamaan
  sleep(Math.random() * 5);

  const jar    = new CookieJar();
  const browserUA = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36';
  const jsonParams = { jar, headers: { 'Content-Type': 'application/json', 'User-Agent': browserUA }, timeout: '15s' };

  // ── Step 1: Login SPV ──────────────────────────────────────────────────────
  const loginRes = http.post(
    `${BASE_URL}/api/auth/login`,
    JSON.stringify({ username: SPV.username, password: SPV.password }),
    { ...jsonParams, tags: { step: 'login' } }
  );

  const loginOk = check(loginRes, {
    '✅ Login: HTTP 200':  (r) => r.status === 200,
    '✅ Login: ok:true':   (r) => { try { return JSON.parse(r.body||'').ok === true; } catch { return false; } },
  });

  if (!loginOk) {
    console.error(`❌ LOGIN GAGAL [VU${__VU}] — ${loginRes.status} — ${(loginRes.body||'').substring(0,100)}`);
    return;
  }
  console.log(`✅ Login OK   [VU${__VU}] spv_utama — ${Math.round(loginRes.timings.duration)}ms`);

  sleep(1);

  // ── Step 2: Buat SPV Visit ─────────────────────────────────────────────────
  const visitPayload = JSON.stringify({
    outlet_id:    outletId,
    visit_date:   new Date().toISOString().split('T')[0],
    time_arrive:  '09:00',
    time_leave:   '10:00',
    visit_shift:  'open',
    pic_on_duty:  'PIC Test',
    payload_json: { catatan_umum: 'Test kunjungan stress test' },
    employees:    [],
  });

  const visitRes = http.post(
    `${BASE_URL}/api/spv-visits`,
    visitPayload,
    { ...jsonParams, tags: { step: 'create_visit' } }
  );

  let visitId = null;
  try { visitId = JSON.parse(visitRes.body||'').data?.visit_id || null; } catch {}

  const visitOk = check(visitRes, {
    '✅ Visit: HTTP 200/201':  (r) => r.status === 200 || r.status === 201,
    '✅ Visit: ada visit_id':  () => visitId !== null,
  });

  if (!visitOk) {
    console.error(`❌ CREATE VISIT GAGAL [VU${__VU}] outlet_id:${outletId} — ${visitRes.status} — ${(visitRes.body||'').substring(0,150)}`);
    return;
  }
  console.log(`✅ Visit OK   [VU${__VU}] outlet_id:${outletId} → visit_id:${visitId} — ${Math.round(visitRes.timings.duration)}ms`);

  sleep(1);

  // ── Step 3: Upload 3 Foto (Base64 JSON — bypass ModSecurity) ─────────────
  const captions = ['Kondisi outlet tampak depan', 'Area dapur & peralatan', 'Stok bahan baku'];

  // Encode file bytes ke base64
  const b64String = encoding.b64encode(fotoBytes);
  const dataUri   = 'data:image/jpeg;base64,' + b64String;

  for (let i = 0; i < 3; i++) {
    const uploadBody = JSON.stringify({
      data:  dataUri,
      label: captions[i],
      tags:  ['test', `foto-${i+1}`],
    });

    const uploadRes = http.post(
      `${BASE_URL}/api/spv-visits/${visitId}/photos`,
      uploadBody,
      { ...jsonParams, timeout: '35s', tags: { step: 'upload_foto' } }
    );

    let photoId = null;
    try { photoId = JSON.parse(uploadRes.body||'').data?.photo_id || null; } catch {}

    const uploadOk = check(uploadRes, {
      '✅ Upload: HTTP 200/201':  (r) => r.status === 200 || r.status === 201,
      '✅ Upload: ada photo_id':  () => photoId !== null,
      '✅ Upload: < 30 detik':    (r) => r.timings.duration < 30000,
    });

    if (!uploadOk) {
      console.error(`❌ UPLOAD FOTO ${i+1} GAGAL [VU${__VU}] — ${uploadRes.status} — ${(uploadRes.body||'').substring(0,150)}`);
    } else {
      console.log(`✅ Foto ${i+1}/3 OK [VU${__VU}] photo_id:${photoId} — ${Math.round(uploadRes.timings.duration)}ms`);
    }

    sleep(2); // jeda antar foto
  }
}

// ── Ringkasan ─────────────────────────────────────────────────────────────────
export function handleSummary(data) {
  const loginP95  = Math.round(data.metrics['http_req_duration{step:login}']?.values?.['p(95)']        || 0);
  const visitP95  = Math.round(data.metrics['http_req_duration{step:create_visit}']?.values?.['p(95)'] || 0);
  const uploadP95 = Math.round(data.metrics['http_req_duration{step:upload_foto}']?.values?.['p(95)']  || 0);
  const uploadAvg = Math.round(data.metrics['http_req_duration{step:upload_foto}']?.values?.avg        || 0);
  const failed    = ((data.metrics.http_req_failed?.values?.rate || 0) * 100).toFixed(1);
  const checks    = ((data.metrics.checks?.values?.rate || 0) * 100).toFixed(1);

  const lulus = parseFloat(failed) < 10 && uploadP95 < 30000 && parseFloat(checks) > 85;

  return {
    stdout: `
╔══════════════════════════════════════════════════════════════╗
║      SS Operations — Hasil Skenario C: Upload Foto           ║
╠══════════════════════════════════════════════════════════════╣
║  Error Rate            : ${String(failed + '%').padEnd(34)}║
║  Checks Lulus          : ${String(checks + '%').padEnd(34)}║
║  Login Response p95    : ${String(loginP95 + ' ms').padEnd(34)}║
║  Create Visit p95      : ${String(visitP95 + ' ms').padEnd(34)}║
║  Upload Foto avg       : ${String(uploadAvg + ' ms').padEnd(34)}║
║  Upload Foto p95       : ${String(uploadP95 + ' ms').padEnd(34)}║
╠══════════════════════════════════════════════════════════════╣
║  Status                : ${(lulus ? '✅ LULUS' : '❌ GAGAL').padEnd(34)}║
╚══════════════════════════════════════════════════════════════╝
`,
  };
}
