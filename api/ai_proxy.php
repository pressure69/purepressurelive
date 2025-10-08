<?php
// ai_proxy.php — Simple proxy to OpenAI; falls back gracefully if no key.
// ENV: putkey in /etc/environment  ->  OPENAI_API_KEY="sk-..."
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../config.php';

$apiKey = getenv('OPENAI_API_KEY') ?: '';
$body   = json_decode(file_get_contents('php://input'), true) ?: [];
$messages = $body['messages'] ?? [];
$purpose  = $body['purpose'] ?? 'general';

if (!$apiKey) {
    echo json_encode([
        'ok' => true,
        'source' => 'fallback',
        'message' => "AI offline (no OPENAI_API_KEY set). Using local sort/rules."
    ]);
    exit;
}

// Minimal guard
$messages = array_slice($messages, -20);

$payload = [
    "model" => "gpt-4o-mini",
    "messages" => array_merge([
        ["role"=>"system","content"=>
            "You are ModelBot for PurePressureLive. Style: bold, helpful, no fluff.
             Know: tokens/tips, private shows, filters, OBS/HLS, ID checks. Keep answers concise."]
    ], $messages),
    "temperature" => 0.4
];

$ch = curl_init("https://api.openai.com/v1/chat/completions");
curl_setopt_array($ch, [
    CURLOPT_HTTPHEADER => [
        "Authorization: Bearer $apiKey",
        "Content-Type: application/json"
    ],
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => json_encode($payload),
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT => 30
]);
$resp = curl_exec($ch);
$err  = curl_error($ch);
$code = curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
curl_close($ch);

if ($err || $code >= 400) {
    echo json_encode(['ok'=>false,'error'=>"AI error: $err ($code)"]);
    exit;
}
$j = json_decode($resp, true);
$text = $j['choices'][0]['message']['content'] ?? '';
echo json_encode(['ok'=>true,'source'=>'openai','text'=>$text]);
