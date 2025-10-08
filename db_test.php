<?php
require_once "config.php";

try {
    echo "✅ Connected successfully!<br>";
    $stmt = $pdo->query("SHOW TABLES");
    echo "Tables:<br>";
    while ($row = $stmt->fetch(PDO::FETCH_NUM)) {
        echo "- " . htmlspecialchars($row[0]) . "<br>";
    }
} catch (PDOException $e) {
    echo "❌ DB Error: " . $e->getMessage();
}
?>
