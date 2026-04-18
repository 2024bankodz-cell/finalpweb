<?php
// Load environment variables from .env file if it exists
if (file_exists(__DIR__ . '/../.env')) {
    $env_lines = file(__DIR__ . '/../.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($env_lines as $line) {
        if (strpos($line, '=') !== false && strpos($line, '#') !== 0) {
            list($key, $value) = explode('=', $line, 2);
            $_ENV[trim($key)] = trim($value);
        }
    }
}

// Database Configuration with environment variable fallbacks
define('DB_HOST', $_ENV['DB_HOST'] ?? $_SERVER['DB_HOST'] ?? 'localhost');
define('DB_NAME', $_ENV['DB_NAME'] ?? $_SERVER['DB_NAME'] ?? 'usthb_scolarite');
define('DB_USER', $_ENV['DB_USER'] ?? $_SERVER['DB_USER'] ?? 'root');
define('DB_PASS', $_ENV['DB_PASS'] ?? $_SERVER['DB_PASS'] ?? '1234');
define('APP_URL', $_ENV['APP_URL'] ?? $_SERVER['APP_URL'] ?? 'http://localhost/my_backend');

function get_app_base_path(): string {
    static $base = null;
    if ($base !== null) {
        return $base;
    }

    $documentRoot = realpath($_SERVER['DOCUMENT_ROOT'] ?? '') ?: null;
    $appRoot = realpath(__DIR__ . '/..');

    if ($documentRoot && $appRoot && str_starts_with($appRoot, $documentRoot)) {
        $base = substr($appRoot, strlen($documentRoot));
        $base = str_replace('\\', '/', $base);
        $base = rtrim($base, '/');
        return $base === '' ? '' : $base;
    }

    $script = $_SERVER['SCRIPT_NAME'] ?? '';
    $dir = str_replace('\\', '/', dirname($script));
    if ($dir === '/' || $dir === '\\' || $dir === '.') {
        return '';
    }
    return rtrim($dir, '/');
}

function app_url(string $path = ''): string {
    $path = ltrim($path, '/');
    $base = get_app_base_path();
    return $base === '' ? '/' . $path : $base . '/' . $path;
}

function url(string $path = ''): string {
    return app_url($path);
}

function get_pdo() {
    try {
        $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", DB_USER, DB_PASS);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        return $pdo;
    } catch (PDOException $e) {
        die("Connection failed: " . $e->getMessage());
    }
}

define('APP_NAME', 'USTHB Scolarité');
define('APP_SUB', 'Faculté d\'Informatique');
define('APP_YEAR', '2025/2026');
?>
