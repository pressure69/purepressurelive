<?php
// ===========================================
// PurePressureLive - Live Feed Page
// ===========================================
declare(strict_types=1);
session_start();
$pdo = new PDO('mysql:host=localhost;dbname=purepressurelive;charset=utf8mb4', 'root', '');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// --- Fetch live streams ---
$stmt = $pdo->query("SELECT username, display_name, title, started_at FROM streams WHERE is_live = 1 ORDER BY started_at DESC");
$streams = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>PurePressureLive | Live Feed</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/hls.js@latest"></script>
<style>
  body {
    margin: 0;
    padding: 0;
    background: #000;
    color: #fff;
    font-family: 'Inter', sans-serif;
    text-align: center;
  }
  header {
    background: #ff007f;
    padding: 15px;
    font-size: 22px;
    font-weight: bold;
    letter-spacing: 1px;
  }
  .card {
    display: inline-block;
    margin: 15px;
    background: #111;
    border-radius: 10px;
    width: 320px;
    overflow: hidden;
    box-shadow: 0 0 10px rgba(255,0,127,0.3);
  }
  .card video {
    width: 100%;
    border-bottom: 1px solid #222;
  }
  .card h3 {
    color: #ff007f;
    margin: 10px 0 5px;
  }
  .card p {
    color: #ccc;
    font-size: 14px;
    margin-bottom: 15px;
  }
  @media (max-width: 600px) {
    .card { width: 90%; }
  }
</style>
</head>
<body>
<header>🔥 Live Now on PurePressureLive</header>

<?php if (empty($streams)): ?>
  <p style="margin-top:40px; color:#aaa;">No one is live right now. Check back soon 💋</p>
<?php else: ?>
  <?php foreach ($streams as $s): 
    $u = htmlspecialchars($s['username']);
    $d = htmlspecialchars($s['display_name'] ?? $u);
    $t = htmlspecialchars($s['title'] ?? 'Live Stream');
  ?>
  <div class="card">
    <video id="player_<?php echo $u; ?>" controls autoplay muted playsinline></video>
    <h3><?php echo $d; ?></h3>
    <p><?php echo $t; ?></p>
    <script>
      const hls_<?php echo $u; ?> = new Hls();
      const url_<?php echo $u; ?> = `/hls/<?php echo $u; ?>.m3u8`;
      const video_<?php echo $u; ?> = document.getElementById('player_<?php echo $u; ?>');
      if (Hls.isSupported()) {
        hls_<?php echo $u; ?>.loadSource(url_<?php echo $u; ?>);
        hls_<?php echo $u; ?>.attachMedia(video_<?php echo $u; ?>);
      } else if (video_<?php echo $u; ?>.canPlayType('application/vnd.apple.mpegurl')) {
        video_<?php echo $u; ?>.src = url_<?php echo $u; ?>;
      }
    </script>
  </div>
  <?php endforeach; ?>
<?php endif; ?>

</body>
</html>
