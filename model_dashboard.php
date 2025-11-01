<?php
declare(strict_types=1);

require_once __DIR__ . '/config.php';

/** @var \PDO $pdo */
if (empty($_SESSION['model_id'])) {
    header('Location: /login.php');
    exit;
}

$modelId = (int) $_SESSION['model_id'];

try {
    $stmt = $pdo->prepare(
        'SELECT username, display_name, preview_image, is_live, stream_key, token_goal
         FROM models
         WHERE id = :id'
    );
    $stmt->execute(['id' => $modelId]);
    $model = $stmt->fetch(\PDO::FETCH_ASSOC);
} catch (\PDOException $e) {
    error_log('Failed to load model dashboard: ' . $e->getMessage());
    $model = false;
}

if (!$model) {
    echo 'Model not found.';
    exit;
}

$displayName = $model['display_name'] !== null && $model['display_name'] !== ''
    ? $model['display_name']
    : ($model['username'] ?? 'Model');
$tokenGoal = isset($model['token_goal']) ? (int) $model['token_goal'] : 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Model Dashboard - PurePressureLive</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <style>
    :root {
      color-scheme: dark;
      --ppl-pink: #ff1493;
      --ppl-purple: #6b1fff;
      --ppl-bg: #090909;
      --ppl-card: #151515;
      --ppl-text-muted: #9a9a9a;
    }

    * {
      box-sizing: border-box;
    }

    body {
      margin: 0;
      padding: 0;
      min-height: 100vh;
      font-family: 'Segoe UI', Roboto, sans-serif;
      background: radial-gradient(circle at top, rgba(255, 20, 147, 0.18), transparent 55%),
                  radial-gradient(circle at bottom, rgba(107, 31, 255, 0.25), transparent 60%),
                  var(--ppl-bg);
      color: #fff;
      display: flex;
      align-items: center;
      justify-content: center;
    }

    .container {
      width: min(960px, 94vw);
      margin: 40px auto;
      padding: 24px;
      border-radius: 24px;
      background: linear-gradient(145deg, rgba(21, 21, 21, 0.92), rgba(12, 12, 12, 0.92));
      backdrop-filter: blur(16px);
      box-shadow: 0 28px 60px rgba(0, 0, 0, 0.55);
    }

    header {
      display: flex;
      flex-direction: column;
      gap: 12px;
      align-items: flex-start;
      margin-bottom: 24px;
    }

    header h1 {
      margin: 0;
      font-size: clamp(1.8rem, 3vw, 2.4rem);
      font-weight: 700;
      letter-spacing: 0.03em;
      color: #fff;
    }

    header .subline {
      font-size: 0.95rem;
      color: var(--ppl-text-muted);
      max-width: 60ch;
    }

    .grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
      gap: 20px;
    }

    .card {
      position: relative;
      padding: 20px;
      border-radius: 20px;
      background: var(--ppl-card);
      border: 1px solid rgba(255, 255, 255, 0.05);
      box-shadow: inset 0 0 0 1px rgba(255, 255, 255, 0.02);
    }

    .card h2 {
      margin: 0 0 14px;
      font-size: 1.1rem;
      letter-spacing: 0.05em;
      text-transform: uppercase;
      color: rgba(255, 255, 255, 0.82);
    }

    .stream-key {
      word-break: break-all;
      font-family: 'JetBrains Mono', 'Courier New', monospace;
      font-size: 0.95rem;
      letter-spacing: 0.05em;
      background: rgba(255, 255, 255, 0.04);
      padding: 12px;
      border-radius: 12px;
    }

    .goal-bar {
      width: 100%;
      height: 18px;
      border-radius: 999px;
      background: rgba(255, 255, 255, 0.08);
      overflow: hidden;
      margin-bottom: 12px;
    }

    .goal-fill {
      height: 100%;
      width: 0;
      border-radius: inherit;
      background: linear-gradient(90deg, var(--ppl-pink), var(--ppl-purple));
      transition: width 0.6s ease;
    }

    .goal-stats {
      display: flex;
      justify-content: space-between;
      align-items: center;
      font-size: 0.95rem;
      color: rgba(255, 255, 255, 0.88);
    }

    ul.data-list {
      list-style: none;
      padding: 0;
      margin: 0;
      display: flex;
      flex-direction: column;
      gap: 12px;
    }

    ul.data-list li {
      padding: 12px 14px;
      border-radius: 14px;
      background: rgba(0, 0, 0, 0.35);
      display: flex;
      justify-content: space-between;
      align-items: center;
      gap: 12px;
      font-size: 0.95rem;
    }

    ul.data-list li .label {
      font-weight: 600;
      color: #fff;
    }

    ul.data-list li .value {
      font-weight: 500;
      color: var(--ppl-pink);
    }

    .muted {
      color: var(--ppl-text-muted);
      font-style: italic;
    }

    .share-buttons {
      margin-top: 28px;
      display: flex;
      flex-wrap: wrap;
      gap: 12px;
    }

    .share-buttons a {
      flex: 1 1 160px;
      text-align: center;
      text-decoration: none;
      padding: 12px 16px;
      border-radius: 16px;
      font-weight: 600;
      letter-spacing: 0.04em;
      transition: transform 0.2s ease, box-shadow 0.2s ease;
      color: #fff;
    }

    .share-buttons a:hover {
      transform: translateY(-2px);
      box-shadow: 0 12px 20px rgba(0, 0, 0, 0.35);
    }

    .twitter { background: linear-gradient(120deg, #1DA1F2, #0f6cd6); }
    .facebook { background: linear-gradient(120deg, #3b5998, #1f2f69); }

    .toast {
      position: fixed;
      top: 24px;
      right: 24px;
      min-width: 220px;
      max-width: min(360px, 80vw);
      padding: 14px 18px;
      border-radius: 14px;
      background: rgba(224, 44, 109, 0.92);
      color: #fff;
      font-weight: 600;
      letter-spacing: 0.03em;
      box-shadow: 0 18px 30px rgba(0, 0, 0, 0.45);
      opacity: 0;
      transform: translateY(-10px);
      transition: opacity 0.3s ease, transform 0.3s ease;
      pointer-events: none;
      z-index: 999;
    }

    .toast.show {
      opacity: 1;
      transform: translateY(0);
    }

    .toast.success {
      background: rgba(40, 170, 120, 0.92);
    }

    @media (max-width: 640px) {
      .container {
        padding: 20px;
        margin: 20px auto;
      }

      header {
        align-items: flex-start;
      }

      ul.data-list li {
        flex-direction: column;
        align-items: flex-start;
      }

      .goal-stats {
        flex-direction: column;
        align-items: flex-start;
        gap: 6px;
      }
    }
  </style>
</head>
<body>
  <div class="toast" id="toast" role="status" aria-live="polite" aria-atomic="true"></div>
  <main class="container">
    <header>
      <h1>Welcome, <?php echo htmlspecialchars($displayName, ENT_QUOTES, 'UTF-8'); ?></h1>
      <p class="subline">Track your top tippers, chase tonight's token goal, and keep that stream energy magnetic.</p>
    </header>

    <div class="grid">
      <section class="card">
        <h2>Stream Key</h2>
        <div class="stream-key"><?php echo htmlspecialchars((string) ($model['stream_key'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></div>
      </section>

      <section class="card">
        <h2>Token Goal</h2>
        <div class="goal-bar" aria-hidden="true"><div class="goal-fill" id="goalFill"></div></div>
        <div class="goal-stats">
          <span id="goalText">0 / <?php echo $tokenGoal; ?> tokens</span>
          <span id="goalPercent" class="muted">0%</span>
        </div>
      </section>

      <section class="card">
        <h2>Recent Tips</h2>
        <ul id="tipsList" class="data-list">
          <li class="muted">Loading your latest love...</li>
        </ul>
      </section>

      <section class="card">
        <h2>Top Fans</h2>
        <ul id="fansList" class="data-list">
          <li class="muted">Pulling your fan leaderboard...</li>
        </ul>
      </section>
    </div>

    <div class="share-buttons">
      <a class="twitter" target="_blank" rel="noopener"
         href="https://twitter.com/intent/tweet?text=%F0%9F%94%A5+I'm+live+on+PurePressureLive!+Come+watch+me+now!+https://purepressurelive.com/feed.php">
         Share on Twitter
      </a>
      <a class="facebook" target="_blank" rel="noopener"
         href="https://www.facebook.com/sharer/sharer.php?u=https://purepressurelive.com/feed.php">
         Share on Facebook
      </a>
    </div>
  </main>

  <script>
    const tipsList = document.getElementById('tipsList');
    const fansList = document.getElementById('fansList');
    const goalFill = document.getElementById('goalFill');
    const goalText = document.getElementById('goalText');
    const goalPercent = document.getElementById('goalPercent');
    const toast = document.getElementById('toast');
    let toastTimeout = null;

    function showToast(message, variant = 'error') {
      if (!toast) {
        return;
      }

      toast.textContent = message;
      toast.classList.remove('success');
      if (variant === 'success') {
        toast.classList.add('success');
      }
      toast.classList.add('show');

      if (toastTimeout) {
        clearTimeout(toastTimeout);
      }

      toastTimeout = setTimeout(() => {
        toast.classList.remove('show');
      }, 3500);
    }

    function renderEmptyList(listElement, message) {
      listElement.innerHTML = '';
      const li = document.createElement('li');
      li.className = 'muted';
      li.textContent = message;
      listElement.appendChild(li);
    }

    function renderTips(tips) {
      if (!Array.isArray(tips) || tips.length === 0) {
        renderEmptyList(tipsList, 'No tips yet — tease your fans to spark the flow.');
        return;
      }

      tipsList.innerHTML = '';
      tips.slice(0, 10).forEach((tip) => {
        const li = document.createElement('li');

        const name = document.createElement('span');
        name.className = 'label';
        name.textContent = tip.username ?? 'Anonymous';

        const value = document.createElement('span');
        value.className = 'value';
        value.textContent = `${Number.parseInt(tip.amount, 10) || 0} tokens`;

        li.appendChild(name);
        li.appendChild(value);
        tipsList.appendChild(li);
      });
    }

    function renderFans(fans) {
      if (!Array.isArray(fans) || fans.length === 0) {
        renderEmptyList(fansList, 'Your diehards will show soon — keep the energy electric.');
        return;
      }

      fansList.innerHTML = '';
      fans.slice(0, 10).forEach((fan, index) => {
        const li = document.createElement('li');

        const label = document.createElement('span');
        label.className = 'label';
        label.textContent = `#${index + 1} ${fan.username ?? 'Anonymous'}`;

        const value = document.createElement('span');
        value.className = 'value';
        value.textContent = `${Number.parseInt(fan.total, 10) || 0} tokens`;

        li.appendChild(label);
        li.appendChild(value);
        fansList.appendChild(li);
      });
    }

    function updateGoal(total, goal) {
      const safeTotal = Number.parseInt(total, 10) || 0;
      const safeGoal = Number.parseInt(goal, 10) || 0;
      const percent = safeGoal > 0 ? Math.min(Math.round((safeTotal / safeGoal) * 100), 999) : 0;

      goalFill.style.width = `${Math.min(percent, 100)}%`;
      goalText.textContent = safeGoal > 0
        ? `${safeTotal.toLocaleString()} / ${safeGoal.toLocaleString()} tokens`
        : `${safeTotal.toLocaleString()} tokens earned`;
      goalPercent.textContent = safeGoal > 0 ? `${percent}%` : 'No goal set';
    }

    async function loadDashboard() {
      try {
        const res = await fetch('api/get_dashboard_data.php', {
          headers: {
            'Accept': 'application/json',
          },
          credentials: 'same-origin',
        });

        if (!res.ok) {
          throw new Error(`Dashboard request failed (${res.status})`);
        }

        const data = await res.json();

        if (!data.success) {
          throw new Error(data.message || 'Unable to load dashboard data.');
        }

        renderTips(data.tips);
        renderFans(data.fans);
        updateGoal(data.total, data.goal);
      } catch (error) {
        console.error(error);
        showToast(error.message || 'Network error while loading your dashboard.');
        renderEmptyList(tipsList, 'Unable to load tips right now. Retrying...');
        renderEmptyList(fansList, 'We will refresh your leaderboard shortly.');
        updateGoal(0, <?php echo $tokenGoal; ?>);
      }
    }

    setInterval(loadDashboard, 5000);
    loadDashboard();
  </script>
</body>
</html>
