<?php

declare(strict_types=1);

namespace App\Simulation\Engine;

final class SimulationConfiguration
{
    public function __construct(
        public readonly int $projectId,
        public readonly int $durationMinutes,
        public readonly float $arrivalRatePerHour,
        public readonly int $seed = 42,
        public readonly int $replications = 1
    ) {
    }
}