<?php

declare(strict_types=1);

session_start();
?>
<!doctype html><html lang="en"><head>
<meta charset="utf-8"><title>PurePressureLive</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<link rel="preload" href="/style.css" as="style">
<link rel="stylesheet" href="/style.css">
</head><body>
<header class="topbar">
  <div class="brand">PurePressure<span>Live</span></div>
  <nav class="nav">
    <a href="/login.php">Login</a>
    <a href="/register.php">Register</a>
    <a href="/public_chat.php">Public Chat</a>
    <a href="/ai_test.php?prompt=hello">AI Tester</a>
  </nav>
</header>
<main class="wrap">
  <h1>Welcome to <span class="accent">PurePressureLive</span></h1>
  <p class="tagline">Seductive live experiences — built for models and fans.</p>
  <section class="cards">
    <a class="card" href="/model_dashboard.php"><h3>Model Dashboard</h3><p>Go Live, set token goals, manage previews.</p></a>
    <a class="card" href="/feed.php"><h3>Fan Feed</h3><p>Scroll the hottest streams and clips.</p></a>
    <a class="card" href="/inbox.php"><h3>Messages</h3><p>Public chat, private DMs, and requests.</p></a>
  </section>
  <section class="syscheck">
    <h2>Quick Check</h2>
    <ul>
      <li>PHP: <strong><?= htmlspecialchars(PHP_VERSION) ?></strong></li>
      <li>Doc root: <code><?= htmlspecialchars($_SERVER['DOCUMENT_ROOT'] ?? '') ?></code></li>
      <li>ai_test.php:
        <?php if (is_file(__DIR__ . '/ai_test.php')) : ?>
          <strong>found</strong> — <a href="/ai_test.php?prompt=hello">open</a>
        <?php else :
            ?><strong>missing</strong><?php
        endif; ?>
      </li>
    </ul>
  </section>
</main>
<footer class="foot"><small>© <?= date('Y') ?> PurePressureLive</small></footer>
</body></html>
