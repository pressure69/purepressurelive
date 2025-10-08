<?php
require 'config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? null;
    $amount = (int)($_POST['amount'] ?? 0);

    if (!$username || $amount <= 0) {
        http_response_code(400);
        echo json_encode(["error" => "Invalid request"]);
        exit();
    }

    $db = new PDO($dsn, $db_user, $db_pass);

    // Update token balance
    $db->prepare("UPDATE models SET tokens = tokens + ? WHERE username=?")
       ->execute([$amount, $username]);

    // Log tip
    $db->prepare("INSERT INTO tips (model, fan, amount, created_at) VALUES (?, ?, ?, NOW())")
       ->execute([$username, 'fan123', $amount]); // TODO: replace 'fan123' with real session fan

    echo json_encode(["success" => true, "amount" => $amount]);
}
