<?php
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>PurePressureLive</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="icon" type="image/png" href="assets/img/favicon.png">
  <style>
    * {margin:0; padding:0; box-sizing:border-box;}
    body, html {height:100%; font-family: Arial, sans-serif; color:#fff; overflow:hidden;}

    /* Fullscreen video */
    #bg-video {
      position: fixed;
      top:0; left:0;
      width:100%; height:100%;
      object-fit: cover;
      z-index:-1;
    }

    /* Overlay */
    .overlay {
      position: fixed;
      top:0; left:0;
      width:100%; height:100%;
      background: rgba(0,0,0,0.6);
      display:flex;
      flex-direction:column;
      align-items:center;
      justify-content:center;
      text-align:center;
      padding:20px;
    }

    h1 {
      font-size:3em;
      margin-bottom:20px;
      letter-spacing:2px;
    }

    .btn-group {
      display:flex;
      gap:20px;
    }

    .btn {
      padding:12px 24px;
      font-size:1.2em;
      font-weight:bold;
      color:#fff;
      background:#e60023;
      border:none;
      border-radius:6px;
      cursor:pointer;
      text-decoration:none;
      transition:background 0.3s;
    }
    .btn:hover {
      background:#ff0033;
    }

    footer {
      position:fixed;
      bottom:15px;
      font-size:0.9em;
      opacity:0.8;
    }
  </style>
</head>
<body>
  <video autoplay muted loop id="bg-video">
    <source src="assets/video/bg.mp4" type="video/mp4">
    Your browser does not support HTML5 video.
  </video>

  <div class="overlay">
    <h1>🔥 PurePressureLive 🔥</h1>
    <div class="btn-group">
      <a href="login.php" class="btn">Login</a>
      <a href="register.php" class="btn">Register</a>
    </div>
    <footer>
      &copy; <?php echo date("Y"); ?> PurePressureLive. All rights reserved.
    </footer>
  </div>
</body>
</html>
