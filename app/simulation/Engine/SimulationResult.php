<?php

declare(strict_types=1);

namespace App\Simulation\Engine;

final class SimulationResult
{
    /**
     * @param array<string, mixed> $kpis
     * @param array<int, array<string, mixed>> $events
     */
    public function __construct(
        public readonly array $kpis = [],
        public readonly array $events = []
    ) {
    }
}