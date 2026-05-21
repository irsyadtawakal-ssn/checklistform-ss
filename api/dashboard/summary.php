<?php
declare(strict_types=1);

require_once __DIR__ . '/../../src/bootstrap.php';
require_once ROOT_PATH . '/src/helpers/db.php';
require_once ROOT_PATH . '/src/helpers/response.php';
require_once ROOT_PATH . '/src/middleware/role.php';

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'GET') jsonError('Method tidak didukung', 405);

requireRole('spv', 'owner', 'admin');

// ─── Parameter ────────────────────────────────────────────────────────────
$today = date('Y-m-d');
$from  = $_GET['from'] ?? date('Y-m-01'); // default: awal bulan ini
$to    = $_GET['to']   ?? $today;

if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $from)) $from = date('Y-m-01');
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $to))   $to   = $today;
if ($from > $to) [$from, $to] = [$to, $from];

// Batas 92 hari
$diffDays = (int) ((strtotime($to) - strtotime($from)) / 86400);
if ($diffDays > 92) $from = date('Y-m-d', strtotime($to . ' -92 days'));

// ─── Load critical item IDs ───────────────────────────────────────────────
$checklistData = json_decode(file_get_contents(ROOT_PATH . '/assets/data/checklist.json'), true);
$criticalIds   = ['open' => [], 'ops' => [], 'close' => []];
$totalItems    = ['open' => 0, 'ops' => 0, 'close' => 0];

foreach (['open', 'ops', 'close'] as $shift) {
    foreach ($checklistData['checklist'][$shift] as $section) {
        foreach ($section['items'] as $item) {
            $totalItems[$shift]++;
            if (($item['badge'] ?? '') === 'ibadge-crit') {
                $criticalIds[$shift][] = $item['id'];
            }
        }
    }
}

$pdo = db();

// ─── Semua submissions dalam periode ─────────────────────────────────────
$subStmt = $pdo->prepare(
    "SELECT cs.id, cs.outlet_id, cs.shift, cs.late
     FROM checklist_submissions cs
     WHERE cs.submission_date BETWEEN ? AND ?"
);
$subStmt->execute([$from, $to]);
$submissions = $subStmt->fetchAll();

// Bulk fetch item states
$subIds = array_column($submissions, 'id');
$itemStates = [];
if ($subIds) {
    $placeholders = implode(',', array_fill(0, count($subIds), '?'));
    $items = $pdo->prepare(
        "SELECT submission_id, item_code, checked FROM checklist_items_state WHERE submission_id IN ({$placeholders})"
    );
    $items->execute($subIds);
    foreach ($items->fetchAll() as $row) {
        $itemStates[(int)$row['submission_id']][$row['item_code']] = (bool)$row['checked'];
    }
}

// ─── Hitung per-outlet stats ──────────────────────────────────────────────
$outletStats = []; // [outlet_id] = {total_sub, danger, warn, ok, late, pct_sum}

foreach ($submissions as $sub) {
    $sid   = (int)$sub['id'];
    $oid   = (int)$sub['outlet_id'];
    $shift = $sub['shift'];

    $checks   = $itemStates[$sid] ?? [];
    $critIds  = $criticalIds[$shift] ?? [];
    $total    = $totalItems[$shift]  ?? 0;
    $done     = count(array_filter($checks));
    $critDone = count(array_filter(array_intersect_key($checks, array_flip($critIds))));
    $critMiss = count($critIds) - $critDone;
    $pct      = $total > 0 ? round($done / $total * 100) : 0;

    $status = $critMiss > 0 ? 'danger' : ($done === $total ? 'ok' : 'warn');

    if (!isset($outletStats[$oid])) {
        $outletStats[$oid] = ['total_sub' => 0, 'danger' => 0, 'warn' => 0, 'ok' => 0, 'late' => 0, 'pct_sum' => 0];
    }
    $outletStats[$oid]['total_sub']++;
    $outletStats[$oid][$status]++;
    $outletStats[$oid]['pct_sum'] += $pct;
    if ($sub['late']) $outletStats[$oid]['late']++;
}

// ─── Rankings ─────────────────────────────────────────────────────────────
$outletRows = $pdo->query("SELECT id, code, name FROM outlets WHERE active = 1 ORDER BY code")->fetchAll();
$rankings   = [];
foreach ($outletRows as $o) {
    $oid = (int)$o['id'];
    $s   = $outletStats[$oid] ?? ['total_sub' => 0, 'danger' => 0, 'warn' => 0, 'ok' => 0, 'late' => 0, 'pct_sum' => 0];
    $avgPct = $s['total_sub'] > 0 ? round($s['pct_sum'] / $s['total_sub']) : null;
    $rankings[] = [
        'id'             => $oid,
        'code'           => $o['code'],
        'name'           => $o['name'],
        'compliance_pct' => $avgPct,
        'total_sub'      => $s['total_sub'],
        'danger_count'   => $s['danger'],
        'warn_count'     => $s['warn'],
        'ok_count'       => $s['ok'],
        'late_count'     => $s['late'],
    ];
}
usort($rankings, fn($a, $b) => ($b['compliance_pct'] ?? -1) <=> ($a['compliance_pct'] ?? -1));

// ─── Global KPIs ──────────────────────────────────────────────────────────
$totalSubs    = count($submissions);
$totalLate    = (int) array_sum(array_column($submissions, 'late'));
$totalDanger  = (int) array_sum(array_column($outletStats, 'danger'));

$pctValues = array_filter(array_column($rankings, 'compliance_pct'), fn($v) => $v !== null);
$avgCompliance = count($pctValues) > 0 ? (int) round(array_sum($pctValues) / count($pctValues)) : 0;

$spvStmt = $pdo->prepare("SELECT COUNT(*) FROM spv_visits WHERE visit_date BETWEEN ? AND ?");
$spvStmt->execute([$from, $to]);
$spvVisitCount = (int) $spvStmt->fetchColumn();

jsonOk([
    'period'          => ['from' => $from, 'to' => $to],
    'avg_compliance'  => $avgCompliance,
    'total_subs'      => $totalSubs,
    'total_late'      => $totalLate,
    'total_danger'    => $totalDanger,
    'spv_visit_count' => $spvVisitCount,
    'rankings'        => $rankings,
]);
