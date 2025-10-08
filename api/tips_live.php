<?php
// api/tips_live.php — returns recent tips for a given model
session_start();
require_once __DIR__ . '/../config.php';

$model_id = $_SESSION['model_id'] ?? null;
if(!$model_id){ http_response_code(403); echo json_encode(['error'=>'not logged in']); exit; }

header('Content-Type: application/json');

$stmt = $pdo->prepare("SELECT user,amount,message,created_at 
                       FROM tips 
                       WHERE model_id=? AND created_at >= NOW() - INTERVAL 30 SECOND 
                       ORDER BY id DESC");
$stmt->execute([$model_id]);
echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
