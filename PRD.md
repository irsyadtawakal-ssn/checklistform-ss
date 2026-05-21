# PRD — SS Operations Webapp (Suka Shawarma)

**Versi:** 1.0
**Tanggal:** 2026-05-15
**Status:** Draft untuk review owner

---

## 1. Ringkasan Eksekutif

Webapp internal untuk operasional harian dan kontrol multi-outlet bisnis F&B **Suka Shawarma** (13 outlet internal + 6 mitra = 19 outlet aktif). Tujuan utamanya adalah **menggantikan checklist manual (Google Docs, kertas, WhatsApp)** dengan satu sistem digital terpusat yang:

1. Memastikan setiap outlet menjalankan SOP harian 3-shift dengan disiplin.
2. Memberi SPV alat audit terstruktur saat visit outlet.
3. Memberi Owner & SPV dashboard real-time terkait kepatuhan SOP semua outlet.

**Target utama (north-star metric):** % outlet yang menyelesaikan semua item KRITIKAL checklist harian, per shift, per hari — naik dari baseline (manual, sulit diukur) menjadi minimal 95% setelah 60 hari go-live.

---

## 2. Latar Belakang & Masalah

### Konteks bisnis
Suka Shawarma punya operasional kompleks: pengelolaan daging spit yang sensitif suhu, beberapa channel penjualan (dine-in, GoFood, GrabFood, ShopeeFood), kas fisik harian, dan inventaris alat dapur yang harus dijaga. 19 outlet di Jabodetabek dengan standar yang harus seragam.

### Masalah yang dipecahkan
- **Tidak ada visibility real-time** ke kepatuhan SOP harian per outlet.
- **Checklist kertas / Google Docs** mudah dimanipulasi, sulit di-audit, hilang.
- **SPV visit tidak terstruktur** — laporan kunjungan tidak konsisten antar SPV.
- **Tidak ada early warning** untuk outlet dengan item kritikal terlewat (food safety, selisih kas).
- **Data sales/waste/stock** tersebar di berbagai dokumen, tidak bisa dibandingkan antar outlet.

### Bukan masalah yang dipecahkan (out of scope MVP)
- POS / kasir transaksi (sudah pakai Pawoon).
- Aplikasi pesan-antar konsumen.
- HRIS lengkap (payroll, absensi biometrik) — kecuali tracking kehadiran sederhana via login.
- Akuntansi / pembukuan formal.

---

## 3. User & Role

| Role | Deskripsi | Akses Utama |
|------|-----------|-------------|
| **Outlet Account (PIC)** | **1 akun shared per outlet** untuk MVP. PIC yang bertugas mengisi "Nama PIC shift" sebagai text input tiap submit. Multi-user per outlet di Phase 2. | Form checklist harian untuk outlet-nya saja. Riwayat submission outlet-nya. |
| **SPV (Supervisor)** | Berkeliling ke beberapa outlet. Isi report kunjungan. Monitor checklist semua outlet. | Form SPV visit + dashboard semua outlet (read). Unlock submission untuk koreksi. |
| **Owner / Manager** | Manajemen puncak / pemilik. Hanya konsumsi data. | Dashboard agregat semua outlet, export PDF & Excel. |
| **Admin** | IT / operations admin. Kelola master data. | CRUD outlet, user, target sales. Item checklist hardcode di Phase 1. |

**Catatan:**
- Semua SPV bisa lihat semua outlet (tidak ada area-assignment di MVP).
- Mitra outlet tetap di list dan dipantau SPV, tapi **owner mitra tidak punya akun apapun** di Phase 1.
- Model auth: 1 user account per outlet (di-assign saat admin create), tapi schema sudah siap multi-user (kolom `users.outlet_id` bisa banyak baris merujuk outlet sama untuk Phase 2).

---

## 4. User Flow (High Level)

### 4.1 Flow PIC Outlet — Checklist Harian

```
Login (username+password outlet account, mis. ss_kitchen / ******)
  → Auto-detect outlet dari profil user (1 akun = 1 outlet)
  → Pilih shift (Pembukaan / Operasional / Penutupan)
  → Isi data shift (suhu, stok, kas, dll)
  → Centang item checklist per section
  → Item KRITIKAL wajib selesai sebelum submit
  → Verifikasi: NAMA PIC SHIFT (text input wajib, karena akun shared) +
                NAMA SPV penanggung jawab + catatan handover
  → Submit → form terkunci, status "Submitted" → backend simpan
  → (Auto-save localStorage tiap input agar tidak hilang)
```

