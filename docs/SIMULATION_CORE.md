# Simulation Core Refactoring

This document describes the first internal refactoring step toward a maintainable discrete-event simulation engine.

## Purpose

The previous simulation implementation already used event-queue logic, FIFO queues and capacity-limited resources. This refactoring extracts reusable simulation primitives so that the engine can evolve without turning `SimulationService` into a monolithic class.

## Added Core Classes

| Class | Responsibility |
| --- | --- |
| `App\Simulation\SimulationEvent` | Immutable event object containing simulation time, event type and payload. |
| `App\Simulation\EventQueue` | Chronological event queue with deterministic FIFO behavior for equal timestamps. |
| `App\Simulation\SimulationClock` | Simulation time tracking with protection against moving backwards. |
| `App\Simulation\RandomGenerator` | Seeded helper for deterministic probability checks and processing-time variation. |
| `App\Simulation\Job` | Production entity lifecycle state and lead-time calculation. |
| `App\Simulation\FifoQueue` | FIFO queue abstraction for waiting jobs. |
| `App\Simulation\ResourcePool` | Capacity-slot based resource reservation and release. |
| `App\Simulation\StatisticsCollector` | Time-weighted WIP, lead-time and queue-wait statistics. |
| `App\Simulation\KpiCalculator` | Basic KPI calculations such as throughput, utilization and OEE. |
| `App\Simulation\BottleneckAnalyzer` | Finds the busiest operation/resource from busy-time statistics. |

## Integration Status

`SimulationService` now uses `EventQueue` instead of directly depending on `SplPriorityQueue`. The remaining simulation primitives are covered by unit tests and are ready for gradual integration into future engine iterations.

## Design Rules

- Simulation primitives must remain framework-independent.
- Database access stays in repositories, not in simulation classes.
- API payload validation stays in validators.
- `SimulationService` orchestrates loading, validation, execution and persistence.
- Deterministic runs must remain possible with the same random seed.

## Test Coverage

The `SimulationCoreTest` unit test verifies:

- chronological event processing;
- FIFO queue ordering;
- finite resource-slot reservation and release;
- job status and lead-time calculation;
- time-weighted WIP and KPI calculations;
- clock monotonicity;
- bottleneck detection.
