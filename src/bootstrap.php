<?php
declare(strict_types=1);

// Matikan display_errors supaya PHP tidak inject teks error ke response JSON
ini_set('display_errors', '0');
ini_set('display_startup_errors', '0');
error_reporting(E_ALL);

define('ROOT_PATH', dirname(__DIR__));

require_once ROOT_PATH . '/src/helpers/env.php';
loadEnv(ROOT_PATH . '/.env');

date_default_timezone_set(env('APP_TIMEZONE', 'Asia/Jakarta'));

define('APP_ENV',          env('APP_ENV',         'production'));
define('APP_URL',          env('APP_URL',          ''));
define('APP_SECRET',       env('APP_SECRET',       ''));
define('SESSION_LIFETIME', (int) env('SESSION_LIFETIME', 43200));
define('SESSION_NAME',     env('SESSION_NAME',     'ss_ops_session'));
define('UPLOAD_MAX_SIZE',  (int) env('UPLOAD_MAX_SIZE',  10485760));
define('UPLOAD_PATH',      env('UPLOAD_PATH',      'uploads/spv'));
define('WA_TOKEN',         env('WA_TOKEN',         ''));
define('WA_SPV_NUMBER',    env('WA_SPV_NUMBER',    ''));
define('MAIL_FROM',        env('MAIL_FROM',        ''));
define('MAIL_DIGEST_TO',   env('MAIL_DIGEST_TO',   ''));

// ─── Log ke file app.log ──────────────────────────────────────────────────
$_logDir = ROOT_PATH . '/logs';
if (!is_dir($_logDir)) @mkdir($_logDir, 0750, true);
ini_set('error_log', $_logDir . '/app.log');
unset($_logDir);

// Centralized error handler (production: log only, dev: display)
set_error_handler(function (int $errno, string $errstr, string $errfile, int $errline): bool {
    if (!(error_reporting() & $errno)) {
        return false;
    }
    $msg = "[{$errno}] {$errstr} in {$errfile}:{$errline}";
    error_log($msg);
    if (APP_ENV === 'development') {
        throw new ErrorException($errstr, 0, $errno, $errfile, $errline);
    }
    return true;
});

set_exception_handler(function (Throwable $e): void {
    error_log($e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());
    if (!headers_sent()) {
        http_response_code(500);
        header('Content-Type: application/json; charset=UTF-8');
    }
    $body = ['ok' => false, 'message' => 'Terjadi kesalahan server.'];
    if (APP_ENV === 'development') {
        $body['debug'] = $e->getMessage();
    }
    echo json_encode($body, JSON_UNESCAPED_UNICODE);
    exit;
});