**Catatan tracking pelaku:** karena 1 akun = 1 outlet (shared), field "Nama PIC shift" jadi satu-satunya cara identifikasi siapa yang benar-benar mengisi. Field ini **wajib diisi** sebelum submit dan tersimpan ke `checklist_submissions.pic_name` (free text). Phase 2 nanti, multi-user per outlet akan menggantikan field ini dengan auto-fill dari user login.

**Aturan bisnis:**
- 1 outlet × 1 tanggal × 1 shift = 1 submission. Tidak boleh duplikat.
- Item KRITIKAL tidak selesai → submit diblokir (sesuai mockup).
- Setelah submit, form di-lock. Hanya SPV/Admin yang bisa unlock untuk koreksi (audit log mencatat).
- Window pengisian shift: Pembukaan (06:00–10:00), Operasional (10:00–18:00), Penutupan (18:00–23:59). Tetap bisa isi di luar window tapi flagged "late".

### 4.2 Flow SPV — Visit Outlet

```
Login (username+password)
  → Tab "New Visit"
  → Pilih outlet + tanggal + jam tiba/keluar
  → 7 tab: Info → Inventaris → Penjualan → Stok → Karyawan → Foto → Ringkasan
  → Foto upload (kategori: exterior/dapur/stok/kasir/drip tray/karyawan/rusak/dll)
  → Generate ringkasan otomatis (auto-flagging: red/amber/green)
  → Submit → arsip
  → Bisa export PDF
```

### 4.3 Flow SPV — Monitoring Dashboard

```
Login → Dashboard utama (read-only)
  → Lihat compliance grid: 19 outlet × 3 shift × hari ini
    - Hijau = semua kritikal selesai
    - Kuning = kritikal selesai tapi ada non-kritikal terlewat
    - Merah = ada kritikal yang belum
    - Abu = belum submit
  → Drill-down ke detail submission (siapa PIC, jam submit, item apa yang gagal)
  → Filter: tanggal, outlet, shift
  → Tombol "Unlock for edit" jika ada kesalahan input PIC
```

### 4.4 Flow Owner — Dashboard

```
Login → Dashboard ringkasan
  → Compliance bulan ini (rata-rata semua outlet)
  → Top 5 & Bottom 5 outlet by compliance score
  → Tabel: SPV visit count bulan ini
  → Export Excel: data harian / bulanan
  → Export PDF: per report SPV
```

---

## 5. Fitur — Daftar Lengkap dengan Prioritas

### Legend
- **R** = Required (MVP / Phase 1 — wajib rilis)
- **r** = Recommended (Phase 2 — segera setelah MVP)
- **o** = Optional (Phase 3+ atau hanya jika kebutuhan muncul)

### 5.1 Autentikasi & User Management
| ID | Fitur | Prio |
|----|-------|------|
| F1.1 | Login username + password | R |
| F1.2 | Logout | R |
| F1.3 | Forgot password (via email admin) | r |
| F1.4 | Auto-logout setelah 12 jam | R |
| F1.5 | Admin: CRUD user (set role, outlet assignment) | R |
| F1.6 | Admin: reset password user | R |
| F1.7 | Audit log login (siapa login kapan dari IP mana) | o |

### 5.2 Checklist Harian (PIC)
| ID | Fitur | Prio |
|----|-------|------|
| F2.1 | Form checklist 3 shift (Pembukaan/Operasional/Penutupan) | R |
| F2.2 | Section dengan tag KRITIKAL/OPERASIONAL/FINANSIAL/HIGIENE/ADMIN | R |
| F2.3 | Item checkbox dengan badge "kritikal" / "catat" | R |
| F2.4 | Item note (warn/danger) sesuai mockup | R |
| F2.5 | Data input numerik per shift (suhu, stok, kas, dll) | R |
| F2.6 | Progress bar real-time + pills compliance | R |
| F2.7 | Block submit jika ada KRITIKAL belum selesai | R |
| F2.8 | Sign section (PIC name + SPV name + handover note) | R |
| F2.9 | Auto-save localStorage tiap perubahan | R |
| F2.10 | Lock setelah submit | R |
| F2.11 | Riwayat submission PIC sendiri | r |
| F2.12 | Late-submission flagging (di luar window shift) | r |

