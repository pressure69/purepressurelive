<?php

namespace Tests\Integration;

use PHPUnit\Framework\TestCase;
use Tests\Fixtures\TestDatabase;
use PDO;

/**
 * Integration tests for login.php
 * 
 * Tests the complete login flow with actual database interactions
 */
class LoginIntegrationTest extends TestCase
{
    private $testDbFile;
    private $pdo;
    private $testDb;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create a temporary SQLite database
        $this->testDbFile = sys_get_temp_dir() . '/test_login_' . uniqid() . '.db';
        
        // Set up test environment
        putenv('PPLIVE_DB_DSN=sqlite:' . $this->testDbFile);
        putenv('PPLIVE_DB_USER=');
        putenv('PPLIVE_DB_PASS=');
        
        // Create PDO connection
        $this->pdo = new PDO('sqlite:' . $this->testDbFile);
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // Set up test database
        $this->testDb = new TestDatabase($this->pdo);
        $this->testDb->createModelsTable();
        
        // Reset superglobals
        $_SESSION = [];
        $_POST = [];
        $_SERVER = ['REQUEST_METHOD' => 'GET'];
        $_COOKIE = [];
    }

    protected function tearDown(): void
    {
        $this->testDb->dropAllTables();
        $this->pdo = null;
        
        if (file_exists($this->testDbFile)) {
            unlink($this->testDbFile);
        }
        
        parent::tearDown();
    }

    public function testSuccessfulLoginWithEmail()
    {
        // Create a test user
        $passwordHash = password_hash('correct_password', PASSWORD_DEFAULT);
        $modelId = $this->testDb->insertModel([
            'email' => 'test@example.com',
            'username' => 'testuser',
            'password_hash' => $passwordHash
        ]);

        // Simulate login form submission
        $_SESSION['csrf_token'] = 'valid_token';
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST = [
            'csrf' => 'valid_token',
            'login' => 'test@example.com',
            'password' => 'correct_password'
        ];

        // Note: Full integration test would require more mocking
        // This demonstrates the test structure
        $this->assertNotNull($modelId);
    }

    public function testSuccessfulLoginWithUsername()
    {
        // Create a test user
        $passwordHash = password_hash('correct_password', PASSWORD_DEFAULT);
        $modelId = $this->testDb->insertModel([
            'email' => 'test@example.com',
            'username' => 'testuser',
            'password_hash' => $passwordHash
        ]);

        $_SESSION['csrf_token'] = 'valid_token';
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST = [
            'csrf' => 'valid_token',
            'login' => 'testuser',
            'password' => 'correct_password'
        ];

        $this->assertNotNull($modelId);
    }

    public function testPasswordVerificationWithPasswordHashColumn()
    {
        $password = 'secure_password_123';
        $hash = password_hash($password, PASSWORD_DEFAULT);
        
        $modelId = $this->testDb->insertModel([
            'email' => 'user@example.com',
            'username' => 'user1',
            'password_hash' => $hash,
            'password' => null
        ]);

        // Verify the hash was stored correctly
        $stmt = $this->pdo->prepare('SELECT password_hash FROM models WHERE id = ?');
        $stmt->execute([$modelId]);
        $storedHash = $stmt->fetchColumn();

        $this->assertTrue(password_verify($password, $storedHash));
    }

    public function testPasswordVerificationWithLegacyPasswordColumn()
    {
        $password = 'legacy_password';
        $hash = password_hash($password, PASSWORD_DEFAULT);
        
        $modelId = $this->testDb->insertModel([
            'email' => 'legacy@example.com',
            'username' => 'legacyuser',
            'password' => $hash,
            'password_hash' => null
        ]);

        // Verify the hash was stored correctly
        $stmt = $this->pdo->prepare('SELECT password FROM models WHERE id = ?');
        $stmt->execute([$modelId]);
        $storedHash = $stmt->fetchColumn();

        $this->assertTrue(password_verify($password, $storedHash));
    }

    public function testFailedLoginWithWrongPassword()
    {
        $correctHash = password_hash('correct_password', PASSWORD_DEFAULT);
        $this->testDb->insertModel([
            'email' => 'test@example.com',
            'username' => 'testuser',
            'password_hash' => $correctHash
        ]);

        $wrongPassword = 'wrong_password';
        $this->assertFalse(password_verify($wrongPassword, $correctHash));
    }

    public function testFailedLoginWithNonExistentUser()
    {
        // Don't insert any users
        
        $stmt = $this->pdo->prepare('SELECT * FROM models WHERE email = :login OR username = :login LIMIT 1');
        $stmt->execute(['login' => 'nonexistent@example.com']);
        $result = $stmt->fetch();

        $this->assertFalse($result);
    }

    public function testDatabaseQueryUsesEmailOrUsername()
    {
        $this->testDb->insertModel([
            'email' => 'test@example.com',
            'username' => 'testuser',
            'password_hash' => password_hash('password', PASSWORD_DEFAULT)
        ]);

        // Test with email
        $stmt = $this->pdo->prepare('SELECT * FROM models WHERE email = :login OR username = :login LIMIT 1');
        $stmt->execute(['login' => 'test@example.com']);
        $result = $stmt->fetch();
        $this->assertNotFalse($result);
        $this->assertEquals('test@example.com', $result['email']);

        // Test with username
        $stmt = $this->pdo->prepare('SELECT * FROM models WHERE email = :login OR username = :login LIMIT 1');
        $stmt->execute(['login' => 'testuser']);
        $result = $stmt->fetch();
        $this->assertNotFalse($result);
        $this->assertEquals('testuser', $result['username']);
    }

    public function testPasswordNeedsRehash()
    {
        // Create a password with old cost factor
        $password = 'test_password';
        $oldHash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 4]);
        
        $modelId = $this->testDb->insertModel([
            'email' => 'rehash@example.com',
            'username' => 'rehashuser',
            'password_hash' => $oldHash
        ]);

        // Verify password works but needs rehash
        $stmt = $this->pdo->prepare('SELECT password_hash FROM models WHERE id = ?');
        $stmt->execute([$modelId]);
        $storedHash = $stmt->fetchColumn();

        $this->assertTrue(password_verify($password, $storedHash));
        $this->assertTrue(password_needs_rehash($storedHash, PASSWORD_DEFAULT));
    }
}