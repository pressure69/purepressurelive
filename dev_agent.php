<?php
session_start();
require_once __DIR__ . "/claude.php";

// === Security ===
// Replace with your own password or better yet hook into your admin login.
$ADMIN_PASS = "SITE89";
if (!isset($_SESSION['admin_logged_in'])) {
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && $_POST['password'] === $ADMIN_PASS) {
        $_SESSION['admin_logged_in'] = true;
    } else {
        echo '<form method="POST" style="margin:2em;font-family:sans-serif;">
                <h2>Developer Agent Login</h2>
                <input type="password" name="password" placeholder="Admin Password" required>
                <button type="submit">Login</button>
              </form>';
        exit;
    }
}

// === Handle submission ===
$result = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['code'], $_POST['instructions'])) {
    $code = $_POST['code'];
    $instructions = $_POST['instructions'];
    $result = rewriteCode($code, $instructions);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>PurePressure Dev Agent</title>
  <style>
    body { font-family: Arial, sans-serif; margin: 2em; background: #111; color: #eee; }
    h1 { color: #f43; }
    textarea { width: 100%; height: 200px; margin: 0.5em 0; font-family: monospace; }
    .output { white-space: pre-wrap; background: #222; padding: 1em; margin-top: 1em; border: 1px solid #333; }
    input, button { padding: 0.5em 1em; }
  </style>
</head>
<body>
  <h1>PurePressure Dev Agent 🛠️</h1>
  <form method="POST">
    <label>Paste Code:</label><br>
    <textarea name="code" placeholder="Paste your PHP/JS/HTML code here..."><?php echo htmlspecialchars($_POST['code'] ?? ''); ?></textarea><br>
    <label>Instructions:</label><br>
    <textarea name="instructions" placeholder="Tell Claude what to do (e.g. Fix login bug, add token balance)"><?php echo htmlspecialchars($_POST['instructions'] ?? ''); ?></textarea><br>
    <button type="submit">Rewrite with Claude</button>
  </form>

  <?php if ($result): ?>
    <h2>Claude’s Rewrite:</h2>
    <div class="output"><?php echo htmlspecialchars($result); ?></div>
  <?php endif; ?>
</body>
</html>
