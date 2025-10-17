<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use PDO;
use PDOException;

/**
 * Unit tests for config.php
 * 
 * Tests the database configuration and connection logic including:
 * - Environment variable handling
 * - DSN construction with fallback values
 * - PDO connection options
 * - Error handling
 */
class ConfigTest extends TestCase
{
    private $originalEnv = [];

    protected function setUp(): void
    {
        parent::setUp();
        // Backup original environment variables
        $this->originalEnv = [
            'PPLIVE_DB_DSN' => getenv('PPLIVE_DB_DSN'),
            'PPLIVE_DB_USER' => getenv('PPLIVE_DB_USER'),
            'PPLIVE_DB_PASS' => getenv('PPLIVE_DB_PASS'),
            'PPLIVE_DB_HOST' => getenv('PPLIVE_DB_HOST'),
            'PPLIVE_DB_NAME' => getenv('PPLIVE_DB_NAME'),
            'PPLIVE_DB_CHARSET' => getenv('PPLIVE_DB_CHARSET'),
        ];
    }

    protected function tearDown(): void
    {
        // Restore original environment variables
        foreach ($this->originalEnv as $key => $value) {
            if ($value === false) {
                putenv($key);
            } else {
                putenv("$key=$value");
            }
        }
        parent::tearDown();
    }

    public function testDsnConstructionWithExplicitDsn()
    {
        putenv('PPLIVE_DB_DSN=mysql:host=testhost;dbname=testdb;charset=utf8mb4');
        putenv('PPLIVE_DB_USER=testuser');
        putenv('PPLIVE_DB_PASS=testpass');

        ob_start();
        $result = $this->getConfigVariables();
        ob_end_clean();

        $this->assertEquals('mysql:host=testhost;dbname=testdb;charset=utf8mb4', $result['dsn']);
    }

    public function testDsnConstructionWithComponentEnvVars()
    {
        putenv('PPLIVE_DB_DSN=');
        putenv('PPLIVE_DB_HOST=myhost');
        putenv('PPLIVE_DB_NAME=mydb');
        putenv('PPLIVE_DB_CHARSET=utf8mb4');
        putenv('PPLIVE_DB_USER=myuser');
        putenv('PPLIVE_DB_PASS=mypass');

        ob_start();
        $result = $this->getConfigVariables();
        ob_end_clean();

        $this->assertEquals('mysql:host=myhost;dbname=mydb;charset=utf8mb4', $result['dsn']);
    }

    public function testDsnConstructionWithDefaultHost()
    {
        putenv('PPLIVE_DB_DSN=');
        putenv('PPLIVE_DB_HOST=');
        putenv('PPLIVE_DB_NAME=customdb');
        putenv('PPLIVE_DB_CHARSET=utf8');
        putenv('PPLIVE_DB_USER=user');
        putenv('PPLIVE_DB_PASS=pass');

        ob_start();
        $result = $this->getConfigVariables();
        ob_end_clean();

        $this->assertStringContainsString('host=localhost', $result['dsn']);
    }

    public function testDsnConstructionWithDefaultDbName()
    {
        putenv('PPLIVE_DB_DSN=');
        putenv('PPLIVE_DB_HOST=localhost');
        putenv('PPLIVE_DB_NAME=');
        putenv('PPLIVE_DB_CHARSET=utf8');
        putenv('PPLIVE_DB_USER=user');
        putenv('PPLIVE_DB_PASS=pass');

        ob_start();
        $result = $this->getConfigVariables();
        ob_end_clean();

        $this->assertStringContainsString('dbname=purepressure', $result['dsn']);
    }

    public function testDsnConstructionWithDefaultCharset()
    {
        putenv('PPLIVE_DB_DSN=');
        putenv('PPLIVE_DB_HOST=localhost');
        putenv('PPLIVE_DB_NAME=testdb');
        putenv('PPLIVE_DB_CHARSET=');
        putenv('PPLIVE_DB_USER=user');
        putenv('PPLIVE_DB_PASS=pass');

        ob_start();
        $result = $this->getConfigVariables();
        ob_end_clean();

        $this->assertStringContainsString('charset=utf8mb4', $result['dsn']);
    }

