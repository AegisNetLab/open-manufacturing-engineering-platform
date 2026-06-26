<?php

declare(strict_types=1);

namespace App\Simulation;

final class StatisticsCollector
{
    private float $wipArea = 0.0;
    private float $lastWipUpdateAt = 0.0;

    /** @var array<int, float> */
    private array $leadTimes = [];

    /** @var array<int, float> */
    private array $queueWaitTimes = [];

    public function updateWipArea(float $time, int $wip): void
    {
        if ($time > $this->lastWipUpdateAt) {
            $this->wipArea += ($time - $this->lastWipUpdateAt) * max(0, $wip);
            $this->lastWipUpdateAt = $time;
        }
    }

    public function addLeadTime(float $leadTime): void
    {
        $this->leadTimes[] = max(0.0, $leadTime);
    }

    public function addQueueWaitTime(float $waitTime): void
    {
        $this->queueWaitTimes[] = max(0.0, $waitTime);
    }

    public function averageWip(float $durationMinutes): float
    {
        return $this->wipArea / max(0.01, $durationMinutes);
    }

    /** @return array<int, float> */
    public function leadTimes(): array
    {
        return $this->leadTimes;
    }

    public function averageWaitingTime(): float
    {
        return $this->queueWaitTimes === [] ? 0.0 : array_sum($this->queueWaitTimes) / count($this->queueWaitTimes);
    }
}
