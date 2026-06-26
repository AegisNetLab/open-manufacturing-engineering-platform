<?php

declare(strict_types=1);

namespace App\Simulation\Engine;

use InvalidArgumentException;

final class SimulationClock
{
    private float $time = 0.0;

    public function now(): float
    {
        return $this->time;
    }

    public function advanceTo(float $time): void
    {
        if ($time < $this->time) {
            throw new InvalidArgumentException('Simulation clock cannot move backwards.');
        }

        $this->time = $time;
    }

    public function reset(): void
    {
        $this->time = 0.0;
    }
}