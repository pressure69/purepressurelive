<?php
// Shared configuration for PurePressureLive PHP endpoints.
// Creates a PDO connection in $pdo so dashboard and API scripts
// can query the database without duplicating boilerplate.

$dsn = getenv('PPLIVE_DB_DSN');
$dbUser = getenv('PPLIVE_DB_USER');
$dbPass = getenv('PPLIVE_DB_PASS');

if (!$dsn) {
    $dbHost = getenv('PPLIVE_DB_HOST') ?: 'localhost';
    $dbName = getenv('PPLIVE_DB_NAME') ?: 'purepressure';
    $dbCharset = getenv('PPLIVE_DB_CHARSET') ?: 'utf8mb4';
    $dsn = sprintf('mysql:host=%s;dbname=%s;charset=%s', $dbHost, $dbName, $dbCharset);
}

$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
];

/** @var PDO|null $pdo */
$pdo = null;
$pdoError = null;

try {
    $pdo = new PDO($dsn, $dbUser, $dbPass, $options);
} catch (Throwable $e) {
    $pdoError = $e;
    error_log('Database connection failed: ' . $e->getMessage());
}
