<?php

declare(strict_types=1);

namespace App\Simulation\Contracts;

interface RandomGeneratorInterface
{
    public function seed(int $seed): void;

    public function uniform(float $min = 0.0, float $max = 1.0): float;

    public function probability(float $percent): bool;
}