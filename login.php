<?php
session_start();
require_once __DIR__ . '/config.php';

$databaseOnline = $pdo instanceof PDO;
$databaseIssueMessage = null;

if (!$databaseOnline) {
    $databaseIssueMessage = 'We\'re experiencing a database issue right now. Logins are temporarily unavailable while we work on a fix. Please try again soon.';
}

if (!isset($_SESSION['csrf_token'])) {
    try {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    } catch (Throwable $e) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(16));
    }
}

if (isset($_SESSION['model_id'])) {
    header('Location: model_dashboard.php');
    exit;
}

$errors = [];
$loginValue = '';
$remember = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf'] ?? '';
    $loginValue = trim($_POST['login'] ?? $_POST['email'] ?? $_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $remember = !empty($_POST['remember']);

    if (!$token || !isset($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $token)) {
        $errors[] = 'Your session has expired. Please try again.';
    }

    if ($loginValue === '' || $password === '') {
        $errors[] = 'Email/username and password are both required.';
    }

    if (!$errors && !$databaseOnline) {
        $errors[] = 'Logins are currently disabled while we resolve a database issue. Please try again later.';
    }

    if (!$errors && $databaseOnline) {
        try {
            $stmt = $pdo->prepare('SELECT * FROM models WHERE email = :login OR username = :login LIMIT 1');
            $stmt->execute(['login' => $loginValue]);
            $model = $stmt->fetch();
        } catch (PDOException $e) {
            error_log('Login query failed: ' . $e->getMessage());
            $model = false;
        }

        if (!$model) {
            $errors[] = 'We could not find an account with those credentials.';
        } else {
            $verified = false;
            $needsRehash = false;
            $hashColumn = null;

            if (isset($model['password_hash']) && $model['password_hash'] !== '' && password_verify($password, (string) $model['password_hash'])) {
                $verified = true;
                $needsRehash = password_needs_rehash((string) $model['password_hash'], PASSWORD_DEFAULT);
                $hashColumn = 'password_hash';
            }

            if (!$verified && isset($model['password']) && $model['password'] !== '' && password_verify($password, (string) $model['password'])) {
                $verified = true;
                $needsRehash = password_needs_rehash((string) $model['password'], PASSWORD_DEFAULT);
                $hashColumn = 'password';
            }

            if (!$verified && isset($model['password']) && $model['password'] !== '' && hash_equals((string) $model['password'], $password)) {
                $verified = true;
                $needsRehash = true;
                $hashColumn = 'password';
            }

            if (!$verified) {
                $errors[] = 'Incorrect password. Please try again.';
            } else {
                session_regenerate_id(true);
                $_SESSION['model_id'] = (int) $model['id'];
                $_SESSION['model_username'] = $model['username'] ?? '';
                $_SESSION['model_display_name'] = $model['display_name'] ?? ($model['username'] ?? '');

                if ($remember) {
                    $params = session_get_cookie_params();
                    $cookie = [
                        'expires'  => time() + 60 * 60 * 24 * 30,
                        'path'     => $params['path'] ?? '/',
                        'domain'   => $params['domain'] ?? '',
                        'secure'   => $params['secure'] ?? isset($_SERVER['HTTPS']),
                        'httponly' => $params['httponly'] ?? true,
                    ];
                    if (PHP_VERSION_ID >= 70300) {
                        $cookie['samesite'] = $params['samesite'] ?? 'Lax';
                        setcookie(session_name(), session_id(), $cookie);
                    } else {
                        setcookie(session_name(), session_id(), $cookie['expires'], $cookie['path'], $cookie['domain'], $cookie['secure'], $cookie['httponly']);
                    }
                }

                if ($needsRehash && $hashColumn !== null) {
                    try {
                        $newHash = password_hash($password, PASSWORD_DEFAULT);
                        $updateColumn = $hashColumn === 'password_hash' ? 'password_hash' : 'password';
                        $update = $pdo->prepare("UPDATE models SET {$updateColumn} = :hash WHERE id = :id");
                        $update->execute(['hash' => $newHash, 'id' => $model['id']]);
                    } catch (PDOException $e) {
                        error_log('Unable to update password hash: ' . $e->getMessage());
                    }
                }

                header('Location: model_dashboard.php');
                exit;
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Login - PurePressureLive</title>
  <style>
    body {
      margin: 0;
      font-family: 'Inter', system-ui, sans-serif;
      background: radial-gradient(circle at top,#1f1f2e,#0b0b0f 65%);
      color: #f5f5f5;
      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 24px;
    }
    .card {
      width: min(420px, 100%);
      background: rgba(20, 20, 32, 0.9);
      border-radius: 18px;
      padding: 32px;
      box-shadow: 0 18px 60px rgba(0, 0, 0, 0.35);
      border: 1px solid rgba(255, 255, 255, 0.08);
      backdrop-filter: blur(6px);
    }
    h1 {
      margin: 0 0 6px;
      font-size: 1.8rem;
    }
    p.lead {
      margin: 0 0 20px;
      color: rgba(255,255,255,0.65);
    }
    label {
      display: block;
      font-size: 0.85rem;
      font-weight: 600;
      margin-bottom: 6px;
    }
    input[type="text"],
    input[type="email"],
    input[type="password"] {
      width: 100%;
      padding: 12px 14px;
      border-radius: 12px;
      border: 1px solid rgba(255,255,255,0.1);
      background: rgba(12, 12, 20, 0.9);
      color: #fff;
      margin-bottom: 16px;
    }
    .remember {
      display: flex;
      align-items: center;
      gap: 8px;
      margin-bottom: 20px;
      font-size: 0.85rem;
      color: rgba(255,255,255,0.75);
    }
    button {
      width: 100%;
      padding: 14px;
      border-radius: 14px;
      border: none;
      background: linear-gradient(135deg,#ff2a8e,#ff5c5c);
      color: #fff;
      font-size: 1rem;
      font-weight: 700;
      cursor: pointer;
      transition: transform 0.2s ease, box-shadow 0.2s ease;
    }
    button:hover {
      transform: translateY(-1px);
      box-shadow: 0 12px 30px rgba(255, 42, 142, 0.35);
    }
    .notice,
    .errors {
      padding: 12px 14px;
      border-radius: 12px;
      margin-bottom: 18px;
      font-size: 0.9rem;
    }
    .notice {
      background: rgba(255, 255, 255, 0.06);
      border: 1px solid rgba(255, 255, 255, 0.12);
      color: rgba(255,255,255,0.85);
    }
    .errors {
      background: rgba(255, 64, 64, 0.15);
      border: 1px solid rgba(255, 64, 64, 0.5);
      padding: 12px 14px;
      color: #ffd9d9;
    }
    .back-link {
      margin-top: 18px;
      text-align: center;
    }
    .back-link a {
      color: rgba(255,255,255,0.75);
      text-decoration: none;
    }
    .back-link a:hover {
      color: #fff;
    }
  </style>
</head>
<body>
  <div class="card">
    <h1>Welcome back</h1>
    <p class="lead">Sign in to manage your PurePressureLive stream.</p>
    <?php if ($databaseIssueMessage): ?>
      <div class="notice">
        <?= htmlspecialchars($databaseIssueMessage) ?>
      </div>
    <?php endif; ?>
    <?php if ($errors): ?>
      <div class="errors">
        <ul>
          <?php foreach ($errors as $error): ?>
            <li><?= htmlspecialchars($error) ?></li>
          <?php endforeach; ?>
        </ul>
      </div>
    <?php endif; ?>
    <form method="post" action="login.php" autocomplete="on">
      <input type="hidden" name="csrf" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
      <label for="login">Email or username</label>
      <input id="login" name="login" type="text" value="<?= htmlspecialchars($loginValue) ?>" required>
      <label for="password">Password</label>
      <input id="password" name="password" type="password" required>
      <label class="remember">
        <input type="checkbox" name="remember" value="1" <?= $remember ? 'checked' : '' ?>>
        Remember me for 30 days
      </label>
      <button type="submit">Sign in</button>
    </form>
    <div class="back-link">
      <a href="index.html">← Back to homepage</a>
    </div>
  </div>
</body>
</html>
