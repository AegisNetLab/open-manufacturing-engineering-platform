<?php

declare(strict_types=1);

namespace App\Simulation;

final class RandomGenerator
{
    public function __construct(private int $seed = 42)
    {
        mt_srand($this->seed);
    }

    public function seed(): int
    {
        return $this->seed;
    }

    public function reseed(int $seed): void
    {
        $this->seed = $seed;
        mt_srand($this->seed);
    }

    public function percentHit(float $percent): bool
    {
        return $percent > 0.0 && mt_rand(1, 10000) <= (int) round($percent * 100.0);
    }

    public function factor(float $minimum = 0.9, float $maximum = 1.1): float
    {
        if ($maximum <= $minimum) {
            return $minimum;
        }

        return $minimum + ((mt_rand(0, 1000000) / 1000000.0) * ($maximum - $minimum));
    }
}
