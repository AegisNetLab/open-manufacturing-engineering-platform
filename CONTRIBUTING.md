# Contributing to OpenMEP

Thank you for considering a contribution to the Open Manufacturing Engineering Platform.

## Development Principles

- Keep the application modular and readable.
- Follow PSR-12 for PHP code.
- Use ES2022 JavaScript modules for frontend code.
- Keep controllers thin; business logic belongs in services.
- Keep SQL inside repository classes only.
- All API endpoints must return the standard JSON response format.
- Update documentation when behavior changes.

## Local Development

1. Create a MySQL database named `openmep`.
2. Import `database/schema.sql`.
3. Copy `config/config.example.php` to `config/config.php`.
4. Adjust database credentials.
5. Start PHP's local server:

```bash
php -S localhost:8000
```

## Pull Request Checklist

- PHP syntax checks pass.
- JavaScript syntax checks pass where applicable.
- Database migrations or schema changes are documented.
- New or changed APIs are documented in `docs/API.md`.
- The end-to-end workflow remains intact.
