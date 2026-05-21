#!/usr/bin/env php
<?php
/**
 * SS Operations — Backup MySQL harian
 *
 * Dijalankan via cron cPanel setiap hari pukul 03:00 WIB:
 *   0 20 * * * /usr/local/bin/php /home/USERNAME/public_html/scripts/db-backup.php >> /home/USERNAME/public_html/logs/cron.log 2>&1
 *   (UTC 20:00 = WIB 03:00)
 *
 * Struktur folder backup:
 *   backup/
 *     hot/      ← 30 hari terakhir (cepat diakses)
 *     archive/  ← > 30 hari (retensi 2 tahun)
 */

declare(strict_types=1);

define('ROOT_PATH', dirname(__DIR__));
require_once ROOT_PATH . '/src/helpers/env.php';
loadEnv(ROOT_PATH . '/.env');

// ─── Konfigurasi ───────────────────────────────────────────────────────────
$dbHost   = env('DB_HOST', 'localhost');
$dbName   = env('DB_NAME', '');
$dbUser   = env('DB_USER', '');
$dbPass   = env('DB_PASS', '');
$hotDir   = ROOT_PATH . '/backup/hot';
$archDir  = ROOT_PATH . '/backup/archive';
$logFile  = ROOT_PATH . '/logs/cron.log';

$HOT_DAYS     = 30;   // hari sebelum dipindah ke archive
$ARCHIVE_DAYS = 730;  // hari sebelum archive dihapus (2 tahun)

// ─── Setup folder ──────────────────────────────────────────────────────────
foreach ([$hotDir, $archDir, dirname($logFile)] as $dir) {
    if (!is_dir($dir)) mkdir($dir, 0700, true);
}

function log_msg(string $msg): void {
    $ts = date('Y-m-d H:i:s');
    file_put_contents(ROOT_PATH . '/logs/cron.log', "[{$ts}] [backup] {$msg}\n", FILE_APPEND);
    echo "[{$ts}] [backup] {$msg}\n";
}

// ─── Jalankan mysqldump ────────────────────────────────────────────────────
$dateStr  = date('Y-m-d');
$fileName = "ss_ops_{$dateStr}.sql.gz";
$hotPath  = "{$hotDir}/{$fileName}";

log_msg("Mulai backup database: {$dbName}");

// Pastikan mysqldump tersedia
$mysqldump = trim(shell_exec('which mysqldump 2>/dev/null') ?: '');
if (!$mysqldump) {
    log_msg("ERROR: mysqldump tidak ditemukan. Pastikan MySQL CLI terinstall.");
    exit(1);
}

// Build command — password via env var untuk keamanan
$env = "MYSQL_PWD=" . escapeshellarg($dbPass);
$cmd = "{$env} mysqldump"
     . " -h " . escapeshellarg($dbHost)
     . " -u " . escapeshellarg($dbUser)
     . " --single-transaction --routines --triggers --add-drop-table"
     . " " . escapeshellarg($dbName)
     . " | gzip -9 > " . escapeshellarg($hotPath)
     . " 2>&1";

exec($cmd, $output, $exitCode);

if ($exitCode !== 0 || !file_exists($hotPath) || filesize($hotPath) < 100) {
    log_msg("ERROR: Backup gagal (exit={$exitCode}). " . implode(' ', $output));
    exit(1);
}

$size = number_format(filesize($hotPath) / 1024, 1);
log_msg("Backup selesai: {$fileName} ({$size} KB)");

// ─── Rotasi: pindah file hot > 30 hari ke archive ─────────────────────────
$moved = 0;
foreach (glob("{$hotDir}/*.sql.gz") as $file) {
    $age = (int) ((time() - filemtime($file)) / 86400);
    if ($age > $HOT_DAYS) {
        $dest = $archDir . '/' . basename($file);
        if (rename($file, $dest)) $moved++;
    }
}
if ($moved > 0) log_msg("Dipindah ke archive: {$moved} file");

// ─── Hapus archive > 2 tahun ───────────────────────────────────────────────
$deleted = 0;
foreach (glob("{$archDir}/*.sql.gz") as $file) {
    $age = (int) ((time() - filemtime($file)) / 86400);
    if ($age > $ARCHIVE_DAYS) {
        unlink($file);
        $deleted++;
    }
}
if ($deleted > 0) log_msg("Dihapus dari archive (> 2 tahun): {$deleted} file");

// ─── Ringkasan ─────────────────────────────────────────────────────────────
$hotCount  = count(glob("{$hotDir}/*.sql.gz")  ?: []);
$archCount = count(glob("{$archDir}/*.sql.gz") ?: []);
log_msg("Selesai. Hot: {$hotCount} file | Archive: {$archCount} file");
exit(0);
