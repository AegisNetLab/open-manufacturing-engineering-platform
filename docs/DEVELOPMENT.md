# Development Guide

## Local Development

```bash
php -S localhost:8000
```

## Database Setup

```bash
mysql -u root -p < database/schema.sql
```

## Coding Standards

- PHP follows PSR-12.
- JavaScript uses ES2022 modules.
- Database columns use snake_case.
- One class has one responsibility.
- SQL is allowed only in repository classes.

## Process Designer v1

The first integrated Process Designer implementation is now available as an application module.
It provides a browser-side visual node editor, process connection handling, model validation,
project-based persistence, and REST endpoints backed by the `operations` and
`process_connections` tables.

Implemented endpoints:

- `GET /api/process/load.php?project_id={id}`
- `POST /api/process/save.php`
- `POST /api/process/validate.php`

The module intentionally stores visual node metadata in `operations.metadata_json` to preserve
compatibility with the normalized physical schema while keeping the MVP editor simple.

## Resource Manager v1

The Resource Manager implements the MVP resource workflow for machines, operators, tools, buffers and transport devices. Resources are project-scoped and persisted through the `resources` table. The module follows the existing controller-service-repository-validator layering and exposes JSON endpoints under `/api/resources`.

Implemented endpoints:

- `GET /api/resources/list.php?project_id={id}`
- `POST /api/resources/save.php`
- `POST /api/resources/delete.php`

The frontend module `public/js/resources.js` uses the shared `ApiClient`, loads layout elements for optional physical linking, and keeps all persistence behind the REST API layer.

## Simulation Engine v1

The first integrated Simulation module implements an MVP discrete-event style execution flow using the saved process graph as the executable model.

Implemented endpoints:

- `POST /api/simulation/run.php`
- `GET /api/simulation/status.php`
- `GET /api/simulation/results.php`

Implemented capabilities:

- Scenario persistence in `simulation_scenarios`.
- Run persistence in `simulation_runs`.
- KPI persistence in `simulation_results`.
- Deterministic execution through `random_seed`.
- Primary-route execution from Start to End.
- Resource-busy time tracking.
- Throughput, lead time, WIP, utilization and simplified OEE calculation.
- Scrap handling.
- Bottleneck identification.
- Event log metadata for UI display.

Current MVP limitation: routing uses the highest-probability outgoing connection as the primary executable route. Full probabilistic decision routing and explicit operation-resource assignment will be expanded in the next simulation iteration.
