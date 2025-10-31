<?php

declare(strict_types=1);

/** Shared PDO helper (memoized). Prefer calling db() over globals. */
function db(): \PDO
{
    static $pdo = null;
    if ($pdo instanceof \PDO) {
        return $pdo;
    }
    $dsn = getenv('DB_DSN') ?: sprintf(
        'mysql:host=%s;dbname=%s;charset=utf8mb4',
        getenv('DB_HOST') ?: 'localhost',
        getenv('DB_NAME') ?: 'purepressurelive'
    );
    $user = getenv('DB_USER') ?: 'root';
    $pass = getenv('DB_PASS') ?: '';
    $options = [
        \PDO::ATTR_ERRMODE            => \PDO::ERRMODE_EXCEPTION,
        \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
        \PDO::ATTR_EMULATE_PREPARES   => false,
    ];
    try {
        $pdo = new \PDO($dsn, $user, $pass, $options);
    } catch (\PDOException $e) {
        if (\PHP_SAPI === 'cli') {
            fwrite(STDERR, 'DB connection failed: ' . $e->getMessage() . PHP_EOL);
        }
        throw $e;
    }
    return $pdo;
}
