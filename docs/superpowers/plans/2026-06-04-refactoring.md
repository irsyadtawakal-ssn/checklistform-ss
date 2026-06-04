# Refactoring Moderate (Repositories + Validators) Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Refactor seluruh codebase PHP agar setiap file API lebih pendek, validasi konsisten, dan SQL query terpusat — tanpa mengubah URL, perilaku, atau cara deploy di cPanel.

**Architecture:** Tambah tiga layer baru: (1) helpers yang di-extract dari file lokal ke `src/helpers/`, (2) repositories di `src/repositories/` berisi semua SQL query per domain, (3) validators di `src/validators/` berisi semua validasi input per domain. Bootstrap di-expand agar API file cukup 1 baris `require_once`.

**Tech Stack:** PHP 8.x prosedural, PDO MySQL, cPanel shared hosting. Tidak ada framework, tidak ada Composer, tidak ada test runner — verifikasi manual via browser/curl.

---

## Peta File

| File | Status | Tanggung Jawab |
|------|--------|----------------|
| `src/bootstrap.php` | Modify | Auto-include semua helpers wajib |
| `src/helpers/audit.php` | Create | `auditLog()` |
| `src/helpers/notify.php` | Create | `sendWhatsApp()` |
| `src/helpers/password.php` | Create | `generatePassword()` |
| `src/helpers/compliance.php` | Modify | Hapus `sendWhatsApp()` |
| `src/repositories/checklist.php` | Create | SQL untuk checklist submissions |
| `src/repositories/spv_visit.php` | Create | SQL untuk spv visits & photos |
| `src/repositories/user.php` | Create | SQL untuk users |
| `src/validators/checklist.php` | Create | Validasi input checklist |
| `src/validators/user.php` | Create | Validasi input user |
| `api/checklists.php` | Modify | Slim — pakai validator + repo |
| `api/spv-visits.php` | Modify | Slim — pakai validator + repo |
| `api/spv-attach.php` | Modify | Slim — pakai repo |
| `api/admin/users.php` | Modify | Slim — hapus fungsi lokal, pakai repo |
| `api/admin/outlets.php` | Modify | Slim — hapus `auditLog()` lokal |
| `api/admin/checklist-master.php` | Modify | Tambah require_once tunggal |
| `api/admin/spv-areas.php` | Modify | Tambah require_once tunggal |
| `api/admin/spv-visits.php` | Modify | Tambah require_once tunggal |
| `api/admin/audit-log.php` | Modify | Tambah require_once tunggal |
| `api/admin/error-log.php` | Modify | Tambah require_once tunggal |

---

## Task 1: Buat src/helpers/audit.php

**Files:**
- Create: `src/helpers/audit.php`

- [ ] **Step 1: Buat file baru**

```php
<?php
declare(strict_types=1);

function auditLog(PDO $pdo, int $userId, string $action, string $targetType, int $targetId, array $payload = []): void
{
    $pdo->prepare(
        "INSERT INTO audit_log (user_id, action, target_type, target_id, payload_json, ip)
         VALUES (?, ?, ?, ?, ?, ?)"
    )->execute([
        $userId, $action, $targetType, $targetId,
        $payload ? json_encode($payload, JSON_UNESCAPED_UNICODE) : null,
        $_SERVER['REMOTE_ADDR'] ?? null,
    ]);
}
```

Simpan ke `src/helpers/audit.php`.

- [ ] **Step 2: Commit**

```bash
git add src/helpers/audit.php
git commit -m "refactor: extract auditLog() ke src/helpers/audit.php"
```

---

## Task 2: Buat src/helpers/notify.php

**Files:**
- Create: `src/helpers/notify.php`

- [ ] **Step 1: Buat file baru**

```php
<?php
declare(strict_types=1);

function sendWhatsApp(string $target, string $message): void
{
    $token = defined('WA_TOKEN') ? WA_TOKEN : (getenv('WA_TOKEN') ?: '');
    if (!$token || !$target) return;

    $ch = curl_init('https://api.fonnte.com/send');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_TIMEOUT        => 8,
        CURLOPT_HTTPHEADER     => ['Authorization: ' . $token],
        CURLOPT_POSTFIELDS     => http_build_query([
            'target'  => $target,
            'message' => $message,
        ]),
    ]);
    curl_exec($ch);
    curl_close($ch);
}
```

Simpan ke `src/helpers/notify.php`.

- [ ] **Step 2: Hapus sendWhatsApp() dari compliance.php**

Buka `src/helpers/compliance.php`. Hapus blok fungsi `sendWhatsApp()` (baris 44–63). File hanya tinggal fungsi `computeCompliance()`.

- [ ] **Step 3: Commit**

```bash
git add src/helpers/notify.php src/helpers/compliance.php
git commit -m "refactor: pindah sendWhatsApp() ke src/helpers/notify.php"
```

---

## Task 3: Buat src/helpers/password.php

**Files:**
- Create: `src/helpers/password.php`

- [ ] **Step 1: Buat file baru**

```php
<?php
declare(strict_types=1);

function generatePassword(int $length = 8): string
{
    $chars  = 'abcdefghijkmnpqrstuvwxyzABCDEFGHJKLMNPQRSTUVWXYZ23456789';
    $result = '';
    for ($i = 0; $i < $length; $i++) {
        $result .= $chars[random_int(0, strlen($chars) - 1)];
    }
    return $result;
}
```

Simpan ke `src/helpers/password.php`.

- [ ] **Step 2: Commit**

```bash
git add src/helpers/password.php
git commit -m "refactor: extract generatePassword() ke src/helpers/password.php"
```

---

## Task 4: Expand src/bootstrap.php

