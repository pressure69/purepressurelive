<?php

namespace Tests\Fixtures;

use PDO;

/**
 * Test database fixture helper
 * Provides methods to set up test data for integration tests
 */
class TestDatabase
{
    private $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Create the models table schema
     */
    public function createModelsTable(): void
    {
        $this->pdo->exec("
            CREATE TABLE IF NOT EXISTS models (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                email TEXT NOT NULL UNIQUE,
                username TEXT NOT NULL UNIQUE,
                password TEXT,
                password_hash TEXT,
                display_name TEXT,
                preview_image TEXT,
                is_live INTEGER DEFAULT 0,
                stream_key TEXT,
                token_goal INTEGER DEFAULT 0,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            )
        ");
    }

    /**
     * Insert a test model user
     */
    public function insertModel(array $data): int
    {
        $defaults = [
            'email' => 'test@example.com',
            'username' => 'testuser',
            'password_hash' => password_hash('password123', PASSWORD_DEFAULT),
            'display_name' => 'Test User',
            'stream_key' => 'test_stream_key_' . uniqid(),
            'token_goal' => 1000,
        ];

        $data = array_merge($defaults, $data);

        $stmt = $this->pdo->prepare("
            INSERT INTO models (email, username, password, password_hash, display_name, stream_key, token_goal)
            VALUES (:email, :username, :password, :password_hash, :display_name, :stream_key, :token_goal)
        ");

        $stmt->execute($data);

        return (int) $this->pdo->lastInsertId();
    }

    /**
     * Clear all data from models table
     */
    public function clearModels(): void
    {
        $this->pdo->exec("DELETE FROM models");
    }

    /**
     * Drop all tables
     */
    public function dropAllTables(): void
    {
        $this->pdo->exec("DROP TABLE IF EXISTS models");
    }
}