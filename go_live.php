<?php
session_start();
require 'config.php';

if (!isset($_SESSION['username'])) {
    http_response_code(403);
    echo json_encode(["error" => "Not logged in"]);
    exit();
}

$username = $_SESSION['username'];

// Example: generate a random stream key for OBS / LiveKit
$streamKey = bin2hex(random_bytes(16));

// Save status in DB
$db = new PDO($dsn, $db_user, $db_pass);
$stmt = $db->prepare("UPDATE models SET stream_key=?, is_live=1 WHERE username=?");
$stmt->execute([$streamKey, $username]);

echo json_encode([
    "status" => "LIVE",
    "streamKey" => $streamKey
]);
