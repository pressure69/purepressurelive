<?php
session_start();
require_once __DIR__ . '/config.php';

if (!($pdo instanceof PDO)) {
    http_response_code(503);
    ?><!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Dashboard temporarily unavailable</title>
  <style>
    body {
      margin: 0;
      font-family: Arial, sans-serif;
      background: #0d0d0d;
      color: #fff;
      display: flex;
      align-items: center;
      justify-content: center;
      min-height: 100vh;
      padding: 24px;
      text-align: center;
    }
    .card {
      max-width: 480px;
      background: #1a1a1a;
      padding: 32px;
      border-radius: 16px;
      border: 1px solid rgba(255, 255, 255, 0.08);
    }
    a {
      color: #ff1493;
      text-decoration: none;
    }
  </style>
</head>
<body>
  <div class="card">
    <h1>We’ll be right back</h1>
    <p>We’re currently experiencing a database issue, so the dashboard is temporarily unavailable. Our team has been notified and we’re working on restoring access.</p>
    <p><a href="/login.php">Return to login</a></p>
  </div>
</body>
</html>
<?php
    exit;
}

// Verify model is logged in
if (!isset($_SESSION['model_id'])) {
    header('Location: /login.php');
    exit;
}

$model_id = $_SESSION['model_id'];

// Fetch model info
try {
    $stmt = $pdo->prepare("SELECT username, display_name, preview_image, is_live, stream_key, token_goal FROM models WHERE id = ?");
    $stmt->execute([$model_id]);
    $model = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log('Failed to load model dashboard: ' . $e->getMessage());
    $model = false;
}

if (!$model) {
    echo "Model not found.";
    exit;
}

$displayName = $model['display_name'] ?? $model['username'] ?? 'Model';
$tokenGoal = isset($model['token_goal']) ? (int) $model['token_goal'] : 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Model Dashboard - PurePressureLive</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <style>
    body {
      margin:0; padding:0;
      background:#0d0d0d; color:#fff;
      font-family: Arial, sans-serif;
    }
    .container {
      max-width: 900px; margin:0 auto; padding:20px;
    }
    h1 { color:#ff1493; text-align:center; }
    .section { margin:20px 0; padding:15px; background:#1a1a1a; border-radius:8px; }
    .goal-bar {
      background:#333; border-radius:8px; overflow:hidden; margin:10px 0;
    }
    .goal-fill {
      background:#ff1493; height:20px; width:0%;
      transition: width 0.5s;
    }
    ul { list-style:none; padding:0; }
    li { padding:5px 0; border-bottom:1px solid #333; }
    .share-buttons { text-align:center; margin-top:20px; }
    .share-buttons a {
      display:inline-block; padding:10px 15px; margin:5px;
      border-radius:6px; color:white; text-decoration:none;
    }
    .twitter { background:#1DA1F2; }
    .facebook { background:#3b5998; }
  </style>
</head>
<body>
  <div class="container">
    <h1>Welcome, <?php echo htmlspecialchars($displayName); ?></h1>

    <div class="section">
      <h2>Stream Key</h2>
      <p><?php echo htmlspecialchars($model['stream_key']); ?></p>
    </div>

    <div class="section">
      <h2>Token Goal</h2>
      <div class="goal-bar"><div class="goal-fill" id="goalFill"></div></div>
      <p id="goalText">0 / <?php echo $tokenGoal; ?> tokens</p>
    </div>

    <div class="section">
      <h2>Recent Tips</h2>
      <ul id="tipsList"><li>Loading...</li></ul>
    </div>

    <div class="section">
      <h2>Top Fans</h2>
      <ul id="fansList"><li>Loading...</li></ul>
    </div>

    <div class="share-buttons">
      <a class="twitter" target="_blank"
         href="https://twitter.com/intent/tweet?text=🔥 I'm live on PurePressureLive! Come watch me now! https://purepressurelive.com/feed.php">
         Share on Twitter
      </a>
      <a class="facebook" target="_blank"
         href="https://www.facebook.com/sharer/sharer.php?u=https://purepressurelive.com/feed.php">
         Share on Facebook
      </a>
    </div>
  </div>

  <script>
    async function loadDashboard() {
      try {
        const res = await fetch("api/get_dashboard_data.php");
        const data = await res.json();

        if (!data.success) return;

        // Update tips
        const tipsList = document.getElementById("tipsList");
        tipsList.innerHTML = "";
        data.tips.forEach(t => {
          let li = document.createElement("li");
          li.textContent = t.username + " tipped " + t.amount + " tokens";
          tipsList.appendChild(li);
        });

        // Update leaderboard
        const fansList = document.getElementById("fansList");
        fansList.innerHTML = "";
        data.fans.forEach(f => {
          let li = document.createElement("li");
          li.textContent = f.username + " — " + f.total + " tokens";
          fansList.appendChild(li);
        });

        // Update goal
        let percent = data.goal > 0 ? Math.min((data.total / data.goal) * 100, 100) : 0;
        document.getElementById("goalFill").style.width = percent + "%";
        document.getElementById("goalText").textContent = data.total + " / " + data.goal + " tokens";
      } catch (err) {
        console.error(err);
      }
    }

    setInterval(loadDashboard, 5000); // auto-refresh
    loadDashboard();
  </script>
</body>
</html>
