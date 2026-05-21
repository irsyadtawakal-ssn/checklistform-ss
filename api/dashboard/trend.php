<?php
declare(strict_types=1);

require_once __DIR__ . '/../../src/bootstrap.php';
require_once ROOT_PATH . '/src/helpers/db.php';
require_once ROOT_PATH . '/src/helpers/response.php';
require_once ROOT_PATH . '/src/middleware/role.php';

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'GET') jsonError('Method tidak didukung', 405);

requireRole('spv', 'owner', 'admin');

$months = max(1, min((int) ($_GET['months'] ?? 6), 12));
$pdo    = db();

// Pakai compliance_status/pct yang tersimpan di tabel (denormalized sejak Phase 7)
// Untuk data lama yang belum punya compliance_pct, fallback ke 0
$rows = $pdo->prepare(
    "SELECT
        DATE_FORMAT(submission_date, '%Y-%m')                          AS month,
        COUNT(*)                                                       AS total_subs,
        ROUND(AVG(IFNULL(compliance_pct, 0)))                         AS avg_pct,
        SUM(CASE WHEN compliance_status = 'danger' THEN 1 ELSE 0 END) AS danger_count,
        SUM(CASE WHEN compliance_status = 'warn'   THEN 1 ELSE 0 END) AS warn_count,
        SUM(CASE WHEN compliance_status = 'ok'     THEN 1 ELSE 0 END) AS ok_count,
        SUM(CASE WHEN compliance_status IS NULL     THEN 1 ELSE 0 END) AS legacy_count
     FROM checklist_submissions
     WHERE submission_date >= DATE_SUB(CURDATE(), INTERVAL ? MONTH)
     GROUP BY month
     ORDER BY month ASC"
);
$rows->execute([$months]);
$data = $rows->fetchAll();

foreach ($data as &$r) {
    $r['total_subs']   = (int)   $r['total_subs'];
    $r['avg_pct']      = (int)   $r['avg_pct'];
    $r['danger_count'] = (int)   $r['danger_count'];
    $r['warn_count']   = (int)   $r['warn_count'];
    $r['ok_count']     = (int)   $r['ok_count'];
    $r['legacy_count'] = (int)   $r['legacy_count'];
}

// Total outlet aktif (untuk expected submissions per bulan)
$outletCount = (int) $pdo->query("SELECT COUNT(*) FROM outlets WHERE active = 1")->fetchColumn();

jsonOk(['months' => $months, 'outlet_count' => $outletCount, 'trend' => $data]);
