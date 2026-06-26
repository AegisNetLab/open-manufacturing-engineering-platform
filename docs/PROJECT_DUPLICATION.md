# Project Duplication

Project duplication creates a new editable project from an existing engineering model.

## Purpose

The feature supports design iteration without overwriting an existing project. Engineers can duplicate a validated model, change layout, resources, process routing or scenarios, and compare simulation results later.

## API

### `POST /api/projects/duplicate.php`

Request body:

```json
{
  "id": 1,
  "name": "Automotive Cell - Variant B"
}
```

`name` is optional. When omitted, the backend uses the source project name with ` (Copy)` appended.

Successful response:

```json
{
  "success": true,
  "data": {
    "project": {
      "id": 2,
      "name": "Automotive Cell - Variant B"
    }
  },
  "message": "Project duplicated."
}
```

## Copied data

The duplication process copies the current engineering model:

- project metadata
- layout elements
- resources
- operations
- process connections
- operation-resource assignments
- simulation scenarios

Simulation runs and simulation results are intentionally not copied. Results are historical execution records and must remain attached to the original run.

## ID remapping

Internal database identifiers are remapped during duplication:

- operation links to layout elements are rewritten to the duplicated layout IDs;
- process connections are rewritten to the duplicated operation IDs;
- operation-resource assignments are rewritten to duplicated operation and resource IDs.

The entire operation runs inside one database transaction. If any copy step fails, the new project is rolled back.

## Audit log

A `project / duplicated` audit event is recorded for the duplicated project. The source project ID is stored in audit metadata.
