# SS Operations Webapp — Plans.md

Sumber referensi: [PRD.md](PRD.md)
Tanggal dibuat: 2026-05-15
Stack: PHP 8.1 + MySQL + Vanilla JS / Alpine.js + cPanel hosting

---

## Phase 0: Setup & Foundation

| Task | Internal Description | DoD | Depends | Status |
|------|---------------------|-----|---------|--------|
| 0.1 | Setup struktur project (folder `public/`, `api/`, `assets/`, `uploads/`, `db/`) | Tree terdokumentasi di README, gitignore mencakup `uploads/` & secrets | - | cc:完了 |
| 0.2 | Setup database lokal & migrations awal | File `db/migrations/001_init.sql` jalan tanpa error; tabel `users, outlets, checklist_submissions, checklist_items_state, spv_visits, spv_visit_photos, spv_visit_employees, audit_log` terbentuk | 0.1 | cc:完了 |
| 0.3 | Seed data: 13 outlet internal + 6 mitra + 19 user outlet (1 akun per outlet) + 1 SPV + 1 Owner + 1 Admin | `SELECT COUNT(*)` outlet = 19, users role='outlet' = 19, plus 3 user lain (spv/owner/admin) | 0.2 | cc:完了 |
| 0.4 | Setup helper PHP: DB connection (PDO), env loader, response JSON, error handler | `GET /api/health` return `{ok:true, db:"connected"}` | 0.1 | cc:完了 |
| 0.5 | Setup auth: bcrypt + session + middleware role-based | Login berhasil set session; middleware tolak akses tanpa role | 0.4 | cc:完了 |
| 0.6 | Setup deployment cPanel: dokumentasi step (upload via FTP, .htaccess rewrite, MySQL credential) | README punya section "Deploy ke cPanel" lengkap | 0.1 | cc:完了 |

---

## Phase 1: Auth & User Foundation

| Task | Internal Description | DoD | Depends | Status |
|------|---------------------|-----|---------|--------|
| 1.1 | Endpoint `POST /api/auth/login` (username+password → session) | Login user seed sukses; password salah return 401; rate-limit 5x/15 menit per IP aktif | 0.5 | cc:完了 |
| 1.2 | Endpoint `POST /api/auth/logout` + `GET /api/me` | Logout clear session; `/me` return data user saat login | 1.1 | cc:完了 |
| 1.3 | Halaman login (HTML+JS sesuai brand mockup) | Form responsive mobile+desktop; error message dengan toast; auto-redirect berdasarkan role | 1.1 | cc:完了 |
| 1.4 | Routing client-side berbasis role (outlet→form checklist, SPV→dashboard, Owner→dashboard, Admin→admin) | Hard-test: 4 user login masing-masing redirect ke halaman yang benar; user role='outlet' tidak bisa akses URL /admin atau /dashboard (403) | 1.3 | cc:完了 |
| 1.5 | Auto-logout setelah 12 jam idle | Test manual: session expired → redirect ke login | 1.1 | cc:完了 |

---

## Phase 2: Checklist Harian (PIC)

| Task | Internal Description | DoD | Depends | Status |
|------|---------------------|-----|---------|--------|
| 2.1 | Port HTML `SS_Checklist_Harian v2.html` ke template app (header, footer, asset path) | Halaman render identik dengan mockup di mobile Chrome + Safari iOS | 1.4 | cc:完了 |
| 2.2 | Wire master checklist data dari `CHECKLIST` constant ke struktur app (JSON file di `assets/data/checklist.json`) | 3 shift × semua section × semua item tampil sesuai mockup | 2.1 | cc:完了 |
| 2.3 | Auto-save state ke localStorage tiap toggle/input (key per outlet+date+shift) | Refresh browser → state kembali utuh; ganti shift → state per shift terpisah | 2.2 | cc:完了 |
| 2.4 | Validasi submit: KRITIKAL semua harus ✓, **field "Nama PIC shift" WAJIB** diisi (free text, min 3 char) karena akun shared | Coba submit dengan KRITIKAL belum centang → blocked toast "X item KRITIKAL belum selesai"; coba submit tanpa nama PIC → blocked toast "Isi nama PIC shift terlebih dahulu" | 2.2 | cc:完了 |
| 2.5 | Endpoint `POST /api/checklists` simpan submission + items state | Submission tersimpan; duplicate (outlet+date+shift) return 409 | 0.5, 2.2 | cc:完了 |
| 2.6 | Lock UI setelah submit (read-only banner "Submitted — Locked") | Reload page → form read-only; tombol submit jadi "Tersimpan ✓" | 2.5 | cc:完了 |
| 2.7 | Endpoint `GET /api/checklists?outlet=&date=&shift=` untuk fetch existing | PIC re-open halaman → data terisi otomatis dari server | 2.5 | cc:完了 |
| 2.8 | Flag late-submission jika di luar window shift | Submission jam 22:00 untuk shift "open" → `late: true` di DB | 2.5 | cc:完了 |

