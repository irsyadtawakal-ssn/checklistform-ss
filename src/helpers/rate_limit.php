<?php
declare(strict_types=1);

/**
 * File-based rate limiter — tidak butuh Redis/APCu.
 * Cocok untuk cPanel shared hosting.
 *
 * Penggunaan:
 *   rateLimitCheck('login', clientIp(), 5, 900);
 *   // max 5 percobaan per 15 menit per IP
 */
function rateLimitCheck(string $action, string $key, int $maxAttempts, int $windowSeconds): void
{
    $dir = ROOT_PATH . '/tmp/rate_limit';
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }

    $file = $dir . '/' . $action . '_' . md5($key) . '.json';
    $now  = time();
    $data = ['attempts' => [], 'blocked_until' => 0];

    if (file_exists($file)) {
        $data = json_decode(file_get_contents($file), true) ?? $data;
    }

    // Cek apakah masih dalam periode blokir
    if ($data['blocked_until'] > $now) {
        $waitSecs = $data['blocked_until'] - $now;
        jsonError("Terlalu banyak percobaan. Coba lagi dalam {$waitSecs} detik.", 429);
    }

    // Buang attempt yang sudah lewat window
    $data['attempts'] = array_filter(
        $data['attempts'],
        fn(int $ts) => $ts > $now - $windowSeconds
    );

    $data['attempts'][] = $now;

    if (count($data['attempts']) > $maxAttempts) {
        $data['blocked_until'] = $now + $windowSeconds;
        $data['attempts']      = [];
        file_put_contents($file, json_encode($data), LOCK_EX);
        jsonError("Terlalu banyak percobaan. Akses diblokir selama {$windowSeconds} detik.", 429);
    }

    file_put_contents($file, json_encode($data), LOCK_EX);
}

function rateLimitReset(string $action, string $key): void
{
    $file = ROOT_PATH . '/tmp/rate_limit/' . $action . '_' . md5($key) . '.json';
    if (file_exists($file)) {
        unlink($file);
    }
}

function clientIp(): string
{
    $ip = $_SERVER['HTTP_CF_CONNECTING_IP']      // Cloudflare
        ?? $_SERVER['HTTP_X_FORWARDED_FOR']
        ?? $_SERVER['REMOTE_ADDR']
        ?? '0.0.0.0';

    // Ambil IP pertama jika ada chain
    return trim(explode(',', $ip)[0]);
}
