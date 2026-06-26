# Security Hardening

OpenMEP v1 security hardening adds baseline browser and API protections without introducing user authentication.

## Implemented controls

- Security response headers are sent from the shared PHP bootstrap.
- Unsafe API methods require a CSRF token.
- The shared ES2022 API client fetches the CSRF token lazily and sends it through the `X-CSRF-Token` header.
- CSRF tokens are stored in the PHP session and generated with `random_bytes()`.
- CSRF failures return a JSON API error with status `419` and error code `csrf_token_invalid`.

## Security headers

The application sends:

- `X-Content-Type-Options: nosniff`
- `X-Frame-Options: SAMEORIGIN`
- `Referrer-Policy: strict-origin-when-cross-origin`
- `Permissions-Policy: camera=(), microphone=(), geolocation=()`
- `Strict-Transport-Security` when HTTPS is detected

## CSRF endpoint

```http
GET /api/system/csrf-token.php
```

Successful response:

```json
{
  "success": true,
  "request_id": "...",
  "data": {
    "token": "..."
  },
  "message": "CSRF token generated."
}
```

## Client behavior

`public/js/api.js` automatically retrieves and attaches the token for `POST`, `PUT`, `PATCH` and `DELETE` style requests. Module code should continue to use the common `ApiClient` rather than direct `fetch()` calls for persistent changes.

## Limitations

This is not an authentication or authorization system. Multi-user accounts, roles and permissions remain future extensions. For MVP, the goal is to protect same-session browser API calls and reduce common deployment risks.
