<?php

declare(strict_types=1);

session_start();
require_once __DIR__ . '/config.php';

// ---------------- SECURITY SETTINGS ----------------
ini_set('session.cookie_httponly', '1');
ini_set('session.use_strict_mode', '1');
header('X-Frame-Options: DENY');
header('X-Content-Type-Options: nosniff');

// CSRF Token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// If Remember Me cookie exists and no active session, try auto-login
if (!isset($_SESSION['user_id']) && isset($_COOKIE['remember_me'])) {
    try {
        $token = $_COOKIE['remember_me'];
        $stmt = $pdo->prepare('SELECT id, username, role FROM users WHERE remember_token = :token LIMIT 1');
        $stmt->execute(['token' => hash('sha256', $token)]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($user) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'] ?? 'fan';
            header('Location: ' . ($_SESSION['role'] === 'model' ? '/model_dashboard.php' : '/feed.php'));
            exit;
        }
    } catch (Throwable $t) {
        error_log('Auto-login failed: ' . $t->getMessage());
    }
}

// ---------------- LOGIN SUBMISSION ----------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'] ?? '')) {
        die('Invalid CSRF token');
    }

    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $remember = isset($_POST['remember']);

    if ($username && $password) {
        try {
            // Try "users" table first
            $stmt = $pdo->prepare('SELECT id, username, password, role FROM users WHERE username = :u LIMIT 1');
            $stmt->execute(['u' => $username]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            // If not found, check models/fans
            if (!$user) {
                $stmt = $pdo->prepare('SELECT id, username, password, "model" as role FROM models WHERE username = :u
                                       UNION
                                       SELECT id, username, password, "fan" as role FROM fans WHERE username = :u LIMIT 1');
                $stmt->execute(['u' => $username]);
                $user = $stmt->fetch(PDO::FETCH_ASSOC);
            }

            if ($user && password_verify($password, $user['password'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['role'] = $user['role'];
                session_regenerate_id(true);

                // Handle Remember Me
                if ($remember) {
                    $token = bin2hex(random_bytes(32));
                    $hashed = hash('sha256', $token);
                    if (isset($user['role']) && $user['role'] !== 'fan') {
                        $upd = $pdo->prepare('UPDATE models SET remember_token = :t WHERE id = :id');
                    } else {
                        $upd = $pdo->prepare('UPDATE fans SET remember_token = :t WHERE id = :id');
                    }
                    $upd->execute(['t' => $hashed, 'id' => $user['id']]);
                    setcookie('remember_me', $token, [
                        'expires' => time() + 60 * 60 * 24 * 30,
                        'path' => '/',
                        'secure' => true,
                        'httponly' => true,
                        'samesite' => 'Lax'
                    ]);
                }

                header('Location: ' . ($user['role'] === 'model' ? '/model_dashboard.php' : '/feed.php'));
                exit;
            } else {
                $error = 'Invalid username or password.';
            }
        } catch (Throwable $t) {
            error_log('Login error: ' . $t->getMessage());
            $error = 'Server error. Please try again.';
        }
    } else {
        $error = 'Please enter both fields.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>PurePressureLive | Login</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<style>
body {
    background: radial-gradient(circle at 50% 50%, #150000, #000000 90%);
    font-family: 'Arial Black', sans-serif;
    color: #fff;
    display: flex;
    justify-content: center;
    align-items: center;
    height: 100vh;
    overflow: hidden;
}
.login-container {
    background: rgba(40, 0, 0, 0.8);
    padding: 2.5em;
    border-radius: 10px;
    box-shadow: 0 0 25px 4px crimson;
    width: 340px;
}
h2 {
    text-align: center;
    margin-bottom: 1em;
    color: #ff0033;
}
input {
    width: 100%;
    padding: 10px;
    margin-bottom: 1em;
    border: none;
    border-radius: 4px;
    background: #220000;
    color: #fff;
}
button {
    width: 100%;
    padding: 10px;
    background: crimson;
    border: none;
    border-radius: 4px;
    color: #fff;
    font-weight: bold;
    cursor: pointer;
}
button:hover {
    background: #ff0033;
}
.error {
    color: #ff8080;
    text-align: center;
}
label {
    font-size: 0.9em;
}
</style>
</head>
<body>
<div class="login-container">
    <h2>Welcome to PurePressureLive</h2>
    <?php if (!empty($error)) : ?>
        <p class="error"><?= htmlspecialchars($error) ?></p>
    <?php endif; ?>
    <form method="POST">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
        <input type="text" name="username" placeholder="Username" required>
        <input type="password" name="password" placeholder="Password" required>
        <label><input type="checkbox" name="remember"> Remember Me</label>
        <button type="submit">Log In</button>
    </form>
</div>
</body>
</html>
