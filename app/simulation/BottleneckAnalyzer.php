<?php

declare(strict_types=1);

namespace App\Simulation;

final class BottleneckAnalyzer
{
    /** @param array<string, float> $busyMinutes */
    public function detect(array $busyMinutes): ?string
    {
        if ($busyMinutes === []) {
            return null;
        }

        arsort($busyMinutes);
        return (string) array_key_first($busyMinutes);
    }
}
