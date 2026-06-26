# Audit Log

OpenMEP records important engineering and project lifecycle actions in the `audit_log` table.

## Purpose

The audit log provides a lightweight trace of changes that affect project data. It is intended for engineering review, troubleshooting and release support. It is not a security event system and it does not replace database backups.

## Stored fields

- `project_id`: related project, nullable when the project has been deleted
- `entity_type`: project, resource, layout, process, simulation, scenario or other domain object
- `entity_id`: affected record identifier when available
- `action`: created, updated, deleted, imported, exported, simulated or similar action
- `summary`: human-readable event text
- `metadata_json`: small structured context
- `created_at`: event timestamp

## API

### GET `/api/audit/list.php`

Query parameters:

- `project_id` optional project filter
- `limit` optional result limit, from 1 to 500

Example response:

```json
{
  "success": true,
  "data": {
    "events": [
      {
        "id": 1,
        "project_id": 3,
        "entity_type": "resource",
        "entity_id": 7,
        "action": "updated",
        "summary": "Resource updated: CNC-01",
        "metadata": {
          "resource_type": "machine",
          "quantity": 2
        },
        "created_at": "2026-06-26 10:00:00"
      }
    ]
  },
  "message": ""
}
```

## Current coverage

The first version records:

- project create, update and delete
- resource create, update and delete

Future versions can extend this to layout saves, process saves, simulation runs, imports, exports and user accounts when authentication is introduced.
