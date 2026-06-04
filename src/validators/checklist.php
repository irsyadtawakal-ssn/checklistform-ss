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
