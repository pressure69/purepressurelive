<?php

// PurePressureLive Fan Dashboard
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'fan') {
    header('Location: login.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Fan Dashboard - PurePressureLive</title>
<style>
body{background:#000;color:#fff;font-family:Arial;text-align:center;}
a{color:#900;margin:5px;text-decoration:none;}
.container{margin-top:40px;}
.card{background:#111;border:1px solid #900;border-radius:10px;padding:20px;margin:10px;display:inline-block;width:250px;}
.card h3{color:#f66;}
button{background:#900;color:#fff;padding:10px;border:none;border-radius:5px;cursor:pointer;}
button:hover{background:#b00;}
</style>
</head>
<body>
<h2>Welcome, <?=$_SESSION['username']?>!</h2>
<nav>
  <a href="public_chat.php">💬 Public Chat</a>
  <a href="logout.php">🚪 Logout</a>
</nav>
<div class="container">
  <h3>Your Favorite Models</h3>
  <div class="card">
    <h3>Model: BellaLuxe</h3>
    <p>Live Now 💖</p>
    <button onclick="window.location='model_stream.php?id=1'">Watch</button>
  </div>
  <div class="card">
    <h3>Model: ScarletFlame</h3>
    <p>Offline 💤</p>
    <button disabled>Watch</button>
  </div>
  <div class="card">
    <h3>Model: IvyRose</h3>
    <p>Live Now 🔥</p>
    <button onclick="window.location='model_stream.php?id=3'">Watch</button>
  </div>
</div>
</body>
</html>
