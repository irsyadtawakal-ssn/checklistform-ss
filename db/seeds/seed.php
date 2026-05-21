<?php
/**
 * SS Operations — One-Time Database Seeder
 *
 * Cara pakai (pilih salah satu):
 *
 * A) Via cPanel Terminal / SSH:
 *    cd /home/CPANELUSER/public_html/ops
 *    php db/seeds/seed.php
 *
 * B) Via browser (sementara):
 *    1. Pindahkan file ini ke folder public/ sementara
 *    2. Buka: https://ops.sukashawarma.com/seed.php?key=seed_ss_2026
 *    3. Setelah selesai, hapus file dari public/
 *
 * HAPUS FILE INI SETELAH DIJALANKAN.
 */

declare(strict_types=1);

// Proteksi akses browser (jika dijalankan via web)
if (php_sapi_name() !== 'cli') {
    $key = $_GET['key'] ?? '';
    if ($key !== 'seed_ss_2026') {
        http_response_code(403);
        die('Akses ditolak. Tambahkan ?key=seed_ss_2026 di URL.');
    }
    header('Content-Type: text/plain; charset=UTF-8');
}

// Bootstrap — resolve root regardless of where this file is placed
$root = __DIR__;
while (!file_exists($root . '/src/bootstrap.php') && dirname($root) !== $root) {
    $root = dirname($root);
}
chdir($root);
require_once $root . '/src/bootstrap.php';
require_once ROOT_PATH . '/src/helpers/db.php';

$pdo = db();

// Password default semua akun seed: ss1234!
$defaultPassword = 'ss1234!';
$hash = password_hash($defaultPassword, PASSWORD_BCRYPT, ['cost' => 12]);

echo "========================================\n";
echo " SS Operations — Database Seeder\n";
echo "========================================\n";
echo "Password default semua akun: {$defaultPassword}\n\n";

// ─── OUTLETS ──────────────────────────────────────────────────────────────────
$outletSqlFile = ROOT_PATH . '/db/seeds/001_outlets.sql';

$existingOutlets = (int) $pdo->query('SELECT COUNT(*) FROM outlets')->fetchColumn();
if ($existingOutlets > 0) {
    echo "[SKIP] Outlets sudah ada ({$existingOutlets} baris). Lewati seed outlet.\n";
} else {
    $sql = file_get_contents($outletSqlFile);
    // Hapus komentar dan jalankan INSERT saja
    $sql = preg_replace('/--[^\n]*\n/', "\n", $sql);
    $pdo->exec($sql);
    $count = (int) $pdo->query('SELECT COUNT(*) FROM outlets')->fetchColumn();
    echo "[OK]   Outlets seeded: {$count} baris\n";
}

// ─── Ambil outlet IDs ─────────────────────────────────────────────────────────
$outlets = $pdo->query('SELECT id, code FROM outlets ORDER BY id')->fetchAll();
$outletMap = array_column($outlets, 'id', 'code');

// ─── USERS ────────────────────────────────────────────────────────────────────
$existingUsers = (int) $pdo->query('SELECT COUNT(*) FROM users')->fetchColumn();
if ($existingUsers > 0) {
    echo "[SKIP] Users sudah ada ({$existingUsers} baris). Lewati seed users.\n\n";
} else {
    // 19 akun outlet (1 per outlet)
    $outletUsers = [
        ['ss.empang',       'PIC SS Empang',       'SS-001'],
        ['ss.paledang',     'PIC SS Paledang',     'SS-002'],
        ['ss.cimanggu',     'PIC SS Cimanggu',     'SS-003'],
        ['ss.sukmajaya',    'PIC SS Sukmajaya',    'SS-004'],
        ['ss.beji',         'PIC SS Beji',         'SS-005'],
        ['ss.sawangan',     'PIC SS Sawangan',     'SS-006'],
        ['ss.jagakarsa',    'PIC SS Jagakarsa',    'SS-007'],
        ['ss.pajajaran',    'PIC SS Pajajaran',    'SS-008'],
        ['ss.dramaga',      'PIC SS Dramaga',      'SS-009'],
        ['ss.cibinong',     'PIC SS Cibinong',     'SS-010'],
        ['ss.citayam',      'PIC SS Citayam',      'SS-011'],
        ['ss.tebet',        'PIC SS Tebet',        'SS-012'],
        ['ss.cirendeu',     'PIC SS Cirendeu',     'SS-013'],
        ['ss.kalisari',     'PIC SS Kalisari',     'SS-014'],
        ['ss.bekasi',       'PIC SS Bekasi',       'SS-015'],
        ['ss.jatiwaringin', 'PIC SS Jatiwaringin', 'SS-016'],
        ['ss.ciseeng',      'PIC SS Ciseeng',      'SS-017'],
        ['ss.jatiasih',     'PIC SS Jatiasih',     'SS-018'],
        ['ss.pekayon',      'PIC SS Pekayon',      'SS-019'],
    ];

    $stmtUser = $pdo->prepare(
        'INSERT INTO users (username, password_hash, full_name, role, outlet_id) VALUES (?, ?, ?, ?, ?)'
    );

    foreach ($outletUsers as [$username, $fullName, $outletCode]) {
        $outletId = $outletMap[$outletCode] ?? null;
        if ($outletId === null) {
            echo "[WARN] Outlet code tidak ditemukan: {$outletCode}. Skip user {$username}.\n";
            continue;
        }
        $stmtUser->execute([$username, $hash, $fullName, 'outlet', $outletId]);
        echo "[OK]   User outlet: {$username} → {$outletCode}\n";
    }

    // 3 akun non-outlet
    $staffUsers = [
        ['spv_utama',  'Supervisor Utama',  'spv'],
        ['owner_ss',   'Owner Suka Shawarma', 'owner'],
        ['admin_ss',   'Admin Sistem',       'admin'],
    ];

    foreach ($staffUsers as [$username, $fullName, $role]) {
        $stmtUser->execute([$username, $hash, $fullName, $role, null]);
        echo "[OK]   User {$role}: {$username}\n";
    }
}

// ─── Verifikasi ───────────────────────────────────────────────────────────────
echo "\n--- Verifikasi ---\n";
$totalOutlets  = $pdo->query('SELECT COUNT(*) FROM outlets')->fetchColumn();
$totalUsers    = $pdo->query('SELECT COUNT(*) FROM users')->fetchColumn();
$outletAccounts= $pdo->query("SELECT COUNT(*) FROM users WHERE role='outlet'")->fetchColumn();
$otherAccounts = $pdo->query("SELECT COUNT(*) FROM users WHERE role != 'outlet'")->fetchColumn();

echo "Outlets     : {$totalOutlets} (expected: 19)\n";
echo "Users outlet: {$outletAccounts} (expected: 19)\n";
echo "Users lain  : {$otherAccounts} (expected: 3 — spv/owner/admin)\n";
echo "Total users : {$totalUsers}\n";

$allOk = ($totalOutlets == 19 && $outletAccounts == 19 && $otherAccounts == 3);
echo "\nStatus: " . ($allOk ? "✓ SEMUA OK" : "✗ ADA YANG TIDAK SESUAI") . "\n";
echo "\n========================================\n";
echo "Selesai. HAPUS file ini dari server.\n";
echo "========================================\n";
