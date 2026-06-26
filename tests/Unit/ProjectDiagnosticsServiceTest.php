<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Services\ProjectDiagnosticsService;
use Tests\Support\TestCase;

final class ProjectDiagnosticsServiceTest extends TestCase
{
    public function run(): void
    {
        $service = $this->serviceWithoutConstructor();

        $ready = $service->buildReport(1, [
            'layout_elements' => 2,
            'resources' => 3,
            'operations' => 4,
            'process_connections' => 3,
            'simulation_scenarios' => 1,
            'simulation_runs' => 1,
            'simulation_results' => 1,
        ], [
            'valid' => true,
            'errors' => [],
            'warnings' => [],
        ]);

        $this->assertSame('ready', $ready['readiness']);
        $this->assertTrue($ready['can_run_simulation']);
        $this->assertSame('Project is ready for simulation and reporting.', $ready['summary']['next_action']);

        $notReady = $service->buildReport(1, [
            'layout_elements' => 0,
            'resources' => 0,
            'operations' => 0,
            'process_connections' => 0,
            'simulation_scenarios' => 0,
            'simulation_runs' => 0,
            'simulation_results' => 0,
        ], [
            'valid' => false,
            'errors' => [['field' => 'operations', 'message' => 'Missing process.']],
            'warnings' => [],
        ]);

        $this->assertSame('not_ready', $notReady['readiness']);
        $this->assertFalse($notReady['can_run_simulation']);
        $this->assertSame('Create at least one resource in the Resource Manager.', $notReady['summary']['next_action']);
        $this->assertTrue($notReady['summary']['error_count'] > 0);
    }

    private function serviceWithoutConstructor(): ProjectDiagnosticsService
    {
        $reflection = new \ReflectionClass(ProjectDiagnosticsService::class);

        return $reflection->newInstanceWithoutConstructor();
    }
}
