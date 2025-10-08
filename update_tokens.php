<?php
session_start();
if (!isset($_SESSION['username'])) { 
    echo json_encode(["success"=>false, "error"=>"Not logged in"]); 
    exit(); 
}

$username = $_SESSION['username'];
$amount = intval($_POST['amount'] ?? 0);

if ($amount <= 0) { 
    echo json_encode(["success"=>false, "error"=>"Invalid amount"]); 
    exit(); 
}

require_once __DIR__."/config.php";
try {
    $pdo = new PDO(getenv("PPLIVE_DB_DSN"), getenv("PPLIVE_DB_USER"), getenv("PPLIVE_DB_PASS"));
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Add tokens to current model’s goal
    $stmt = $pdo->prepare("UPDATE token_goals SET current_amount = current_amount + ? WHERE username = ?");
    $stmt->execute([$amount, $username]);

    echo json_encode(["success"=>true, "added"=>$amount]);
} catch (Exception $e) {
    echo json_encode(["success"=>false, "error"=>$e->getMessage()]);
}
