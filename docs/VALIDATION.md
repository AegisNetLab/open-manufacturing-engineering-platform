# Validation Engine

OpenMEP uses server-side validation before process models are saved and before simulation runs are executed.

## Validation Scope

The current validation engine checks:

- exactly one Start node
- at least one End node
- no missing operation code
- unique operation codes per project payload
- positive cycle time for executable operations
- required resource assignment for Operation, Inspection and Transport nodes
- incoming and outgoing graph connections
- unknown connection references
- decision node probability totals
- graph reachability from the Start node
- warnings for operations that are not linked to a layout element

## Resource Assignment

Process operations now store the selected resource name in operation metadata as `resource_name`.
The simulation engine reads this value and uses the matching resource capacity from the `resources` table.

## Simulation Gate

Simulation execution is blocked when the saved process model is missing required resources or required cycle times.
This keeps the Simulation module aligned with the approved workflow: Layout → Resources → Process → Simulation → Results.

## API Behavior

`POST /api/process/validate.php` returns the standard JSON response format:

```json
{
  "success": true,
  "data": {
    "valid": false,
    "errors": [],
    "warnings": []
  },
  "message": "Process validation completed."
}
```

Validation errors are blocking. Warnings are displayed to the user but do not block saving.
