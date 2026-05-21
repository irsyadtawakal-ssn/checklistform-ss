# Rencana Stress Test — SS Operations Webapp
**Versi**: 1.0  
**Tanggal dibuat**: 2026-05-16  
**Stack**: PHP 8.1 + MySQL + cPanel Shared Hosting  
**Target**: ops.sukashawarma.com  

---

## 1. Tujuan

Memastikan sistem tetap stabil dan responsif ketika:
- Semua 19 outlet submit checklist di waktu yang hampir bersamaan (peak jam buka & tutup)
- SPV melakukan kunjungan dan upload foto bersamaan
- Owner & SPV membuka dashboard secara bersamaan
- Data harian menumpuk selama 30+ hari

---

## 2. Scope & Batasan

| Item | Masuk Scope | Di Luar Scope |
|------|-------------|---------------|
| Login concurrent | ✅ | |
| Submit checklist concurrent | ✅ | |
| Upload foto | ✅ | |
| Dashboard load | ✅ | |
| Export Excel / PDF | ✅ | |
| WhatsApp notification (Fonnte) | | ❌ (eksternal) |
| Email digest | | ❌ (eksternal) |
| Penetration test / security | | ❌ (scope terpisah) |

---

## 3. Skenario Test

### Skenario A — Login Serentak (Beban Ringan)
**Deskripsi**: Simulasi 19 PIC outlet + 1 SPV + 1 Owner login dalam waktu ±2 menit  
**Target**: Semua berhasil login, response time < 3 detik  
**Cara manual**:
- Buka 5 browser/tab, login dengan akun berbeda secara berurutan cepat
- Atau gunakan tool (lihat bagian 5)

| Metric | Target Lulus |
|--------|-------------|
| Login success rate | 100% |
| Response time | < 3 detik |
| Error rate | 0% |

---

### Skenario B — Submit Checklist Concurrent (Beban Utama)
**Deskripsi**: 19 outlet submit checklist shift Pembukaan dalam rentang 10 menit  
**Ini adalah skenario paling kritis** — terjadi setiap hari jam 07:00–09:00

**Langkah manual**:
1. Minta 3–5 orang bantu login dari device berbeda (HP/laptop)
2. Masing-masing isi checklist lengkap (semua item + nama PIC)
3. Submit bersamaan
4. Pantau apakah semua muncul di dashboard

| Metric | Target Lulus |
|--------|-------------|
| Submit success rate | 100% |
| Data muncul di dashboard | Ya, real-time |
| Tidak ada duplikasi submission | Ya |
| Response time submit | < 5 detik |

---

### Skenario C — Upload Foto Concurrent
**Deskripsi**: 2–3 SPV upload foto kunjungan bersamaan  
**Foto**: masing-masing 3–5 foto, ukuran asli sebelum kompres 2–5 MB

| Metric | Target Lulus |
|--------|-------------|
| Upload success rate | ≥ 90% |
| File tersimpan di server | Ya |
| Tidak ada file corrupt | Ya |
| Timeout (> 30 detik) | 0 kasus |

---

### Skenario D — Dashboard Load dengan Data Penuh
**Deskripsi**: 3 user (Owner, SPV, Admin) buka dashboard bersamaan dengan filter range 30 hari  
**Dilakukan setelah** ada minimal 7 hari data real dari UAT pilot

| Metric | Target Lulus |
|--------|-------------|
| Grid 19 outlet × 30 hari render | < 5 detik |
| Filter tanggal responsif | < 2 detik |
| Export Excel 30 hari | Berhasil, < 30 detik |
| Tidak ada blank/error page | Ya |

---

### Skenario E — Endurance Test (Opsional)
**Deskripsi**: Simulasi aktivitas normal selama 7 hari UAT pilot  
**Ini otomatis terjadi** selama fase UAT — tidak perlu simulasi terpisah  
**Yang dipantau**:
- Ukuran file `logs/app.log` (tidak boleh membengkak)
- Ukuran folder `uploads/` (tumbuh wajar)
- Response time di hari ke-7 vs hari ke-1 (tidak boleh jauh lebih lambat)
- Backup harian berjalan (cek folder `backup/`)

---

## 4. Checklist Persiapan Sebelum Test

### 4.1 Server
- [ ] Backup database sebelum mulai (`mysqldump` manual via cPanel)
- [ ] Catat PHP memory_limit saat ini (cPanel → PHP Version → ini settings)
- [ ] Catat `max_execution_time` (biasanya 30 detik di shared hosting)
- [ ] Catat `upload_max_filesize` dan `post_max_size`
- [ ] Aktifkan error log: pastikan `logs/app.log` bisa ditulis
- [ ] Kosongkan `tmp/rate_limit/` agar tidak kena blokir saat test login banyak

