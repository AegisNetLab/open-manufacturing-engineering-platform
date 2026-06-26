<?php

declare(strict_types=1);

namespace Tests\Unit\Simulation;

use App\Simulation\Engine\EventQueue;
use App\Simulation\Engine\SimulationClock;
use App\Simulation\Engine\SimulationConfiguration;
use App\Simulation\Engine\SimulationContext;
use App\Simulation\Engine\SimulationResult;
use Tests\Support\TestCase;

final class SimulationContextTest extends TestCase
{
    public function run(): void
    {
        $this->testContextStoresCoreRuntimeObjects();
        $this->testResultCanBeReplaced();
    }

    private function testContextStoresCoreRuntimeObjects(): void
    {
        $configuration = new SimulationConfiguration(
            projectId: 1,
            durationMinutes: 480,
            arrivalRatePerHour: 12.5,
            seed: 123,
            replications: 3
        );

        $clock = new SimulationClock();
        $queue = new EventQueue();

        $context = new SimulationContext($configuration, $clock, $queue);

        $this->assertSame($configuration, $context->configuration());
        $this->assertSame($clock, $context->clock());
        $this->assertSame($queue, $context->eventQueue());
        $this->assertSame(480, $context->configuration()->durationMinutes);
        $this->assertSame(12.5, $context->configuration()->arrivalRatePerHour);
    }

    private function testResultCanBeReplaced(): void
    {
        $context = new SimulationContext(
            new SimulationConfiguration(1, 60, 5.0),
            new SimulationClock(),
            new EventQueue()
        );

        $result = new SimulationResult(
            ['throughput_per_hour' => 10.0],
            [['time' => 0.0, 'type' => 'SIMULATION_STARTED']]
        );

        $context->setResult($result);

        $this->assertSame($result, $context->result());
        $this->assertSame(10.0, $context->result()->kpis['throughput_per_hour']);
    }
}