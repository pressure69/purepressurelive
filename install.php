<?php
require 'config.php';

/*
 This script sets up all PurePressureLive database tables:
 - models, fans, followers
 - messages, tips
 - streams, payments
 - transactions (audit trail)
 - analytics_daily (leaderboards/stats)
 - indexes for performance
*/

$tables = [

"CREATE TABLE IF NOT EXISTS models (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(100) DEFAULT NULL,
    password VARCHAR(255) NOT NULL,
    display_name VARCHAR(100) DEFAULT NULL,
    bio TEXT DEFAULT NULL,
    preview_image VARCHAR(255) DEFAULT NULL,
    is_live TINYINT(1) DEFAULT 0,
    stream_key VARCHAR(64) DEFAULT NULL,
    token_goal INT DEFAULT 1000,
    tokens_earned INT DEFAULT 0,
    followers_count INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

"CREATE TABLE IF NOT EXISTS fans (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(100) DEFAULT NULL,
    password VARCHAR(255) NOT NULL,
    display_name VARCHAR(100) DEFAULT NULL,
    tokens_balance INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

"CREATE TABLE IF NOT EXISTS followers (
    fan_id INT NOT NULL,
    model_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (fan_id, model_id),
    FOREIGN KEY (fan_id) REFERENCES fans(id) ON DELETE CASCADE,
    FOREIGN KEY (model_id) REFERENCES models(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

"CREATE TABLE IF NOT EXISTS messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    sender_type ENUM('fan','model') NOT NULL,
    sender_id INT NOT NULL,
    receiver_type ENUM('fan','model','room') NOT NULL,
    receiver_id INT NOT NULL,
    message TEXT NOT NULL,
    is_tip TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

"CREATE TABLE IF NOT EXISTS tips (
    id INT AUTO_INCREMENT PRIMARY KEY,
    fan_id INT NOT NULL,
    model_id INT NOT NULL,
    amount INT NOT NULL,
    message VARCHAR(255) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (fan_id) REFERENCES fans(id) ON DELETE CASCADE,
    FOREIGN KEY (model_id) REFERENCES models(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

"CREATE TABLE IF NOT EXISTS streams (
    id INT AUTO_INCREMENT PRIMARY KEY,
    model_id INT NOT NULL,
    title VARCHAR(150) DEFAULT NULL,
    category VARCHAR(100) DEFAULT NULL,
    started_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    ended_at TIMESTAMP NULL,
    viewers_peak INT DEFAULT 0,
    tokens_earned INT DEFAULT 0,
    FOREIGN KEY (model_id) REFERENCES models(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

"CREATE TABLE IF NOT EXISTS payments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    fan_id INT NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    currency VARCHAR(10) DEFAULT 'USD',
    provider VARCHAR(50) NOT NULL,
    transaction_id VARCHAR(255) NOT NULL,
    status ENUM('pending','completed','failed') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (fan_id) REFERENCES fans(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

"CREATE TABLE IF NOT EXISTS transactions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    type ENUM('tip','payment','payout') NOT NULL,
    fan_id INT DEFAULT NULL,
    model_id INT DEFAULT NULL,
    amount DECIMAL(10,2) NOT NULL,
    reference_id INT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (fan_id) REFERENCES fans(id) ON DELETE SET NULL,
    FOREIGN KEY (model_id) REFERENCES models(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

"CREATE TABLE IF NOT EXISTS analytics_daily (
    id INT AUTO_INCREMENT PRIMARY KEY,
    date DATE NOT NULL,
    total_models INT DEFAULT 0,
    total_fans INT DEFAULT 0,
    total_tokens DECIMAL(12,2) DEFAULT 0,
    top_model_id INT DEFAULT NULL,
    top_model_tokens INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (top_model_id) REFERENCES models(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

"CREATE INDEX idx_models_live ON models(is_live)",
"CREATE INDEX idx_messages_receiver ON messages(receiver_type, receiver_id)",
"CREATE INDEX idx_tips_model ON tips(model_id)",
"CREATE INDEX idx_streams_model ON streams(model_id)",
"CREATE INDEX idx_transactions_model ON transactions(model_id)",
"CREATE INDEX idx_analytics_date ON analytics_daily(date)"
];

foreach ($tables as $sql) {
    try {
        $pdo->exec($sql);
        echo "✅ Executed: " . strtok($sql, "(") . "<br>";
    } catch (Exception $e) {
        echo "❌ Error: " . $e->getMessage() . "<br>";
    }
}

echo "<hr><b>🎉 Database install/upgrade complete!</b>";
