# API Hardening

OpenMEP uses thin PHP endpoint files, controller classes, services and repositories. This document describes the API hardening rules added after the first MVP implementation.

## Goals

- Keep the approved JSON response envelope.
- Reject unsupported HTTP methods consistently.
- Convert malformed JSON into a safe `400 Bad Request` response.
- Convert unexpected PHP errors into safe JSON errors.
- Keep controllers thin and free of SQL.
- Preserve backward compatibility with the existing `/api/...` endpoint paths.

## Response format

Successful responses keep the approved format:

```json
{
  "success": true,
  "data": {},
  "message": ""
}
```

Validation errors keep the approved format:

```json
{
  "success": false,
  "errors": [
    {"field": "name", "message": "Project name is required."}
  ]
}
```

General API errors now include a machine-readable error code while preserving `message`:

```json
{
  "success": false,
  "message": "Malformed JSON request body.",
  "error": {
    "code": "malformed_json",
    "message": "Malformed JSON request body."
  }
}
```

## HTTP method guards

Endpoint scripts call `ApiGuard::requireMethod()` before controller execution. This keeps the controller classes focused on request handling and service orchestration.

| Endpoint type | Method |
| --- | --- |
| `list`, `load`, `status`, `results`, `export` | `GET` |
| `create`, `update`, `save`, `delete`, `run`, `validate`, `import` | `POST` |

## Exception handling

The global bootstrap file registers:

- an error handler that converts PHP warnings/notices into exceptions;
- an exception handler that converts `ApiException` into JSON responses;
- a safe fallback for unexpected exceptions.

Unexpected exceptions are logged server-side and returned to the client as:

```json
{
  "success": false,
  "message": "An unexpected server error occurred.",
  "error": {
    "code": "internal_server_error",
    "message": "An unexpected server error occurred."
  }
}
```

## OpenAPI

A lightweight OpenAPI 3.0 document is available at:

```text
docs/openapi.yaml
```

It documents the stable MVP endpoints and standard response envelopes.
