# Project Diagnostics

Project Diagnostics provides a fast readiness check before a project is simulated, demonstrated, exported, or published as an example model.

## Purpose

The diagnostics feature verifies that the current engineering project contains the minimum data required for an executable manufacturing simulation:

- layout objects exist when physical context is expected;
- resources are defined;
- process operations and routing connections exist;
- the process model passes executable-model validation;
- at least one simulation scenario is available;
- simulation results exist for reporting.

The check is intentionally conservative. Failed `error` checks block simulation readiness. Failed `warning` checks do not block simulation, but they identify incomplete project documentation or missing analysis output.

## API

```http
GET /api/projects/diagnostics.php?project_id=1
```

Successful response:

```json
{
  "success": true,
  "data": {
    "project_id": 1,
    "readiness": "ready",
    "can_run_simulation": true,
    "counts": {
      "layout_elements": 12,
      "resources": 6,
      "operations": 8,
      "process_connections": 7,
      "simulation_scenarios": 1,
      "simulation_runs": 2,
      "simulation_results": 2
    },
    "checks": [],
    "summary": {
      "error_count": 0,
      "warning_count": 0,
      "next_action": "Project is ready for simulation and reporting."
    }
  },
  "message": "Project diagnostics completed."
}
```

## CLI

```bash
php scripts/diagnose-project.php --project-id=1
```

Exit codes:

- `0` — project is ready for simulation;
- `1` — invalid CLI usage or project not found;
- `2` — diagnostics completed, but blocking readiness checks failed.

## Readiness Rules

| Check | Severity | Rule |
|---|---:|---|
| Layout | Warning | At least one layout element exists. |
| Resources | Error | At least one resource exists. |
| Process operations | Error | At least one process operation exists. |
| Process connections | Error | At least one routing connection exists. |
| Process validation | Error | Process Validator reports the model as executable. |
| Simulation scenarios | Error | At least one saved scenario exists. |
| Simulation results | Warning | At least one result exists for reporting. |

## Architectural Notes

Diagnostics follows the approved backend layering:

- `ProjectDiagnosticsController` handles the HTTP request.
- `ProjectDiagnosticsService` evaluates readiness.
- `ProjectDiagnosticsRepository` performs database counts only.
- `ProcessValidator` remains the source of truth for executable process validation.

No SQL is executed in controllers.
