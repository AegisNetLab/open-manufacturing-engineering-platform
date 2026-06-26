# Observability and Logging

OpenMEP writes structured JSON application logs to `storage/logs`.

## Request ID

Every request receives a generated `request_id`. API JSON responses include this value so a UI error can be matched with the server-side log entry.

## Log files

Log files are written daily using this naming convention:

```text
storage/logs/app-YYYY-MM-DD.log
```

Each log line is a JSON object with:

- `timestamp`
- `level`
- `request_id`
- `message`
- `context`

Sensitive context fields such as `password`, `token`, `secret` and `authorization` are redacted automatically.

## CLI log viewer

```bash
php scripts/tail-log.php
php scripts/tail-log.php --lines=100
php scripts/tail-log.php --level=ERROR
php scripts/tail-log.php --date=2026-06-26
```

## API errors

Global exception handling records unexpected errors and returns a safe JSON response. Validation and API exceptions are logged as warnings, while unhandled exceptions are logged as errors.

## Configuration

The log path is configured in `config/config.php`:

```php
'logging' => [
    'path' => dirname(__DIR__) . '/storage/logs',
],
```
