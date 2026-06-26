# API Smoke Tests

OpenMEP includes a lightweight API smoke test command for checking a deployed or locally running application over HTTP.

The command is intentionally simple and has no external dependencies. It validates that selected endpoints are reachable and return the standard OpenMEP JSON response shape.

## Command

```bash
php scripts/api-smoke-test.php --base-url=http://localhost/openmep
```

For installation checks before MySQL is configured, run only non-database endpoints:

```bash
php scripts/api-smoke-test.php --base-url=http://localhost/openmep --skip-db
```

Machine-readable output is available for CI systems:

```bash
php scripts/api-smoke-test.php --base-url=http://localhost/openmep --json
```

## Checked Endpoints

Default checks:

- `GET /api/system/health.php`
- `GET /api/system/csrf-token.php`
- `GET /api/projects/list.php`
- `GET /api/dashboard/summary.php`

With `--skip-db`, only the system endpoints are executed.

## Validation Rules

Each response must:

- return an expected HTTP status code;
- contain valid JSON;
- include the standard `success` field used by OpenMEP APIs.

The command exits with status code `0` when all checks pass and `1` when at least one check fails.

## Local Workflow

1. Configure OpenMEP normally.
2. Run database migrations.
3. Start the application with Apache, Nginx, or PHP's built-in web server.
4. Run the smoke test against the application root URL.

Example with PHP's built-in server from the repository root:

```bash
php -S localhost:8080
php scripts/api-smoke-test.php --base-url=http://localhost:8080
```

## Scope

This command is not a replacement for unit tests or service-level regression tests. It is a fast deployment verification tool for checking whether the application is reachable and whether core API response handling works after installation or release packaging.
