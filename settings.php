<?php
session_start();
require 'config.php';

if (!isset($_SESSION['username'])) {
    http_response_code(403);
    echo json_encode(["error" => "Not logged in"]);
    exit();
}

$username = $_SESSION['username'];
$mode = $_POST['mode'] ?? 'public';
$price = (int)($_POST['price'] ?? 0);

$db = new PDO($dsn, $db_user, $db_pass);
$stmt = $db->prepare("UPDATE models SET room_mode=?, private_price=? WHERE username=?");
$stmt->execute([$mode, $price, $username]);

echo json_encode(["success" => true, "mode" => $mode, "price" => $price]);
