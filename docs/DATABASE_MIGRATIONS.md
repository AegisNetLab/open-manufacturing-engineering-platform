# Database Migrations

OpenMEP now includes a lightweight PHP migration runner for incremental database changes.

The legacy `database/schema.sql` file remains available for simple first-time local setup, but new schema changes should be added as versioned migration classes under `app/migrations`.

## Commands

List registered migrations without connecting to MySQL:

```bash
php scripts/migrate.php --dry-run
```

Show applied and pending migrations:

```bash
php scripts/migrate.php --status
```

Run pending migrations:

```bash
php scripts/migrate.php
```

## Migration Rules

- Each migration has a stable version string.
- Migration versions must be unique.
- The runner records applied versions in `schema_migrations`.
- Migrations must be idempotent where practical.
- SQL must stay out of controllers.
- Schema changes should preserve backward compatibility unless an approved implementation decision says otherwise.

## Creating a New Migration

1. Add a new class in `app/migrations`.
2. Implement `MigrationInterface`.
3. Register it in `MigrationRunner::defaultMigrations()`.
4. Add or update tests where possible.
5. Run:

```bash
./scripts/run-quality-checks.sh
```

## Initial Database Creation

The configured database, for example `openmep`, must exist before running the migration runner. For a clean local installation, either create it manually or import `database/schema.sql`.

```sql
CREATE DATABASE openmep CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```
