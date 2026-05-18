<?php
// Database connection loader
// Provides $conn (MySQLi connection)

// Load Composer autoload + Dotenv if available
$autoloadPath = __DIR__ . '/../vendor/autoload.php';

if (file_exists($autoloadPath)) {
    require_once $autoloadPath;

    if (class_exists('Dotenv\\Dotenv')) {
        $dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
        $dotenv->safeLoad();
    }
}

/**
 * Safe env getter
 */
function env($key, $default = null) {
    $value = getenv($key);
    return ($value === false || $value === null || $value === '') ? $default : $value;
}

/*
|--------------------------------------------------------------------------
| DATABASE CONFIG (XAMPP SAFE DEFAULTS)
|--------------------------------------------------------------------------
*/

// FIX: use localhost instead of "casestudy"
$host     = env('DB_HOST', 'localhost');

$database = env('DB_NAME', 'Study_Pulse');
$username = env('DB_USER', 'root');
$password = env('DB_PASSWORD', '');

// Port (safe range check)
$port = (int) env('DB_PORT', 3306);

if ($port < 3306 || $port > 3310) {
    $port = 3306;
}

/*
|--------------------------------------------------------------------------
| CREATE CONNECTION
|--------------------------------------------------------------------------
*/
$conn = new mysqli($host, $username, $password, $database, $port);

// Check connection
if ($conn->connect_error) {
    error_log("DB Connection failed: " . $conn->connect_error);
    die("Database connection failed. Please check your configuration.");
}

// Set charset
$conn->set_charset("utf8mb4");