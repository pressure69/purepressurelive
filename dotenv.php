<?php
/**
 * Simple .env loader for PurePressureLive
 * ----------------------------------------
 * Reads key=value pairs from a .env file and loads them into
 * PHP's getenv(), $_ENV, and $_SERVER arrays.
 */

function loadEnv(string $path): void {
    if (!file_exists($path)) {
        error_log("❌ .env file not found at $path");
        die("Configuration error: .env missing.");
    }

    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

    foreach ($lines as $line) {
        // Skip comments
        if (str_starts_with(trim($line), '#')) {
            continue;
        }

        // Parse KEY=VALUE
        [$name, $value] = array_map('trim', explode('=', $line, 2));

        // Remove quotes if present
        $value = trim($value, " \t\n\r\0\x0B\"'");

        // Export into environment
        putenv("$name=$value");
        $_ENV[$name] = $value;
        $_SERVER[$name] = $value;
    }
}

/**
 * Ensure required env vars exist, else fail fast.
 */
function requireEnv(array $keys): void {
    foreach ($keys as $key) {
        $val = getenv($key);
        if ($val === false || $val === '') {
            error_log("❌ Missing required environment variable: $key");
            die("Configuration error: $key not set.");
        }
    }
}
