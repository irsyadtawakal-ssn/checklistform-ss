<?php
declare(strict_types=1);

/**
 * Helper: hitung compliance status dari item checks.
 *
 * @param  array  $checklistData  Decoded checklist.json
 * @param  string $shift          open|ops|close
 * @param  array  $checks         [item_code => bool]
 * @return array  {status, pct, crit_missed, crit_total, done, total}
 */
function computeCompliance(array $checklistData, string $shift, array $checks): array
{
    $criticalIds = [];
    $totalItems  = 0;

    foreach ($checklistData['checklist'][$shift] as $section) {
        foreach ($section['items'] as $item) {
            $totalItems++;
            if (($item['badge'] ?? '') === 'ibadge-crit') {
                $criticalIds[] = $item['id'];
            }
        }
    }

    $done       = count(array_filter($checks));
    $critDone   = count(array_filter(array_intersect_key($checks, array_flip($criticalIds))));
    $critMissed = count($criticalIds) - $critDone;
    $pct        = $totalItems > 0 ? (int) round($done / $totalItems * 100) : 0;
    $status     = $critMissed > 0 ? 'danger' : ($done === $totalItems ? 'ok' : 'warn');

    return [
        'status'      => $status,
        'pct'         => $pct,
        'crit_missed' => $critMissed,
        'crit_total'  => count($criticalIds),
        'done'        => $done,
        'total'       => $totalItems,
    ];
}
