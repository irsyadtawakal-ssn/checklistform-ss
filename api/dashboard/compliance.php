<?php
declare(strict_types=1);

require_once __DIR__ . '/../../src/bootstrap.php';
require_once ROOT_PATH . '/src/helpers/db.php';
require_once ROOT_PATH . '/src/helpers/response.php';
require_once ROOT_PATH . '/src/middleware/role.php';

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'GET') jsonError('Method tidak didukung', 405);

$currentUser  = requireRole('spv', 'owner', 'admin');

// ─── Parameter ────────────────────────────────────────────────────────────
$today = date('Y-m-d');
$from  = $_GET['from'] ?? date('Y-m-d', strtotime('-6 days'));
$to    = $_GET['to']   ?? $today;
$shiftFilter  = $_GET['shift']  ?? 'all'; // all|open|ops|close
$outletFilter = (int) ($_GET['outlet'] ?? 0); // 0 = semua

// Sanitasi tanggal
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $from)) $from = date('Y-m-d', strtotime('-6 days'));
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $to))   $to   = $today;
if ($from > $to) [$from, $to] = [$to, $from];

// Batas maksimal 31 hari untuk performa
$diffDays = (int) ((strtotime($to) - strtotime($from)) / 86400);
if ($diffDays > 31) $from = date('Y-m-d', strtotime($to . ' -31 days'));

// ─── Load critical item IDs dari JSON ─────────────────────────────────────
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

// ─── Ambil semua outlet aktif (filter area SPV jika ada assignment) ──────
$pdo = db();

// SPV area filter (7.7): jika SPV punya assignment, hanya tampilkan outlet tersebut
$spvAreaIds = [];
if ($currentUser['role'] === 'spv') {
    $areaStmt = $pdo->prepare('SELECT outlet_id FROM spv_outlet_assignments WHERE spv_id = ?');
    $areaStmt->execute([(int)$currentUser['id']]);
    $spvAreaIds = $areaStmt->fetchAll(PDO::FETCH_COLUMN);
}

$outletSql    = 'SELECT id, code, name FROM outlets WHERE active = 1';
$outletParams = [];
if ($spvAreaIds) {
    $ph = implode(',', array_fill(0, count($spvAreaIds), '?'));
    $outletSql .= " AND id IN ({$ph})";
    $outletParams = array_merge($outletParams, $spvAreaIds);
}
if ($outletFilter > 0) { $outletSql .= ' AND id = ?'; $outletParams[] = $outletFilter; }
$outletSql .= ' ORDER BY code';
$outletStmt = $pdo->prepare($outletSql);
$outletStmt->execute($outletParams);
$outlets = $outletStmt->fetchAll();

// ─── Ambil semua submissions dalam rentang ─────────────────────────────────
$shiftCond = $shiftFilter !== 'all' ? ' AND cs.shift = ?' : '';
$params = [$from, $to];
if ($shiftFilter !== 'all') $params[] = $shiftFilter;
if ($outletFilter > 0) { $params[] = $outletFilter; }

$subSql = "SELECT cs.id, cs.outlet_id, cs.shift, cs.submission_date,
                  cs.status, cs.pic_name, cs.late, cs.locked
           FROM checklist_submissions cs
           WHERE cs.submission_date BETWEEN ? AND ?
             {$shiftCond}"
        . ($outletFilter > 0 ? ' AND cs.outlet_id = ?' : '')
        . ' ORDER BY cs.submission_date, cs.outlet_id, cs.shift';

$subStmt = $pdo->prepare($subSql);
$subStmt->execute($params);
$submissions = $subStmt->fetchAll();

// Ambil item states untuk semua submissions sekaligus
$subIds = array_column($submissions, 'id');
$itemStates = []; // [submission_id][item_code] = checked
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

// ─── Index submissions → [outlet_id][date][shift] ─────────────────────────
$subIndex = [];
foreach ($submissions as $sub) {
    $sid   = (int)$sub['id'];
    $oid   = (int)$sub['outlet_id'];
    $date  = $sub['submission_date'];
    $shift = $sub['shift'];

    $checks     = $itemStates[$sid] ?? [];
    $critIds    = $criticalIds[$shift] ?? [];
    $total      = $totalItems[$shift]  ?? 0;
    $done       = count(array_filter($checks));
    $critDone   = count(array_filter(array_intersect_key($checks, array_flip($critIds))));
    $critMissed = count($critIds) - $critDone;
    $pct        = $total > 0 ? round($done / $total * 100) : 0;

    $statusVal = 'danger'; // default: critical missed
    if ($critMissed === 0) {
        $statusVal = ($done === $total) ? 'ok' : 'warn';
    }

    $subIndex[$oid][$date][$shift] = [
        'submission_id' => $sid,
        'status'        => $statusVal,
        'pct'           => $pct,
        'done'          => $done,
        'total'         => $total,
        'crit_missed'   => $critMissed,
        'pic_name'      => $sub['pic_name'],
        'late'          => (bool)$sub['late'],
        'locked'        => (bool)$sub['locked'],
    ];
}

// ─── Build date array ─────────────────────────────────────────────────────
$dates = [];
$cur = $from;
while ($cur <= $to) { $dates[] = $cur; $cur = date('Y-m-d', strtotime($cur . ' +1 day')); }

// ─── Build response ────────────────────────────────────────────────────────
$shifts = $shiftFilter === 'all' ? ['open', 'ops', 'close'] : [$shiftFilter];
$result = [];
foreach ($outlets as $outlet) {
    $oid  = (int)$outlet['id'];
    $row  = ['id' => $oid, 'code' => $outlet['code'], 'name' => $outlet['name'], 'days' => []];
    foreach ($dates as $date) {
        $dayData = [];
        foreach ($shifts as $s) {
            $dayData[$s] = $subIndex[$oid][$date][$s] ?? ['status' => 'idle'];
        }
        $row['days'][$date] = $dayData;
    }
    $result[] = $row;
}

jsonOk(['dates' => $dates, 'outlets' => $result]);
