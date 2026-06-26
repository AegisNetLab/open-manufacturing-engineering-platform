<?php

declare(strict_types=1);

namespace App\Simulation;

final class KpiCalculator
{
    /** @param array<int, float> $leadTimes */
    public function averageLeadTime(array $leadTimes): float
    {
        return $leadTimes === [] ? 0.0 : array_sum($leadTimes) / count($leadTimes);
    }

    public function throughputPerHour(int $completedJobs, float $durationMinutes): float
    {
        return $completedJobs / max(0.01, $durationMinutes / 60.0);
    }

    public function utilizationPercent(float $busyMinutes, float $availableCapacityMinutes): float
    {
        return min(100.0, ($busyMinutes / max(1.0, $availableCapacityMinutes)) * 100.0);
    }

    public function simplifiedOee(float $availability, float $performance, float $quality): float
    {
        return min(100.0, max(0.0, $availability * $performance * $quality * 100.0));
    }
}
