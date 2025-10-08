<?php
error_reporting(E_ALL);
ini_set("display_errors", 1);

echo "<h1>PurePressureLive Debug</h1><hr>";

require 'config.php';
echo "<p>✅ config.php loaded</p>";

session_start();
echo "<p>✅ Session started</p>";

$stmt = $pdo->query("SELECT NOW() AS nowtime");
$row = $stmt->fetch();
echo "<p>✅ DB connection OK. Server time: ".$row['nowtime']."</p>";

echo "<p>✅ Debug complete</p>";
