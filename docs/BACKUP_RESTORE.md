# Backup and Restore

OpenMEP includes lightweight database backup and restore scripts for local MVP installations.

## Create a backup

```bash
php scripts/backup-database.php
```

By default, SQL backups are written to:

```text
storage/backups/
```

A custom output directory can be provided:

```bash
php scripts/backup-database.php --output=/path/to/backups
```

## Restore a backup

Restoring is destructive and must be confirmed explicitly:

```bash
php scripts/restore-database.php --file=/path/to/openmep-backup.sql --yes
```

## Notes

- The backup format is plain SQL.
- Foreign key checks are disabled during restore and re-enabled afterwards.
- Backups are intended for development, small installations and pre-release safety snapshots.
- Production installations should also use server-level database backups.
