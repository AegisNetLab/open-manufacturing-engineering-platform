# Health Checks and Installation Diagnostics

OpenMEP includes a lightweight diagnostics layer for local installation checks and deployment smoke tests.

## HTTP endpoint

```http
GET /api/system/health.php
```

The endpoint returns the standard OpenMEP JSON envelope and includes:

- PHP version status
- required PHP extension status
- database connectivity
- writable path checks
- application version
- current environment

A fully healthy system returns HTTP `200`. A degraded system returns HTTP `503` with machine-readable check details.

## CLI installation check

Run:

```bash
php scripts/install-check.php
```

The command exits with code `0` when all checks pass and code `1` when any required check fails.

## Intended use

Use these checks before running migrations, after deployment, and during manual QA. They are intentionally small and do not replace full test execution.
