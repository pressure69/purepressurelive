<?php

declare(strict_types=1);

require_once __DIR__ . '/config.php';
error_reporting(E_ALL);
ini_set('display_errors', '1');

/** @var PDO $pdo */

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $role = $_POST['role'] ?? 'fan';

    if ($username && $password) {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare('INSERT INTO users (username, password, role) VALUES (?, ?, ?)');
        try {
            $stmt->execute([$username, $hash, $role]);
            echo "<p style='color:lime'>✅ Account created as <strong>$role</strong>. <a href='/login.php'>Login</a></p>";
            exit;
        } catch (PDOException $e) {
            if ($e->getCode() === '23000') {
                echo "<p style='color:red'>Username already exists.</p>";
            } else {
                echo "<p style='color:red'>Database error: {$e->getMessage()}</p>";
            }
        }
    } else {
        echo "<p style='color:red'>All fields are required.</p>";
    }
}
?>
<!DOCTYPE html>
<html lang="en"><head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Register - PurePressureLive</title>
<style>
body{background:#0b0b0f;color:#fff;font-family:sans-serif;display:flex;justify-content:center;align-items:center;height:100vh;margin:0;}
form{display:flex;flex-direction:column;width:300px;gap:10px;}
input,select{padding:10px;border-radius:6px;border:1px solid #ff2b70;background:#141414;color:#fff;}
button{padding:10px;border:none;border-radius:6px;background:#ff2b70;color:#fff;cursor:pointer;}
button:hover{background:#ff5891;}
a{color:#ff2b70;text-decoration:none;}
</style></head><body>
<form method="POST">
<h2>Create Account</h2>
<input type="text" name="username" placeholder="Username" required>
<input type="password" name="password" placeholder="Password" required>
<select name="role" required>
  <option value="fan">Fan</option>
  <option value="model">Model</option>
</select>
<button type="submit">Register</button>
<p><a href="/login.php">Already have an account?</a></p>
</form>
</body></html>
