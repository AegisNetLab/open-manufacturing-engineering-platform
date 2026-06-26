<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Helpers\RateLimiter;
use Tests\Support\TestCase;

final class RateLimiterTest extends TestCase
{
    public function run(): void
    {
        $dir = sys_get_temp_dir() . '/openmep_rate_limiter_' . bin2hex(random_bytes(4));
        $limiter = new RateLimiter($dir, 2, 10);

        $first = $limiter->attempt('127.0.0.1', 'POST /api/projects/create.php', 1000);
        $second = $limiter->attempt('127.0.0.1', 'POST /api/projects/create.php', 1001);
        $third = $limiter->attempt('127.0.0.1', 'POST /api/projects/create.php', 1002);
        $afterReset = $limiter->attempt('127.0.0.1', 'POST /api/projects/create.php', 1011);

        $this->assertTrue($first['allowed']);
        $this->assertSame(1, $first['remaining']);
        $this->assertTrue($second['allowed']);
        $this->assertSame(0, $second['remaining']);
        $this->assertFalse($third['allowed']);
        $this->assertSame(8, $third['retry_after']);
        $this->assertTrue($afterReset['allowed']);
        $this->assertSame(1, $afterReset['remaining']);

        $otherScope = $limiter->attempt('127.0.0.1', 'GET /api/projects/list.php', 1002);
        $this->assertTrue($otherScope['allowed']);

        $limiter->clear();
        @rmdir($dir);
    }
}
