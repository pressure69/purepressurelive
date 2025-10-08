<?php
/**
 * PurePressureLive - Global Configuration
 * ----------------------------------------
 * Handles database connection, session setup, and error logging.
 */

// === Error Reporting ===
error_reporting(E_ALL);
ini_set('display_errors', 0); // Don't leak errors to users
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/logs/php_errors.log');

// === Session Management ===
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// === Environment Variables ===
// These should come from your droplet’s environment, preserved by `clear_env = no`
$db_dsn  = getenv('PPLIVE_DB_DSN');
$db_user = getenv('PPLIVE_DB_USER');
$db_pass = getenv('PPLIVE_DB_PASS');

// Sanity check
if (!$db_dsn || !$db_user) {
    error_log("❌ Missing required database environment variables.");
    die("Configuration error. Please contact site admin.");
}

// === Database Connection ===
try {
    $pdo = new PDO($db_dsn, $db_user, $db_pass, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ]);
} catch (PDOException $e) {
    error_log("❌ DB Connection failed: " . $e->getMessage());
    die("Database connection error.");
}

// === Global Helper Functions ===

/**
 * Require user to be logged in.
 */
function require_login(): void {
    if (empty($_SESSION['user_id'])) {
        header("Location: /login.php");
        exit;
    }
}

/**
 * Simple debug logger.
 */
function pplog(string $message): void {
    $logFile = __DIR__ . '/logs/app.log';
    $line = "[" . date('Y-m-d H:i:s') . "] " . $message . PHP_EOL;
    file_put_contents($logFile, $line, FILE_APPEND);
}
