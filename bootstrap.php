<?php
// === Frontend Error Handling ===
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/logs/frontend_errors.log');

// Don’t leak details to users
ini_set('display_errors', 0);
error_reporting(E_ALL);

// Catch fatal errors (shutdown)
register_shutdown_function(function() {
    $error = error_get_last();
    if ($error !== null) {
        error_log("[FATAL] {$error['message']} in {$error['file']} on line {$error['line']}");
        http_response_code(500);
        echo "<h1>⚠️ PurePressureLive: Something went wrong</h1>";
    }
});

// Catch uncaught exceptions
set_exception_handler(function($e) {
    error_log("[EXCEPTION] " . $e->getMessage() . " in " . $e->getFile() . ":" . $e->getLine());
    http_response_code(500);
    echo "<h1>⚠️ PurePressureLive: Unexpected error</h1>";
});
?>
