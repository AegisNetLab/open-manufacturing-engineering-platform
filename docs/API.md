# REST API Reference

OpenMEP APIs use JSON request and response payloads.

## Standard Response

```json
{
  "success": true,
  "data": {},
  "message": ""
}
```

Validation errors use this format:

```json
{
  "success": false,
  "errors": [
    {"field": "name", "message": "Project name is required."}
  ]
}
```

## Projects

| Method | Endpoint | Purpose |
| --- | --- | --- |
| GET | `/api/projects/list.php` | List projects with optional search/filter/sort parameters |
| POST | `/api/projects/create.php` | Create project |
| POST | `/api/projects/update.php` | Update project |
| POST | `/api/projects/delete.php` | Delete project |

### Create Project Payload

```json
{
  "name": "Demo Factory",
  "description": "Example project",
  "production_type": "serial",
  "shift_length_minutes": 480
}
```

## Layout

| Method | Endpoint | Purpose |
| --- | --- | --- |
| GET | `/api/layout/load.php?project_id=1` | Load layout elements |
| POST | `/api/layout/save.php` | Replace and save complete project layout |

### Save Layout Payload

```json
{
  "project_id": 1,
  "elements": [
    {
      "name": "CNC-01",
      "type": "machine",
      "x": 120,
      "y": 80,
      "width": 144,
      "height": 96,
      "rotation": 0,
      "color": "#1565C0",
      "metadata": {"library_id": "cnc"}
    }
  ]
}
```

## Resources

| Method | Endpoint | Purpose |
| --- | --- | --- |
| GET | `/api/resources/list.php?project_id=1` | List resources |
| POST | `/api/resources/save.php` | Create or update a resource |
| POST | `/api/resources/delete.php` | Delete a resource |

### Save Resource Payload

```json
{
  "id": null,
  "project_id": 1,
  "name": "CNC-01",
  "resource_type": "machine",
  "quantity": 1,
  "metadata": {
    "layout_element_id": 10,
    "notes": "Primary milling machine"
  }
}
```

## Process

| Method | Endpoint | Purpose |
| --- | --- | --- |
| GET | `/api/process/load.php?project_id=1` | Load process model |
| POST | `/api/process/save.php` | Save complete process model |
| POST | `/api/process/validate.php` | Validate process model |

### Save Process Payload

```json
{
  "project_id": 1,
  "operations": [
    {
      "client_id": "node-1",
      "operation_code": "OP10",
      "name": "CNC Milling",
      "node_type": "operation",
      "cycle_time_seconds": 270,
      "setup_time_seconds": 0,
      "batch_size": 1,
      "scrap_rate": 1.5,
      "rework_rate": 0,
      "linked_layout_element_id": 10,
      "metadata": {"resource_name": "CNC-01"}
    }
  ],
  "connections": [
    {
      "source_client_id": "node-1",
      "target_client_id": "node-2",
      "connection_type": "normal",
      "probability": 100
    }
  ]
}
```

## Simulation

| Method | Endpoint | Purpose |
| --- | --- | --- |
| POST | `/api/simulation/run.php` | Run a simulation scenario |
| GET | `/api/simulation/status.php?project_id=1` | Load latest run status |
| GET | `/api/simulation/results.php?project_id=1` | Load stored simulation results |

### Run Simulation Payload

```json
{
  "project_id": 1,
  "name": "Baseline Scenario",
  "duration_minutes": 480,
  "arrival_rate": 10,
  "random_seed": 42,
  "distribution": "deterministic"
}
```


## Import / Export

| Method | Endpoint | Purpose |
| --- | --- | --- |
| GET | `/api/export/project.php?project_id={id}` | Download a project engineering package as JSON. |
| POST | `/api/import/project.php` | Import a project engineering package as a new project. |
| GET | `/api/results/export_csv.php?project_id={id}` | Download simulation results as CSV. |

Project package imports create a new project and remap internal IDs. Existing projects are not overwritten.

## System

### Health check

`GET /api/system/health.php`

Returns runtime diagnostics for PHP, required extensions, database connectivity, writable paths, application version and environment. A healthy system returns HTTP `200`; degraded systems return HTTP `503` with details in the standard JSON response envelope.

## Scenario API

### `GET /api/scenarios/list.php?project_id={id}`

Lists saved simulation scenarios for a project.

### `POST /api/scenarios/save.php`

Creates or updates a simulation scenario. Include `id` to update.

### `POST /api/scenarios/delete.php`

Deletes a simulation scenario by `id` and `project_id`.

## Reports

### GET `/api/reports/simulation.php`

Returns a printable HTML simulation report for a project result.

Query parameters:

| Parameter | Required | Description |
| --- | --- | --- |
| `project_id` | Yes | Project identifier. |
| `run_id` | No | Simulation run identifier. If omitted, the latest result is used. |

The endpoint returns `text/html` on success and the standard JSON error response when the requested report cannot be found.


## Audit

### GET `/api/audit/list.php`

Returns recent audit log events. Optional query parameters: `project_id`, `limit`.


## System CSRF Token

`GET /api/system/csrf-token.php` returns a session CSRF token used by the shared JavaScript API client for unsafe API methods.


### Duplicate Project

`POST /api/projects/duplicate.php`

Creates a new project from an existing project, including layout elements, resources, operations, process connections, operation-resource assignments, and simulation scenarios. Simulation runs and results are not copied.

Request:

```json
{
  "id": 1,
  "name": "Duplicated Project Name"
}
```

Response contains the newly created `project`.


### Project list filters

`GET /api/projects/list.php` accepts optional query parameters: `query`, `production_type`, `sort`, and `direction`. Supported sort fields are `name`, `production_type`, `shift_length_minutes`, `updated_at`, and `created_at`.
