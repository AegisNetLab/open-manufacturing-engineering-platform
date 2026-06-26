<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Services\DatabaseBackupService;
use PDO;
use Tests\Support\TestCase;

final class DatabaseBackupServiceTest extends TestCase
{
    public function run(): void
    {
        if (!in_array('sqlite', PDO::getAvailableDrivers(), true)) {
            $this->assertTrue(true, 'SQLite PDO is optional for this lightweight test.');
            return;
        }

        $pdo = new PDO('sqlite::memory:');
        $service = new DatabaseBackupService($pdo);

        $this->assertSame('NULL', $service->sqlLiteral(null));
        $this->assertSame('42', $service->sqlLiteral(42));
        $this->assertSame('1', $service->sqlLiteral(true));
        $this->assertSame("'CNC-01'", $service->sqlLiteral('CNC-01'));
        $this->assertSame("'O''Brien'", $service->sqlLiteral("O'Brien"));
    }
}
