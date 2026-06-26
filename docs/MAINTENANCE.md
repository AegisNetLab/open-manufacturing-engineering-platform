# Maintenance Utilities

OpenMEP includes lightweight maintenance scripts for self-hosted installations. These scripts are intentionally simple and do not require Docker, Composer, or external services.

## Backup Cleanup

Database backups created by `scripts/backup-database.php` are stored in `storage/backups`. Use the cleanup script to remove old SQL backup files while keeping a minimum number of recent backups.

Dry run:

```bash
php scripts/cleanup-backups.php --dry-run --retention-days=30 --keep=3
```

Apply cleanup:

```bash
php scripts/cleanup-backups.php --retention-days=30 --keep=3
```

Optional custom backup directory:

```bash
php scripts/cleanup-backups.php --dir=/path/to/backups --retention-days=14 --keep=5
```

## Retention Policy

The default policy keeps at least three newest backup files and deletes older `.sql` backups with an age greater than or equal to the configured retention window.

Recommended production practice:

- create database backups before upgrades;
- store off-server copies for disaster recovery;
- run backup cleanup periodically from cron;
- test restore procedures before relying on backups.

Example weekly cron entry:

```cron
0 3 * * 0 cd /var/www/openmep && php scripts/cleanup-backups.php --retention-days=30 --keep=3
```

## Safety Notes

The cleanup script only targets files ending in `.sql` inside the selected backup directory. Use `--dry-run` before enabling scheduled cleanup.
