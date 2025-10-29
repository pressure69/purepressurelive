<?php

declare(strict_types=1);

/**
 * Bootstrap for PHPStan only — provide a harmless PDO stub so analysis
 * doesn't try to open a DB connection.
 * @var PDO $pdo
 */
if (!isset($pdo)) {
    $pdo = new class () extends PDO {
        public function __construct()
        {
        }
    };
}
