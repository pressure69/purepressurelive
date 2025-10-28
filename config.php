<?php

declare(strict_types=1);

if (session_status() !== PHP_SESSION_ACTIVE) {
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }
}

$dsn = 'mysql:host=localhost;dbname=purepressurelive;charset=utf8mb4';
$user = 'root';
$pass = 'SITE89'; // <- Replace this with your real MySQL root password

try {
    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
} catch (PDOException $e) {
    die('Database connection failed: ' . htmlspecialchars($e->getMessage()));
}
