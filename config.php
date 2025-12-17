<?php
// Production-hardened config loader

// Load .env if present (optional)
if (file_exists(__DIR__ . '/.env')) {
    $lines = file(__DIR__ . '/.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) continue;
        [$k, $v] = array_map('trim', explode('=', $line, 2) + [1 => '']);
        if ($k !== '') putenv(sprintf('%s=%s', $k, $v));
    }
}

// Environment
$app_env = getenv('APP_ENV') ?: 'development';

// Error reporting: hide errors in production
if ($app_env === 'production') {
    ini_set('display_errors', '0');
    error_reporting(0);
} else {
    ini_set('display_errors', '1');
    error_reporting(E_ALL);
}

// Timezone (override via ENV if needed)
date_default_timezone_set(getenv('APP_TZ') ?: 'Asia/Kolkata');

// Database configuration via environment variables (fallbacks keep previous defaults)
define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
define('DB_USER', getenv('DB_USER') ?: 'root');
define('DB_PASS', getenv('DB_PASS') ?: '');
define('DB_NAME', getenv('DB_NAME') ?: 'parkingpro');

// Base URL (recommended to set in env for production)
define('BASE_URL', rtrim(getenv('BASE_URL') ?: 'http://localhost/parking%20website/', '/') . '/');

// SMS / third-party credentials
define('SMS_API_KEY', getenv('SMS_API_KEY') ?: '');
define('TWILIO_SID', getenv('TWILIO_SID') ?: '');
define('TWILIO_TOKEN', getenv('TWILIO_TOKEN') ?: '');
define('TWILIO_FROM', getenv('TWILIO_FROM') ?: '');

// Session hardening
ini_set('session.use_strict_mode', 1);
$secureCookie = ($app_env === 'production');
session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    'domain' => '',
    'secure' => $secureCookie,
    'httponly' => true,
    'samesite' => 'Lax'
]);
session_start();

// MySQLi setup: throw exceptions on errors and set charset
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
try {
    $mysqli = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    $mysqli->set_charset('utf8mb4');
} catch (Exception $e) {
    if ($app_env === 'production') {
        error_log('DB connection error: ' . $e->getMessage());
        // Generic message for users
        die('Database connection error.');
    }
    // In development, show full error
    die('Failed to connect to MySQL: ' . $e->getMessage());
}

// Global error and exception handlers to avoid uncaught fatal errors
set_error_handler(function ($severity, $message, $file, $line) use ($app_env) {
    // Convert PHP errors to exceptions so they can be handled uniformly
    throw new ErrorException($message, 0, $severity, $file, $line);
});

set_exception_handler(function ($e) use ($app_env) {
    $msg = sprintf("Uncaught exception: %s in %s on line %d\n", $e->getMessage(), $e->getFile(), $e->getLine());
    error_log($msg . "\n" . $e->getTraceAsString());
    if ($app_env === 'production') {
        // Generic user-friendly message
        http_response_code(500);
        echo 'An internal error occurred. Please try again later.';
    } else {
        // Detailed output for development
        echo nl2br(htmlspecialchars($msg . "\n" . $e->getTraceAsString()));
    }
    exit;
});

register_shutdown_function(function () use ($app_env) {
    $err = error_get_last();
    if ($err && in_array($err['type'], [E_ERROR, E_CORE_ERROR, E_COMPILE_ERROR, E_PARSE], true)) {
        $msg = sprintf("Fatal error: %s in %s on line %d", $err['message'], $err['file'], $err['line']);
        error_log($msg);
        if ($app_env === 'production') {
            http_response_code(500);
            echo 'An internal error occurred. Please try again later.';
        } else {
            echo htmlspecialchars($msg);
        }
    }
});

// End of config
?>
