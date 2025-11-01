<?php
declare(strict_types=1);

session_start();

$isLoggedIn = isset($_SESSION['user_id']);
$userRole = $_SESSION['role'] ?? null;

$primaryCtaHref = $isLoggedIn
    ? ($userRole === 'model' ? 'model_dashboard.php' : 'feed.php')
    : 'register.php';
$primaryCtaLabel = $isLoggedIn ? 'Enter Live' : 'Join the Movement';

$secondaryCtaHref = $isLoggedIn ? 'settings.php' : 'login.php';
$secondaryCtaLabel = $isLoggedIn ? 'Account Settings' : 'Sign In';

$backgroundVideo = 'assets/video/bg.mp4';
$hasBackgroundVideo = is_file(__DIR__ . '/' . $backgroundVideo);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>PurePressureLive — Stream Bold</title>
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="description" content="PurePressureLive is the bold, high-performance live streaming arena for adults to connect, earn, and ignite their fanbases.">
    <link rel="icon" type="image/png" href="assets/img/favicon.png">
    <style>
        :root {
            color-scheme: dark;
            --bg-gradient: radial-gradient(circle at 20% 20%, rgba(255, 68, 110, 0.85), rgba(18, 18, 30, 0.95));
            --accent: #ff2d55;
            --accent-strong: #ff124a;
            --neutral: #10101a;
            --glass: rgba(16, 16, 26, 0.72);
            --glass-strong: rgba(16, 16, 26, 0.85);
            --text: #f6f6f9;
            --muted: #b7b7c9;
            --shadow: 0 18px 45px rgba(0, 0, 0, 0.45);
        }

        *, *::before, *::after {
            box-sizing: border-box;
        }

        html, body {
            margin: 0;
            padding: 0;
            width: 100%;
            min-height: 100%;
            font-family: 'Inter', 'Helvetica Neue', Arial, sans-serif;
            background: #0b0b13;
            color: var(--text);
            overflow-x: hidden;
        }

        body {
            position: relative;
            display: flex;
            flex-direction: column;
            background-image: var(--bg-gradient);
        }

        .background-video {
            position: fixed;
            inset: 0;
            width: 100%;
            height: 100%;
            object-fit: cover;
            filter: saturate(120%) contrast(110%);
            opacity: 0.55;
            z-index: -2;
        }

        .overlay {
            position: fixed;
            inset: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(160deg, rgba(11, 11, 19, 0.8) 0%, rgba(11, 11, 19, 0.2) 45%, rgba(11, 11, 19, 0.85) 100%);
            z-index: -1;
            pointer-events: none;
        }

        header {
            width: 100%;
            padding: clamp(18px, 2vw, 32px);
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
        }

        .brand {
            display: flex;
            align-items: center;
            gap: 12px;
            text-decoration: none;
        }

        .brand-logo {
            width: 42px;
            height: 42px;
            border-radius: 14px;
            background: linear-gradient(135deg, #5c00ff 0%, #ff2d55 50%, #ff8a00 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            color: #ffffff;
            letter-spacing: 1px;
            box-shadow: var(--shadow);
        }

        .brand-title {
            font-size: clamp(1.1rem, 2.2vw, 1.6rem);
            font-weight: 700;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            color: var(--text);
        }

        .nav-links {
            display: flex;
            align-items: center;
            gap: clamp(14px, 2vw, 28px);
        }

        .nav-links a {
            text-decoration: none;
            color: var(--muted);
            font-size: clamp(0.85rem, 1.8vw, 1rem);
            text-transform: uppercase;
            letter-spacing: 0.12em;
            transition: color 0.25s ease;
        }

        .nav-links a:hover,
        .nav-links a:focus {
            color: var(--text);
        }

        .hero {
            flex: 1;
            width: min(1220px, 94vw);
            margin: 0 auto;
            padding: clamp(36px, 6vw, 120px) 0;
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: clamp(28px, 6vw, 64px);
        }

        .hero-copy {
            display: flex;
            flex-direction: column;
            gap: clamp(22px, 3vw, 32px);
            background: linear-gradient(145deg, rgba(16, 16, 26, 0.85), rgba(16, 16, 26, 0.35));
            border-radius: 28px;
            padding: clamp(24px, 3vw, 48px);
            box-shadow: var(--shadow);
            backdrop-filter: blur(16px);
        }

        .hero-eyebrow {
            font-size: clamp(0.85rem, 1.4vw, 1rem);
            letter-spacing: 0.28em;
            text-transform: uppercase;
            color: var(--muted);
        }

        .hero-title {
            font-size: clamp(2.4rem, 5vw, 4.4rem);
            line-height: 1.05;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            color: #ffffff;
        }

        .hero-title span {
            color: var(--accent);
        }

        .hero-subtitle {
            font-size: clamp(1rem, 2vw, 1.3rem);
            color: rgba(246, 246, 249, 0.88);
            max-width: 520px;
            line-height: 1.6;
        }

        .cta-row {
            display: flex;
            flex-wrap: wrap;
            gap: 16px;
        }

        .cta {
            padding: 16px 28px;
            border-radius: 999px;
            border: 1px solid transparent;
            font-size: 1rem;
            font-weight: 700;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            text-decoration: none;
            transition: transform 0.25s ease, box-shadow 0.25s ease, border-color 0.25s ease;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 180px;
        }

        .cta-primary {
            background: linear-gradient(135deg, var(--accent) 0%, var(--accent-strong) 100%);
            color: #fff;
            box-shadow: 0 22px 32px rgba(255, 45, 85, 0.3);
        }

        .cta-primary:hover,
        .cta-primary:focus {
            transform: translateY(-2px);
            box-shadow: 0 28px 38px rgba(255, 45, 85, 0.4);
        }

        .cta-secondary {
            border-color: rgba(255, 255, 255, 0.25);
            color: var(--text);
            background: rgba(16, 16, 26, 0.5);
        }

        .cta-secondary:hover,
        .cta-secondary:focus {
            border-color: rgba(255, 255, 255, 0.45);
            transform: translateY(-2px);
        }

        .hero-badge-row {
            display: flex;
            flex-wrap: wrap;
            gap: 14px;
            color: var(--muted);
            font-size: 0.9rem;
            letter-spacing: 0.12em;
            text-transform: uppercase;
        }

        .hero-badge-row span {
            display: inline-flex;
            align-items: center;
            gap: 10px;
        }

        .hero-visual {
            position: relative;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .phone-frame {
            position: relative;
            width: min(320px, 80vw);
            aspect-ratio: 9 / 16;
            border-radius: 32px;
            padding: 18px;
            background: linear-gradient(145deg, rgba(255, 255, 255, 0.08), rgba(255, 255, 255, 0.02));
            box-shadow: 0 18px 60px rgba(0, 0, 0, 0.45);
            backdrop-filter: blur(24px);
        }

        .phone-screen {
            position: relative;
            width: 100%;
            height: 100%;
            border-radius: 24px;
            overflow: hidden;
            background: radial-gradient(circle at 50% 0%, rgba(255, 45, 85, 0.35), rgba(16, 16, 26, 1));
        }

        .phone-screen::after {
            content: '';
            position: absolute;
            inset: 0;
            background: url('assets/img/preview-stream.jpg') center/cover no-repeat;
            mix-blend-mode: lighten;
            opacity: 0.65;
            animation: pulse 6s ease-in-out infinite;
        }

        .phone-overlay {
            position: absolute;
            inset: 0;
            padding: 20px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            color: #ffffff;
        }

        .phone-top {
            display: flex;
            align-items: center;
            justify-content: space-between;
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.18em;
            opacity: 0.9;
        }

        .phone-middle {
            margin-top: auto;
        }

        .phone-title {
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: 10px;
            text-transform: uppercase;
            letter-spacing: 0.12em;
        }

        .phone-tagline {
            font-size: 0.95rem;
            line-height: 1.5;
            color: rgba(255, 255, 255, 0.85);
        }

        .metrics {
            width: 100%;
            margin: clamp(36px, 6vw, 64px) auto;
            padding: 0 clamp(12px, 4vw, 24px);
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: clamp(16px, 3vw, 28px);
        }

        .metric-card {
            background: var(--glass-strong);
            border-radius: 20px;
            padding: clamp(18px, 2.4vw, 28px);
            box-shadow: var(--shadow);
            backdrop-filter: blur(18px);
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .metric-value {
            font-size: clamp(1.6rem, 3vw, 2.3rem);
            font-weight: 800;
            letter-spacing: 0.1em;
            text-transform: uppercase;
        }

        .metric-label {
            font-size: 0.85rem;
            color: var(--muted);
            letter-spacing: 0.18em;
            text-transform: uppercase;
        }

        .sections {
            width: min(1180px, 92vw);
            margin: 0 auto clamp(60px, 8vw, 110px);
            display: grid;
            gap: clamp(32px, 5vw, 60px);
        }

        .section-card {
            background: var(--glass);
            border-radius: 24px;
            padding: clamp(26px, 3vw, 42px);
            box-shadow: var(--shadow);
            backdrop-filter: blur(16px);
        }

        .section-card h2 {
            margin: 0 0 14px;
            text-transform: uppercase;
            letter-spacing: 0.1em;
            font-size: clamp(1.4rem, 2.2vw, 2.1rem);
        }

        .section-card p {
            margin: 0 0 20px;
            color: rgba(246, 246, 249, 0.85);
            line-height: 1.6;
        }

        .feature-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: clamp(18px, 3vw, 28px);
        }

        .feature-item {
            background: rgba(255, 255, 255, 0.04);
            border-radius: 18px;
            padding: clamp(18px, 2.4vw, 24px);
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .feature-item h3 {
            margin: 0;
            font-size: 1rem;
            text-transform: uppercase;
            letter-spacing: 0.12em;
        }

        .feature-item p {
            margin: 0;
            font-size: 0.95rem;
            line-height: 1.5;
            color: rgba(246, 246, 249, 0.78);
        }

        footer {
            width: 100%;
            padding: clamp(18px, 2vw, 28px);
            background: rgba(11, 11, 19, 0.85);
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: space-between;
            gap: 14px;
            font-size: 0.85rem;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            color: var(--muted);
        }

        footer nav {
            display: flex;
            gap: 18px;
        }

        footer a {
            color: var(--muted);
            text-decoration: none;
            transition: color 0.25s ease;
        }

        footer a:hover,
        footer a:focus {
            color: var(--text);
        }

        @keyframes pulse {
            0%, 100% { opacity: 0.55; transform: scale(1); }
            50% { opacity: 0.72; transform: scale(1.02); }
        }

        @media (max-width: 960px) {
            header {
                flex-direction: column;
                align-items: flex-start;
            }

            .nav-links {
                width: 100%;
                justify-content: space-between;
            }

            .hero {
                grid-template-columns: 1fr;
                text-align: center;
            }

            .hero-copy {
                align-items: center;
            }

            .hero-subtitle {
                max-width: 100%;
            }

            .cta-row {
                justify-content: center;
            }

            .hero-badge-row {
                justify-content: center;
            }

            .hero-visual {
                margin-top: 10px;
            }
        }

        @media (max-width: 600px) {
            header {
                padding: 20px 18px;
            }

            .nav-links {
                flex-direction: column;
                align-items: flex-start;
                gap: 10px;
            }

            footer {
                flex-direction: column;
                align-items: flex-start;
            }
        }
    </style>
</head>
<body>
<?php if ($hasBackgroundVideo): ?>
    <video class="background-video" autoplay muted loop playsinline>
        <source src="<?= htmlspecialchars($backgroundVideo, ENT_QUOTES, 'UTF-8'); ?>" type="video/mp4">
    </video>
<?php endif; ?>
    <div class="overlay"></div>
    <header>
        <a class="brand" href="index.php">
            <div class="brand-logo">PPL</div>
            <span class="brand-title">PurePressureLive</span>
        </a>
        <nav class="nav-links" aria-label="Primary">
            <a href="feed.php">Live Feed</a>
            <a href="model_dashboard.php">Model Hub</a>
            <a href="tips.php">Token Boost</a>
            <a href="ask_ai.php">Creator AI</a>
        </nav>
    </header>
    <main>
        <section class="hero">
            <div class="hero-copy">
                <span class="hero-eyebrow">Stream Loud · Earn Fast · Stay Private</span>
                <h1 class="hero-title">Own the <span>Night</span> with PurePressureLive</h1>
                <p class="hero-subtitle">
                    Supercharge your live shows with low-latency delivery, encrypted payouts, and fans hungry for exclusives.
                    Whether you are a rising creator or a top earner, PurePressureLive keeps you high-definition, high-energy, and highly paid.
                </p>
                <div class="cta-row">
                    <a class="cta cta-primary" href="<?= htmlspecialchars($primaryCtaHref, ENT_QUOTES, 'UTF-8'); ?>"><?= htmlspecialchars($primaryCtaLabel, ENT_QUOTES, 'UTF-8'); ?></a>
                    <a class="cta cta-secondary" href="<?= htmlspecialchars($secondaryCtaHref, ENT_QUOTES, 'UTF-8'); ?>"><?= htmlspecialchars($secondaryCtaLabel, ENT_QUOTES, 'UTF-8'); ?></a>
                </div>
                <div class="hero-badge-row">
                    <span>⚡ 0% buffer streaming</span>
                    <span>🔒 Bank-grade privacy</span>
                    <span>💸 Weekly token drops</span>
                </div>
            </div>
            <div class="hero-visual" aria-hidden="true">
                <div class="phone-frame">
                    <div class="phone-screen"></div>
                    <div class="phone-overlay">
                        <div class="phone-top">
                            <span>Live Now</span>
                            <span>58.4K viewers</span>
                        </div>
                        <div class="phone-middle">
                            <div class="phone-title">NovaKade</div>
                            <div class="phone-tagline">"Slide in, strap up, and ride the neon rush."</div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
        <section class="metrics" aria-label="Platform statistics">
            <article class="metric-card">
                <div class="metric-value">+4.2M</div>
                <div class="metric-label">Monthly Fans</div>
            </article>
            <article class="metric-card">
                <div class="metric-value">$18.6M</div>
                <div class="metric-label">Creator Payouts</div>
            </article>
            <article class="metric-card">
                <div class="metric-value">38ms</div>
                <div class="metric-label">Average Latency</div>
            </article>
            <article class="metric-card">
                <div class="metric-value">24/7</div>
                <div class="metric-label">Support Squad</div>
            </article>
        </section>
        <section class="sections">
            <div class="section-card">
                <h2>Why Models Choose PurePressureLive</h2>
                <p>Empower your hustle with instant HD encoding, protected archives, and withdrawal windows that respect your grind. Dial in dynamic pricing mid-show, trigger tip storms with a tap, and collaborate securely using private multi-stream invites.</p>
                <div class="feature-grid">
                    <div class="feature-item">
                        <h3>Turbo Encoding</h3>
                        <p>Automatically optimizes your bitrate for every device so your show stays sharp on 5G, Wi-Fi, or late-night edge connections.</p>
                    </div>
                    <div class="feature-item">
                        <h3>Stealth Payouts</h3>
                        <p>Route earnings through encrypted vaults with two-factor approvals, giving you bank-level control on web or mobile.</p>
                    </div>
                    <div class="feature-item">
                        <h3>Consent-First Moderation</h3>
                        <p>AI-flagged chat filters and human review teams keep your room safe without killing the vibe or slowing your roll.</p>
                    </div>
                    <div class="feature-item">
                        <h3>Creator AI Wingman</h3>
                        <p>Spin prompts, merch captions, and token goals with Ask AI, tuned specifically for PurePressureLive crowd chemistry.</p>
                    </div>
                </div>
            </div>
            <div class="section-card">
                <h2>For Fans Who Want It Raw & Real</h2>
                <p>Crave the thrill with curated discovery feeds, loyalty streaks, and exclusive backstage passes. Replay highlights instantly and hype your favorites with streak-matching boosts that hit harder than any tip jar.</p>
                <div class="feature-grid">
                    <div class="feature-item">
                        <h3>Swipe-to-Tip</h3>
                        <p>Smash reactions, drop tokens, and unlock milestones with gestures built for one-handed control on any screen size.</p>
                    </div>
                    <div class="feature-item">
                        <h3>Curated Heat</h3>
                        <p>Our AI feed watches your cravings and serves up live rooms that match your pulse, no fluff, no filler.</p>
                    </div>
                    <div class="feature-item">
                        <h3>Encrypted Vault</h3>
                        <p>Keep purchase history, private chats, and exclusive drops secured with session-based locks that follow you everywhere.</p>
                    </div>
                    <div class="feature-item">
                        <h3>Instant Catch-Up</h3>
                        <p>Missed the climax? Rewind up to 60 minutes with zero buffering and jump right back into live without losing the rush.</p>
                    </div>
                </div>
            </div>
        </section>
    </main>
    <footer>
        <span>&copy; <?= date('Y'); ?> PurePressureLive. All rights reserved.</span>
        <nav aria-label="Legal">
            <a href="terms.php">Terms</a>
            <a href="privacy.php">Privacy</a>
            <a href="support.php">Support</a>
        </nav>
    </footer>
    <script>
        (function () {
            const root = document.documentElement;
            const updateGradient = () => {
                const now = new Date();
                const seconds = now.getSeconds();
                const hue = (seconds / 60) * 360;
                root.style.setProperty('--bg-gradient', `radial-gradient(circle at 20% 20%, hsla(${hue}, 85%, 58%, 0.8), rgba(11, 11, 19, 0.92))`);
            };
            updateGradient();
            setInterval(updateGradient, 8000);
        })();
    </script>
</body>
</html>