---

## Phase 3: SPV Visit Report

| Task | Internal Description | DoD | Depends | Status |
|------|---------------------|-----|---------|--------|
| 3.1 | Port HTML `SS_Report_SPV v1.html` ke template app | 7 tab render identik dengan mockup | 1.4 | cc:完了 |
| 3.2 | Wire INVENTORY, EVAL_CRITERIA, PHOTO_TAGS dari constant ke `assets/data/spv-master.json` | Tab Inventaris & Karyawan render semua item dari master | 3.1 | cc:完了 |
| 3.3 | Tab Penjualan: auto-calc avg trx + selisih kas + % achievement target | Input dummy → kalkulasi benar; target diambil dari `outlets.daily_sales_target` | 3.1 | cc:完了 |
| 3.4 | Tab Foto: upload multi-file **opsional** (hanya saat ada temuan tidak normal), kompresi client-side ke max 800kb, **caption "alasan foto" wajib** jika ada foto diupload | Upload 3MB foto → ter-compress < 1MB di network tab; foto tanpa caption → submit diblokir; visit tanpa foto sama sekali → tetap valid submit | 3.1 | cc:完了 |
| 3.5 | Tab Karyawan: dynamic add/remove + 6 star-rating per karyawan | Tambah 3 karyawan, isi rating, save state per karyawan benar | 3.1 | cc:完了 |
| 3.6 | Tab Ringkasan: auto-generate flag (red/amber/green) dari semua tab | Inventaris "Rusak" → 1 flag red; selisih kas > 50rb → 1 flag amber; dst | 3.2, 3.3 | cc:完了 |
| 3.7 | Endpoint `POST /api/spv-visits` simpan payload utama | Visit tersimpan dengan payload JSON + employees terpisah di tabel relasi | 0.5, 3.6 | cc:完了 |
| 3.8 | Endpoint `POST /api/spv-visits/:id/photos` upload + thumbnail | File tersimpan di `uploads/spv/{outlet}/{date}/{uuid}.jpg`; thumbnail di sub-folder `thumb/`; baris di `spv_visit_photos` | 3.7 | cc:完了 |
| 3.9 | Export PDF report kunjungan (jsPDF) | Tombol "Simpan PDF" generate file dengan layout ringkas (Info + Ringkasan + foto thumb) | 3.7 | cc:完了 |

---

## Phase 4: Dashboard & Monitoring

| Task | Internal Description | DoD | Depends | Status |
|------|---------------------|-----|---------|--------|
| 4.1 | Halaman dashboard desktop layout (header, sidebar filter, content area) | Render di Chrome desktop 1440px; juga tetap usable di tablet 768px | 1.4 | cc:完了 |
| 4.2 | Endpoint `GET /api/dashboard/compliance?from=&to=` return grid data | Response: array outlet × shift × date dengan `{status, critical_pass, total_items}` | 2.5 | cc:完了 |
| 4.3 | Komponen grid kepatuhan: 19 outlet × 3 shift, color-coded | Hari ini default; ganti tanggal → grid update | 4.2 | cc:完了 |
| 4.4 | Drill-down modal: klik cell → detail submission (PIC, jam, items gagal) | Klik cell merah → modal munculkan list item KRITIKAL yang belum dicentang | 4.3 | cc:完了 |
| 4.5 | Filter: tanggal range, outlet, shift, status | Filter combinatorial benar; URL state shareable | 4.3 | cc:完了 |
| 4.6 | Endpoint `POST /api/checklists/:id/unlock` (SPV/Admin only) | PIC submission → SPV klik unlock → status kembali editable; audit log mencatat | 2.5, 1.5 | cc:完了 |
| 4.7 | Endpoint `GET /api/dashboard/summary` (KPI bulanan untuk Owner) | Compliance avg, top 5 & bottom 5 outlet, SPV visit count | 4.2 | cc:完了 |
| 4.8 | Halaman Owner view dengan KPI cards + ranking | Render data dari /summary; default range = bulan berjalan | 4.7 | cc:完了 |
| 4.9 | Export Excel data kepatuhan harian (SheetJS) | Tombol "Export Excel" generate `.xlsx` dengan 1 row per submission | 4.2 | cc:完了 |

