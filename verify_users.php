<?php /** * PurePressureLive - Verify Users * Admin-only page 
 to review ID uploads and approve/reject accounts. */
require_once __DIR__ . '/config.php'; require_login();
// Restrict: only allow admin (you can hardcode your username 
// here)
if ($_SESSION['username'] !== 'admin') { die("❌ Access denied. 
    Admins only.");
}
// Handle Approve / Reject actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') { $userId = 
    intval($_POST['user_id'] ?? 0); $action = $_POST['action'] 
    ?? ''; if ($userId > 0) {
        if ($action === 'approve') { $stmt = 
            $pdo->prepare("UPDATE users SET verified = 1 WHERE 
            id = ?"); $stmt->execute([$userId]); pplog("✅ User 
            ID {$userId} approved by {$_SESSION['username']}");
        } elseif ($action === 'reject') {
            // Optionally remove ID file when rejecting
            $stmt = $pdo->prepare("SELECT id_file FROM users 
            WHERE id = ?"); $stmt->execute([$userId]); $file = 
            $stmt->fetchColumn(); if ($file && 
            file_exists(__DIR__ . "/uploads/ids/" . $file)) {
                unlink(__DIR__ . "/uploads/ids/" . $file);
            }
            $stmt = $pdo->prepare("DELETE FROM users WHERE id = 
            ?"); $stmt->execute([$userId]); pplog("❌ User ID 
            {$userId} rejected by {$_SESSION['username']}");
        }
    }
}
// Fetch unverified users
$stmt = $pdo->query("SELECT id, username, id_file, created_at 
FROM users WHERE verified = 0 ORDER BY created_at DESC"); 
$pending = $stmt->fetchAll(); ?> <!DOCTYPE html> <html 
lang="en"> <head>
    <meta charset="UTF-8"> <title>Verify Users - 
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
        pointer; margin-right: 6px; } button.reject { 
        background: #ff3333; } button:hover { opacity: 0.9; }
    </style> </head> <body> <h1>🛂 Verify New Users</h1> <?php 
    if (empty($pending)): ?>
        <p>No pending users 🎉</p> <?php else: ?> <table> <tr> 
                <th>ID</th> <th>Username</th> <th>ID File</th> 
                <th>Registered</th> <th>Action</th>
            </tr> <?php foreach ($pending as $user): ?> <tr> 
                <td><?= htmlspecialchars($user['id']) ?></td> 
                <td><?= htmlspecialchars($user['username']) 
                ?></td> <td>
                    <?php if ($user['id_file']): ?> <a 
                        class="id-link" href="uploads/ids/<?= 
                        htmlspecialchars($user['id_file']) ?>" 
                        target="_blank">View ID</a>
                    <?php else: ?> ❌ None <?php endif; ?> 
                </td> <td><?= 
                htmlspecialchars($user['created_at'] ?? 
                'Unknown') ?></td> <td>
                    <form method="POST" 
                    style="display:inline;">
                        <input type="hidden" name="user_id" 
                        value="<?= $user['id'] ?>"> <button 
                        type="submit" name="action" 
                        value="approve">Approve</button>
                    </form> <form method="POST" 
                    style="display:inline;">
                        <input type="hidden" name="user_id" 
                        value="<?= $user['id'] ?>"> <button 
                        class="reject" type="submit" 
                        name="action" 
                        value="reject">Reject</button>
                    </form> </td> </tr> <?php endforeach; ?> 
        </table>
    <?php endif; ?> </body>
</html>
