<?php

declare(strict_types=1);

session_start();
require_once __DIR__ . '/config.php';

/*
 |--------------------------------------------------------------
 | PurePressureLive — upload_stream.php
 |--------------------------------------------------------------
 | Receives WebM video chunks from the browser (MediaRecorder)
 | and forwards them to the local RTMP server via ffmpeg.
 | Works only for logged-in models.
*/

header('Content-Type: text/plain');

if (($_SESSION['role'] ?? '') !== 'model') {
    http_response_code(403);
    exit('❌ Unauthorized');
}

$modelId = (int) $_SESSION['user_id'];

try {
    $stmt = $pdo->prepare('SELECT stream_key FROM models WHERE id = :id LIMIT 1');
    $stmt->execute(['id' => $modelId]);
    $streamKey = $stmt->fetchColumn();
} catch (Throwable $t) {
    error_log('Stream key lookup failed: ' . $t->getMessage());
    http_response_code(500);
    exit('Database error.');
}

if (empty($streamKey)) {
    http_response_code(400);
    exit('Missing stream key.');
}

// --- Save temporary chunk ---
$tmpFile = tempnam(sys_get_temp_dir(), 'pplive_') . '.webm';
$raw = file_get_contents('php://input');
if (!$raw || strlen($raw) < 50) {
    http_response_code(400);
    exit('Empty chunk.');
}
file_put_contents($tmpFile, $raw);

// --- Send chunk to RTMP asynchronously ---
$cmd = sprintf(
    'ffmpeg -hide_banner -loglevel error -re -i %s -c:v libx264 -preset veryfast -c:a aac -f flv rtmp://127.0.0.1/live/%s > /dev/null 2>&1 &',
    escapeshellarg($tmpFile),
    escapeshellarg($streamKey)
);
shell_exec($cmd);

// optional cleanup (let ffmpeg read first)
register_shutdown_function(fn () => @unlink($tmpFile));

http_response_code(200);
echo 'Streaming chunk sent ✅';
