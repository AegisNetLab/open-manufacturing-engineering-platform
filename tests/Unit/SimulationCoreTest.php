<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Simulation\BottleneckAnalyzer;
use App\Simulation\EventQueue;
use App\Simulation\FifoQueue;
use App\Simulation\Job;
use App\Simulation\KpiCalculator;
use App\Simulation\ResourcePool;
use App\Simulation\SimulationClock;
use App\Simulation\StatisticsCollector;
use Tests\Support\TestCase;

final class SimulationCoreTest extends TestCase
{
    public function run(): void
    {
        $this->eventQueueProcessesEventsChronologically();
        $this->fifoQueuePreservesInsertionOrder();
        $this->resourcePoolTracksCapacitySlots();
        $this->jobCalculatesLeadTime();
        $this->statisticsAndKpisAreCalculated();
        $this->clockCannotMoveBackwards();
        $this->bottleneckAnalyzerReturnsBusiestOperation();
    }

    private function eventQueueProcessesEventsChronologically(): void
    {
        $queue = new EventQueue();
        $queue->schedule(10.0, 'B');
        $queue->schedule(5.0, 'A');
        $queue->schedule(5.0, 'A2');

        $this->assertSame('A', $queue->pop()?->type);
        $this->assertSame('A2', $queue->pop()?->type);
        $this->assertSame('B', $queue->pop()?->type);
        $this->assertTrue($queue->isEmpty());
    }

    private function fifoQueuePreservesInsertionOrder(): void
    {
        $queue = new FifoQueue();
        $queue->enqueue(1, 0.0);
        $queue->enqueue(2, 1.0);

        $this->assertSame(1, $queue->dequeue()['job_id']);
        $this->assertSame(2, $queue->dequeue()['job_id']);
        $this->assertTrue($queue->isEmpty());
    }

    private function resourcePoolTracksCapacitySlots(): void
    {
        $pool = new ResourcePool('CNC-01', 2);
        $this->assertSame(2, $pool->capacity());
        $this->assertTrue($pool->canStartAt(0.0));

        [$slot] = $pool->nextAvailableSlot();
        $pool->reserve($slot, 15.0);

        $this->assertTrue($pool->canStartAt(0.0));
        [$secondSlot] = $pool->nextAvailableSlot();
        $pool->reserve($secondSlot, 20.0);

        $this->assertFalse($pool->canStartAt(10.0));
        $pool->release($slot, 10.0);
        $this->assertTrue($pool->canStartAt(10.0));
    }

    private function jobCalculatesLeadTime(): void
    {
        $job = new Job(1, 3.0);
        $this->assertSame(Job::STATUS_ACTIVE, $job->status());
        $job->complete(13.5);

        $this->assertSame(Job::STATUS_COMPLETED, $job->status());
        $this->assertSame(10.5, $job->leadTime());
    }

    private function statisticsAndKpisAreCalculated(): void
    {
        $stats = new StatisticsCollector();
        $stats->updateWipArea(10.0, 2);
        $stats->updateWipArea(20.0, 1);
        $stats->addLeadTime(12.0);
        $stats->addLeadTime(18.0);
        $stats->addQueueWaitTime(4.0);
        $stats->addQueueWaitTime(6.0);

        $calculator = new KpiCalculator();
        $this->assertSame(1.5, $stats->averageWip(20.0));
        $this->assertSame(15.0, $calculator->averageLeadTime($stats->leadTimes()));
        $this->assertSame(5.0, $stats->averageWaitingTime());
        $this->assertSame(12.0, $calculator->throughputPerHour(12, 60.0));
        $this->assertSame(50.0, $calculator->utilizationPercent(30.0, 60.0));
    }

    private function clockCannotMoveBackwards(): void
    {
        $clock = new SimulationClock();
        $clock->advanceTo(5.0);
        $this->assertSame(5.0, $clock->now());

        try {
            $clock->advanceTo(4.0);
            $this->assertTrue(false, 'Expected clock exception.');
        } catch (\InvalidArgumentException) {
            $this->assertTrue(true);
        }
    }

    private function bottleneckAnalyzerReturnsBusiestOperation(): void
    {
        $analyzer = new BottleneckAnalyzer();
        $this->assertSame('Assembly', $analyzer->detect(['CNC' => 20.0, 'Assembly' => 35.0]));
    }
}
