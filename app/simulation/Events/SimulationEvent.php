<?php

declare(strict_types=1);

namespace App\Simulation\Events;

use App\Simulation\Contracts\SimulationEventInterface;

final class SimulationEvent implements SimulationEventInterface
{
    /**
     * @param array<string, mixed> $payload
     */
    public function __construct(
        private readonly float $time,
        private readonly string $type,
        private readonly array $payload = []
    ) {
    }

    public function time(): float
    {
        return $this->time;
    }

    public function type(): string
    {
        return $this->type;
    }

    /**
     * @return array<string, mixed>
     */
    public function payload(): array
    {
        return $this->payload;
    }

    /**
     * @return array{time: float, type: string, payload: array<string, mixed>}
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