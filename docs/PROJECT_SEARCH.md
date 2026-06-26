# Project Search and Filtering

OpenMEP supports lightweight project list filtering from both the Project Manager screen and the REST API.

## User Interface

The Project Manager table includes:

- free-text search across project name and description;
- production type filter;
- sortable fields;
- ascending or descending direction.

The search box is debounced to avoid unnecessary requests while typing.

## REST API

Endpoint:

```http
GET /api/projects/list.php
```

Optional query parameters:

| Parameter | Description | Allowed values |
| --- | --- | --- |
| `query` | Searches project name and description. | Any text, truncated to 100 characters. |
| `production_type` | Filters by production type. | `serial`, `job_shop`, `mixed` |
| `sort` | Sort column. | `name`, `production_type`, `shift_length_minutes`, `updated_at`, `created_at` |
| `direction` | Sort direction. | `ASC`, `DESC` |

Example:

```http
GET /api/projects/list.php?query=assembly&production_type=serial&sort=name&direction=ASC
```

## Implementation Notes

Filtering remains intentionally simple and database-portable. The repository uses prepared statements for filter values and a fixed allowlist for sortable columns to avoid SQL injection through dynamic ordering.
