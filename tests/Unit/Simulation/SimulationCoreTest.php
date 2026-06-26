<?php

declare(strict_types=1);

namespace Tests\Unit\Simulation;

use App\Simulation\Engine\EventQueue;
use App\Simulation\Engine\SimulationClock;
use App\Simulation\Events\SimulationEvent;
use App\Simulation\Random\MtRandomGenerator;
use InvalidArgumentException;
use Tests\Support\TestCase;

final class SimulationCoreTest extends TestCase
{
    public function run(): void
    {
        $this->testClockAdvancesForwardOnly();
        $this->testEventQueueReturnsEarliestEventFirst();
        $this->testSimulationEventCanBeSerialized();
        $this->testRandomGeneratorProbabilityBoundaries();
    }

    private function testClockAdvancesForwardOnly(): void
    {
        $clock = new SimulationClock();

        $clock->advanceTo(12.5);

        $this->assertSame(12.5, $clock->now());

        try {
            $clock->advanceTo(10.0);
            $this->assertTrue(false, 'Expected clock rollback to fail.');
        } catch (InvalidArgumentException) {
            $this->assertTrue(true);
        }
    }

    private function testEventQueueReturnsEarliestEventFirst(): void
    {
        $queue = new EventQueue();

        $queue->schedule(new SimulationEvent(10.0, 'late'));
        $queue->schedule(new SimulationEvent(2.0, 'early'));
        $queue->schedule(new SimulationEvent(5.0, 'middle'));

        $this->assertSame('early', $queue->next()?->type());
        $this->assertSame('middle', $queue->next()?->type());
        $this->assertSame('late', $queue->next()?->type());

        $this->assertTrue($queue->isEmpty());
    }

    private function testSimulationEventCanBeSerialized(): void
    {
        $event = new SimulationEvent(
            4.5,
            'JOB_ARRIVED',
            ['job_id' => 7]
        );

        $data = $event->toArray();

        $this->assertSame(4.5, $data['time']);
        $this->assertSame('JOB_ARRIVED', $data['type']);
        $this->assertSame(['job_id' => 7], $data['payload']);
    }

    private function testRandomGeneratorProbabilityBoundaries(): void
    {
        $random = new MtRandomGenerator();

        $random->seed(42);

        $this->assertFalse($random->probability(0.0));
        $this->assertTrue($random->probability(100.0));
    }
}