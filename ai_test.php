<?php

declare(strict_types=1);

ini_set('display_errors', '1');
error_reporting(E_ALL);

header('Content-Type: text/html; charset=UTF-8');

function call_ai(string $prompt, string $model = 'MiniMaxAI/MiniMax-M2', string $endpoint = 'http://127.0.0.1:8000/v1/chat/completions'): array
{
    $payload = [
        'model'       => $model,
        'messages'    => [
            ['role' => 'system', 'content' => 'You are a helpful assistant. Keep replies brief.'],
            ['role' => 'user',   'content' => $prompt],
        ],
        'temperature' => 0.3,
    ];

    $ch = curl_init($endpoint);
    curl_setopt_array($ch, [
        CURLOPT_POST           => true,
        CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
        CURLOPT_POSTFIELDS     => json_encode($payload, JSON_UNESCAPED_SLASHES),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 30,
    ]);

    $raw = curl_exec($ch);
    if ($raw === false) {
        $err = curl_error($ch);
        curl_close($ch);
        return ['ok' => false, 'error' => "cURL error: $err"];
    }

    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    $data = json_decode($raw, true);
    if ($code >= 400) {
        return ['ok' => false, 'error' => "HTTP $code from endpoint", 'raw' => $raw, 'data' => $data];
    }

    $content = $data['choices'][0]['message']['content'] ?? ($data['message']['content'] ?? null);
    return ['ok' => true, 'content' => $content, 'data' => $data];
}

$prompt = $_POST['prompt'] ?? ($_GET['prompt'] ?? 'Say hello to PurePressureLive!');
$model  = $_POST['model']  ?? ($_GET['model']  ?? 'MiniMaxAI/MiniMax-M2');
$res    = call_ai($prompt, $model);
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>AI Test • OpenAI-compatible</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<style>
body{font-family:system-ui,-apple-system,Segoe UI,Roboto,Ubuntu,sans-serif;max-width:900px;margin:24px auto;padding:0 16px;background:#0b0b0b;color:#eaeaea}
textarea,input,select{width:100%;padding:10px;margin:6px 0;background:#161616;color:#eaeaea;border:1px solid #333;border-radius:8px}
button{padding:10px 14px;border:0;border-radius:8px;background:#d1123f;color:#fff;font-weight:600;cursor:pointer}
pre{background:#111;border:1px solid #333;padding:12px;border-radius:8px;white-space:pre-wrap;word-break:break-word}
small{color:#9a9a9a}
</style>
</head>
<body>
<h1>AI Test (OpenAI-compatible)</h1>

<form method="post">
  <label>Model
    <input name="model" value="<?=htmlspecialchars($model, ENT_QUOTES)?>">
  </label>
  <label>Prompt
    <textarea name="prompt" rows="4"><?=htmlspecialchars($prompt, ENT_QUOTES)?></textarea>
  </label>
  <button type="submit">Send</button>
</form>

<h2>Reply</h2>
<pre><?= isset($res['content']) && $res['content'] !== null ? htmlspecialchars($res['content'], ENT_QUOTES) : '— no content —' ?></pre>

<h3>Debug (raw JSON)</h3>
<pre><?= htmlspecialchars(json_encode($res['data'] ?? null, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES), ENT_QUOTES) ?></pre>
<small>Endpoint: http://127.0.0.1:8000/v1/chat/completions</small>
</body>
</html>
