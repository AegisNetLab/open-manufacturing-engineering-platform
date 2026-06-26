<?php

declare(strict_types=1);

namespace App\Simulation\Random;

use App\Simulation\Contracts\RandomGeneratorInterface;

final class MtRandomGenerator implements RandomGeneratorInterface
{
    public function seed(int $seed): void
    {
        mt_srand($seed);
    }

    public function uniform(float $min = 0.0, float $max = 1.0): float
    {
        if ($max < $min) {
            [$min, $max] = [$max, $min];
        }

        $ratio = mt_rand() / mt_getrandmax();

        return $min + (($max - $min) * $ratio);
    }

    public function probability(float $percent): bool
    {
        if ($percent <= 0.0) {
            return false;
        }

        if ($percent >= 100.0) {
            return true;
        }

        return $this->uniform(0.0, 100.0) <= $percent;
    }
}