### 4.2 Data
- [ ] Pastikan 19 akun outlet seed aktif (`active = 1`)
- [ ] Password semua akun sudah di-reset dari default `ss1234!`
- [ ] Data dummy submissions dari QA sudah dibersihkan (opsional)

### 4.3 Tim Test
- [ ] Minimal 3 orang tester dengan device berbeda (HP Android, iOS, laptop)
- [ ] WhatsApp group koordinasi untuk submit bersamaan
- [ ] Spreadsheet pencatat hasil test (lihat bagian 6)

---

## 5. Tools (Opsional — untuk Simulasi Otomatis)

> Untuk shared hosting cPanel, **test manual dengan banyak orang** sudah cukup untuk tahap ini. Tools di bawah untuk skala lebih besar atau otomasi.

| Tool | Kegunaan | Level |
|------|----------|-------|
| **k6** (gratis) | Simulasi ratusan concurrent user dari laptop | Menengah |
| **Apache JMeter** | GUI-based load test, bisa record scenario | Menengah |
| **Postman Collection Runner** | Jalankan sequence API calls, mudah dipakai | Mudah |
| **Browser DevTools Throttling** | Simulasi koneksi lambat (3G) | Mudah |

### Contoh Test Manual dengan Postman
1. Buat collection: Login → GET Checklist → POST Checklist
2. Set variable `{{outlet_id}}`, `{{username}}`, `{{password}}`
3. Jalankan Collection Runner dengan 19 iterasi (1 per outlet)
4. Pantau response time & status code

---

## 6. Lembar Pencatat Hasil

### Template per Skenario

| # | Skenario | Waktu Mulai | Waktu Selesai | Success | Error | Response Time Avg | Status |
|---|----------|-------------|---------------|---------|-------|-------------------|--------|
| A | Login Serentak | | | /19 | | ms | LULUS/GAGAL |
| B | Submit Concurrent | | | /19 | | ms | LULUS/GAGAL |
| C | Upload Foto | | | /foto | | ms | LULUS/GAGAL |
| D | Dashboard Load | | | | | ms | LULUS/GAGAL |

### Catatan Error
| Waktu | Skenario | Error Message | File Log | Status Resolve |
|-------|----------|---------------|----------|----------------|
| | | | | |

---

## 7. Kriteria Lulus (Go/No-Go)

### WAJIB LULUS sebelum Full Roll-out
- [ ] Skenario B (submit concurrent) lulus 100% tanpa error
- [ ] Tidak ada data submission yang hilang atau terduplikasi
- [ ] Dashboard load dalam < 5 detik untuk range 30 hari
- [ ] Error log tidak menampilkan fatal error atau DB connection error

### BOLEH ADA PERBAIKAN (tidak block roll-out)
- [ ] Response time > target (< 3 detik) tapi masih < 8 detik
- [ ] Upload foto sesekali timeout (sudah ada retry mechanism)
- [ ] Export Excel lambat untuk data > 60 hari

### BLOCK ROLL-OUT (harus fix dulu)
- [ ] Ada submission yang gagal tanpa error message
- [ ] Database connection error / too many connections
- [ ] Session logout sendiri saat dipakai
- [ ] Data mismatch antara submission dan yang tampil di dashboard

---

## 8. Jadwal Eksekusi

| Fase | Kapan | Durasi | PIC |
|------|-------|--------|-----|
| Persiapan (checklist 4.1–4.3) | Sebelum UAT pilot | 1 hari | Admin |
| Skenario A + B (manual) | Hari pertama UAT pilot | 2 jam | Tim test |
| Skenario C (foto upload) | Hari pertama UAT pilot | 1 jam | SPV |
| Skenario D (dashboard) | Hari ke-3 UAT (ada data) | 1 jam | Owner + SPV |
| Skenario E (endurance) | Sepanjang 7 hari UAT | Otomatis | Pantau harian |
| Review hasil & fix | Hari ke-8 setelah UAT | 1 hari | Dev |
| Full roll-out 19 outlet | Setelah semua LULUS | — | Admin |

---

## 9. Kontak & Eskalasi

| Kondisi | Tindakan |
|---------|----------|
| Error 500 muncul di halaman | Cek `logs/app.log` di cPanel |
| DB connection error | Cek cPanel → MySQL Databases → koneksi aktif |
| Server lambat / timeout | Cek cPanel → Resource Usage |
| Bug baru ditemukan | Catat di lembar error (bagian 6), laporkan ke dev |
| Data hilang | **STOP TEST** — restore dari backup, investigasi dulu |

---

*Dokumen ini diperbarui sesuai hasil test. Versi final disimpan setelah full roll-out selesai.*
