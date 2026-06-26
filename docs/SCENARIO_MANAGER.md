# Scenario Manager

The Scenario Manager stores reusable simulation configurations for a project.

## Scope

Scenario Manager v1 supports:

- listing scenarios by project;
- creating scenarios;
- updating existing scenarios;
- deleting scenarios;
- loading a saved scenario into the Simulation form;
- running simulations from either ad-hoc form values or a saved scenario configuration.

## Data Model

Scenarios are stored in `simulation_scenarios`.

| Field | Purpose |
| --- | --- |
| `project_id` | Project owning the scenario |
| `name` | Scenario display name |
| `duration_minutes` | Simulation duration |
| `arrival_rate` | Job arrivals per hour |
| `random_seed` | Optional deterministic seed |
| `metadata_json` | Extensible settings such as arrival distribution |

## REST API

### List Scenarios

```http
GET /api/scenarios/list.php?project_id=1
```

### Save Scenario

```http
POST /api/scenarios/save.php
Content-Type: application/json
```

```json
{
  "project_id": 1,
  "name": "Baseline Scenario",
  "duration_minutes": 480,
  "arrival_rate": 10,
  "random_seed": 42,
  "metadata": {
    "distribution": "deterministic"
  }
}
```

Include `id` to update an existing scenario.

### Delete Scenario

```http
POST /api/scenarios/delete.php
Content-Type: application/json
```

```json
{
  "id": 1,
  "project_id": 1
}
```

## Validation Rules

- Project ID is required.
- Scenario name is required and limited to 100 characters.
- Duration must be between 1 and 525600 minutes.
- Arrival rate must be greater than zero.
- Random seed must be numeric when provided.

## Design Notes

The Simulation Engine still creates immutable simulation runs and results. Scenario records are editable templates, while simulation runs are historical records.
