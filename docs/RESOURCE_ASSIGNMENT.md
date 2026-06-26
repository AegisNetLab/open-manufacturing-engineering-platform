# Resource Assignment

OpenMEP stores operation-to-resource links in the normalized `operation_resources` table. The Process Designer still keeps `resource_name` in operation metadata as a backward-compatible display field, but the persisted assignment source of truth is the resource ID.

## Behavior

- The Process Designer resource selector uses `resources.id` as the option value.
- Saving a process writes the selected resource to `operation_resources` with `required_quantity`.
- Loading a process joins `operation_resources` and `resources` to restore `resource_id`, `resource_name`, `required_quantity`, and `resource_assignments`.
- Older process payloads that only contain `resource_name` remain accepted by validators for backward compatibility.
- The Simulation Engine resolves resource assignments from `operation_resources` first, then falls back to legacy metadata names.

## Capacity impact

`required_quantity` defines how many units of the assigned resource must be available before an operation can start processing a queued job. If an operation requires two operator slots, both slots are reserved until the operation finishes.

## Implementation notes

The normalized mapping improves referential consistency and prepares the platform for future multi-resource operations, operator-machine combinations, and skill-based dispatching.