**Files:**
- Modify: `src/bootstrap.php`

- [ ] **Step 1: Tambah auto-include di akhir bootstrap.php**

Buka `src/bootstrap.php`. Tambahkan blok ini di baris paling bawah (setelah `set_exception_handler`):

```php
// ─── Auto-include helpers wajib ───────────────────────────────────────────
require_once ROOT_PATH . '/src/helpers/db.php';
require_once ROOT_PATH . '/src/helpers/response.php';
require_once ROOT_PATH . '/src/helpers/csrf.php';
require_once ROOT_PATH . '/src/helpers/auth.php';
require_once ROOT_PATH . '/src/middleware/role.php';
require_once ROOT_PATH . '/src/helpers/audit.php';
require_once ROOT_PATH . '/src/helpers/notify.php';
require_once ROOT_PATH . '/src/helpers/password.php';
require_once ROOT_PATH . '/src/helpers/compliance.php';
```

- [ ] **Step 2: Verifikasi tidak ada error**

Buka browser ke `https://ops.sukashawarma.com/api/health` (atau endpoint apapun yang ada). Pastikan response JSON normal, tidak ada PHP fatal error.

- [ ] **Step 3: Commit**

```bash
git add src/bootstrap.php
git commit -m "refactor: bootstrap auto-include semua helpers wajib"
```

---

## Task 5: Buat src/repositories/checklist.php

**Files:**
- Create: `src/repositories/checklist.php`

- [ ] **Step 1: Buat file repository**

```php
<?php
declare(strict_types=1);

function repo_getSubmission(int $outletId, string $date, string $shift): ?array
{
    $pdo  = db();
    $stmt = $pdo->prepare(
        'SELECT id, outlet_id, user_id, shift, submission_date, status,
                data_fields_json, pic_name, spv_name, handover_note,
                late, locked, submitted_at
         FROM checklist_submissions
         WHERE outlet_id = ? AND submission_date = ? AND shift = ?
         LIMIT 1'
    );
    $stmt->execute([$outletId, $date, $shift]);
    $submission = $stmt->fetch() ?: null;

    if (!$submission) return null;

    $items = $pdo->prepare(
        'SELECT item_code, checked FROM checklist_items_state WHERE submission_id = ?'
    );
    $items->execute([$submission['id']]);
    $checks = [];
    foreach ($items->fetchAll() as $row) {
        $checks[$row['item_code']] = (bool) $row['checked'];
    }

    $submission['data_fields'] = $submission['data_fields_json']
        ? json_decode($submission['data_fields_json'], true)
        : [];
    unset($submission['data_fields_json']);
    $submission['checks'] = $checks;
    $submission['locked'] = (bool) $submission['locked'];
    $submission['late']   = (bool) $submission['late'];

    return $submission;
}

function repo_findSubmissionForUpsert(int $outletId, string $date, string $shift): ?array
{
    $pdo  = db();
    $stmt = $pdo->prepare(
        'SELECT id, locked FROM checklist_submissions
         WHERE outlet_id = ? AND submission_date = ? AND shift = ? LIMIT 1'
    );
    $stmt->execute([$outletId, $date, $shift]);
    return $stmt->fetch() ?: null;
}

function repo_insertSubmission(PDO $pdo, int $outletId, int $userId, string $shift, string $today, array $data): int
{
    $ins = $pdo->prepare(
        'INSERT INTO checklist_submissions
            (outlet_id, user_id, shift, submission_date, status, data_fields_json,
             pic_name, spv_name, handover_note, late, locked,
             compliance_status, compliance_pct, submitted_at)
         VALUES (?, ?, ?, ?, "submitted", ?, ?, ?, ?, ?, 1, ?, ?, NOW())'
    );
    $ins->execute([
        $outletId, $userId, $shift, $today,
        json_encode($data['dataFields'], JSON_UNESCAPED_UNICODE),
        $data['picName'], $data['spvName'] ?: null, $data['handover'] ?: null, $data['late'],
        $data['compStatus'], $data['compPct'],
    ]);
    return (int) $pdo->lastInsertId();
}

function repo_updateSubmission(PDO $pdo, int $submissionId, array $data): void
{
    $pdo->prepare(
        'UPDATE checklist_submissions
         SET status = "submitted", data_fields_json = ?, pic_name = ?, spv_name = ?,
             handover_note = ?, late = ?, locked = 1,
             compliance_status = ?, compliance_pct = ?,
             submitted_at = NOW(), updated_at = NOW()
         WHERE id = ?'
    )->execute([
        json_encode($data['dataFields'], JSON_UNESCAPED_UNICODE),
        $data['picName'], $data['spvName'] ?: null, $data['handover'] ?: null, $data['late'],
        $data['compStatus'], $data['compPct'],
        $submissionId,
    ]);
}

function repo_upsertItemStates(PDO $pdo, int $submissionId, array $checks): void
{
    if (!$checks) return;
    $upsert = $pdo->prepare(
        'INSERT INTO checklist_items_state (submission_id, item_code, checked)
         VALUES (?, ?, ?)
         ON DUPLICATE KEY UPDATE checked = VALUES(checked)'
    );
    foreach ($checks as $code => $checked) {
        $upsert->execute([$submissionId, $code, $checked ? 1 : 0]);
    }
}

function repo_unlockSubmission(int $submissionId, int $unlockedBy): void
{
    db()->prepare(
        'UPDATE checklist_submissions
         SET locked = 0, status = "draft", unlocked_by = ?, unlocked_at = NOW()
         WHERE id = ?'
    )->execute([$unlockedBy, $submissionId]);
}

function repo_getSubmissionById(int $submissionId): ?array
{
    $stmt = db()->prepare('SELECT id, locked FROM checklist_submissions WHERE id = ? LIMIT 1');
    $stmt->execute([$submissionId]);
    return $stmt->fetch() ?: null;
}

function repo_getOutletName(int $outletId): string
{
    $stmt = db()->prepare('SELECT name FROM outlets WHERE id = ? LIMIT 1');
    $stmt->execute([$outletId]);
    return $stmt->fetchColumn() ?: 'Outlet #' . $outletId;
}
```

