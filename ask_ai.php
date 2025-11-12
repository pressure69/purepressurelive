<?php
declare(strict_types=1);

require __DIR__ . '/config.php';
require_login();

$role = $_SESSION['role'] ?? 'model';
$isAdmin = $role === 'admin';

if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$csrfToken = $_SESSION['csrf_token'];

/**
 * Proxy prompt to the configured AI endpoint or return a friendly fallback.
 */
function ask_ai(string $prompt): string
{
    $endpoint = getenv('PPLIVE_AI_ENDPOINT');
    $token    = getenv('PPLIVE_AI_TOKEN');

    if ($endpoint && $token) {
        $payload = json_encode([
            'prompt'       => $prompt,
            'max_tokens'   => 400,
            'temperature'  => 0.85,
            'top_p'        => 0.95,
            'stream'       => false,
        ], JSON_THROW_ON_ERROR);

        $ch = curl_init($endpoint);
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER     => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $token,
            ],
            CURLOPT_TIMEOUT        => 15,
            CURLOPT_POSTFIELDS     => $payload,
        ]);

        $raw = curl_exec($ch);
        if ($raw === false) {
            $error = curl_error($ch);
            curl_close($ch);
            throw new RuntimeException('AI request failed: ' . $error);
        }

        $status = curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        curl_close($ch);

        if ($status >= 400) {
            throw new RuntimeException('AI service returned HTTP ' . $status);
        }

        $decoded = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);
        if (isset($decoded['response'])) {
            return trim((string) $decoded['response']);
        }
        if (isset($decoded['choices'][0]['text'])) {
            return trim((string) $decoded['choices'][0]['text']);
        }

        throw new RuntimeException('AI payload malformed.');
    }

    return "🤖 Creator AI is warming up. Here's a quick hype draft based on your prompt: \n\n" . $prompt;
}

$personas = [
    'hype'   => 'Hype Captain (build energy & tip storms)',
    'velvet' => 'Velvet Seductress (slow-burn intimacy)',
    'tech'   => 'Tech Wingman (gear, lighting, setup coaching)',
];

