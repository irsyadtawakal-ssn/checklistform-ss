# SS Operations Webapp

Webapp internal operasional harian **Suka Shawarma** — 19 outlet, 3 shift, real-time compliance monitoring.

**Stack:** PHP 8.1 + MySQL + Vanilla JS / Alpine.js + cPanel hosting  
**Domain:** `https://ops.sukashawarma.com`

---

## Struktur Folder

```
ss-operations/
│
├── public/                     # Web root — semua halaman HTML/PHP yang diakses browser
│   ├── index.php               # Entry point utama
│   ├── login.php               # Halaman login
│   ├── checklist.php           # Form checklist harian (PIC)
│   ├── spv-visit.php           # Form kunjungan SPV
│   ├── dashboard.php           # Dashboard compliance (SPV/Owner)
│   ├── admin.php               # Panel admin
│   └── .htaccess               # URL rewrite rules
│
├── api/                        # REST API endpoints (PHP)
│   ├── auth/
│   │   ├── login.php           # POST /api/auth/login
│   │   └── logout.php          # POST /api/auth/logout
│   ├── checklists/
│   │   └── index.php           # GET/POST /api/checklists
│   ├── spv-visits/
│   │   └── index.php           # GET/POST /api/spv-visits
│   ├── dashboard/
│   │   ├── compliance.php      # GET /api/dashboard/compliance
│   │   └── summary.php         # GET /api/dashboard/summary
│   └── admin/
│       ├── users.php           # CRUD /api/admin/users
│       └── outlets.php         # CRUD /api/admin/outlets
│
├── assets/                     # Static assets
│   ├── css/                    # Stylesheet (design system "Operational Almanac")
│   ├── js/                     # JavaScript & Alpine.js components
│   ├── fonts/                  # Font files (Fraunces, Geist)
│   └── data/                   # Master JSON data
│       ├── checklist.json      # Item checklist 3 shift × semua section
│       └── spv-master.json     # Master inventaris & kriteria evaluasi SPV
│
├── uploads/                    # File upload dari user — GITIGNORED
│   └── spv/                    # Foto kunjungan SPV
│       └── {outlet}/{date}/    # Diorganisir per outlet dan tanggal
│
├── db/                         # File database
│   ├── migrations/             # SQL migration (001_init.sql, 002_..., dst)
│   └── seeds/                  # Data awal (outlet, user, dll)
│
├── src/                        # PHP library / source files
│   ├── helpers/                # Helper functions
│   │   ├── db.php              # PDO connection (singleton)
│   │   ├── env.php             # .env loader
│   │   ├── response.php        # JSON response helper
│   │   └── auth.php            # Auth utilities (session, bcrypt)
│   └── middleware/             # Middleware
│       ├── auth.php            # Cek session aktif
│       └── role.php            # Role-based access control
│
├── config/                     # Konfigurasi app
│   └── app.php                 # Load .env + define constants
│
├── design/                     # Mockup HTML referensi (read-only, tidak deploy)
│   ├── 00-design-system.html
│   ├── 01-login.html
│   ├── 02-checklist-harian.html
│   ├── 03-spv-visit.html
│   ├── 04-dashboard.html
│   └── 05-admin.html
│
├── .env                        # Secrets — GITIGNORED (lihat .env.example)
├── .env.example                # Template environment variables
├── .gitignore
├── .htaccess                   # Root URL rewriting ke public/
├── Plans.md                    # Rencana development bertahap
├── PRD.md                      # Product Requirements Document
└── README.md                   # File ini
```

---

## Deploy ke cPanel

### Prasyarat
- Hosting cPanel dengan PHP 8.1+ dan MySQL 8.0 / MariaDB 10.5+
- Akses FTP (FileZilla atau WinSCP) ke hosting
- Sub-domain `ops.sukashawarma.com` sudah dibuat di cPanel

---

### Langkah 1 — Set PHP Version

1. Login cPanel → cari **"MultiPHP Manager"** atau **"PHP Selector"**
2. Pilih folder `ops/` (atau domain `ops.sukashawarma.com`)
3. Ubah versi PHP ke **8.1** atau lebih tinggi
4. Klik **Apply**

---

### Langkah 2 — Buat Database MySQL

1. cPanel → **MySQL Databases**
2. Buat database baru: `ss_operations` → catat nama lengkapnya (biasanya `cpaneluser_ss_operations`)
3. Buat user baru: `ss_user` dengan password kuat → catat
4. Assign user ke database dengan **All Privileges**
5. Buat juga: cPanel → **MySQL Databases** → catat **DB Host** (biasanya `localhost`)

---

### Langkah 3 — Upload File via FTP

Gunakan FileZilla atau File Manager cPanel:

1. Hubungkan FTP ke server (Host dari cPanel → FTP Accounts)
2. Navigasi ke `public_html/`
3. Buat folder baru `ops/` di dalam `public_html/`
4. Upload **semua file project** ke `public_html/ops/` — termasuk folder `api/`, `assets/`, `db/`, `src/`, dll.
5. **Jangan** upload folder `design/` (hanya mockup, tidak dibutuhkan di server)
6. **Jangan** upload file `.env` (akan dibuat manual di server)