    public function testDsnConstructionWithAllDefaults()
    {
        putenv('PPLIVE_DB_DSN=');
        putenv('PPLIVE_DB_HOST=');
        putenv('PPLIVE_DB_NAME=');
        putenv('PPLIVE_DB_CHARSET=');
        putenv('PPLIVE_DB_USER=user');
        putenv('PPLIVE_DB_PASS=pass');

        ob_start();
        $result = $this->getConfigVariables();
        ob_end_clean();

        $expectedDsn = 'mysql:host=localhost;dbname=purepressure;charset=utf8mb4';
        $this->assertEquals($expectedDsn, $result['dsn']);
    }

    public function testPdoOptionsAreCorrect()
    {
        putenv('PPLIVE_DB_DSN=mysql:host=localhost;dbname=test');
        putenv('PPLIVE_DB_USER=user');
        putenv('PPLIVE_DB_PASS=pass');

        ob_start();
        $result = $this->getConfigVariables();
        ob_end_clean();

        $this->assertArrayHasKey(PDO::ATTR_ERRMODE, $result['options']);
        $this->assertEquals(PDO::ERRMODE_EXCEPTION, $result['options'][PDO::ATTR_ERRMODE]);
        
        $this->assertArrayHasKey(PDO::ATTR_DEFAULT_FETCH_MODE, $result['options']);
        $this->assertEquals(PDO::FETCH_ASSOC, $result['options'][PDO::ATTR_DEFAULT_FETCH_MODE]);
        
        $this->assertArrayHasKey(PDO::ATTR_EMULATE_PREPARES, $result['options']);
        $this->assertFalse($result['options'][PDO::ATTR_EMULATE_PREPARES]);
    }

    public function testDbUserEnvironmentVariable()
    {
        putenv('PPLIVE_DB_DSN=mysql:host=localhost;dbname=test');
        putenv('PPLIVE_DB_USER=custom_user');
        putenv('PPLIVE_DB_PASS=pass');

        ob_start();
        $result = $this->getConfigVariables();
        ob_end_clean();

        $this->assertEquals('custom_user', $result['dbUser']);
    }

    public function testDbPassEnvironmentVariable()
    {
        putenv('PPLIVE_DB_DSN=mysql:host=localhost;dbname=test');
        putenv('PPLIVE_DB_USER=user');
        putenv('PPLIVE_DB_PASS=secret_password');

        ob_start();
        $result = $this->getConfigVariables();
        ob_end_clean();

        $this->assertEquals('secret_password', $result['dbPass']);
    }

    public function testEmptyPasswordIsAllowed()
    {
        putenv('PPLIVE_DB_DSN=mysql:host=localhost;dbname=test');
        putenv('PPLIVE_DB_USER=user');
        putenv('PPLIVE_DB_PASS=');

        ob_start();
        $result = $this->getConfigVariables();
        ob_end_clean();

        $this->assertEquals('', $result['dbPass']);
    }

    /**
     * Helper method to extract variables from config.php without actually connecting
     */
    private function getConfigVariables(): array
    {
        // Read config.php content
        $configContent = file_get_contents(__DIR__ . '/../../config.php');
        
        // Replace the PDO connection attempt with a mock to avoid actual connection
        $configContent = preg_replace(
            '/try \{.*?new PDO.*?\} catch.*?exit.*?\}/s',
            '$pdo = null;',
            $configContent
        );
        
        // Prepare variables to avoid undefined variable warnings
        $dsn = null;
        $dbUser = null;
        $dbPass = null;
        $options = [];
        $pdo = null;
        
        // Evaluate the modified config
        eval('?>' . $configContent);
        
        return [
            'dsn' => $dsn ?? null,
            'dbUser' => $dbUser ?? null,
            'dbPass' => $dbPass ?? null,
            'options' => $options ?? [],
            'pdo' => $pdo ?? null,
        ];
    }
}