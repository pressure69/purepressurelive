<?php

namespace Tests\Integration;

use PHPUnit\Framework\TestCase;
use PDO;
use PDOException;

/**
 * Integration tests for config.php
 * 
 * Tests actual database connection behavior.
 * Note: These tests require a test database to be configured.
 * 
 * @SuppressWarnings(PHPMD.UnusedLocalVariable)
 */
class ConfigIntegrationTest extends TestCase
{
    private $testDbFile;

    protected function setUp(): void
    {
        parent::setUp();
        // Use SQLite for integration tests to avoid requiring MySQL
        $this->testDbFile = sys_get_temp_dir() . '/test_purepressure_' . uniqid() . '.db';
        
        // Set up test environment
        putenv('PPLIVE_DB_DSN=sqlite:' . $this->testDbFile);
        putenv('PPLIVE_DB_USER=');
        putenv('PPLIVE_DB_PASS=');
    }

    protected function tearDown(): void
    {
        if (file_exists($this->testDbFile)) {
            unlink($this->testDbFile);
        }
        parent::tearDown();
    }

    public function testConfigCreatesValidPdoConnection()
    {
        ob_start();
        require __DIR__ . '/../../config.php';
        ob_end_clean();

        $this->assertInstanceOf(PDO::class, $pdo);
        $this->assertEquals(PDO::ERRMODE_EXCEPTION, $pdo->getAttribute(PDO::ATTR_ERRMODE));
        $this->assertEquals(PDO::FETCH_ASSOC, $pdo->getAttribute(PDO::ATTR_DEFAULT_FETCH_MODE));
    }

    public function testConfigHandlesInvalidConnection()
    {
        // Run config.php in a separate PHP process to capture exit status/output
        $configPath = realpath(__DIR__ . '/../../config.php');
        $code = <<<PHP
<?php
putenv('PPLIVE_DB_DSN=mysql:host=nonexistent-host-12345;dbname=test');
putenv('PPLIVE_DB_USER=invalid_user');
putenv('PPLIVE_DB_PASS=invalid_pass');
require '$configPath';
echo "SHOULD_NOT_REACH";
PHP;
        $tmp = tempnam(sys_get_temp_dir(), 'cfgfail_') . '.php';
        file_put_contents($tmp, $code);
        $cmd = escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg($tmp) . ' 2>&1';
        exec($cmd, $out, $codeExit);
        @unlink($tmp);
        $output = implode("\n", $out);
        $this->assertNotEquals(0, $codeExit, "Process should exit non-zero on connection failure");
        $this->assertStringContainsString('Database connection failed', $output);
        $this->assertStringNotContainsString('SHOULD_NOT_REACH', $output);
    }

    public function testPdoCanExecuteQueries()
    {
        ob_start();
        require __DIR__ . '/../../config.php';
        ob_end_clean();

        // Create a test table
        $pdo->exec('CREATE TABLE test_table (id INTEGER PRIMARY KEY, name TEXT)');
        
        // Insert data
        $stmt = $pdo->prepare('INSERT INTO test_table (name) VALUES (?)');
        $stmt->execute(['test_name']);
        
        // Query data
        $stmt = $pdo->query('SELECT * FROM test_table');
        $results = $stmt->fetchAll();
        
        $this->assertCount(1, $results);
        $this->assertEquals('test_name', $results[0]['name']);
    }

    public function testPdoUsesAssociativeFetchMode()
    {
        ob_start();
        require __DIR__ . '/../../config.php';
        ob_end_clean();

        $pdo->exec('CREATE TABLE test_fetch (id INTEGER PRIMARY KEY, value TEXT)');
        $pdo->exec("INSERT INTO test_fetch (value) VALUES ('test')");
        
        $stmt = $pdo->query('SELECT * FROM test_fetch');
        $row = $stmt->fetch();
        
        $this->assertIsArray($row);
        $this->assertArrayHasKey('id', $row);
        $this->assertArrayHasKey('value', $row);
        $this->assertArrayNotHasKey(0, $row); // Should not have numeric keys
    }

    public function testPdoThrowsExceptionsOnError()
    {
        ob_start();
        require __DIR__ . '/../../config.php';
        ob_end_clean();

        $this->expectException(PDOException::class);
        
        // Try to query a non-existent table
        $pdo->query('SELECT * FROM non_existent_table');
    }
}