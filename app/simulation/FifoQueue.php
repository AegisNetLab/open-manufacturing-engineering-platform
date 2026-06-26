<?php

declare(strict_types=1);

namespace App\Simulation;

final class FifoQueue
{
    /** @var array<int, array{job_id:int,entered_at:float}> */
    private array $items = [];

    public function enqueue(int $jobId, float $enteredAt): void
    {
        $this->items[] = ['job_id' => $jobId, 'entered_at' => $enteredAt];
    }

    /** @return array{job_id:int,entered_at:float}|null */
    public function dequeue(): ?array
    {
        if ($this->items === []) {
            return null;
        }

        return array_shift($this->items);
    }

    public function count(): int
    {
        return count($this->items);
    }

    public function isEmpty(): bool
    {
        return $this->items === [];
    }
}
