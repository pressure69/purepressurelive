<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class HealthCheckTest extends TestCase
{
    public function testPhpIsRunning(): void
    {
        $this->assertTrue(PHP_VERSION_ID > 80000, 'PHP 8+ expected');
    }
}
