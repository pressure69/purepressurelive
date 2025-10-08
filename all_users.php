<?php /** * PurePressureLive - All Users Admin * Full user list 
 with actions for admin. */
require_once __DIR__ . '/config.php'; require_login();
// Restrict: only allow admin (adjust to your admin username)
if ($_SESSION['username'] !== 'admin') { die("❌ Access denied. 
    Admins only.");
}
// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') { $userId = 
    intval($_POST['user_id'] ?? 0); $action = $_POST['action'] 
    ?? ''; if ($userId > 0) {
        if ($action === 'deactivate') { $stmt = 
            $pdo->prepare("UPDATE users SET verified = 0 WHERE 
            id = ?"); $stmt->execute([$userId]); pplog("🚫 User 
            ID {$userId} deactivated by 
            {$_SESSION['username']}");
        } elseif ($action === 'ban') {
            $stmt = $pdo->prepare("UPDATE users SET banned = 1 
            WHERE id = ?"); $stmt->execute([$userId]); 
            pplog("⛔ User ID {$userId} banned by 
            {$_SESSION['username']}");
        } elseif ($action === 'delete') {
            $stmt = $pdo->prepare("SELECT id_file FROM users 
            WHERE id = ?"); $stmt->execute([$userId]); $file = 
            $stmt->fetchColumn(); if ($file && 
            file_exists(__DIR__ . "/uploads/ids/" . $file)) {
                unlink(__DIR__ . "/uploads/ids/" . $file);
            }
            $stmt = $pdo->prepare("DELETE FROM users WHERE id = 
            ?"); $stmt->execute([$userId]); pplog("🗑️ User ID 
            {$userId} deleted by {$_SESSION['username']}");
        }
    }
}
// Fetch all users
$stmt = $pdo->query("SELECT id, username, id_file, verified, 
banned, created_at FROM users ORDER BY created_at DESC"); 
$users = $stmt->fetchAll(); ?> <!DOCTYPE html> <html lang="en"> 
<head>
    <meta charset="UTF-8"> <title>All Users - 
    PurePressureLive</title> <style>
        body { font-family: Arial, sans-serif; background: 
        #111; color: #eee; padding: 20px; }
        h1 { color: #ff007f; } table { width: 100%; 
        border-collapse: collapse; margin-top: 20px; } th, td { 
        padding: 12px; border-bottom: 1px solid #444; 
        text-align: left; } th { background: #222; } tr:hover { 
        background: #222; } a.id-link { color: #ff007f; 
        text-decoration: none; } a.id-link:hover { 
        text-decoration: underline; } form { display: inline; } 
        button { background: #ff007f; border: none; padding: 
        6px 12px; border-radius: 6px; color: #fff; cursor: 
        pointer; margin-right: 6px; } button.deactivate { 
        background: #ffaa00; } button.ban { background: 
        #ff3333; }
        button.delete { background: #cc0000; } button:hover { 
        opacity: 0.9; }
    </style> </head> <body> <h1>👥 All Users</h1> <table> <tr> 
            <th>ID</th> <th>Username</th> <th>ID File</th> 
            <th>Status</th> <th>Registered</th> 
            <th>Actions</th>
        </tr> <?php foreach ($users as $user): ?> <tr> <td><?= 
            htmlspecialchars($user['id']) ?></td> <td><?= 
            htmlspecialchars($user['username']) ?></td> <td>
                <?php if ($user['id_file']): ?> <a 
                    class="id-link" href="uploads/ids/<?= 
                    htmlspecialchars($user['id_file']) ?>" 
                    target="_blank">View ID</a>
                <?php else: ?> ❌ None <?php endif; ?> </td> 
            <td>
                <?php if ($user['banned']): ?> ⛔ Banned <?php 
                elseif ($user['verified']): ?>
                    ✅ Verified <?php else: ?> ⏳ Pending <?php 
                endif; ?>
            </td> <td><?= htmlspecialchars($user['created_at'] 
            ?? 'Unknown') ?></td> <td>
                <form method="POST"> <input type="hidden" 
                    name="user_id" value="<?= $user['id'] ?>"> 
                    <button class="deactivate" type="submit" 
                    name="action" 
                    value="deactivate">Deactivate</button> 
                    <button class="ban" type="submit" 
                    name="action" value="ban">Ban</button> 
                    <button class="delete" type="submit" 
                    name="action" 
                    value="delete">Delete</button>
                </form> </td> </tr> <?php endforeach; ?> 
    </table>
</body>
</html>

