<?php

declare(strict_types=1);

namespace App\Simulation\Engine;

use App\Simulation\Contracts\SimulationEventInterface;
use SplPriorityQueue;

final class EventQueue
{
    private SplPriorityQueue $queue;

    private int $sequence = 0;

    public function __construct()
    {
        $this->queue = new SplPriorityQueue();
        $this->queue->setExtractFlags(SplPriorityQueue::EXTR_DATA);
    }

    public function schedule(SimulationEventInterface $event): void
    {
        $this->sequence++;

        $this->queue->insert($event, [
            -$event->time(),
            -$this->sequence,
        ]);
    }

    public function next(): ?SimulationEventInterface
    {
        if ($this->queue->isEmpty()) {
            return null;
        }

        $event = $this->queue->extract();

        return $event instanceof SimulationEventInterface ? $event : null;
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