### 5.3 SPV Visit Report
| ID | Fitur | Prio |
|----|-------|------|
| F3.1 | Form 7-tab sesuai mockup (Info, Inventaris, Penjualan, Stok, Karyawan, Foto, Ringkasan) | R |
| F3.2 | Inventaris dengan kuantitas + status (OK/Perlu Cek/Rusak) | R |
| F3.3 | Penjualan: auto-calc avg trx, selisih kas, % achievement | R |
| F3.4 | Stok bahan baku dengan kategori (daging, pendukung, packaging) | R |
| F3.5 | Evaluasi karyawan: 6 kriteria star-rating (1–5) | R |
| F3.6 | Upload foto **opsional** — hanya saat ada temuan tidak normal (rusak, kotor, anomali). Max 10MB per file, kompresi otomatis ke < 1MB. Tag kategori + caption "alasan foto" wajib jika diupload | R |
| F3.7 | Auto-generate ringkasan dengan flag red/amber/green | R |
| F3.8 | Export PDF report kunjungan | R |
| F3.9 | Riwayat visit per SPV / per outlet | R |

### 5.4 Dashboard & Monitoring
| ID | Fitur | Prio |
|----|-------|------|
| F4.1 | Dashboard compliance grid (outlet × shift × tanggal) | R |
| F4.2 | Color-coded status (hijau/kuning/merah/abu) | R |
| F4.3 | Drill-down ke detail submission | R |
| F4.4 | Filter: tanggal range, outlet, shift, status | R |
| F4.5 | SPV: tombol unlock submission untuk koreksi PIC | R |
| F4.6 | Owner: agregat KPI bulanan + top/bottom outlet | R |
| F4.7 | Export Excel data kepatuhan + sales | R |
| F4.8 | Heatmap kepatuhan 30 hari per outlet | r |
| F4.9 | Trend chart (line/bar) compliance per bulan | r |
| F4.10 | Notifikasi WhatsApp ke SPV jika ada KRITIKAL gagal | o |
| F4.11 | Notifikasi email digest harian | o |

### 5.5 Admin Panel
| ID | Fitur | Prio |
|----|-------|------|
| F5.1 | CRUD outlet (nama, alamat, tipe internal/mitra) | R |
| F5.2 | CRUD user (assign outlet, role, password generate) | R |
| F5.3 | Set target penjualan harian per outlet | R |
| F5.4 | CRUD item checklist (per shift, per section) | r |
| F5.5 | CRUD inventaris master | r |
| F5.6 | Audit log: edit, unlock, perubahan master | r |

### 5.6 Cross-cutting
| ID | Fitur | Prio |
|----|-------|------|
| F6.1 | Responsif: form di mobile/tablet, dashboard di desktop (tetap mobile-friendly) | R |
| F6.2 | Visual design baru (dari /frontend-design) — bukan pertahankan mockup. Mockup hanya referensi struktur konten | R |
| F6.3 | Indonesia language UI | R |
| F6.4 | Timezone Asia/Jakarta | R |
| F6.5 | Validasi input semua field numerik (min/max masuk akal) | R |
| F6.6 | Error handling user-friendly (toast notification) | R |

---

## 6. Spesifikasi Teknis

### 6.1 Tech Stack
| Layer | Pilihan | Alasan |
|-------|---------|--------|
| Hosting | cPanel shared hosting (LAMP) | User sudah punya cPanel |
| Backend | PHP 8.1+ (vanilla atau Slim Framework 4) | Native cPanel, ringan, banyak shared hosting support |
| Database | MySQL 8 / MariaDB 10.5+ | Standard di cPanel |
| API | RESTful JSON | Simple, debuggable |
| Frontend | Vanilla JS + Alpine.js (untuk reactivity ringan) + HTML/CSS dari mockup | Tanpa build step kompleks; mockup HTML/CSS bisa dipakai langsung; minim dependencies |
| Auth | Session-based + JWT untuk API | Simple, cocok untuk webapp internal |
| Storage foto | Folder `uploads/` cPanel, dengan sub-folder per outlet/tanggal | Tidak butuh service eksternal |
| Image processing | PHP GD / Imagick untuk kompresi & thumbnail | Built-in di cPanel |
| PDF export | jsPDF (client-side) atau Dompdf (server-side) | jsPDF lebih ringan untuk MVP |
| Excel export | SheetJS (client-side) | Tidak butuh backend lib |
| Auto-save | localStorage browser | No backend cost |
| Build | Tidak ada build step. Static frontend + PHP backend langsung | Cocok untuk shared hosting |

**Catatan deployment:** Frontend & backend di-host di sub-domain yang sama: **`ops.sukashawarma.com`** (di-bind ke folder cPanel `public_html/ops/` atau sub-domain document root). Tidak ada CORS issue karena same-origin. HTTPS wajib via Let's Encrypt AutoSSL.