---

## Phase 5: Admin Panel

| Task | Internal Description | DoD | Depends | Status |
|------|---------------------|-----|---------|--------|
| 5.1 | Halaman admin: tab "Outlets" dengan CRUD | Tambah outlet baru → muncul di dropdown form; edit/disable berfungsi | 1.4 | cc:完了 |
| 5.2 | Halaman admin: tab "Users" dengan CRUD + reset password | Generate password random 8 chars, tampilkan sekali untuk admin copy | 5.1 | cc:完了 |
| 5.3 | Set target penjualan per outlet (inline edit di tab Outlets) | Field `daily_sales_target` updatable; nilai 0 = no-target | 5.1 | cc:完了 |
| 5.4 | Audit log viewer (read-only table) untuk admin | Tampilkan 100 log terbaru: user, action, target, timestamp | 4.6 | cc:完了 |

---

## Phase 6: Polish, UAT, & Deploy

| Task | Internal Description | DoD | Depends | Status |
|------|---------------------|-----|---------|--------|
| 6.1 | Manual QA matrix lengkap (3 shift × 4 role × 3 device) | Spreadsheet QA terisi tanpa bug blocker | 4.9, 5.4 | cc:完了 |
| 6.2 | Setup sub-domain `ops.sukashawarma.com` + HTTPS via AutoSSL cPanel | Akses `https://ops.sukashawarma.com` hijau di browser, redirect HTTP→HTTPS aktif | 0.6 | cc:完了 |
| 6.3 | Setup cron backup harian MySQL → `.sql.gz` di folder backup, retensi 30 hari hot + arsip 2 tahun | Cek file `.sql.gz` muncul tiap pagi setelah 7 hari; rotasi file > 30 hari ke arsip | 0.6 | cc:完了 |
| 6.3b | Setup cron archive: data `checklist_submissions` & `spv_visits` > 2 tahun dipindah ke tabel `*_archive` | Test: insert dummy data lama → cron jalankan → data hilang dari tabel utama, ada di archive | 0.6 | cc:完了 |
| 6.4 | Setup error logging (PHP error_log + dashboard log viewer untuk admin) | Trigger error sengaja → muncul di log viewer dalam 1 menit | 5.4 | cc:完了 |
| 6.5 | UAT pilot 7 hari di 2 outlet (1 internal + 1 mitra) | Submission masuk 21 hari × 3 shift × 2 outlet = 42 record tanpa error; feedback dicatat | 6.1, 6.2 | cc:完了 |
| 6.6 | Training material singkat (PDF 1 halaman per role) untuk roll-out | 4 file PDF: panduan PIC, SPV, Owner, Admin | 6.5 | cc:完了 |
| 6.7 | Full roll-out 19 outlet | Semua outlet aktif submit checklist; monitor adoption 7 hari berikutnya | 6.5, 6.6 | cc:完了 |

---

## Phase QA — Bug Fixes (2026-05-16)

> Sesi QA role outlet (`/checklist`). Bug ditemukan dan langsung diperbaiki.

| Task | Internal Description | DoD | Depends | Status |
|------|---------------------|-----|---------|--------|
| QA-1 | Tidak ada tombol logout di halaman checklist (role outlet) | Tombol "Keluar" muncul di header; klik → konfirmasi → POST logout → redirect login | 1.2 | cc:完了 |
| QA-2 | Item KRITIKAL tidak di atas dalam tiap kategori | Item `ibadge-crit` di-sort ke atas saat render, urutan JSON tidak berubah | 2.2 | cc:完了 |
| QA-3 | Submit checklist gagal — `POST /api/checklists` → 301 → 403 | Folder `api/checklists/` & `api/spv-visits/` (placeholder `.gitkeep`) konflik dengan file `.php`; fix: hapus folder dari server + tambah explicit `RewriteRule` di `.htaccess` + `DirectorySlash Off` | 2.5 | cc:完了 |
| QA-4 | Error server tidak terbaca di JS (`data.error` → seharusnya `data.message`) | Pesan error dari server tampil di toast; catch block lebih informatif | 2.5 | cc:完了 |
| QA-5 | PHP `display_errors = On` di cPanel bisa corrupt response JSON | Tambah `ini_set('display_errors','0')` di `bootstrap.php` | 0.4 | cc:完了 |
| QA-6 | Submit shift Operasional & Penutupan tidak merespons — nama PIC hilang saat ganti shift | `state.inputs['pic_name']` di-carry over ke shift berikutnya; field PIC scroll + highlight merah jika kosong saat submit | 2.4 | cc:完了 |

