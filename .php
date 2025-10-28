<?php session_start(); if(!isset($_SESSION['user_id'])){header('Location: login.php');exit;} ?>
<!DOCTYPE html>
<html><head><meta charset="UTF-8"><title>Messages</title></head>
<body style="background:#000;color:#fff;">
<h2><?=ucfirst(basename(__FILE__, '.php'))?></h2>
<p>Messages coming soon...</p>
</body></html>