Simpan ke `src/repositories/checklist.php`.

- [ ] **Step 2: Commit**

```bash
git add src/repositories/checklist.php
git commit -m "refactor: buat src/repositories/checklist.php"
```

---

## Task 6: Buat src/repositories/spv_visit.php

**Files:**
- Create: `src/repositories/spv_visit.php`

- [ ] **Step 1: Buat file repository**

```php
<?php
declare(strict_types=1);

function repo_getVisit(int $visitId): ?array
{
    $stmt = db()->prepare('SELECT id, spv_id FROM spv_visits WHERE id = ? LIMIT 1');
    $stmt->execute([$visitId]);
    return $stmt->fetch() ?: null;
}

function repo_createVisit(int $outletId, int $spvId, array $data): int
{
    $pdo = db();
    $pdo->prepare(
        'INSERT INTO spv_visits
            (outlet_id, spv_id, visit_date, time_arrive, time_leave, visit_shift,
             pic_on_duty, payload_json, submitted_at)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())'
    )->execute([
        $outletId, $spvId,
        $data['visit_date'],
        $data['time_arrive']  ?: null,
        $data['time_leave']   ?: null,
        $data['visit_shift']  ?: null,
        $data['pic_on_duty']  ?: null,
        json_encode($data['payload_json'] ?? [], JSON_UNESCAPED_UNICODE),
    ]);
    return (int) $pdo->lastInsertId();
}

function repo_insertEmployees(PDO $pdo, int $visitId, array $employees): void
{
    if (!$employees) return;
    $stmt = $pdo->prepare(
        'INSERT INTO spv_visit_employees (visit_id, name, role, eval_json, notes) VALUES (?, ?, ?, ?, ?)'
    );
    foreach ($employees as $emp) {
        $stmt->execute([
            $visitId,
            $emp['name'] ?? '',
            $emp['role'] ?? null,
            json_encode($emp['eval_json'] ?? [], JSON_UNESCAPED_UNICODE),
            $emp['notes'] ?? null,
        ]);
    }
}

function repo_getOutletCodeByVisit(int $visitId): string
{
    $pdo  = db();
    $stmt = $pdo->prepare(
        'SELECT code FROM outlets WHERE id = (SELECT outlet_id FROM spv_visits WHERE id = ?)'
    );
    $stmt->execute([$visitId]);
    return $stmt->fetchColumn() ?: 'unknown';
}

function repo_savePhoto(int $visitId, string $relPath, ?string $thumbRel, string $tags, string $label): int
{
    $pdo = db();
    $pdo->prepare(
        'INSERT INTO spv_visit_photos (visit_id, file_path, thumb_path, tag, label) VALUES (?, ?, ?, ?, ?)'
    )->execute([$visitId, $relPath, $thumbRel, $tags, $label]);
    return (int) $pdo->lastInsertId();
}
```

Simpan ke `src/repositories/spv_visit.php`.

- [ ] **Step 2: Commit**

```bash
git add src/repositories/spv_visit.php
git commit -m "refactor: buat src/repositories/spv_visit.php"
```

---

## Task 7: Buat src/repositories/user.php

**Files:**
- Create: `src/repositories/user.php`

- [ ] **Step 1: Buat file repository**

```php
<?php
declare(strict_types=1);

function repo_listUsers(): array
{
    $rows = db()->query(
        "SELECT u.id, u.username, u.full_name, u.role, u.outlet_id, u.active,
                u.last_login_at, u.created_at,
                o.code AS outlet_code, o.name AS outlet_name
         FROM users u
         LEFT JOIN outlets o ON o.id = u.outlet_id
         ORDER BY u.role, u.username"
    )->fetchAll();

    foreach ($rows as &$r) {
        $r['active'] = (bool) $r['active'];
    }
    return $rows;
}

function repo_findUserById(int $id): ?array
{
    $stmt = db()->prepare(
        "SELECT u.id, u.username, u.full_name, u.role, u.outlet_id, u.active,
                u.last_login_at, u.created_at,
                o.code AS outlet_code, o.name AS outlet_name
         FROM users u LEFT JOIN outlets o ON o.id = u.outlet_id WHERE u.id = ?"
    );
    $stmt->execute([$id]);
    $row = $stmt->fetch() ?: null;
    if ($row) $row['active'] = (bool) $row['active'];
    return $row;
}

function repo_findUserByUsername(string $username): ?array
{
    $stmt = db()->prepare('SELECT id FROM users WHERE username = ? LIMIT 1');
    $stmt->execute([$username]);
    return $stmt->fetch() ?: null;
}

function repo_findOutletActive(int $outletId): ?array
{
    $stmt = db()->prepare('SELECT id FROM outlets WHERE id = ? AND active = 1 LIMIT 1');
    $stmt->execute([$outletId]);
    return $stmt->fetch() ?: null;
}

function repo_createUser(string $username, string $hash, string $fullName, string $role, ?int $outletId): int
{
    $pdo = db();
    $pdo->prepare(
        'INSERT INTO users (username, password_hash, full_name, role, outlet_id) VALUES (?, ?, ?, ?, ?)'
    )->execute([$username, $hash, $fullName, $role, $outletId]);
    return (int) $pdo->lastInsertId();
}

function repo_updateUser(int $id, array $fields, array $params): void
{
    $params[] = $id;
    db()->prepare('UPDATE users SET ' . implode(', ', $fields) . ' WHERE id = ?')->execute($params);
}

function repo_resetPassword(int $id, string $hash): void
{
    db()->prepare('UPDATE users SET password_hash = ? WHERE id = ?')->execute([$hash, $id]);
}

function repo_listUsersForReset(string $role): array
{
    $pdo = db();
    if ($role === 'all') {
        return $pdo->query(
            "SELECT u.id, u.username, u.full_name, u.role, o.name AS outlet_name
             FROM users u LEFT JOIN outlets o ON o.id = u.outlet_id
             WHERE u.active = 1 AND u.role != 'admin'
             ORDER BY u.role, u.username"
        )->fetchAll();
    }
    $stmt = $pdo->prepare(
        "SELECT u.id, u.username, u.full_name, u.role, o.name AS outlet_name
         FROM users u LEFT JOIN outlets o ON o.id = u.outlet_id
         WHERE u.active = 1 AND u.role = ?
         ORDER BY u.username"
    );
    $stmt->execute([$role]);
    return $stmt->fetchAll();
}
```