### 6.2 Skema Database (high level)

```sql
-- Users & roles
users(id, username, password_hash, full_name, role, outlet_id, active, created_at)
  -- role: 'outlet' | 'spv' | 'owner' | 'admin'
  -- outlet_id WAJIB untuk role 'outlet' (1 akun = 1 outlet di MVP);
  --   bisa multi-row referensi outlet sama di Phase 2 (multi-user per outlet)
  -- outlet_id NULL untuk SPV/Owner/Admin (akses semua outlet)

outlets(id, code, name, type, address, daily_sales_target, active)
  -- type: 'internal' | 'mitra'
  -- 'mitra' outlet tetap ada di list (SPV monitor), tapi tidak ada user account khusus

-- Checklist
checklist_submissions(id, outlet_id, user_id, shift, submission_date, status,
                      data_fields_json, pic_name, spv_name, handover_note,
                      submitted_at, locked, unlocked_by, unlocked_at)
  -- shift: 'open' | 'ops' | 'close'
  -- status: 'draft' | 'submitted'

checklist_items_state(id, submission_id, item_code, checked, notes)
  -- item_code: 'o1a', 'p2c', etc (sesuai mockup)

-- SPV Visit
spv_visits(id, outlet_id, spv_id, visit_date, time_arrive, time_leave,
           visit_shift, pic_on_duty, payload_json, summary_json,
           submitted_at)

spv_visit_photos(id, visit_id, file_path, tag, label, uploaded_at)

spv_visit_employees(id, visit_id, name, role, eval_json, notes)

-- Admin / audit
audit_log(id, user_id, action, target_type, target_id, payload_json, ip, created_at)
```

### 6.3 API Endpoints (representative)
```
POST   /api/auth/login
POST   /api/auth/logout
GET    /api/me

GET    /api/outlets
GET    /api/outlets/:id

POST   /api/checklists                        -- submit checklist
GET    /api/checklists?outlet=&date=&shift=   -- list / filter
GET    /api/checklists/:id
POST   /api/checklists/:id/unlock             -- SPV/Admin only

POST   /api/spv-visits                        -- submit SPV report
GET    /api/spv-visits?outlet=&from=&to=
GET    /api/spv-visits/:id
POST   /api/spv-visits/:id/photos             -- upload foto

GET    /api/dashboard/compliance?from=&to=    -- compliance grid
GET    /api/dashboard/summary                 -- KPI summary

-- Admin
POST   /api/admin/users
PUT    /api/admin/users/:id
POST   /api/admin/users/:id/reset-password
POST   /api/admin/outlets
PUT    /api/admin/outlets/:id
```

