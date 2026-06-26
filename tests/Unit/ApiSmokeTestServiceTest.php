<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Services\ApiSmokeTestService;
use Tests\Support\TestCase;

final class ApiSmokeTestServiceTest extends TestCase
{
    public function run(): void
    {
        $this->testNormalizeBaseUrl();
        $this->testBuildUrl();
        $this->testDefaultChecks();
    }

    private function testNormalizeBaseUrl(): void
    {
        $this->assertSame(
            'http://localhost/openmep',
            ApiSmokeTestService::normalizeBaseUrl(' http://localhost/openmep/ ')
        );
    }

    private function testBuildUrl(): void
    {
        $service = new ApiSmokeTestService('http://localhost/openmep/');

        $this->assertSame(
            'http://localhost/openmep/api/system/health.php',
            $service->buildUrl('/api/system/health.php')
        );

        $this->assertSame(
            'http://localhost/openmep/api/system/csrf-token.php',
            $service->buildUrl('api/system/csrf-token.php')
        );
    }

    private function testDefaultChecks(): void
    {
        $minimalChecks = ApiSmokeTestService::defaultChecks(true);
        $fullChecks = ApiSmokeTestService::defaultChecks(false);

        $this->assertSame(2, count($minimalChecks));
        $this->assertTrue(count($fullChecks) > count($minimalChecks));
        $this->assertSame('/api/system/health.php', $minimalChecks[0]['path']);
        $this->assertSame(false, $minimalChecks[0]['requires_database']);
    }
}
