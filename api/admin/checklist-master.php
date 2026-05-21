<?php
declare(strict_types=1);

require_once __DIR__ . '/../../src/bootstrap.php';
require_once ROOT_PATH . '/src/helpers/response.php';
require_once ROOT_PATH . '/src/helpers/csrf.php';
require_once ROOT_PATH . '/src/middleware/role.php';

requireRole('admin');

$method      = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$jsonPath    = ROOT_PATH . '/assets/data/checklist.json';
$lockFile    = ROOT_PATH . '/tmp/checklist_master.lock';

if (!is_dir(dirname($lockFile))) @mkdir(dirname($lockFile), 0755, true);

function readChecklist(string $path): array {
    return json_decode(file_get_contents($path), true) ?? [];
}

function writeChecklist(string $path, array $data, string $lockFile): bool {
    $fp = fopen($lockFile, 'c');
    if (!$fp || !flock($fp, LOCK_EX | LOCK_NB)) return false;
    $result = file_put_contents($path, json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)) !== false;
    flock($fp, LOCK_UN);
    fclose($fp);
    return $result;
}

// ─── GET → return full checklist structure ─────────────────────────────────
if ($method === 'GET') {
    jsonOk(readChecklist($jsonPath));
}

// ─── POST → add item to a section ─────────────────────────────────────────
// Body: { shift, section_index, text, badge?, btext?, note?, note_type? }
if ($method === 'POST') {
    csrfValidate();
    $body    = json_decode(file_get_contents('php://input'), true);
    $shift   = $body['shift'] ?? '';
    $secIdx  = (int) ($body['section_index'] ?? -1);
    $text    = trim($body['text'] ?? '');

    if (!in_array($shift, ['open','ops','close'], true)) jsonError('shift tidak valid', 400);
    if (!$text)  jsonError('text item wajib diisi', 422);

    $data = readChecklist($jsonPath);
    if (!isset($data['checklist'][$shift][$secIdx])) jsonError('section tidak ditemukan', 404);

    // Generate ID unik: prefix shift + section + timestamp
    $prefix = ['open'=>'o','ops'=>'p','close'=>'c'][$shift];
    $newId  = $prefix . $secIdx . '_' . substr(uniqid(), -5);

    $newItem = [
        'id'    => $newId,
        'text'  => $text,
        'badge' => $body['badge'] ?? '',
        'btext' => $body['btext'] ?? '',
    ];
    if (!empty($body['note']))      $newItem['note']     = $body['note'];
    if (!empty($body['note_type'])) $newItem['noteType'] = $body['note_type'];

    $data['checklist'][$shift][$secIdx]['items'][] = $newItem;

    if (!writeChecklist($jsonPath, $data, $lockFile)) jsonError('Gagal menyimpan file — coba lagi', 500);

    jsonOk(['item' => $newItem, 'shift' => $shift, 'section_index' => $secIdx], 201);
}

// ─── PUT → edit item teks / badge ─────────────────────────────────────────
// Body: { shift, item_id, text?, badge?, btext?, note?, note_type? }
if ($method === 'PUT') {
    csrfValidate();
    $body   = json_decode(file_get_contents('php://input'), true);
    $shift  = $body['shift']   ?? '';
    $itemId = $body['item_id'] ?? '';

    if (!in_array($shift, ['open','ops','close'], true)) jsonError('shift tidak valid', 400);
    if (!$itemId) jsonError('item_id wajib diisi', 400);

    $data  = readChecklist($jsonPath);
    $found = false;

    foreach ($data['checklist'][$shift] as &$section) {
        foreach ($section['items'] as &$item) {
            if ($item['id'] === $itemId) {
                if (isset($body['text']))      $item['text']     = trim($body['text']);
                if (isset($body['badge']))     $item['badge']    = $body['badge'];
                if (isset($body['btext']))     $item['btext']    = $body['btext'];
                if (isset($body['note']))      $item['note']     = $body['note'];
                if (isset($body['note_type'])) $item['noteType'] = $body['note_type'];
                $found = true;
                $updatedItem = $item;
                break 2;
            }
        }
    }

    if (!$found) jsonError('item tidak ditemukan', 404);
    if (!writeChecklist($jsonPath, $data, $lockFile)) jsonError('Gagal menyimpan file', 500);

    jsonOk(['item' => $updatedItem]);
}

// ─── DELETE → hapus item ───────────────────────────────────────────────────
// Body: { shift, item_id }
if ($method === 'DELETE') {
    csrfValidate();
    $body   = json_decode(file_get_contents('php://input'), true);
    $shift  = $body['shift']   ?? '';
    $itemId = $body['item_id'] ?? '';

    if (!in_array($shift, ['open','ops','close'], true)) jsonError('shift tidak valid', 400);
    if (!$itemId) jsonError('item_id wajib diisi', 400);

    $data  = readChecklist($jsonPath);
    $found = false;

    foreach ($data['checklist'][$shift] as &$section) {
        foreach ($section['items'] as $k => $item) {
            if ($item['id'] === $itemId) {
                array_splice($section['items'], $k, 1);
                $found = true;
                break 2;
            }
        }
    }

    if (!$found) jsonError('item tidak ditemukan', 404);
    if (!writeChecklist($jsonPath, $data, $lockFile)) jsonError('Gagal menyimpan file', 500);

    jsonOk(['deleted' => $itemId]);
}

jsonError('Method tidak didukung', 405);
