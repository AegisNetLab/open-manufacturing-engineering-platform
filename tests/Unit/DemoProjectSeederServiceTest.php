<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Services\DemoProjectSeederService;
use Tests\Support\TestCase;

final class DemoProjectSeederServiceTest extends TestCase
{
    public function run(): void
    {
        $service = new DemoProjectSeederService();
        $plan = $service->buildPlan('Custom Demo');

        $this->assertSame('Custom Demo', $plan['project_name']);
        $this->assertSame(1, $plan['projects']);
        $this->assertSame(8, $plan['layout_elements']);
        $this->assertSame(5, $plan['resources']);
        $this->assertSame(8, $plan['operations']);
        $this->assertSame(9, $plan['connections']);
        $this->assertSame(1, $plan['scenarios']);
    }
}
