<?php
// Simple upload bridge for browser-to-RTMP using ffmpeg
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit("Method not allowed");
}

$streamKey = $_GET['key'] ?? 'default';
$tempFile = tempnam(sys_get_temp_dir(), 'stream_');

file_put_contents($tempFile, file_get_contents('php://input'));

$ffmpegCmd = "ffmpeg -re -i $tempFile -c:v libx264 -preset veryfast -tune zerolatency -c:a aac -ar 44100 -f flv rtmp://localhost/live/$streamKey";
exec($ffmpegCmd . " > /dev/null 2>&1 &");

echo "Started stream for key: $streamKey";
