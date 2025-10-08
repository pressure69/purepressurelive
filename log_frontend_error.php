<?php
/**
 * Receives frontend errors and logs them with user/session info.
 */
require_once __DIR__ . '/dotenv.php';
loadEnv(__DIR__ . '/.env');

session_start();

$logPath = getenv('PPLIVE_LOG_PATH') ?: (__DIR__ . '/logs');
$frontendLog = $logPath . '/frontend_errors.log';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents("php://input"), true);
    if ($data) {
        // Identify user if logged in
        $userId   = $_SESSION['user_id'] ?? 'guest';
        $username = $_SESSION['username'] ?? 'guest';

        $entry = "[" . date('Y-m-d H:i:s') . "] " .
                 "User: {$username} (ID: {$userId}) | " .
                 ($data['message'] ?? 'Unknown error') .
                 " | Source: " . ($data['source'] ?? 'N/A') .
                 " | Line: " . ($data['line'] ?? '-') .
                 " | Col: " . ($data['column'] ?? '-') .
                 "\nStack: " . ($data['stack'] ?? 'N/A') .
                 "\n-----------------------------\n";

        file_put_contents($frontendLog, $entry, FILE_APPEND);
    }
}
http_response_code(204); // No content
