<?php

declare(strict_types=1);

define('APP_ROOT', dirname(__DIR__, 2));
define('APP_PATH', APP_ROOT . '/app');
define('VIEW_PATH', APP_PATH . '/views');
define('PUBLIC_PATH', APP_ROOT . '/public');

// Tự động nạp file .env nếu tồn tại
$envFile = APP_ROOT . '/.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || strpos($line, '#') === 0) {
            continue;
        }
        if (strpos($line, '=') !== false) {
            list($name, $value) = explode('=', $line, 2);
            $name = trim($name);
            $value = trim($value);
            // Loại bỏ dấu nháy kép hoặc nháy đơn bao quanh giá trị nếu có
            if (preg_match('/^["\'](.*)["\']$/', $value, $matches)) {
                $value = $matches[1];
            }
            if (!array_key_exists($name, $_ENV)) {
                $_ENV[$name] = $value;
                putenv("$name=$value");
            }
        }
    }
}

// Hàm bổ trợ lấy biến môi trường
if (!function_exists('env')) {
    function env(string $key, $default = null) {
        $val = getenv($key);
        if ($val === false) {
            $val = $_ENV[$key] ?? null;
        }
        return $val !== null && $val !== '' ? $val : $default;
    }
}

define('DB_HOST', env('DB_HOST', '127.0.0.1'));
define('DB_NAME', env('DB_NAME', 'campus_services_booking'));
define('DB_USER', env('DB_USER', 'root'));
define('DB_PASS', env('DB_PASS', ''));
define('DB_CHARSET', env('DB_CHARSET', 'utf8mb4'));

define('APP_NAME', env('APP_NAME', 'Campus Services Booking System'));
define('APP_URL', env('APP_URL', ''));
define('SESSION_NAME', 'csbs_session');

date_default_timezone_set('Asia/Ho_Chi_Minh');

error_reporting(E_ALL);
ini_set('display_errors', '0');
ini_set('log_errors', '1');

if (session_status() === PHP_SESSION_NONE) {
    session_name(SESSION_NAME);
    session_start();
}

spl_autoload_register(function (string $class): void {
    $paths = [
        APP_PATH . '/core/' . $class . '.php',
        APP_PATH . '/controllers/' . $class . '.php',
        APP_PATH . '/models/' . $class . '.php',
        APP_PATH . '/repositories/' . $class . '.php',
        APP_PATH . '/services/' . $class . '.php',
    ];
    foreach ($paths as $path) {
        if (file_exists($path)) {
            require_once $path;
            return;
        }
    }
});

require_once APP_PATH . '/helpers/functions.php';
