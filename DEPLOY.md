# Deploy ke cPanel — SS Operations

## Prasyarat
- cPanel hosting dengan PHP 8.1+ dan MySQL 5.7+
- Akses File Manager atau FTP
- Akses phpMyAdmin
- Sub-domain `ops.sukashawarma.com` sudah dibuat

---

## Langkah 1 — Upload File

### Via File Manager cPanel
1. Masuk cPanel → **File Manager**
2. Navigasi ke folder root sub-domain (biasanya `public_html/ops/` atau `ops.sukashawarma.com/`)
3. Upload seluruh isi project sebagai `.zip` → Extract

### Via FTP (FileZilla)
```
Host:     ops.sukashawarma.com atau IP server
Username: (FTP user dari cPanel)
Password: (FTP password)
Port:     21
Remote:   /public_html/ops/   (sesuaikan path)
```

---

## Langkah 2 — Setup Database

1. cPanel → **phpMyAdmin** → buat database baru: `ss_operations`
2. Buat user MySQL baru, assign ke database dengan hak penuh
3. Import migration SQL:
   - `db/migrations/001_init.sql` (schema utama)
   - `db/migrations/002_archive_tables.sql` (tabel arsip)
4. Import seed data (jika tersedia): `db/seeds/001_seed.sql`

---

## Langkah 3 — Konfigurasi .env

Buat file `.env` di root project (salin dari `.env.example`):

```env
APP_ENV=production
APP_URL=https://ops.sukashawarma.com
APP_SECRET=GANTI_DENGAN_STRING_ACAK_32_KARAKTER

DB_HOST=localhost
DB_NAME=ss_operations
DB_USER=user_mysql_anda
DB_PASS=password_mysql_anda

SESSION_LIFETIME=43200
SESSION_NAME=ss_ops_session
APP_TIMEZONE=Asia/Jakarta
UPLOAD_MAX_SIZE=10485760
UPLOAD_PATH=uploads/spv
```

> ⚠️ Ganti `APP_SECRET` dengan string acak yang kuat. Jangan gunakan nilai default.

---

## Langkah 4 — Aktifkan HTTPS

1. cPanel → **SSL/TLS** → **Let's Encrypt / AutoSSL** → Issue certificate untuk `ops.sukashawarma.com`
2. Setelah certificate aktif, edit `.htaccess` di root project:
   - Cari baris yang dikomentari dengan `# Force HTTPS`
   - **Hapus tanda `#`** di 3 baris berikutnya:

```apache
# Sebelum (di-comment):
# RewriteCond %{HTTPS} off
# RewriteCond %{HTTP_HOST} ^ops\.sukashawarma\.com$ [NC]
# RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]

# Sesudah (aktif):
RewriteCond %{HTTPS} off
RewriteCond %{HTTP_HOST} ^ops\.sukashawarma\.com$ [NC]
RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]
```

3. Test: akses `http://ops.sukashawarma.com` → harus redirect ke `https://`

---

## Langkah 5 — Permission Folder

Via SSH atau File Manager, pastikan permission folder berikut:

```bash
chmod 755 uploads/
chmod 755 logs/
chmod 755 backup/
chmod 755 backup/hot/
chmod 755 backup/archive/
chmod 700 scripts/
```

Atau via cPanel File Manager: klik kanan folder → Change Permissions.

---

## Langkah 6 — Setup Cron Jobs

cPanel → **Cron Jobs** → tambahkan 2 cron berikut:

### Backup Database Harian (03:00 WIB = 20:00 UTC)
```
0 20 * * * /usr/local/bin/php /home/USERNAME/public_html/ops/scripts/db-backup.php >> /home/USERNAME/public_html/ops/logs/cron.log 2>&1
```

### Arsip Data Lama — Setiap Minggu (02:00 WIB = 19:00 UTC)
```
0 19 * * 0 /usr/local/bin/php /home/USERNAME/public_html/ops/scripts/archive-data.php >> /home/USERNAME/public_html/ops/logs/cron.log 2>&1
```

> ⚠️ Ganti `USERNAME` dengan username cPanel Anda.
> Path PHP bisa berbeda. Cek dengan SSH: `which php` atau `which php81`

---

## Langkah 7 — Test Deployment

Buka checklist ini setelah deploy:

- [ ] `https://ops.sukashawarma.com` → halaman login tampil
- [ ] Login sebagai `admin` / `ss1234!` → masuk ke Admin Panel
- [ ] Admin Panel → Tab Outlet → daftar 19 outlet muncul
- [ ] Login sebagai akun outlet (mis. `ss.empang`) → masuk ke form checklist
- [ ] Submit checklist satu shift → status "Submitted" muncul
- [ ] Login sebagai SPV → Dashboard → grid kepatuhan muncul
- [ ] Export Excel → file `.xlsx` terdownload
- [ ] Error Log di Admin Panel → kosong (tidak ada error)

---

## Langkah 8 — Reset Password Seed

⚠️ **WAJIB** dilakukan sebelum distribusi ke pengguna nyata:

1. Login ke Admin Panel sebagai `admin`
2. Tab Pengguna → klik "Reset PW" untuk setiap akun
3. Sampaikan password baru ke masing-masing pengguna secara langsung
4. Password default `ss1234!` tidak boleh digunakan di produksi

---

## Troubleshooting

| Masalah | Kemungkinan Penyebab | Solusi |
|---------|---------------------|--------|
| 500 Internal Server Error | `.env` tidak ada atau salah | Cek path `.env`, pastikan DB credential benar |
| 403 Forbidden | Permission folder salah | Cek permission folder `public/`, `api/` |
| Login berhasil tapi redirect salah | `APP_URL` di `.env` salah | Pastikan `APP_URL=https://ops.sukashawarma.com` |
| Foto tidak bisa diupload | Permission `uploads/` | `chmod 755 uploads/` |
| Cron tidak jalan | Path PHP salah | Cek `/usr/local/bin/php` atau `/usr/local/bin/php81` |
| Backup kosong | Credential MySQL salah di `.env` | Verifikasi `DB_USER`, `DB_PASS`, `DB_NAME` |