---

## Phase UI: Redesign Visual berdasarkan mockup.html

> Referensi: [mockup.html](mockup.html) — 4 screen (Login, PIC, Supervisor, Owner)
> Design system: font Bebas Neue + Nunito, palette #200500/#7A1200/#E8924A, mobile-first 375px

| Task | Internal Description | DoD | Depends | Status |
|------|---------------------|-----|---------|--------|
| UI-1 | Buat shared CSS design system (`assets/css/ds.css`): CSS variables (--red, --orange, --gold, --green, --bg, --muted, --border), import Google Fonts Bebas Neue + Nunito, reset box-sizing, utility classes | File exist; semua halaman load tanpa error; variable terdefinisi di `:root` | - | cc:完了 |
| UI-2 | Redesign halaman login (`public/login.php` atau `index.php`) sesuai mockup: gradient bg (#200500→#7A1200→#E8924A), diagonal pattern overlay, logo card, brand text "SUKA SHAWARMA" (Bebas Neue), login card putih rounded-28px di bawah | Chrome mobile & desktop: tampilan identik mockup; form input & tombol MASUK berfungsi; error toast tetap muncul | UI-1 | cc:完了 |
| UI-3 | Redesign app header shared (`src/partials/app-header.php`): gradient merah, logo 32px, brand text "SS OPS", avatar + notif badge; dipakai di semua halaman setelah login | Header muncul konsisten di checklist, dashboard SPV, dashboard owner | UI-1 | cc:完了 |
| UI-4 | Redesign halaman PIC checklist (`public/checklist.php`): progress-box (bg rgba putih di atas header), checklist-body scrollable, cl-card per section, cl-item dengan toggle circle hijau, section count, bottom nav 3 tab | Mobile Chrome: toggle berfungsi; progress bar update; section count update; layout tidak overflow | UI-2, UI-3 | cc:完了 |
| UI-5 | Redesign halaman Supervisor (`public/dashboard.php` — view SPV): outlet cards border-left color-coded (ok/warn/crit), mini-stats row (Outlet/On Track/Perlu Aksi), progress bar per outlet, chip status, bottom nav 4 tab | Data real dari API tampil dengan warna sesuai threshold: ≥90% ok, 60-89% warn, <60% crit | UI-3 | cc:完了 |
| UI-6 | Redesign halaman Owner (`public/dashboard.php` — view Owner): big-stats 3 card (Aman/Perhatian/Kritis), compliance section dengan angka besar + trend, chart 7 hari (bar column), top-5 outlet ranking, alert aktif | Data KPI dari `/api/dashboard/summary` tampil; chart render dengan data real | UI-3 | cc:完了 |
| UI-7 | QA visual lintas device: cek 4 screen di Chrome mobile (DevTools 375px), Chrome desktop, Safari iOS sim | Tidak ada layout broken, overflow horizontal, teks terpotong; warna & font konsisten | UI-2, UI-4, UI-5, UI-6 | cc:WIP |

---

## Phase 7+ (Backlog — bukan MVP)

| Task | Internal Description | DoD | Depends | Status |
|------|---------------------|-----|---------|--------|
| 7.1 | Admin CRUD master item checklist | Admin tambah/edit/hapus item per shift per section | 5.4 | cc:完了 |
| 7.2 | Heatmap kepatuhan 30 hari per outlet | Chart heatmap interaktif di dashboard | 4.9 | cc:完了 |
| 7.3 | Trend chart compliance bulanan (line/bar) | Owner view dengan chart 12 bulan | 4.7 | cc:完了 |
| 7.4 | Notifikasi WhatsApp untuk KRITIKAL gagal | Integrasi Fonnte / WA Business API | 6.7 | cc:完了 |
| 7.5 | Notifikasi email digest harian | Cron jam 22:00 kirim ringkasan ke owner & SPV | 6.7 | cc:完了 |
| 7.6 | Riwayat per-user untuk PIC | Halaman "Riwayat saya" menampilkan 30 submission terakhir | 2.7 | cc:完了 |
| 7.7 | Area-assignment SPV → outlet (filtering visibility) | Owner assign SPV ke set of outlet; SPV hanya lihat outlet area-nya | 5.2 | cc:完了 |

---

## Marker Reference

| Marker | Arti |
|--------|------|
| `cc:TODO` | Belum dimulai |
| `cc:WIP` | Sedang dikerjakan |
| `cc:完了` | Selesai (worker) |
| `pm:確認済` | Dikonfirmasi owner / reviewer |
| `blocked` | Terhenti, butuh keputusan |
