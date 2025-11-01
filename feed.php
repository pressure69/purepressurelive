<?php
declare(strict_types=1);

require_once __DIR__ . '/config.php';

/** @var \PDO $pdo */
try {
    $streamQuery = $pdo->prepare(
        'SELECT username, display_name, title, started_at
         FROM streams
         WHERE is_live = 1
         ORDER BY started_at DESC'
    );
    $streamQuery->execute();
    $streams = $streamQuery->fetchAll(\PDO::FETCH_ASSOC);
} catch (\PDOException $exception) {
    error_log('Feed page failed to load streams: ' . $exception->getMessage());
    $streams = [];
}

function ppl_escape(?string $value, string $default = ''): string
{
    $value = $value ?? $default;
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

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
    :root {
      color-scheme: dark;
      --ppl-pink: #ff0f7b;
      --ppl-purple: #7a3cff;
      --ppl-bg: #040404;
      --ppl-card: rgba(14, 14, 14, 0.94);
      --ppl-muted: #a0a0a0;
    }

    * {
      box-sizing: border-box;
    }

    body {
      margin: 0;
      padding: 0;
      min-height: 100vh;
      background: radial-gradient(circle at top left, rgba(255, 15, 123, 0.14), transparent 55%),
                  radial-gradient(circle at bottom right, rgba(122, 60, 255, 0.18), transparent 60%),
                  var(--ppl-bg);
      color: #f5f5f5;
      font-family: 'Inter', system-ui, -apple-system, BlinkMacSystemFont, sans-serif;
    }

    header {
      position: sticky;
      top: 0;
      z-index: 10;
      backdrop-filter: blur(18px);
      background: linear-gradient(120deg, rgba(255, 15, 123, 0.9), rgba(122, 60, 255, 0.9));
      padding: 18px clamp(18px, 4vw, 36px);
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 16px;
      box-shadow: 0 18px 45px rgba(0, 0, 0, 0.35);
    }

    header h1 {
      margin: 0;
      font-size: clamp(1.35rem, 2vw, 1.75rem);
      letter-spacing: 0.08em;
      text-transform: uppercase;
    }

    header .cta {
      display: inline-flex;
      align-items: center;
      gap: 8px;
      font-size: 0.95rem;
      font-weight: 600;
      text-decoration: none;
      color: #040404;
      background: #fff;
      padding: 10px 18px;
      border-radius: 999px;
      box-shadow: 0 12px 28px rgba(4, 4, 4, 0.28);
      transition: transform 0.2s ease, box-shadow 0.2s ease;
    }

    header .cta:hover,
    header .cta:focus-visible {
      transform: translateY(-1px);
      box-shadow: 0 18px 36px rgba(4, 4, 4, 0.35);
    }

    main {
      padding: clamp(20px, 5vw, 48px);
      display: grid;
      gap: 24px;
    }

    .grid {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(290px, 1fr));
      gap: clamp(18px, 4vw, 28px);
    }

    .card {
      position: relative;
      background: var(--ppl-card);
      border-radius: 22px;
      padding: 18px;
      box-shadow: 0 18px 38px rgba(0, 0, 0, 0.45);
      border: 1px solid rgba(255, 255, 255, 0.04);
      display: flex;
      flex-direction: column;
      gap: 16px;
      overflow: hidden;
    }

    .card video {
      width: 100%;
      aspect-ratio: 9 / 16;
      border-radius: 18px;
      background: #000;
      object-fit: cover;
      box-shadow: inset 0 0 0 1px rgba(255, 255, 255, 0.05);
    }

    .card h2 {
      margin: 0;
      font-size: 1.1rem;
      color: #fff;
      letter-spacing: 0.02em;
    }

    .card .meta {
      display: flex;
      justify-content: space-between;
      align-items: center;
      font-size: 0.85rem;
      color: var(--ppl-muted);
      text-transform: uppercase;
      letter-spacing: 0.12em;
    }

    .card p {
      margin: 0;
      color: rgba(255, 255, 255, 0.82);
      font-size: 0.95rem;
      line-height: 1.5;
    }

    .empty-state {
      margin: 8vh auto 0;
      max-width: 460px;
      text-align: center;
      padding: 40px 30px;
      border-radius: 26px;
      background: rgba(14, 14, 14, 0.8);
      box-shadow: 0 24px 55px rgba(0, 0, 0, 0.4);
      border: 1px solid rgba(255, 255, 255, 0.04);
    }

    .empty-state h2 {
      margin: 0 0 12px;
      font-size: 1.5rem;
      color: #fff;
    }

    .empty-state p {
      margin: 0 0 20px;
      color: var(--ppl-muted);
      font-size: 1rem;
    }

    .empty-state a {
      display: inline-flex;
      align-items: center;
      gap: 8px;
      padding: 10px 20px;
      border-radius: 999px;
      background: linear-gradient(120deg, var(--ppl-pink), var(--ppl-purple));
      color: #fff;
      font-weight: 600;
      text-decoration: none;
      transition: transform 0.2s ease, box-shadow 0.2s ease;
      box-shadow: 0 16px 34px rgba(255, 15, 123, 0.25);
    }

    .empty-state a:hover,
    .empty-state a:focus-visible {
      transform: translateY(-1px);
      box-shadow: 0 24px 44px rgba(122, 60, 255, 0.28);
    }

    @media (max-width: 640px) {
      header {
        flex-direction: column;
        align-items: flex-start;
      }

      header .cta {
        align-self: stretch;
        justify-content: center;
      }
    }
  </style>
</head>
<body>
<header>
  <h1>🔥 Live Now</h1>
  <a class="cta" href="/go_live.php">Go Live</a>
</header>
<main>
  <?php if (empty($streams)) : ?>
    <section class="empty-state">
      <h2>No one is live right now</h2>
      <p>Slide back in a few or start your own show to light up the feed.</p>
      <a href="/go_live.php">Start Streaming</a>
    </section>
  <?php else : ?>
    <section class="grid">
      <?php foreach ($streams as $stream) :
          $username = ppl_escape($stream['username'] ?? 'creator');
          $displayName = ppl_escape($stream['display_name'] ?: $username);
          $title = ppl_escape($stream['title'] ?? 'Live Stream');
          $startedAt = ppl_escape($stream['started_at'] ?? 'Just Now');
      ?>
        <article class="card">
          <video id="player_<?php echo $username; ?>" controls autoplay muted playsinline poster="/assets/previews/<?php echo $username; ?>.jpg"></video>
          <div class="meta">
            <span>@<?php echo $username; ?></span>
            <span><?php echo $startedAt; ?></span>
          </div>
          <h2><?php echo $displayName; ?></h2>
          <p><?php echo $title; ?></p>
        </article>
        <script>
          (function initPlayer() {
            const videoElement = document.getElementById('player_<?php echo $username; ?>');
            if (!videoElement) {
              return;
            }

            const streamUrl = `/hls/<?php echo $username; ?>.m3u8`;

            if (Hls.isSupported()) {
              const hlsInstance = new Hls({
                maxLoadingDelay: 4,
                liveSyncDuration: 2,
                manifestLoadingRetryDelay: 2000,
              });
              hlsInstance.loadSource(streamUrl);
              hlsInstance.attachMedia(videoElement);
            } else if (videoElement.canPlayType('application/vnd.apple.mpegurl')) {
              videoElement.src = streamUrl;
            } else {
              console.warn('HLS unsupported on this device for stream <?php echo $username; ?>');
            }
          })();
        </script>
      <?php endforeach; ?>
    </section>
  <?php endif; ?>
</main>
</body>
</html>
