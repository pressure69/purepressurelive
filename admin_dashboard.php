<?php
session_start();

// Admin-only gate
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Admin Dashboard - PurePressureLive</title>
  <style>
    body { background:#000; color:#fff; font-family:Arial, sans-serif; margin:0; padding:0; }
    header { background:#111; padding:20px; text-align:center; font-size:22px; font-weight:bold; }
    .container { padding:20px; }
    .card {
      margin-top:20px;
      padding:15px;
      background:#111;
      color:#fff;
      border-radius:10px;
      box-shadow:0 0 10px #222;
    }
    .btn-danger {
      background:#c00;
      color:#fff;
      padding:10px 20px;
      border:none;
      border-radius:5px;
      cursor:pointer;
      font-size:16px;
    }
    .btn-danger:hover { background:#f00; }
    a { color:#0ff; text-decoration:none; }
  </style>
</head>
<body>
  <header>PurePressureLive - Admin Dashboard</header>
  <div class="container">

    <div class="card">
      <h2>⚡ AI Auto-Fix System</h2>
      <p>Collects recent error logs and asks AI for repair suggestions. <br>
         <strong>Admin only.</strong></p>
      <form action="ask_ai.php" method="post">
        <input type="hidden" name="mode" value="autofix">
        <button type="submit" class="btn-danger">⚡ Run Auto-Fix</button>
      </form>
    </div>

    <div class="card">
      <h2>🔍 System Status</h2>
      <p>You can add server stats, PHP/MySQL uptime, or token economy here.</p>
      <a href="feed.php">⬅ Back to Site</a>
    </div>

  </div>
</body>
</html>
