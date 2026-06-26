# Project Pagination

Project Pagination v1 adds server-side pagination to the Project Manager. It keeps the existing search, production type filtering and allowlisted sorting behavior, while preventing large project lists from loading into the browser at once.

## API

`GET /api/projects/list.php` accepts the existing filter parameters plus pagination controls:

| Parameter | Description | Default | Limits |
| --- | --- | --- | --- |
| `query` | Searches project name and description | empty | truncated by service |
| `production_type` | `serial`, `job_shop` or `mixed` | empty | allowlisted |
| `sort` | `updated_at`, `created_at`, `name`, `production_type`, `shift_length_minutes` | `updated_at` | allowlisted |
| `direction` | `ASC` or `DESC` | `DESC` | allowlisted |
| `page` | One-based page number | `1` | minimum `1` |
| `per_page` | Rows per page | `10` | `5` to `100` |

Response data now contains both the current page of projects and pagination metadata:

```json
{
  "success": true,
  "data": {
    "projects": [],
    "pagination": {
      "page": 1,
      "per_page": 10,
      "total_items": 0,
      "total_pages": 1,
      "has_previous": false,
      "has_next": false
    }
  },
  "message": ""
}
```

## Frontend Behavior

The Project Manager now includes a page-size selector and pagination controls below the project table. Changing search, filter, sort, direction or page size resets the result set to page 1. Page navigation keeps the current filters and sorting options.

## Implementation Notes

The repository builds the filtered `WHERE` clause once and reuses it for both the result query and the total count query. Sorting remains allowlisted to avoid SQL injection through column names or direction values.