Simpan ke `src/repositories/user.php`.

- [ ] **Step 2: Commit**

```bash
git add src/repositories/user.php
git commit -m "refactor: buat src/repositories/user.php"
```

---

## Task 8: Buat src/validators/checklist.php

**Files:**
- Create: `src/validators/checklist.php`

- [ ] **Step 1: Buat file validator**

```php
<?php
declare(strict_types=1);

function validateChecklistGet(array $params): void
{
    $outletId = (int) ($params['outlet'] ?? 0);
    $shift    = $params['shift'] ?? '';

    if (!$outletId) jsonError('outlet_id diperlukan', 400);
    if (!in_array($shift, ['open', 'ops', 'close'], true)) jsonError('shift tidak valid', 400);
}

function validateChecklistPost(array $body): void
{
    $shift   = $body['shift']    ?? '';
    $picName = trim($body['pic_name'] ?? '');

    if (!in_array($shift, ['open', 'ops', 'close'], true)) {
        jsonError('shift tidak valid', 400);
    }
    if (strlen($picName) < 3) {
        jsonError('Nama PIC shift wajib diisi (min. 3 karakter)', 422);
    }
}
```

Simpan ke `src/validators/checklist.php`.

- [ ] **Step 2: Commit**

```bash
git add src/validators/checklist.php
git commit -m "refactor: buat src/validators/checklist.php"
```

---

## Task 9: Buat src/validators/user.php

**Files:**
- Create: `src/validators/user.php`

- [ ] **Step 1: Buat file validator**

```php
<?php
declare(strict_types=1);

function validateUserCreate(array $body): void
{
    $username = trim(strtolower($body['username'] ?? ''));
    $fullName = trim($body['full_name'] ?? '');
    $role     = $body['role'] ?? '';
    $outletId = isset($body['outlet_id']) ? (int) $body['outlet_id'] : null;

    if (!$username || !$fullName) {
        jsonError('Username dan nama lengkap wajib diisi', 422);
    }
    if (!preg_match('/^[a-z0-9_\.]{3,30}$/', $username)) {
        jsonError('Username hanya boleh huruf kecil, angka, titik, underscore (3-30 karakter)', 422);
    }
    if (!in_array($role, ['outlet', 'spv', 'owner', 'admin'], true)) {
        jsonError('Role tidak valid', 422);
    }
    if ($role === 'outlet' && !$outletId) {
        jsonError('Outlet wajib dipilih untuk user role outlet', 422);
    }
}

function validateUserUpdate(array $body): void
{
    if (isset($body['full_name']) && !trim($body['full_name'])) {
        jsonError('Nama tidak boleh kosong', 422);
    }
    if (isset($body['type']) && !in_array($body['type'], ['internal', 'mitra'], true)) {
        jsonError('Tipe tidak valid', 422);
    }
}

function validateResetAllRole(string $role): void
{
    if (!in_array($role, ['outlet', 'spv', 'owner', 'all'], true)) {
        jsonError('Role tidak valid', 422);
    }
}
```

Simpan ke `src/validators/user.php`.

- [ ] **Step 2: Tambah include ke bootstrap.php**

Buka `src/bootstrap.php`. Tambahkan blok ini di blok auto-include (setelah baris `compliance.php`):

```php
require_once ROOT_PATH . '/src/repositories/checklist.php';
require_once ROOT_PATH . '/src/repositories/spv_visit.php';
require_once ROOT_PATH . '/src/repositories/user.php';
require_once ROOT_PATH . '/src/validators/checklist.php';
require_once ROOT_PATH . '/src/validators/user.php';
```

- [ ] **Step 3: Commit**

```bash
git add src/validators/user.php src/bootstrap.php
git commit -m "refactor: buat src/validators/user.php, include repo+validator di bootstrap"
```

---

## Task 10: Refactor api/checklists.php

**Files:**
- Modify: `api/checklists.php`

- [ ] **Step 1: Tulis ulang file**

Ganti seluruh isi `api/checklists.php` dengan:

```php
<?php
declare(strict_types=1);

require_once __DIR__ . '/../src/bootstrap.php';

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

// ─── POST /api/checklists/{id}/unlock ────────────────────────────────────
$uri = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH);
if (preg_match('#/api/checklists/(\d+)/unlock$#', $uri, $m)) {
    if ($method !== 'POST') jsonError('Method tidak didukung', 405);

    csrfValidate();
    $user  = requireRole('spv', 'admin');
    $subId = (int) $m[1];

    $sub = repo_getSubmissionById($subId);
    if (!$sub) jsonError('Submission tidak ditemukan', 404);
    if (!(bool) $sub['locked']) jsonError('Submission sudah dalam status tidak terkunci', 409);

    repo_unlockSubmission($subId, (int) $user['id']);
    auditLog(db(), (int) $user['id'], 'checklist_unlock', 'checklist_submission', $subId);

    jsonOk(['unlocked' => true, 'submission_id' => $subId]);
}

// ─── GET /api/checklists?outlet=&date=&shift= ────────────────────────────
if ($method === 'GET') {
    $user = requireRole('outlet', 'spv', 'admin', 'owner');

    $outletId = (int) ($_GET['outlet'] ?? $user['outlet_id'] ?? 0);
    $date     = $_GET['date']  ?? date('Y-m-d');
    $shift    = $_GET['shift'] ?? '';

    validateChecklistGet(['outlet' => $outletId, 'shift' => $shift]);

    if ($user['role'] === 'outlet' && $outletId !== (int) $user['outlet_id']) {
        jsonError('Akses ditolak', 403);
    }

    $submission = repo_getSubmission($outletId, $date, $shift);
    jsonOk(['submission' => $submission]);
}

// ─── POST /api/checklists ─────────────────────────────────────────────────
if ($method === 'POST') {
    csrfValidate();
    $user = requireRole('outlet');

    $body = json_decode(file_get_contents('php://input'), true) ?: [];
    validateChecklistPost($body);

    $shift    = $body['shift'];
    $picName  = trim($body['pic_name']);
    $spvName  = trim($body['spv_name'] ?? '');
    $handover = trim($body['handover_note'] ?? '');
    $checks   = $body['checks'] ?? [];
    $inputs   = $body['inputs'] ?? [];

    $outletId = (int) $user['outlet_id'];
    $userId   = (int) $user['id'];
    $today    = date('Y-m-d');

    $existing = repo_findSubmissionForUpsert($outletId, $today, $shift);
    if ($existing && $existing['locked']) {
        jsonError('Submission sudah terkunci. Hubungi supervisor untuk unlock.', 409);
    }

    // Hitung late flag
    $hour = (int) date('H');
    $lateWindows = ['open' => [5, 10], 'ops' => [9, 16], 'close' => [15, 23]];
    [$openH, $closeH] = $lateWindows[$shift];
    $late = ($hour < $openH || $hour > $closeH) ? 1 : 0;

    // Pisahkan data_fields
    $dataFields = $inputs;
    unset($dataFields['pic_name'], $dataFields['spv_name'], $dataFields['handover']);

    // Compliance
    $checklistData = json_decode(file_get_contents(ROOT_PATH . '/assets/data/checklist.json'), true);
    $comp = computeCompliance($checklistData, $shift, $checks);

    $rowData = [
        'picName'    => $picName,
        'spvName'    => $spvName,
        'handover'   => $handover,
        'late'       => $late,
        'dataFields' => $dataFields,
        'compStatus' => $comp['status'],
        'compPct'    => $comp['pct'],
    ];

    $pdo = db();
    $pdo->beginTransaction();
    try {
        if ($existing) {
            repo_updateSubmission($pdo, (int) $existing['id'], $rowData);
            $submissionId = (int) $existing['id'];
        } else {
            $submissionId = repo_insertSubmission($pdo, $outletId, $userId, $shift, $today, $rowData);
        }
        repo_upsertItemStates($pdo, $submissionId, $checks);
        $pdo->commit();
    } catch (\Throwable $e) {
        $pdo->rollBack();
        jsonError('Gagal menyimpan submission', 500);
    }

    if ($comp['status'] === 'danger' && WA_SPV_NUMBER) {
        $outletName  = repo_getOutletName($outletId);
        $shiftLabel  = ['open' => 'Open', 'ops' => 'Operasional', 'close' => 'Close'][$shift] ?? $shift;
        $msg = "⚠️ *KRITIKAL TERLEWAT*\n"
             . "Outlet: {$outletName}\n"
             . "Shift: {$shiftLabel} | Tanggal: {$today}\n"
             . "PIC: {$picName}\n"
             . "{$comp['crit_missed']} item KRITIKAL belum selesai.\n"
             . "Silakan cek dashboard segera.";
        sendWhatsApp(WA_SPV_NUMBER, $msg);
    }

    jsonOk([
        'submission_id'     => $submissionId,
        'late'              => (bool) $late,
        'compliance_status' => $comp['status'],
        'compliance_pct'    => $comp['pct'],
        'crit_missed'       => $comp['crit_missed'],
        'message'           => $late
            ? 'Submission berhasil disimpan (terlambat dari window shift).'
            : 'Submission berhasil.',
    ], 201);
}

jsonError('Method tidak didukung', 405);
```

- [ ] **Step 2: Verifikasi**

Buka browser / Postman ke `GET /api/checklists?outlet=1&date=2026-06-04&shift=open`. Pastikan response JSON normal.

- [ ] **Step 3: Commit**

```bash
git add api/checklists.php
git commit -m "refactor: slim api/checklists.php pakai repo + validator"
```

---

## Task 11: Refactor api/spv-visits.php

**Files:**
- Modify: `api/spv-visits.php`

- [ ] **Step 1: Tulis ulang file**

Ganti seluruh isi `api/spv-visits.php` dengan:

```php
<?php
declare(strict_types=1);

require_once __DIR__ . '/../src/bootstrap.php';

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$uri    = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH);

// ─── POST /api/spv-visits/{id}/photos ────────────────────────────────────
if (preg_match('#/api/spv-visits/(\d+)/photos$#', $uri, $m)) {
    if ($method !== 'POST') jsonError('Method tidak didukung', 405);

    csrfValidate();
    $user    = requireRole('spv', 'admin');
    $visitId = (int) $m[1];

    $visit = repo_getVisit($visitId);
    if (!$visit) jsonError('Visit tidak ditemukan', 404);
    if ($user['role'] !== 'admin' && (int) $visit['spv_id'] !== (int) $user['id']) {
        jsonError('Akses ditolak', 403);
    }

    $label = trim($_POST['label'] ?? '');
    $tags  = json_decode($_POST['tags'] ?? '[]', true) ?: [];
    $b64   = $_POST['imgdata'] ?? '';

    if (empty($label)) jsonError('Caption foto wajib diisi', 422);
    if (empty($b64))   jsonError('Data foto tidak ditemukan', 400);

    [$relPath, $thumbRel] = saveUploadedImage($visitId, $b64);

    $photoId = repo_savePhoto($visitId, $relPath, $thumbRel, implode(',', $tags), $label);
    jsonOk(['photo_id' => $photoId, 'path' => $relPath], 201);
}

// ─── POST /api/spv-visits ─────────────────────────────────────────────────
if ($method === 'POST') {
    csrfValidate();
    $user = requireRole('spv', 'admin');

    $body = json_decode(file_get_contents('php://input'), true) ?: [];
    $outletId  = (int) ($body['outlet_id'] ?? 0);
    if (!$outletId) jsonError('outlet_id diperlukan', 400);

    $pdo = db();
    $pdo->beginTransaction();
    try {
        $visitId = repo_createVisit($outletId, (int) $user['id'], $body);
        repo_insertEmployees($pdo, $visitId, $body['employees'] ?? []);
        $pdo->commit();
    } catch (\Throwable $e) {
        $pdo->rollBack();
        jsonError('Gagal menyimpan visit', 500);
    }

    jsonOk(['visit_id' => $visitId], 201);
}

jsonError('Method tidak didukung', 405);

// ── Helper upload (lokal, hanya dipakai file ini) ────────────────────────
function saveUploadedImage(int $visitId, string $b64): array
{
    if (str_contains($b64, ',')) {
        $b64 = explode(',', $b64, 2)[1];
    }
    $imageData = base64_decode($b64, true);
    if ($imageData === false || strlen($imageData) < 100) {
        jsonError('Data foto tidak valid', 400);
    }

    $outletCode = repo_getOutletCodeByVisit($visitId);
    $visitDate  = date('Y-m-d');
    $relDir     = 'uploads/spv/' . preg_replace('/[^a-z0-9\-]/i', '', $outletCode) . '/' . $visitDate;
    $absDir     = ROOT_PATH . '/' . $relDir;
    $thumbDir   = $absDir . '/thumb';

    if (!is_dir($absDir))   mkdir($absDir,   0755, true);
    if (!is_dir($thumbDir)) mkdir($thumbDir, 0755, true);

    $uuid     = bin2hex(random_bytes(8));
    $fileName = $uuid . '.jpg';
    $absPath  = $absDir . '/' . $fileName;
    $relPath  = $relDir . '/' . $fileName;
    $thumbAbs = $thumbDir . '/' . $fileName;
    $thumbRel = $relDir . '/thumb/' . $fileName;

    if (file_put_contents($absPath, $imageData) === false) {
        jsonError('Gagal menyimpan foto ke server', 500);
    }

    if (function_exists('imagecreatefromjpeg')) {
        $src = @imagecreatefromjpeg($absPath);
        if ($src) {
            $ow = imagesx($src); $oh = imagesy($src);
            $tw = min(300, $ow);
            $th = (int) round($oh * $tw / $ow);
            $thumb = imagecreatetruecolor($tw, $th);
            imagecopyresampled($thumb, $src, 0, 0, 0, 0, $tw, $th, $ow, $oh);
            imagejpeg($thumb, $thumbAbs, 75);
            imagedestroy($src); imagedestroy($thumb);
        } else {
            $thumbRel = null;
        }
    } else {
        $thumbRel = null;
    }

    return [$relPath, $thumbRel];
}
```

- [ ] **Step 2: Verifikasi**

Test `POST /api/spv-visits` dengan body JSON via Postman. Pastikan response `{"ok":true,"data":{"visit_id":...}}`.

- [ ] **Step 3: Commit**

```bash
git add api/spv-visits.php
git commit -m "refactor: slim api/spv-visits.php pakai repo"
```

---

## Task 12: Refactor api/spv-attach.php

**Files:**
- Modify: `api/spv-attach.php`

- [ ] **Step 1: Tulis ulang file**

Ganti seluruh isi `api/spv-attach.php` dengan:

