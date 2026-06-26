# Import and Export

OpenMEP supports lightweight project package exchange for engineering data and CSV export for simulation results.

## Project Package Export

Endpoint:

```http
GET /api/export/project.php?project_id={id}
```

The response is a downloadable JSON file with the extension `.openmep.json`.

The package contains:

- project settings
- layout elements
- resources
- operations
- process connections
- operation-resource assignments
- simulation scenarios

Simulation runs and immutable result history are intentionally not included in the project package. Results should be exported separately as CSV.

## Project Package Import

Endpoint:

```http
POST /api/import/project.php
Content-Type: application/json
```

The request body must be a package previously exported by OpenMEP.

Import creates a new project and remaps all internal identifiers. Existing projects are never overwritten.

Successful response:

```json
{
  "success": true,
  "data": {
    "project_id": 123
  },
  "message": "Project package imported."
}
```

## Results CSV Export

Endpoint:

```http
GET /api/results/export_csv.php?project_id={id}
```

The exported CSV contains scenario metadata and core KPI values:

- run ID
- scenario name
- run status
- start and finish timestamps
- duration
- arrival rate
- random seed
- throughput
- lead time
- WIP
- utilization
- OEE

## UI Integration

The Project Manager includes:

- `Export` action per project row
- `Import JSON` button for `.openmep.json` files

The Results Dashboard includes:

- `Export CSV` button for the active project's simulation results

## Design Notes

The import/export layer is implemented through controller/service/repository separation and uses database transactions for imports. This keeps the feature aligned with the existing OpenMEP backend architecture.
