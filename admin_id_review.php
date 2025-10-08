<?php
// admin_id_review.php — Review & approve ID verifications
session_start();
require_once __DIR__ . '/config.php';

// 🚨 Protect: only allow special admin login
if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    echo "Access denied. Admins only.";
    exit;
}

// Handle approve/reject
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id'], $_POST['action'])) {
    $id = (int)$_POST['id'];
    $action = $_POST['action'] === 'approve' ? 'approved' : 'rejected';
    $notes  = $_POST['notes'] ?? '';
    $stmt = $pdo->prepare("UPDATE id_verifications SET status=?, notes=? WHERE id=?");
    $stmt->execute([$action, $notes, $id]);
}

// Fetch all pending
$q = $pdo->query("SELECT * FROM id_verifications ORDER BY created_at DESC");
$rows = $q->fetchAll(PDO::FETCH_ASSOC);
$brand = "PurePressureLive";
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<title><?=$brand?> • Admin ID Review</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link href="https://fonts.googleapis.com/css2?family=Anton&family=Poppins:wght@400;600&display=swap" rel="stylesheet">
<style>
:root{--bg:#0f0f12;--panel:#14141a;--ink:#fff;--muted:#9aa0a6;--accent:#d9153b;--line:#23232a}
body{margin:0;background:var(--bg);color:var(--ink);font-family:Poppins,Arial}
.wrap{max-width:1100px;margin:0 auto;padding:18px}
h1{font-family:Anton;margin:0 0 12px 0}
.card{background:var(--panel);border:1px solid var(--line);border-radius:16px;padding:16px;margin-bottom:14px}
.row{display:flex;gap:10px;align-items:center;flex-wrap:wrap}
button{border:1px solid var(--line);background:#0d0d10;color:#fff;border-radius:10px;padding:8px 12px;cursor:pointer}
input{border:1px solid var(--line);background:#0d0d10;color:#fff;border-radius:8px;padding:6px 10px;width:200px}
.status{padding:4px 8px;border-radius:8px;font-size:12px}
.pending{background:#30200c;color:#ffb347}
.approved{background:#0e1a12;color:#7fff9f}
.rejected{background:#2a0c0c;color:#ff7b7b}
</style>
</head>
<body>
<div class="wrap">
  <h1><?=$brand?> • ID Verification Review</h1>

  <?php foreach($rows as $r): ?>
  <div class="card">
    <div><b>User:</b> <?=htmlspecialchars($r['username'] ?: 'unknown')?> (<?=$r['user_type']?>)</div>
    <div><b>Submitted:</b> <?=$r['created_at']?></div>
    <div><b>Doc Type:</b> <?=$r['doc_type']?></div>
    <div><b>Status:</b> <span class="status <?=$r['status']?>"><?=$r['status']?></span></div>
    <div style="margin-top:8px"><a target="_blank" href="<?=htmlspecialchars($r['file_path'])?>">📄 View Document</a></div>
    <form method="post" class="row" style="margin-top:10px">
      <input type="hidden" name="id" value="<?=$r['id']?>">
      <input type="text" name="notes" placeholder="Notes (optional)" value="<?=htmlspecialchars($r['notes'] ?? '')?>">
      <button name="action" value="approve">✅ Approve</button>
      <button name="action" value="reject">❌ Reject</button>
    </form>
  </div>
  <?php endforeach; ?>
</div>
</body>
</html>