$errors           = [];
$aiResponse       = null;
$autoFixResponse  = null;
$submittedPrompt  = '';
$submittedGoal    = '';
$selectedPersona  = 'hype';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $mode = $_POST['mode'] ?? 'prompt';
    $token = $_POST['csrf_token'] ?? '';

    if (!hash_equals($csrfToken, $token)) {
        $errors[] = 'Session expired. Refresh and try again.';
    } else {
        if ($mode === 'autofix') {
            if (!$isAdmin) {
                $errors[] = 'Only admins can request auto-fixes.';
            } else {
                $logFile = __DIR__ . '/logs/frontend_errors.log';
                $logs = is_file($logFile) ? trim((string) file_get_contents($logFile)) : 'No recent errors logged.';
                $autoPrompt = <<<PROMPT
You are the senior PurePressureLive engineer. Analyze these frontend error logs and describe the bug, root cause, and precise PHP/JS fixes with file/line hints. Keep the answer actionable and concise. Logs:\n\n{$logs}
PROMPT;
                try {
                    $autoFixResponse = ask_ai($autoPrompt);
                } catch (Throwable $e) {
                    $errors[] = 'Auto-fix AI failed: ' . $e->getMessage();
                }
            }
        } else {
            $submittedPrompt = trim((string) ($_POST['prompt'] ?? ''));
            $submittedGoal   = trim((string) ($_POST['goal'] ?? ''));
            $selectedPersona = array_key_exists($_POST['persona'] ?? '', $personas)
                ? (string) $_POST['persona']
                : 'hype';

            if ($submittedPrompt === '') {
                $errors[] = 'Give Creator AI a scenario or question.';
            }

            if (!$errors) {
                $personaLabel = $personas[$selectedPersona];
                $context = <<<PROMPT
PurePressureLive briefing:\n- Persona: {$personaLabel}\n- Creator goal: {$submittedGoal}\n- Prompt: {$submittedPrompt}\n\nReply with bold hooks, three on-brand message beats, and at least one monetization call-to-action tailored for a live streaming adult audience. Close with a reminder about consent & boundaries.
PROMPT;
                try {
                    $aiResponse = ask_ai($context);
                } catch (Throwable $e) {
                    $errors[] = 'Creator AI is unavailable: ' . $e->getMessage();
                }
            }
        }
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Creator AI · PurePressureLive</title>
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="description" content="PurePressureLive Creator AI crafts instant hype scripts, tip goals, and promo hooks for your next live show.">
    <link rel="icon" type="image/png" href="assets/img/favicon.png">
    <style>
        :root {
            color-scheme: dark;
            --bg: radial-gradient(circle at 20% 20%, rgba(255, 47, 110, 0.85), rgba(13, 13, 21, 0.98));
            --panel: rgba(14, 14, 24, 0.85);
            --panel-strong: rgba(18, 18, 30, 0.92);
            --accent: #ff335f;
            --accent-strong: #ff1256;
            --muted: #b4b4c4;
            --text: #fafaff;
            --success: #47e2b8;
            --danger: #ff6b6b;
            --border: rgba(255, 255, 255, 0.08);
            font-family: 'Inter', 'Helvetica Neue', Arial, sans-serif;
        }
        *, *::before, *::after { box-sizing: border-box; }
        body {
            margin: 0;
            min-height: 100vh;
            background: var(--bg);
            color: var(--text);
            display: flex;
            flex-direction: column;
        }
        header {
            padding: clamp(18px, 3vw, 32px);
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 16px;
        }
        .brand { display: flex; align-items: center; gap: 12px; text-decoration: none; color: var(--text); }
        .brand-logo {
            width: 44px; height: 44px; border-radius: 14px;
            background: linear-gradient(135deg, #5c00ff 0%, var(--accent) 60%, #ff8a00 100%);
            display: grid; place-items: center; font-weight: 700; letter-spacing: 0.06em;
        }
        .brand span { font-weight: 700; letter-spacing: 0.15em; text-transform: uppercase; }
        nav a { color: var(--muted); text-decoration: none; margin-left: 18px; font-size: 0.9rem; text-transform: uppercase; letter-spacing: 0.12em; }
        nav a:hover { color: var(--text); }
        main { flex: 1; width: min(1200px, 94vw); margin: 0 auto 80px; display: grid; gap: clamp(20px, 3vw, 32px); grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); }
        .panel {
            background: var(--panel);
            border-radius: 28px;
            padding: clamp(20px, 3vw, 34px);
            border: 1px solid var(--border);
            box-shadow: 0 25px 55px rgba(0, 0, 0, 0.45);
        }
        h1 { font-size: clamp(1.8rem, 3.2vw, 2.6rem); margin: 0 0 6px; }
        .eyebrow { text-transform: uppercase; letter-spacing: 0.2em; color: var(--muted); font-size: 0.75rem; }
        form { display: flex; flex-direction: column; gap: 18px; }
        label { font-size: 0.85rem; text-transform: uppercase; letter-spacing: 0.18em; color: var(--muted); }
        textarea, select, input[type="text"] {
            width: 100%;
            border: 1px solid var(--border);
            border-radius: 18px;
            background: var(--panel-strong);
            color: var(--text);
            font-size: 1rem;
            padding: 16px 18px;
            resize: vertical;
            min-height: 120px;
        }
        select, input[type="text"] { min-height: 52px; }
        .actions {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            align-items: center;
            justify-content: flex-start;
        }
        button {
            border: none;
            border-radius: 999px;
            padding: 14px 32px;
            font-size: 0.95rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.15em;
            cursor: pointer;
            transition: transform 0.15s ease, box-shadow 0.15s ease;
        }
        .primary-btn { background: linear-gradient(120deg, var(--accent), var(--accent-strong)); color: #fff; box-shadow: 0 18px 35px rgba(255, 39, 92, 0.45); }
        .secondary-btn { background: transparent; border: 1px solid var(--border); color: var(--muted); }
        button:hover { transform: translateY(-2px); }
        .response {
            white-space: pre-wrap;
            font-family: 'IBM Plex Mono', 'SFMono-Regular', Consolas, monospace;
            background: rgba(0, 0, 0, 0.55);
            border-radius: 24px;
            padding: 20px;
            border: 1px solid var(--border);
            min-height: 160px;
        }
        .errors {
            list-style: none;
            margin: 0 0 20px;
            padding: 0;
            border: 1px solid rgba(255, 107, 107, 0.4);
            border-radius: 18px;
            background: rgba(255, 107, 107, 0.08);
        }
        .errors li { padding: 12px 18px; color: var(--danger); font-weight: 600; }
        .admin-panel { border: 1px solid rgba(71, 226, 184, 0.2); background: rgba(10, 40, 26, 0.65); }
        footer { text-align: center; color: var(--muted); padding-bottom: 32px; font-size: 0.85rem; }
        @media (max-width: 720px) {
            header { flex-direction: column; align-items: flex-start; }
            nav { width: 100%; display: flex; flex-wrap: wrap; gap: 12px; }
            nav a { margin: 0; }
            .actions { flex-direction: column; align-items: stretch; }
            button { width: 100%; text-align: center; }
        }
    </style>
</head>
<body>
<header>
    <a class="brand" href="index.php">
        <div class="brand-logo">PPL</div>
        <span>PurePressureLive</span>
    </a>
    <nav aria-label="Primary">
        <a href="feed.php">Live Feed</a>
        <a href="model_dashboard.php">Model Hub</a>
        <a href="tips.php">Token Boost</a>
        <a href="ask_ai.php" aria-current="page">Creator AI</a>
    </nav>
</header>
<main>
    <section class="panel">
        <p class="eyebrow">Creator cockpit</p>
        <h1>Creator AI Wingman</h1>
        <p>Draft stream hooks, tip goals, and intimate scripts tuned to your persona. Feed it a vibe, goal, or wild fan request—Creator AI keeps it bold, consensual, and monetized.</p>
        <?php if ($errors): ?>
            <ul class="errors">
                <?php foreach ($errors as $error): ?>
                    <li><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
        <form method="post" novalidate>
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>">
            <input type="hidden" name="mode" value="prompt">
            <label for="persona">Persona Focus</label>
            <select id="persona" name="persona">
                <?php foreach ($personas as $value => $label): ?>
                    <option value="<?= htmlspecialchars($value, ENT_QUOTES, 'UTF-8'); ?>" <?= $selectedPersona === $value ? 'selected' : ''; ?>><?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8'); ?></option>
                <?php endforeach; ?>
            </select>
            <label for="goal">Token Goal or Outcome</label>
            <input id="goal" type="text" name="goal" maxlength="140" placeholder="Example: Trigger a 5k token shower in 10 minutes" value="<?= htmlspecialchars($submittedGoal, ENT_QUOTES, 'UTF-8'); ?>">
            <label for="prompt">What do you need?</label>
            <textarea id="prompt" name="prompt" placeholder="Example: Fans keep asking for a backstage POV. Need a seductive tease plus upsell." required><?= htmlspecialchars($submittedPrompt, ENT_QUOTES, 'UTF-8'); ?></textarea>
            <div class="actions">
                <button type="submit" class="primary-btn">Generate Script</button>
                <a class="secondary-btn" href="model_dashboard.php" style="text-decoration:none; text-align:center; line-height:1.4;">Back to Hub</a>
            </div>
        </form>
        <div class="response" aria-live="polite">
            <?php if ($aiResponse): ?>
                <?= nl2br(htmlspecialchars($aiResponse, ENT_QUOTES, 'UTF-8')); ?>
            <?php else: ?>
                <em>Creator AI responses drop here. Feed it your wildest idea.</em>
            <?php endif; ?>
        </div>
    </section>
    <section class="panel admin-panel">
        <p class="eyebrow">Ops Toolkit</p>
        <h2 style="margin-top:0;">Auto-Fix Scanner</h2>
        <p>Pulls the latest frontend error log and asks Creator AI for a root-cause breakdown with suggested patches. Requires admin role.</p>
        <?php if (!$isAdmin): ?>
            <p style="color: var(--muted); font-style: italic;">Only admins can run the scanner. Contact support if you need elevated access.</p>
        <?php else: ?>
            <form method="post" class="autofix-form" novalidate>
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>">
                <input type="hidden" name="mode" value="autofix">
                <div class="actions">
                    <button type="submit" class="primary-btn">Run Auto-Fix Brief</button>
                </div>
            </form>
        <?php endif; ?>
        <div class="response" style="margin-top:18px;">
            <?php if ($autoFixResponse): ?>
                <?= nl2br(htmlspecialchars($autoFixResponse, ENT_QUOTES, 'UTF-8')); ?>
            <?php else: ?>
                <em>Latest AI diagnostics will appear here after you run the scanner.</em>
            <?php endif; ?>
        </div>
    </section>
</main>
<footer>
    &copy; <?= date('Y'); ?> PurePressureLive · Encrypted. Sensual. Always consensual.
</footer>
</body>
</html>
