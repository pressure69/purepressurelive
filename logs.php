<?php
/**
 * PurePressureLive Log Doctor
 * ---------------------------
 * Manage logs with Get, Clear, Download, and All-in-One options.
 */

require_once __DIR__ . '/dotenv.php';
loadEnv(__DIR__ . '/.env');

session_start();

$logPath = getenv('PPLIVE_LOG_PATH') ?: (__DIR__ . '/logs');
$phpLog      = $logPath . '/php_errors.log';
$appLog      = $logPath . '/app.log';
$frontendLog = $logPath . '/frontend_errors.log';

if (!is_dir($logPath)) mkdir($logPath, 0775, true);
foreach ([$phpLog, $appLog, $frontendLog] as $file) {
    if (!file_exists($file)) touch($file);
}

// === Handle AJAX + Download requests ===
if (isset($_GET['action'])) {
    $target = $_GET['action'];
    $map = ['php' => $phpLog, 'app' => $appLog, 'frontend' => $frontendLog];

    // Individual logs
    if (str_starts_with($target, 'get_')) {
        $key = str_replace('get_', '', $target);
        echo file_get_contents($map[$key]) ?: "⚠️ Log empty";
        exit;
    }

    if (str_starts_with($target, 'clear_')) {
        $key = str_replace('clear_', '', $target);
        file_put_contents($map[$key], "");
        echo "✅ " . strtoupper($key) . " log cleared";
        exit;
    }

    if (str_starts_with($target, 'download_')) {
        $key = str_replace('download_', '', $target);
        $file = $map[$key];
        header("Content-Type: text/plain");
        header("Content-Disposition: attachment; filename={$key}_log.txt");
        readfile($file);
        exit;
    }

    // === ALL logs ===
    if ($target === 'get_all') {
        foreach ($map as $name => $file) {
            echo strtoupper($name) . " LOG:\n";
            echo file_get_contents($file) ?: "⚠️ Empty";
            echo "\n-----------------------------\n\n";
        }
        exit;
    }

    if ($target === 'download_all') {
        $zipFile = sys_get_temp_dir() . "/logs_" . time() . ".zip";
        $zip = new ZipArchive();
        $zip->open($zipFile, ZipArchive::CREATE | ZipArchive::OVERWRITE);
        foreach ($map as $name => $file) {
            $zip->addFile($file, "{$name}_log.txt");
        }
        $zip->close();
        header("Content-Type: application/zip");
        header("Content-Disposition: attachment; filename=all_logs.zip");
        readfile($zipFile);
        unlink($zipFile);
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>PurePressureLive Log Doctor</title>
    <style>
        body { font-family: Arial, sans-serif; background: #111; color: #eee; padding: 20px; }
        h1 { color: #ff007f; }
        h2 { margin-top: 30px; }
        .controls { margin-bottom: 10px; }
        button, a.download {
            background: #ff007f;
            border: none;
            padding: 6px 12px;
            border-radius: 6px;
            color: #fff;
            cursor: pointer;
            margin-right: 6px;
            text-decoration: none;
        }
        button:hover, a.download:hover { background: #ff3399; }
        pre {
            background: #222;
            padding: 10px;
            border-radius: 6px;
            max-height: 400px;
            overflow-y: auto;
            white-space: pre-wrap;
        }
        .top-controls { margin-bottom: 20px; }
    </style>
    <script>
    async function fetchLog(action, outputId) {
        const res = await fetch("?action=" + action);
        const text = await res.text();
        document.getElementById(outputId).innerText = text;
    }
    async function fetchAllLogs() {
        const res = await fetch("?action=get_all");
        const text = await res.text();
        document.getElementById("allOut").innerText = text;
    }
    </script>
</head>
<body>
    <h1>🩺 PurePressureLive Log Doctor</h1>
    <p>View, clear, or download logs individually — or all at once.</p>

    <div class="top-controls">
        <button onclick="fetchAllLogs()">Get All Logs</button>
        <a href="?action=download_all" class="download">Download All Logs (ZIP)</a>
    </div>

    <h2>⚡ PHP Errors</h2>
    <div class="controls">
        <button onclick="fetchLog('get_php','phpOut')">Get Errors</button>
        <button onclick="fetchLog('clear_php','phpOut')">Clear Errors</button>
        <a href="?action=download_php" class="download">Download Log</a>
    </div>
    <pre id="phpOut">No data loaded yet.</pre>

    <h2>📓 App Log</h2>
    <div class="controls">
        <button onclick="fetchLog('get_app','appOut')">Get Errors</button>
        <button onclick="fetchLog('clear_app','appOut')">Clear Errors</button>
        <a href="?action=download_app" class="download">Download Log</a>
    </div>
    <pre id="appOut">No data loaded yet.</pre>

    <h2>🌐 Frontend Errors</h2>
    <div class="controls">
        <button onclick="fetchLog('get_frontend','frontOut')">Get Errors</button>
        <button onclick="fetchLog('clear_frontend','frontOut')">Clear Errors</button>
        <a href="?action=download_frontend" class="download">Download Log</a>
    </div>
    <pre id="frontOut">No data loaded yet.</pre>

    <h2>📦 All Logs Output</h2>
    <pre id="allOut">Click "Get All Logs" to load everything here.</pre>
</body>
</html>
