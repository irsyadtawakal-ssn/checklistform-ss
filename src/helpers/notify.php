<?php
declare(strict_types=1);

function sendWhatsApp(string $target, string $message): void
{
    $token = defined('WA_TOKEN') ? WA_TOKEN : (getenv('WA_TOKEN') ?: '');
    if (!$token || !$target) return;

    $ch = curl_init('https://api.fonnte.com/send');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_TIMEOUT        => 8,
        CURLOPT_HTTPHEADER     => ['Authorization: ' . $token],
        CURLOPT_POSTFIELDS     => http_build_query([
            'target'  => $target,
            'message' => $message,
        ]),
    ]);
    curl_exec($ch);
    curl_close($ch);
}