### 6.4 Non-Functional Requirements
- **Performance:** halaman load < 2 detik di 4G; submit checklist < 1 detik.
- **Concurrency:** 50 user aktif bersamaan (saat shift Pembukaan 19 outlet, kira-kira 19 PIC + beberapa SPV).
- **Backup:** auto-backup MySQL harian via cron cPanel ke folder backup; retensi 30 hari.
- **Security:** password bcrypt cost 12; CSRF token; prepared statement (SQL injection safe); rate-limit login 5x / 15 menit per IP; HTTPS wajib (Let's Encrypt via cPanel AutoSSL).
- **Browser support:** Chrome/Edge/Safari 2 versi terakhir; mobile Chrome & Safari iOS.

---

## 7. Visual Design

**Pendekatan:** Design baru dibuat from scratch menggunakan `/frontend-design` skill — bukan pertahankan mockup HTML lama. Mockup lama (`SS_Checklist_Harian v2.html`, `SS_Report_SPV v1.html`) hanya jadi referensi struktur konten & user flow, BUKAN referensi visual.

**Output design (sudah tersedia di folder `design/`):**
1. `design/00-design-system.html` — tokens, palette, typography, components showcase
2. `design/01-login.html` — Login split layout (poster + form)
3. `design/02-checklist-harian.html` — Form PIC mobile-first
4. `design/03-spv-visit.html` — SPV Visit Report 7-tab
5. `design/04-dashboard.html` — Dashboard compliance desktop
6. `design/05-admin.html` — Admin panel desktop

**Aesthetic direction (final):** "Operational Almanac" — warm cream paper (#f5f1e8) + warm ink + saffron deep (#c45a1a) sebagai signature; Fraunces variable serif + Geist sans + Geist Mono; subtle 8-point Islamic star sebagai signature visual; editorial folio system dengan section numbers & roman numerals.

**Status color convention (dipertahankan untuk semantic clarity):**
- Green — OK / completed / aman
- Amber/Yellow — perlu perhatian / WIP
- Red — kritikal gagal / rusak / di bawah target
- Blue — informational / operasional

Setelah `/frontend-design` selesai, design system & komponen yang dihasilkan akan jadi acuan untuk semua task UI di Phase 1–5.

---

## 8. Testing Strategy

| Lapisan | Pendekatan |
|---------|-----------|
| Backend API | PHPUnit untuk auth, submission validation, role-based access |
| Frontend | Manual QA matrix (3 shift × 5 outlet sample × 4 role) sebelum rilis |
| Smoke test | Cron script harian: POST dummy checklist → cek balikan 200 |
| User acceptance | Pilot di 2 outlet (1 internal + 1 mitra) selama 7 hari sebelum full roll-out |

**TDD adoption:** Tidak full TDD untuk Phase 1 (kecepatan rilis prioritas), tapi test critical paths wajib: login, submit checklist, unlock, role-based authorization.

---

## 9. Milestone & Timeline

### Phase 1 — MVP (target 3 minggu)
- Setup infrastruktur (DB, auth, deployment)
- Form Checklist Harian (3 shift)
- Form SPV Visit Report (7 tab)
- Dashboard compliance read-only
- Admin basic (user & outlet CRUD)
- Export PDF & Excel basic

### Phase 2 — Iteration (target +2 minggu setelah Phase 1)
- Riwayat per user
- Admin: edit master checklist items
- Heatmap & trend chart
- Late-submission flagging
- Audit log lengkap

### Phase 3 — Nice-to-have (sesuai kebutuhan)
- Notifikasi WhatsApp / email
- Mobile app wrapper (Capacitor) jika perlu
- Area-assignment SPV
- HR-style: tracking absensi karyawan, dll

---

## 10. Risiko & Mitigasi

| Risiko | Likelihood | Mitigasi |
|--------|-----------|----------|
| PIC malas isi → data tidak akurat | High | Lock submit jika kritikal gagal; SPV monitoring; report harian ke owner |
| Sinyal outlet jelek saat submit | Medium | Auto-save localStorage; retry submit di background |
| cPanel shared hosting kena resource limit | Medium | Optimasi query DB; kompresi foto sebelum upload; cron untuk arsip data > 6 bulan |
| Foto SPV makan kuota storage | Medium | Kompresi otomatis ke max 800kb per foto; cleanup foto > 1 tahun |
| Karyawan turnover, password berserakan | Medium | Admin disable user yang resign; audit log akses |

---

## 11. Asumsi & Pertanyaan Terbuka

### Asumsi (final, sudah dikonfirmasi owner)
- **Domain:** `ops.sukashawarma.com`
- **Akun login:** 1 akun shared per outlet di MVP; multi-user per outlet di Phase 2
- **Foto SPV:** opsional, hanya saat ada temuan tidak normal (rusak/anomali)
- **Operasional outlet:** buka 365 hari/tahun → checklist wajib tiap hari, tidak ada exception tanggal merah
- **Mitra:** outlet mitra tetap dipantau SPV; owner mitra **tanpa akses apapun** di Phase 1
- **Retensi data:** hot data 2 tahun di DB utama, archive otomatis ke `.sql.gz` di folder backup setelahnya. Backup tetap diakses kalau butuh audit pajak (s/d 5 tahun)
- Semua outlet punya akses internet (WiFi / hotspot HP) — full offline tidak prioritas
- Bahasa UI: Bahasa Indonesia saja
- Mata uang: Rupiah, format ribuan tanpa desimal
- Timezone: Asia/Jakarta (UTC+7) hardcoded

---

## 12. Definition of Done — MVP

MVP dinyatakan rilis ketika:
1. 1 user per role bisa login & logout sukses.
2. PIC bisa submit checklist 3 shift di 1 outlet, data tersimpan di DB & terlock setelah submit.
3. SPV bisa submit 1 visit report lengkap dengan upload 3 foto.
4. Dashboard menampilkan grid kepatuhan 19 outlet × 3 shift × hari ini dengan color-coding benar.
5. Admin bisa tambah outlet & user baru.
6. Export PDF report SPV & Excel kepatuhan harian berfungsi.
7. Lulus UAT 7 hari di 2 outlet pilot tanpa kehilangan data.
8. HTTPS aktif, backup harian berjalan, error log monitored.
