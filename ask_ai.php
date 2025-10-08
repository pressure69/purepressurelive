<?php
// === AI Auto-Fix Handler ===
session_start();
require 'config.php'; // includes DB + AI helper

// --- Simple AI request function (edit with your API) ---
function ask_ai($prompt) {
    // Example stub: Replace with your AI call (OpenAI, local API, etc.)
    return "🤖 AI Suggestion for Prompt:\n\n" . $prompt;
}

// --- Auto-Fix Mode (Admin Only) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mode']) && $_POST['mode'] === 'autofix') {
    if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
        die("❌ Access denied: Admin only.");
    }

    // Collect error logs
    $logFile = __DIR__ . '/logs/frontend_errors.log';
    $errors = file_exists($logFile) ? file_get_contents($logFile) : "No recent errors found.";

    // Send to AI
    $prompt = "PurePressureLive Admin Auto-Fix request. Analyze these logs and generate corrected PHP/JS code snippets:\n\n" . $errors;
    $fix = ask_ai($prompt);

    // Display output
    echo "<html><head><title>AI Auto-Fix</title>
          <style>
            body { background:#000; color:#fff; font-family:monospace; padding:20px; }
            pre { background:#111; color:#0f0; padding:15px; border-radius:10px; overflow-x:auto; }
            a { color:#0ff; text-decoration:none; }
          </style></head><body>";

    echo "<h1>⚡ AI Auto-Fix Results</h1>";
    echo "<pre>" . htmlspecialchars($fix) . "</pre>";
    echo "<p><a href='admin_dashboard.php'>⬅ Back to Admin Dashboard</a></p>";

    echo "</body></html>";
    exit;
}

// --- Default fallback (non-autofix requests) ---
echo "⚠ Invalid request.";
