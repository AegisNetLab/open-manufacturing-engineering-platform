<?php

declare(strict_types=1);

namespace App\Simulation\Contracts;

interface SimulationEventInterface
{
    public function time(): float;

    public function type(): string;

    /**
     * @return array<string, mixed>
     */
    public function payload(): array;
}