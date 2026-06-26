<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Services\SystemService;
use Tests\Support\TestCase;

final class SystemServiceTest extends TestCase
{
    public function run(): void
    {
        $service = new SystemService(null);
        $health = $service->health();

        $this->assertSame('Open Manufacturing Engineering Platform', $health['application']);
        $this->assertSame('0.1.0-dev', $health['version']);
        $this->assertTrue(isset($health['checks']['php']), 'PHP health check is missing.');
        $this->assertTrue(isset($health['checks']['extensions']), 'Extension health check is missing.');
        $this->assertTrue(isset($health['checks']['database']), 'Database health check is missing.');
        $this->assertSame('skipped', $health['checks']['database']['status']);
        $this->assertNotEmpty((string) $health['checked_at']);
    }
}
