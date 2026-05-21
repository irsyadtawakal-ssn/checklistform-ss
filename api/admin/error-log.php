<?php
declare(strict_types=1);

require_once __DIR__ . '/../../src/bootstrap.php';
require_once ROOT_PATH . '/src/helpers/response.php';
require_once ROOT_PATH . '/src/middleware/role.php';

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'GET') jsonError('Method tidak didukung', 405);

requireRole('admin');

$logFile = ROOT_PATH . '/logs/app.log';
$lines   = max(1, min((int) ($_GET['lines'] ?? 100), 500));

if (!file_exists($logFile)) {
    jsonOk(['entries' => [], 'size_kb' => 0, 'message' => 'Log file belum ada']);
}

$sizeKb = round(filesize($logFile) / 1024, 1);

// Ambil N baris terakhir secara efisien
$entries = [];
$fp = fopen($logFile, 'r');
if ($fp) {
    // Baca file dari belakang
    fseek($fp, 0, SEEK_END);
    $pos    = ftell($fp);
    $buffer = '';
    $count  = 0;

    while ($pos > 0 && $count < $lines) {
        $read = min(4096, $pos);
        $pos -= $read;
        fseek($fp, $pos);
        $buffer = fread($fp, $read) . $buffer;
    }
    fclose($fp);

    $rawLines = array_filter(explode("\n", trim($buffer)));
    $rawLines = array_slice(array_values($rawLines), -$lines);

    foreach (array_reverse($rawLines) as $line) {
        $line = trim($line);
        if (!$line) continue;

        // Parse format: [YYYY-MM-DD HH:MM:SS] ... atau PHP default
        $level = 'info';
        if (stripos($line, 'Error') !== false || stripos($line, 'Fatal') !== false) $level = 'error';
        elseif (stripos($line, 'Warning') !== false || stripos($line, 'Warn') !== false) $level = 'warn';
        elseif (stripos($line, 'Notice') !== false) $level = 'notice';

        $entries[] = ['text' => $line, 'level' => $level];
    }
}

jsonOk(['entries' => $entries, 'size_kb' => $sizeKb, 'lines_requested' => $lines]);
