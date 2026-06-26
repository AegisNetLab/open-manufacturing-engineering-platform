<?php

declare(strict_types=1);

namespace App\Simulation;

use SplPriorityQueue;

final class EventQueue
{
    /** @var SplPriorityQueue<array{event:SimulationEvent,sequence:int}, array{0:float,1:int}> */
    private SplPriorityQueue $queue;

    private int $sequence = 0;

    public function __construct()
    {
        $this->queue = new SplPriorityQueue();
        $this->queue->setExtractFlags(SplPriorityQueue::EXTR_DATA);
    }

    /**
     * @param array<string, mixed> $payload
     */
    public function schedule(float $time, string $type, array $payload = []): void
    {
        $this->sequence++;
        $this->queue->insert(
            ['event' => new SimulationEvent($time, $type, $payload), 'sequence' => $this->sequence],
            [-$time, -$this->sequence]
        );
    }

    public function pop(): ?SimulationEvent
    {
        if ($this->queue->isEmpty()) {
            return null;
        }

        $item = $this->queue->extract();
        return $item['event'];
    }

    public function isEmpty(): bool
    {
        return $this->queue->isEmpty();
    }

    public function count(): int
    {
        return $this->queue->count();
    }
}
