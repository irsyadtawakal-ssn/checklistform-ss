# Refactoring Design — Moderate (Repositories + Validators)

**Date:** 2026-06-04
**Scope:** Seluruh codebase PHP (api/, src/, public/)
**Approach:** Option C — repository layer + validator layer, selesai tuntas dalam satu sprint

---

## Latar Belakang & Masalah

Codebase saat ini adalah aplikasi PHP prosedural untuk operasional F&B Suka Shawarma (19 outlet).
Stack: PHP + MySQL di cPanel, domain `ops.sukashawarma.com`.

Masalah yang ditemukan:
1. **Boilerplate berulang** — setiap file API mengulang 6–8 baris `require_once` yang identik
2. **Routing inline** — `parse_url` + `preg_match` ditulis ulang di tiap file
3. **SQL query langsung di file API** — tidak ada pemisahan antara logika bisnis dan akses database
4. **Validasi tersebar** — setiap endpoint validasi sendiri-sendiri, tidak konsisten formatnya
5. **Helper functions terdefinisi lokal** — `auditLog()`, `generatePassword()` ada di `api/admin/users.php` padahal dibutuhkan di tempat lain; `sendWhatsApp()` ada di `src/helpers/compliance.php` yang tidak relevan

---

## Tujuan

- File API lebih pendek dan mudah dibaca
- Validasi konsisten di semua endpoint
- SQL query terpusat, mudah dicari dan diuji
- Tidak ada perubahan URL atau cara deploy di cPanel
- Tidak ada perubahan perilaku yang terlihat oleh user

---

## Struktur Folder Baru

```
src/
├── bootstrap.php          ← di-expand: auto-include semua helpers wajib
├── helpers/
│   ├── db.php             ← tidak berubah
│   ├── response.php       ← tidak berubah
│   ├── csrf.php           ← tidak berubah
│   ├── compliance.php     ← sendWhatsApp() dipindah keluar
│   ├── env.php            ← tidak berubah
│   ├── auth.php           ← tidak berubah
│   ├── rate_limit.php     ← tidak berubah
│   ├── stub_page.php      ← tidak berubah
│   ├── audit.php          ← BARU: auditLog() dipindah dari api/admin/users.php
│   ├── notify.php         ← BARU: sendWhatsApp() dipindah dari compliance.php
│   └── password.php       ← BARU: generatePassword() dipindah dari api/admin/users.php
├── middleware/
│   └── role.php           ← tidak berubah
├── repositories/          ← BARU
│   ├── checklist.php
│   ├── spv_visit.php
│   └── user.php
└── validators/            ← BARU
    ├── checklist.php
    └── user.php
```

---

## Detail Komponen

### 1. bootstrap.php (diperluas)

Tambahkan auto-include helpers yang selalu dibutuhkan semua API:
- `db.php`, `response.php`, `csrf.php`, `auth.php`, `role.php`
- `audit.php`, `notify.php`, `password.php`

Efek: setiap file API cukup 1 baris `require_once` alih-alih 6–8 baris.

### 2. src/helpers/audit.php

Ekstrak dari `api/admin/users.php`:
```php
function auditLog(PDO $pdo, int $userId, string $action, string $targetType, int $targetId, array $payload = []): void
```

### 3. src/helpers/notify.php

Pindahkan dari `src/helpers/compliance.php`:
```php
function sendWhatsApp(string $target, string $message): void
```

### 4. src/helpers/password.php

Ekstrak dari `api/admin/users.php`:
```php
function generatePassword(int $length = 8): string
```

### 5. src/repositories/checklist.php

| Fungsi | Keterangan |
|--------|------------|
| `repo_getSubmission(int $outletId, string $date, string $shift): ?array` | Ambil submission beserta item states |
| `repo_upsertSubmission(int $outletId, int $userId, array $data): int` | Insert atau update submission, return submission_id |
| `repo_upsertItemStates(PDO $pdo, int $submissionId, array $checks): void` | Upsert item states |
| `repo_unlockSubmission(int $submissionId, int $unlockedBy): void` | Set locked=0, status=draft |

### 6. src/repositories/spv_visit.php

| Fungsi | Keterangan |
|--------|------------|
| `repo_getVisit(int $visitId): ?array` | Ambil satu visit |
| `repo_createVisit(int $outletId, int $spvId, array $data): int` | Insert visit, return visit_id |
| `repo_insertEmployees(PDO $pdo, int $visitId, array $employees): void` | Insert employee records |
| `repo_savePhoto(int $visitId, string $relPath, ?string $thumbRel, string $tags, string $label): int` | Insert foto, return photo_id |
| `repo_getOutletCode(int $visitId): string` | Ambil kode outlet dari visit |

### 7. src/repositories/user.php

| Fungsi | Keterangan |
|--------|------------|
| `repo_listUsers(): array` | Semua user dengan outlet join |
| `repo_findUserById(int $id): ?array` | Satu user by id |
| `repo_findUserByUsername(string $username): ?array` | Cek duplikat username |
| `repo_createUser(array $data): int` | Insert user, return id |
| `repo_updateUser(int $id, array $fields): void` | Update field dinamis |
| `repo_resetPassword(int $id, string $hash): void` | Update password_hash |
| `repo_listUsersForReset(string $role): array` | Ambil user aktif untuk bulk reset |

### 8. src/validators/checklist.php

```php
function validateChecklistGet(array $params): void   // outlet, date, shift
function validateChecklistPost(array $body): void    // shift, pic_name, checks
```
Jika tidak valid: langsung panggil `jsonError()` dan exit.

### 9. src/validators/user.php

```php
function validateUserCreate(array $body): void  // username, full_name, role, outlet_id
function validateUserUpdate(array $body): void  // field yang diubah tidak kosong
```

---

## Contoh File API Setelah Refactoring

**api/checklists.php (POST):**
```php
require_once __DIR__ . '/../src/bootstrap.php';

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

if ($method === 'POST') {
    csrfValidate();
    $user = requireRole('outlet');
    $body = json_decode(file_get_contents('php://input'), true) ?: [];

    validateChecklistPost($body);
    $result = repo_upsertSubmission((int)$user['outlet_id'], (int)$user['id'], $body);

    if ($result['compliance_status'] === 'danger' && WA_SPV_NUMBER) {
        sendWhatsApp(WA_SPV_NUMBER, buildDangerMessage($result));
    }

    jsonOk($result, 201);
}
```

---

## Batasan & Hal yang TIDAK Berubah

- Tidak ada perubahan URL endpoint
- Tidak ada router baru atau .htaccess baru
- Tidak ada OOP/class — tetap prosedural, konsisten dengan codebase saat ini
- File `public/*.php` tidak disentuh (bukan API)
- File `src/helpers/compliance.php` tetap ada, hanya `sendWhatsApp()` yang dipindah
- Deploy ke cPanel tetap sama persis

---

## Urutan Implementasi

1. Buat `src/helpers/audit.php`, `notify.php`, `password.php`
2. Perluas `src/bootstrap.php` untuk auto-include semua helpers wajib
3. Buat `src/repositories/checklist.php`
4. Buat `src/repositories/spv_visit.php`
5. Buat `src/repositories/user.php`
6. Buat `src/validators/checklist.php`
7. Buat `src/validators/user.php`
8. Refactor `api/checklists.php`
9. Refactor `api/spv-visits.php`
10. Refactor `api/spv-attach.php`
11. Refactor `api/admin/users.php`
12. Refactor sisa file `api/admin/*.php`
13. Hapus definisi lokal `auditLog()`, `generatePassword()` dari file lama