Struktur di server setelah upload:
```
public_html/
└── ops/
    ├── api/
    ├── assets/
    ├── db/
    ├── public/
    ├── src/
    ├── uploads/          ← pastikan folder ini ada & writable
    ├── .env              ← dibuat manual (langkah 4)
    ├── .htaccess
    └── ...
```

---

### Langkah 4 — Set Sub-domain Document Root

1. cPanel → **Subdomains**
2. Cari `ops.sukashawarma.com`
3. Ubah **Document Root** ke `public_html/ops` (bukan `public_html/ops/public`)
4. Klik **Save**

> **Kenapa bukan `/public`?** File `.htaccess` di root project sudah otomatis routing request frontend ke folder `public/` dan mengamankan folder sensitif (`src/`, `db/`, `config/`). Tidak perlu set document root ke `public/`.

---

### Langkah 5 — Buat File .env di Server

Via cPanel File Manager atau FTP, buat file `.env` di `public_html/ops/.env`:

```ini
DB_HOST=localhost
DB_NAME=cpaneluser_ss_operations
DB_USER=cpaneluser_ss_user
DB_PASS=passwordKuatAnda

APP_ENV=production
APP_URL=https://ops.sukashawarma.com
APP_SECRET=isiDenganRandomString32KarakterAtauLebih

SESSION_LIFETIME=43200
SESSION_NAME=ss_ops_session

UPLOAD_MAX_SIZE=10485760
UPLOAD_PATH=uploads/spv

APP_TIMEZONE=Asia/Jakarta
```

> Ganti `cpaneluser_` dengan prefix username cPanel Anda yang sebenarnya.

---

### Langkah 6 — Jalankan Migration Database

1. cPanel → **phpMyAdmin**
2. Klik database `cpaneluser_ss_operations` di sidebar kiri
3. Klik tab **Import**
4. Pilih file `db/migrations/001_init.sql` dari komputer Anda
5. Klik **Go**
6. Verifikasi: seharusnya muncul 8 tabel baru di sidebar kiri

---

### Langkah 7 — Jalankan Seed Data

Setelah migration berhasil:

1. phpMyAdmin → tab **Import** lagi
2. Import `db/seeds/001_outlets.sql` → 19 outlet masuk
3. Import `db/seeds/002_users.sql` → 22 user masuk (19 outlet + SPV + Owner + Admin)

---

### Langkah 8 — Set Permission Folder uploads/

Via cPanel File Manager:
1. Klik folder `public_html/ops/uploads/`
2. Klik kanan → **Change Permissions**
3. Set ke **755** (atau **775** jika perlu)
4. Centang **Recurse into subdirectories**
5. Klik **Change Permissions**

---

### Langkah 9 — Aktifkan HTTPS

1. cPanel → **SSL/TLS** → **AutoSSL**
2. Klik **Run AutoSSL** untuk domain `ops.sukashawarma.com`
3. Tunggu beberapa menit hingga sertifikat diterbitkan (hijau)
4. Test: akses `http://ops.sukashawarma.com` → harus otomatis redirect ke `https://`

---

### Langkah 10 — Verifikasi Deployment

Buka browser, akses endpoint berikut:

```
GET https://ops.sukashawarma.com/api/health
```

Response yang diharapkan:
```json
{"ok": true, "db": "connected"}
```

Jika `db: "disconnected"` → periksa credential di `.env` dan pastikan user MySQL sudah di-assign ke database.

---

### Troubleshooting Umum

| Masalah | Solusi |
|---------|--------|
| 403 Forbidden | Cek permission folder (755) dan `.htaccess` |
| 500 Internal Server Error | Cek PHP version (harus 8.1+); lihat error log di cPanel |
| `db: "disconnected"` | Periksa `.env` — DB_NAME, DB_USER, DB_PASS, DB_HOST |
| Halaman kosong / blank | PHP error tersembunyi; aktifkan `APP_ENV=development` sementara |
| Upload foto gagal | Cek permission folder `uploads/` (harus 755 atau 775) |
| Session tidak tersimpan | Pastikan PHP session directory writable di cPanel |

---

### Update / Redeploy

Saat ada update kode:
1. Upload file yang berubah via FTP (timpa file lama)
2. Jika ada migration baru: import file SQL baru via phpMyAdmin
3. **Jangan** timpa file `.env` di server

---

## Aturan Penting

- **Jangan upload `.env`** ke git. File ini berisi password database.
- **`uploads/`** tidak di-track git — pastikan folder ini ada dan writable di server.
- **PHP 8.1+** dibutuhkan (cek cPanel → PHP Selector).
- **MySQL charset**: wajib `utf8mb4` (support emoji dan karakter khusus).
- Semua waktu disimpan dalam **UTC**, ditampilkan dalam **Asia/Jakarta (UTC+7)**.

---

## Role & Akses

| Role | Halaman |
|------|---------|
| Outlet (PIC) | `/checklist` saja |
| SPV | `/dashboard`, `/spv-visit` |
| Owner | `/dashboard` (view agregat) |
| Admin | `/admin` + semua halaman |
