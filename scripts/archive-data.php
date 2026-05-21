#!/usr/bin/env php
<?php
/**
 * SS Operations — Arsipkan data lama (> 2 tahun)
 *
 * Dijalankan via cron cPanel setiap minggu (misal Minggu pukul 02:00 WIB):
 *   0 19 * * 0 /usr/local/bin/php /home/USERNAME/public_html/scripts/archive-data.php >> /home/USERNAME/public_html/logs/cron.log 2>&1
 *   (UTC 19:00 = WIB 02:00)
 *
 * Yang dilakukan:
 *   1. Salin checklist_submissions + items yang submission_date < 2 tahun lalu ke tabel *_archive
 *   2. Hapus data asli setelah berhasil disalin
 *   3. Salin spv_visits yang visit_date < 2 tahun lalu ke spv_visits_archive
 *   4. Hapus data asli
 *
 * Prasyarat: jalankan db/migrations/002_archive_tables.sql terlebih dahulu
 */

declare(strict_types=1);

define('ROOT_PATH', dirname(__DIR__));
require_once ROOT_PATH . '/src/helpers/env.php';
require_once ROOT_PATH . '/src/helpers/db.php';
loadEnv(ROOT_PATH . '/.env');

$logFile = ROOT_PATH . '/logs/cron.log';
if (!is_dir(dirname($logFile))) mkdir(dirname($logFile), 0755, true);

function alog(string $msg): void {
    $ts = date('Y-m-d H:i:s');
    file_put_contents(ROOT_PATH . '/logs/cron.log', "[{$ts}] [archive] {$msg}\n", FILE_APPEND);
    echo "[{$ts}] [archive] {$msg}\n";
}

$cutoff = date('Y-m-d', strtotime('-2 years'));
alog("Mulai proses arsip. Cutoff: {$cutoff}");

$pdo = db();
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// ─── 1. Archive checklist_submissions ─────────────────────────────────────
alog("Mengarsipkan checklist_submissions...");
$pdo->beginTransaction();
try {
    // Ambil IDs submission yang perlu diarsipkan
    $stmt = $pdo->prepare("SELECT id FROM checklist_submissions WHERE submission_date < ? AND submission_date >= '2020-01-01'");
    $stmt->execute([$cutoff]);
    $subIds = $stmt->fetchAll(PDO::FETCH_COLUMN);

    if ($subIds) {
        $placeholders = implode(',', array_fill(0, count($subIds), '?'));

        // Salin item states dulu (karena FK ke submissions)
        $pdo->prepare(
            "INSERT IGNORE INTO checklist_items_state_archive
                (id, submission_id, item_code, checked, notes)
             SELECT id, submission_id, item_code, checked, notes
             FROM checklist_items_state
             WHERE submission_id IN ({$placeholders})"
        )->execute($subIds);
        $itemsCopied = $pdo->query("SELECT ROW_COUNT()")->fetchColumn();

        // Salin submissions
        $pdo->prepare(
            "INSERT IGNORE INTO checklist_submissions_archive
                (id, outlet_id, user_id, shift, submission_date, status, data_fields_json,
                 pic_name, spv_name, handover_note, late, locked, unlocked_by, unlocked_at,
                 submitted_at, created_at, updated_at)
             SELECT id, outlet_id, user_id, shift, submission_date, status, data_fields_json,
                    pic_name, spv_name, handover_note, late, locked, unlocked_by, unlocked_at,
                    submitted_at, created_at, updated_at
             FROM checklist_submissions
             WHERE id IN ({$placeholders})"
        )->execute($subIds);
        $subsCopied = $pdo->query("SELECT ROW_COUNT()")->fetchColumn();

        // Hapus item states (FK cascade tidak bekerja di archive, hapus manual)
        $pdo->prepare("DELETE FROM checklist_items_state WHERE submission_id IN ({$placeholders})")->execute($subIds);
        // Hapus submissions
        $pdo->prepare("DELETE FROM checklist_submissions WHERE id IN ({$placeholders})")->execute($subIds);

        alog("Submissions diarsipkan: {$subsCopied} | Items: {$itemsCopied}");
    } else {
        alog("Tidak ada submission untuk diarsipkan.");
    }

    $pdo->commit();
} catch (\Throwable $e) {
    $pdo->rollBack();
    alog("ERROR archive submissions: " . $e->getMessage());
    exit(1);
}

// ─── 2. Archive spv_visits ─────────────────────────────────────────────────
alog("Mengarsipkan spv_visits...");
$pdo->beginTransaction();
try {
    $pdo->prepare(
        "INSERT IGNORE INTO spv_visits_archive
            (id, outlet_id, spv_id, visit_date, time_arrive, time_leave, visit_shift,
             pic_on_duty, payload_json, summary_json, submitted_at, created_at, updated_at)
         SELECT id, outlet_id, spv_id, visit_date, time_arrive, time_leave, visit_shift,
                pic_on_duty, payload_json, summary_json, submitted_at, created_at, updated_at
         FROM spv_visits
         WHERE visit_date < ? AND visit_date >= '2020-01-01'"
    )->execute([$cutoff]);
    $visitsCopied = $pdo->query("SELECT ROW_COUNT()")->fetchColumn();

    if ((int)$visitsCopied > 0) {
        $pdo->prepare("DELETE FROM spv_visits WHERE visit_date < ?")->execute([$cutoff]);
        alog("Visits diarsipkan: {$visitsCopied}");
    } else {
        alog("Tidak ada visits untuk diarsipkan.");
    }

    $pdo->commit();
} catch (\Throwable $e) {
    $pdo->rollBack();
    alog("ERROR archive visits: " . $e->getMessage());
    exit(1);
}

alog("Proses arsip selesai.");
exit(0);
