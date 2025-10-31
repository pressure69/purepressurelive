<?php

declare(strict_types=1);

namespace PurePressureLive\Front;

use PHPUnit\Framework\TestCase;

final class BootstrapDbTest extends TestCase
{
    public function testDbReturnsPdo(): void
    {
        require __DIR__ . '/../../bootstrap/db.php';
        $pdo = db();
        $this->assertInstanceOf(\PDO::class, $pdo);
        $this->assertSame($pdo, db()); // memoized
    }
}
