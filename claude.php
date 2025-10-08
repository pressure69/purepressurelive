<?php
// === Claude Helper for PurePressureLive ===
// Provides askClaude() for chat/AI replies
// and rewriteCode() for auto-rewriting site files.

function askClaude($prompt) {
    $apiKey = getenv("ANTHROPIC_API_KEY");
    if (!$apiKey) {
        return "❌ No ANTHROPIC_API_KEY found in environment.";
    }

    $ch = curl_init("https://api.anthropic.com/v1/messages");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Content-Type: application/json",
        "x-api-key: $apiKey",
        "anthropic-version: 2023-06-01"
    ]);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
        "model" => "claude-3-sonnet-20240229",
        "max_tokens" => 500,
        "messages" => [["role" => "user", "content" => $prompt]]
    ]));

    $response = curl_exec($ch);
    curl_close($ch);

    $decoded = json_decode($response, true);
    return $decoded['content'][0]['text'] ?? $response;
}

function rewriteCode($code, $instructions) {
    $apiKey = getenv("ANTHROPIC_API_KEY");
    if (!$apiKey) {
        return "❌ No ANTHROPIC_API_KEY found in environment.";
    }

    $ch = curl_init("https://api.anthropic.com/v1/messages");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Content-Type: application/json",
        "x-api-key: $apiKey",
        "anthropic-version: 2023-06-01"
    ]);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
        "model" => "claude-3-sonnet-20240229",
        "max_tokens" => 2000,
        "messages" => [[
            "role" => "user",
            "content" => "Rewrite the following code:\n\n" . $code . "\n\nInstructions: " . $instructions
        ]]
    ]));

    $response = curl_exec($ch);
    curl_close($ch);

    $decoded = json_decode($response, true);
    return $decoded['content'][0]['text'] ?? $response;
}
?>
