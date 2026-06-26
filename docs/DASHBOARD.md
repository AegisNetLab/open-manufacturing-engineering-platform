# Dashboard

The Dashboard is the default OpenMEP landing screen. It provides a compact overview of the current engineering workspace without duplicating module-specific editing features.

## Purpose

The Dashboard helps users quickly understand project activity and jump into the next engineering task.

It displays:

- total project, layout, operation, resource and simulation counts;
- recently updated projects;
- latest simulation result KPIs;
- simulation readiness for recent projects.

## API

### `GET /api/dashboard/summary.php`

Returns application-level dashboard information.

Response payload:

```json
{
  "success": true,
  "data": {
    "metrics": {
      "projects": 1,
      "layout_elements": 12,
      "operations": 8,
      "resources": 6,
      "simulation_runs": 3,
      "simulation_results": 3
    },
    "recent_projects": [],
    "latest_results": [],
    "readiness": []
  }
}
```

## Readiness Rules

A project is considered dashboard-ready for simulation when it has at least:

- one layout element;
- one resource;
- more than one operation;
- one process connection;
- one simulation scenario.

This is intentionally lightweight. The strict simulation gate remains the dedicated Project Diagnostics and Process Validation flow.

## Frontend Behavior

Recent projects open the Layout Designer. Latest simulation result rows open the Results screen. Readiness items open the Simulation screen after selecting the project.

The Dashboard is now the default startup route and is available through `Alt + 1`.