```php
<?php
declare(strict_types=1);

require_once __DIR__ . '/../src/bootstrap.php';

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    jsonError('Method tidak didukung', 405);
}

csrfValidate();
$user    = requireRole('spv', 'admin');
$visitId = (int) ($_GET['vid'] ?? 0);
if (!$visitId) jsonError('vid diperlukan', 400);

$visit = repo_getVisit($visitId);
if (!$visit) jsonError('Visit tidak ditemukan', 404);
if ($user['role'] !== 'admin' && (int) $visit['spv_id'] !== (int) $user['id']) {
    jsonError('Akses ditolak', 403);
}

$label = trim($_POST['label'] ?? '');
$tags  = json_decode($_POST['tags'] ?? '[]', true) ?: [];
$b64   = $_POST['imgdata'] ?? '';

if (empty($label)) jsonError('Label wajib diisi', 422);
if (empty($b64))   jsonError('Data gambar tidak ditemukan', 400);

if (str_contains($b64, ',')) {
    $b64 = explode(',', $b64, 2)[1];
}
$imageData = base64_decode($b64, true);
if ($imageData === false || strlen($imageData) < 100) {
    jsonError('Data gambar tidak valid', 400);
}

$outletCode = repo_getOutletCodeByVisit($visitId);
$visitDate  = date('Y-m-d');
$relDir     = 'uploads/spv/' . preg_replace('/[^a-z0-9\-]/i', '', $outletCode) . '/' . $visitDate;
$absDir     = ROOT_PATH . '/' . $relDir;
$thumbDir   = $absDir . '/thumb';

if (!is_dir($absDir))   mkdir($absDir,   0755, true);
if (!is_dir($thumbDir)) mkdir($thumbDir, 0755, true);

$uuid     = bin2hex(random_bytes(8));
$fileName = $uuid . '.jpg';
$absPath  = $absDir . '/' . $fileName;
$relPath  = $relDir . '/' . $fileName;
$thumbAbs = $thumbDir . '/' . $fileName;
$thumbRel = $relDir . '/thumb/' . $fileName;

if (file_put_contents($absPath, $imageData) === false) {
    jsonError('Gagal menyimpan gambar ke server', 500);
}

if (function_exists('imagecreatefromjpeg')) {
    $src = @imagecreatefromjpeg($absPath);
    if ($src) {
        $ow = imagesx($src); $oh = imagesy($src);
        $tw = min(300, $ow);
        $th = (int) round($oh * $tw / $ow);
        $thumb = imagecreatetruecolor($tw, $th);
        imagecopyresampled($thumb, $src, 0, 0, 0, 0, $tw, $th, $ow, $oh);
        imagejpeg($thumb, $thumbAbs, 75);
        imagedestroy($src); imagedestroy($thumb);
    } else {
        $thumbRel = null;
    }
} else {
    $thumbRel = null;
}

$photoId = repo_savePhoto($visitId, $relPath, $thumbRel, implode(',', $tags), $label);
jsonOk(['photo_id' => $photoId, 'path' => $relPath], 201);
```

- [ ] **Step 2: Verifikasi**

Test upload foto via SPV visit form di browser. Pastikan foto tersimpan dan response OK.

- [ ] **Step 3: Commit**

```bash
git add api/spv-attach.php
git commit -m "refactor: slim api/spv-attach.php pakai repo"
```

---

## Task 13: Refactor api/admin/users.php

**Files:**
- Modify: `api/admin/users.php`

- [ ] **Step 1: Tulis ulang file**

Ganti seluruh isi `api/admin/users.php` dengan:

```php
<?php
declare(strict_types=1);

require_once __DIR__ . '/../../src/bootstrap.php';

$user   = requireRole('admin');
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

// ─── GET /api/admin/users ─────────────────────────────────────────────────
if ($method === 'GET') {
    jsonOk(repo_listUsers());
}

// ─── POST /api/admin/users ────────────────────────────────────────────────
if ($method === 'POST') {
    csrfValidate();
    $body = json_decode(file_get_contents('php://input'), true) ?: [];

    validateUserCreate($body);

    $username = trim(strtolower($body['username']));
    $fullName = trim($body['full_name']);
    $role     = $body['role'];
    $outletId = isset($body['outlet_id']) ? (int) $body['outlet_id'] : null;

    if (repo_findUserByUsername($username)) jsonError('Username sudah digunakan', 409);
    if ($outletId && !repo_findOutletActive($outletId)) jsonError('Outlet tidak ditemukan atau tidak aktif', 404);

    $plain = generatePassword();
    $hash  = password_hash($plain, PASSWORD_BCRYPT);
    $newId = repo_createUser($username, $hash, $fullName, $role, $role === 'outlet' ? $outletId : null);

    auditLog(db(), (int) $user['id'], 'user_create', 'user', $newId, ['role' => $role]);

    jsonOk([
        'id'             => $newId,
        'username'       => $username,
        'full_name'      => $fullName,
        'role'           => $role,
        'outlet_id'      => $role === 'outlet' ? $outletId : null,
        'active'         => true,
        'plain_password' => $plain,
    ], 201);
}

// ─── PUT /api/admin/users?id= ─────────────────────────────────────────────
if ($method === 'PUT') {
    csrfValidate();
    $id   = (int) ($_GET['id'] ?? 0);
    $body = json_decode(file_get_contents('php://input'), true) ?: [];

    // Reset semua password sekaligus
    if (($body['action'] ?? '') === 'reset_all_passwords') {
        validateResetAllRole($body['role'] ?? '');
        $rows    = repo_listUsersForReset($body['role']);
        $results = [];
        foreach ($rows as $row) {
            $plain = generatePassword();
            $hash  = password_hash($plain, PASSWORD_BCRYPT);
            repo_resetPassword((int) $row['id'], $hash);
            auditLog(db(), (int) $user['id'], 'user_reset_password', 'user', (int) $row['id']);
            $results[] = [
                'username'    => $row['username'],
                'full_name'   => $row['full_name'],
                'role'        => $row['role'],
                'outlet_name' => $row['outlet_name'] ?? '—',
                'password'    => $plain,
            ];
        }
        jsonOk(['results' => $results, 'count' => count($results)]);
    }

    if (!$id) jsonError('id user diperlukan', 400);

    $target = repo_findUserById($id);
    if (!$target) jsonError('User tidak ditemukan', 404);
    if ($target['role'] === 'admin' && $id !== (int) $user['id']) {
        jsonError('Tidak dapat mengubah akun admin lain', 403);
    }

    // Reset password satu user
    if (($body['action'] ?? '') === 'reset_password') {
        $plain = generatePassword();
        $hash  = password_hash($plain, PASSWORD_BCRYPT);
        repo_resetPassword($id, $hash);
        auditLog(db(), (int) $user['id'], 'user_reset_password', 'user', $id);
        jsonOk(['plain_password' => $plain]);
    }

    // Update fields
    $fields = [];
    $params = [];

    if (isset($body['full_name'])) {
        $fn = trim($body['full_name']);
        if (!$fn) jsonError('Nama tidak boleh kosong', 422);
        $fields[] = 'full_name = ?'; $params[] = $fn;
    }
    if (isset($body['active'])) {
        if ($id === (int) $user['id'] && !$body['active']) {
            jsonError('Tidak dapat menonaktifkan akun sendiri', 403);
        }
        $fields[] = 'active = ?'; $params[] = $body['active'] ? 1 : 0;
    }
    if (isset($body['outlet_id']) && $target['role'] === 'outlet') {
        $fields[] = 'outlet_id = ?'; $params[] = $body['outlet_id'] ? (int) $body['outlet_id'] : null;
    }
    if (!$fields) jsonError('Tidak ada field yang diubah', 400);

    repo_updateUser($id, $fields, $params);
    auditLog(db(), (int) $user['id'], 'user_update', 'user', $id);

    jsonOk(repo_findUserById($id));
}

jsonError('Method tidak didukung', 405);
```

