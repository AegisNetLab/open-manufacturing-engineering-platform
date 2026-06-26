<?php

declare(strict_types=1);

namespace App\Simulation\Engine;

final class SimulationContext
{
    private SimulationResult $result;

    public function __construct(
        private readonly SimulationConfiguration $configuration,
        private readonly SimulationClock $clock,
        private readonly EventQueue $eventQueue
    ) {
        $this->result = new SimulationResult();
    }

    public function configuration(): SimulationConfiguration
    {
        return $this->configuration;
    }

    public function clock(): SimulationClock
    {
        return $this->clock;
    }

    public function eventQueue(): EventQueue
    {
        return $this->eventQueue;
    }

    public function result(): SimulationResult
    {
        return $this->result;
    }

    public function setResult(SimulationResult $result): void
    {
        $this->result = $result;
    }
}