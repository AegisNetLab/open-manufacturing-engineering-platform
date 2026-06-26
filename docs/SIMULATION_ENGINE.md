# Simulation Engine

OpenMEP uses a lightweight discrete-event simulation engine implemented in PHP. The engine is intentionally deterministic when the same model and random seed are used, which makes KPI regression testing possible.

## Engine Version

Current implementation: `2.0-event-queue`.

## Execution Model

The engine loads the saved project model from MySQL:

- operations
- process connections
- resources
- simulation scenario settings

It then creates an event queue ordered by simulated timestamp. Events are processed chronologically until the scenario duration is reached.

## Supported Events

- `JOB_CREATED`
- `JOB_ARRIVED`
- `QUEUE_ENTER`
- `PROCESS_START`
- `PROCESS_END`
- `RESOURCE_ALLOCATED`
- `RESOURCE_RELEASED`
- `SCRAP`
- `REWORK`
- `JOB_COMPLETED`
- `SIMULATION_END`

## Queue Rules

Each executable operation has a FIFO queue. When a job arrives at an operation, it enters the queue. If required resource capacity is available at the current simulation clock, the oldest waiting job starts immediately. Otherwise it waits until a resource is released.

## Resource Rules

Resources have finite capacity. Capacity is represented as one or more availability slots. Starting an operation reserves one slot until the processing event finishes. Released resources immediately trigger the next waiting job for the same operation.

If an operation references a resource that is not found in the resource table, the engine falls back to a private operation-level capacity of one. This keeps early project models executable while still encouraging explicit resource assignment.

## Routing Rules

After an operation finishes, the engine evaluates:

1. scrap probability;
2. rework probability and explicit rework connections;
3. normal outgoing process connections;
4. completion if no downstream operation exists.

Decision routing uses connection probabilities. If probabilities are incomplete but validation allows the model, the engine normalizes available probabilities during execution.

## KPI Collection

The engine updates statistics during event processing and stores summarized results in `simulation_results.metadata_json`.

Stored KPI values include:

- throughput per hour
- average lead time
- average WIP
- resource utilization
- OEE
- generated jobs
- completed jobs
- scrapped jobs
- scrap rate
- average waiting time
- bottleneck operation
- resource busy minutes
- operation busy minutes
- average queue lengths
- recent event log

## Determinism

The PHP random generator is seeded from the scenario payload. Identical input model, scenario configuration and seed should produce repeatable KPI results.

## Current MVP Constraints

The engine does not yet model physical transport distance, operator calendars, shift calendars, costs, energy use or multi-factory routing. These remain future extensions.
