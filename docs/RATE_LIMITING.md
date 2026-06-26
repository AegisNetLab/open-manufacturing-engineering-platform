# Rate Limiting

OpenMEP applies a lightweight file-based rate limiter to API requests as part of the MVP security baseline.

## Purpose

The limiter protects the PHP API from accidental request floods and basic automated abuse while keeping installation simple for shared hosting and local MySQL/PHP deployments.

## Default Policy

- Scope: API endpoint and HTTP method
- Identity: client IP address
- Limit: 120 requests
- Window: 60 seconds
- Storage: `storage/rate_limits`

When the limit is exceeded, the API returns HTTP `429 Too Many Requests` with the machine-readable error code `rate_limit_exceeded`.

## Response Headers

Successful API responses include:

- `X-RateLimit-Limit`
- `X-RateLimit-Remaining`
- `X-RateLimit-Reset`

Rate-limited responses additionally include:

- `Retry-After`

## Design Notes

The implementation intentionally avoids Redis or external infrastructure because the MVP targets simple PHP/MySQL installation. The class is isolated in `App\Helpers\RateLimiter`, so replacing the file backend with Redis or database-backed counters is straightforward in a future release.

## Operational Notes

The rate limit storage directory can be safely cleared during maintenance. Buckets are automatically recreated on demand.
