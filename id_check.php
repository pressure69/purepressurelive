<?php
// id_check.php — supports fans & models verification
session_start();
require_once __DIR__ . '/config.php';

// Ensure table
$pdo->exec("CREATE TABLE IF NOT EXISTS id_verifications (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_type ENUM('fan','model') NOT NULL,
  user_id INT NULL,
  username VARCHAR(100) NULL,
  doc_type VARCHAR(50) NOT NULL,
  file_path VARCHAR(255) NOT NULL,
  status ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  notes VARCHAR(255) NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

// Determine identity
$type = ($_GET['type'] ?? $_POST['type'] ?? 'model');
if(!in_array($type,['fan','model'])) $type='model';
$uid  = null; $uname = null;
if($type==='model' && isset($_SESSION['model_id'])){
    $uid = (int)$_SESSION['model_id'];
    $stmt = $pdo->prepare("SELECT username FROM models WHERE id=?");
    $stmt->execute([$uid]); $uname = $stmt->fetchColumn();
}
if($type==='fan' && isset($_SESSION['fan_id'])){
    $uid = (int)$_SESSION['fan_id'];
    $stmt = $pdo->prepare("SELECT username FROM users WHERE id=?");
    $stmt->execute([$uid]); $uname = $stmt->fetchColumn();
}

$msg = '';
if($_SERVER['REQUEST_METHOD']==='POST' && isset($_FILES['idfile'])){
    $doc = $_POST['doc_type'] ?? 'government_id';
    $f   = $_FILES['idfile'];
    if($f['error']===UPLOAD_ERR_OK){
        $ext = strtolower(pathinfo($f['name'], PATHINFO_EXTENSION));
        if(!in_array($ext,['jpg','jpeg','png','pdf','webp'])) $ext='jpg';
        $name = $type.'_id_'.($uid?:'guest').'_' . time().'.'.$ext;
        $dst = __DIR__ . '/uploads/id_verifications/'.$name;
        if(move_uploaded_file($f['tmp_name'],$dst)){
            $rel = 'uploads/id_verifications/'.$name;
            $ins = $pdo->prepare("INSERT INTO id_verifications (user_type,user_id,username,doc_type,file_path) VALUES (?,?,?,?,?)");
            $ins->execute([$type,$uid,$uname,$doc,$rel]);
            $msg = "✅ Submitted. Status: pending. We’ll email you after review.";
        } else {
            $msg = "❌ Upload failed.";
        }
    } else {
        $msg = "❌ File error (".$f['error'].")";
    }
}

// Fetch latest status
$stat = null;
if($uid){
    $q = $pdo->prepare("SELECT status,created_at,notes FROM id_verifications WHERE user_type=? AND user_id=? ORDER BY id DESC LIMIT 1");
    $q->execute([$type,$uid]); $stat = $q->fetch(PDO::FETCH_ASSOC);
}
$brand="PurePressureLive";
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<title><?=$brand?> • ID Verification</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link href="https://fonts.googleapis.com/css2?family=Anton&family=Poppins:wght@400;600&display=swap" rel="stylesheet">
<style>
:root{--bg:#0f0f12;--panel:#14141a;--ink:#fff;--muted:#9aa0a6;--accent:#d9153b;--line:#23232a}
body{margin:0;background:var(--bg);color:var(--ink);font-family:Poppins,Arial}
.wrap{max-width:900px;margin:0 auto;padding:18px}
.card{background:var(--panel);border:1px solid var(--line);border-radius:16px;padding:16px}
h1{font-family:Anton;margin:0 0 12px 0}
label{color:var(--muted);font-size:13px}
input,select,button{border:1px solid var(--line);background:#0d0d10;color:#fff;border-radius:10px;padding:10px 12px}
.row{display:flex;gap:10px;align-items:center;flex-wrap:wrap}
.note{color:var(--muted);font-size:12px}
.alert{margin:10px 0;padding:10px;border-radius:10px}
.success{background:#0e1a12;border:1px solid #1f3828}
.warn{background:#1a0e10;border:1px solid #382028}
</style>
</head>
<body>
<div class="wrap">
  <h1>ID Verification <span style="background:var(--accent);padding:4px 10px;border-radius:999px;font-size:12px;"><?=$type?></span></h1>
  <?php if($msg): ?>
    <div class="alert <?=$msg[0]=='✅'?'success':'warn'?>"><?=$msg?></div>
  <?php endif; ?>

  <div class="card">
    <form method="post" enctype="multipart/form-data" class="row">
      <input type="hidden" name="type" value="<?=$type?>">
      <div>
        <label>Document Type</label><br>
        <select name="doc_type">
          <option value="government_id">Government ID</option>
          <option value="passport">Passport</option>
          <option value="driver_license">Driver License</option>
        </select>
      </div>
      <div>
        <label>Upload (JPG/PNG/PDF)</label><br>
        <input type="file" name="idfile" accept=".jpg,.jpeg,.png,.pdf,.webp" required>
      </div>
      <button>Submit for Review</button>
    </form>
    <div class="note">We never show your ID to other users. Only used for age & compliance checks.</div>
  </div>

  <div class="card" style="margin-top:14px">
    <h3>Your Latest Status</h3>
    <?php if($stat): ?>
      <div>Status: <b><?=$stat['status']?></b></div>
      <div>Submitted: <?=$stat['created_at']?></div>
      <div>Notes: <?=$stat['notes'] ?: '—'?></div>
    <?php else: ?>
      <div>No verification found for this <?=$type?> account.</div>
    <?php endif; ?>
  </div>
</div>
</body>
</html>
