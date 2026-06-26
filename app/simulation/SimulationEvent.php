<?php

declare(strict_types=1);

namespace App\Simulation;

final class SimulationEvent
{
    /**
     * @param array<string, mixed> $payload
     */
    public function __construct(
        public readonly float $time,
        public readonly string $type,
        public readonly array $payload = [],
    ) {
    }

    /**
     * @return array{time:float,type:string,payload:array<string,mixed>}
     */
    public function toArray(): array
    {
        return [
            'time' => $this->time,
            'type' => $this->type,
            'payload' => $this->payload,
        ];
    }
}
