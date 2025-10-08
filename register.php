<?php
/**
 * PurePressureLive Register
 * - Secure registration with ID upload
 * - Stores hashed passwords
 * - Logs all activity
 */

require_once __DIR__ . '/config.php';

// If already logged in → redirect
if (!empty($_SESSION['user_id'])) {
    header("Location: /model_dashboard.php");
    exit;
}

$error = null;
$success = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username   = trim($_POST['username'] ?? '');
    $password   = $_POST['password'] ?? '';
    $confirm    = $_POST['confirm'] ?? '';

    // File upload
    $idFile = $_FILES['id_upload'] ?? null;

    if ($username && $password && $confirm && $idFile) {
        if ($password !== $confirm) {
            $error = "Passwords do not match.";
        } elseif (strlen($password) < 6) {
            $error = "Password must be at least 6 characters.";
        } elseif ($idFile['error'] !== UPLOAD_ERR_OK) {
            $error = "Error uploading ID file.";
        } else {
            // Ensure upload dir exists
            $uploadDir = __DIR__ . '/uploads/ids';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0775, true);
            }

            $ext = pathinfo($idFile['name'], PATHINFO_EXTENSION);
            $safeName = preg_replace('/[^a-zA-Z0-9_-]/', '_', $username);
            $targetFile = $uploadDir . '/' . $safeName . "_id." . strtolower($ext);

            if (!move_uploaded_file($idFile['tmp_name'], $targetFile)) {
                $error = "Failed to save ID file.";
            } else {
                // Insert user
                $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
                $stmt->execute([$username]);
                if ($stmt->fetch()) {
                    $error = "Username already taken.";
                } else {
                    $hash = password_hash($password, PASSWORD_DEFAULT);
                    $stmt = $pdo->prepare("INSERT INTO users (username, password, id_file, verified) VALUES (?, ?, ?, 0)");
                    $stmt->execute([$username, $hash, basename($targetFile)]);
                    pplog("🆕 Registration success for {$username} (ID uploaded: " . basename($targetFile) . ")");
                    $success = "Registration successful! Your ID will be reviewed before activation.";
                }
            }
        }
    } else {
        $error = "All fields are required, including ID upload.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Register - PurePressureLive</title>
    <style>
        body { font-family: Arial, sans-serif; background: #111; color: #eee; display: flex; justify-content: center; align-items: center; height: 100vh; }
        .register-box { background: #222; padding: 30px; border-radius: 12px; width: 360px; }
        h1 { color: #ff007f; text-align: center; }
        input, button { width: 100%; padding: 10px; margin: 10px 0; border-radius: 6px; border: none; }
        input[type=file] { background: #333; color: #eee; }
        button { background: #ff007f; color: #fff; cursor: pointer; }
        button:hover { background: #ff3399; }
        .error { color: #ff4d4d; margin-top: 10px; text-align: center; }
        .success { color: #4dff4d; margin-top: 10px; text-align: center; }
        .note { text-align: center; margin-top: 15px; }
        a { color: #ff007f; text-decoration: none; }
        a:hover { text-decoration: underline; }
    </style>
</head>
<body>
    <div class="register-box">
        <h1>Join PurePressureLive</h1>
        <form method="POST" enctype="multipart/form-data">
            <input type="text" name="username" placeholder="Choose a username" required>
            <input type="password" name="password" placeholder="Password" required>
            <input type="password" name="confirm" placeholder="Confirm password" required>
            <label for="id_upload">Upload Valid ID (required):</label>
            <input type="file" name="id_upload" id="id_upload" accept="image/*,.pdf" required>
            <button type="submit">Register</button>
        </form>
        <?php if ($error): ?>
            <div class="error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        <?php if ($success): ?>
            <div class="success"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>
        <div class="note">Already have an account? <a href="login.php">Login</a></div>
    </div>
</body>
</html>