- [ ] **Step 2: Verifikasi**

Buka halaman admin → Users. Pastikan daftar user muncul, buat user baru, dan reset password berjalan normal.

- [ ] **Step 3: Commit**

```bash
git add api/admin/users.php
git commit -m "refactor: slim api/admin/users.php, hapus fungsi lokal"
```

---

## Task 14: Refactor api/admin/outlets.php

**Files:**
- Modify: `api/admin/outlets.php`

- [ ] **Step 1: Hapus require_once duplikat dan auditLog() lokal**

Buka `api/admin/outlets.php`. Lakukan dua perubahan:

1. Ganti blok 5 baris `require_once` di atas dengan satu baris:
```php
require_once __DIR__ . '/../../src/bootstrap.php';
```

2. Hapus fungsi `auditLog()` lokal di bagian bawah file (baris `// ── Helpers ──` sampai akhir).

- [ ] **Step 2: Verifikasi**

Buka halaman admin → Outlets. Pastikan list outlet muncul dan bisa create/update.

- [ ] **Step 3: Commit**

```bash
git add api/admin/outlets.php
git commit -m "refactor: slim api/admin/outlets.php, hapus auditLog() lokal"
```

---

## Task 15: Slim sisa file api/admin/

**Files:**
- Modify: `api/admin/checklist-master.php`
- Modify: `api/admin/spv-areas.php`
- Modify: `api/admin/spv-visits.php`
- Modify: `api/admin/audit-log.php`
- Modify: `api/admin/error-log.php`

- [ ] **Step 1: Ganti boilerplate require_once di setiap file**

Di masing-masing 5 file tersebut, ganti blok ini:
```php
require_once __DIR__ . '/../../src/bootstrap.php';
require_once ROOT_PATH . '/src/helpers/db.php';
require_once ROOT_PATH . '/src/helpers/response.php';
require_once ROOT_PATH . '/src/helpers/csrf.php';
require_once ROOT_PATH . '/src/middleware/role.php';
```

Dengan satu baris:
```php
require_once __DIR__ . '/../../src/bootstrap.php';
```

> Catatan: Beberapa file mungkin sudah hanya punya satu `require_once` — skip jika sudah benar.

- [ ] **Step 2: Verifikasi**

Buka masing-masing halaman admin yang terkait (audit log, checklist master, spv areas). Pastikan tidak ada error.

- [ ] **Step 3: Commit**

```bash
git add api/admin/checklist-master.php api/admin/spv-areas.php api/admin/spv-visits.php api/admin/audit-log.php api/admin/error-log.php
git commit -m "refactor: slim boilerplate require_once di semua api/admin/"
```

---

## Task 16: Slim sisa file api/ root

**Files:**
- Modify: `api/auth/login.php`
- Modify: `api/auth/logout.php`
- Modify: `api/me.php`
- Modify: `api/health.php`
- Modify: `api/dashboard/summary.php`
- Modify: `api/dashboard/trend.php`
- Modify: `api/dashboard/compliance.php`

- [ ] **Step 1: Ganti boilerplate require_once di setiap file**

Cek setiap file — jika ada lebih dari 1 baris `require_once`, ganti semua dengan satu baris (sesuai kedalaman direktori):

- File di `api/`: `require_once __DIR__ . '/../src/bootstrap.php';`
- File di `api/auth/`: `require_once __DIR__ . '/../../src/bootstrap.php';`
- File di `api/dashboard/`: `require_once __DIR__ . '/../../src/bootstrap.php';`

- [ ] **Step 2: Verifikasi**

Login, cek endpoint `/api/me`, dan buka dashboard. Pastikan semua berjalan normal.

- [ ] **Step 3: Commit**

```bash
git add api/auth/login.php api/auth/logout.php api/me.php api/health.php api/dashboard/summary.php api/dashboard/trend.php api/dashboard/compliance.php
git commit -m "refactor: slim boilerplate require_once di api/ dan sub-direktorinya"
```

---

## Verifikasi Akhir

- [ ] Login sebagai outlet → submit checklist → cek response compliance
- [ ] Login sebagai SPV → submit SPV visit → upload foto
- [ ] Login sebagai admin → buka Users, Outlets, Audit Log
- [ ] Cek `logs/app.log` — tidak ada PHP warning atau notice baru
- [ ] Jalankan deploy ke server: `git push` → auto-deploy via `deploy.php`
