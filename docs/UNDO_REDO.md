# Undo / Redo Framework

OpenMEP includes a lightweight browser-side undo/redo framework for engineering editors.

## Scope

The first implementation covers:

- Layout Designer
- Process Designer

The framework is intentionally frontend-only. Database persistence still happens through explicit Save actions, preserving the MVP rule that users control when engineering changes are stored.

## Design

The shared `HistoryManager` component stores serialized snapshots in two bounded stacks:

- `undoStack`
- `redoStack`

Each editor owns its own snapshot format:

- Layout Designer serializes placed layout elements.
- Process Designer serializes operations, connections, selection state and local sequence data.

The implementation avoids database coupling and does not know about PHP, SQL or REST endpoint details.

## Supported Actions

### Layout Designer

Undo/redo is available for:

- object creation
- object movement
- object resizing
- property editing
- object deletion
- clear layout

### Process Designer

Undo/redo is available for:

- node creation
- node movement
- node property editing
- node deletion
- connection creation
- connection deletion
- auto layout
- clear process

## Save / Load Behavior

History is cleared after successful load and save operations. This prevents applying stale snapshots after the persisted model has been refreshed from the backend.

## Limits

The default history depth is 50 snapshots per editor instance. This keeps memory usage predictable for browser-based engineering sessions.

## Future Improvements

Possible future improvements include command labels, keyboard shortcuts, grouped drag operations and persisted draft recovery.
