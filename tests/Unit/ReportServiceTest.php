<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Services\ReportService;
use ReflectionClass;
use Tests\Support\TestCase;

final class ReportServiceTest extends TestCase
{
    public function run(): void
    {
        $service = (new ReflectionClass(ReportService::class))->newInstanceWithoutConstructor();
        $html = $service->renderSimulationReport([
            'run_id' => 12,
            'scenario_name' => 'Baseline <A>',
            'status' => 'completed',
            'finished_at' => '2026-06-26 10:00:00',
            'duration_minutes' => 480,
            'arrival_rate' => 10,
            'throughput_per_hour' => 8.5,
            'average_lead_time_minutes' => 42.2,
            'average_wip' => 5.4,
            'oee_percent' => 76.2,
            'metadata' => [
                'bottleneck' => 'Assembly',
                'generated_jobs' => 80,
                'completed_jobs' => 70,
                'scrapped_jobs' => 2,
                'reworked_jobs' => 4,
                'resource_utilization' => ['Assembly' => 92.5],
                'queue_summary' => ['Assembly' => 3.2],
                'events' => [
                    ['time' => 1.0, 'type' => 'JOB_CREATED', 'message' => 'Job 1 created.'],
                ],
            ],
        ]);

        $this->assertTrue(str_contains($html, '<!doctype html>'), 'Report should render a full HTML document.');
        $this->assertTrue(str_contains($html, 'Simulation Report'), 'Report title should be present.');
        $this->assertTrue(str_contains($html, 'Baseline &lt;A&gt;'), 'Scenario name should be escaped.');
        $this->assertTrue(str_contains($html, 'Assembly'), 'Bottleneck and utilization data should be rendered.');
        $this->assertTrue(str_contains($html, 'Print / Save as PDF'), 'Report should include a print action.');
    }
}
