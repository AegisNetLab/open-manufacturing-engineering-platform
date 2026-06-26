<?php

declare(strict_types=1);

use App\Helpers\Autoloader;
use App\Helpers\Database;
use App\Migrations\MigrationRunner;

require dirname(__DIR__) . '/app/helpers/Autoloader.php';

Autoloader::register();

$options = getopt('', ['dry-run', 'status', 'help']);

if (isset($options['help'])) {
    echo <<<TXT
OpenMEP Database Migration Tool

Usage:
  php scripts/migrate.php              Run pending migrations
  php scripts/migrate.php --status     Show migration status
  php scripts/migrate.php --dry-run    List registered migrations without connecting to MySQL
  php scripts/migrate.php --help       Show this help text

TXT;
    exit(0);
}

if (isset($options['dry-run'])) {
    echo 'Registered migrations:' . PHP_EOL;
    foreach (MigrationRunner::defaultMigrations() as $migration) {
        echo sprintf('- %s: %s', $migration->version(), $migration->description()) . PHP_EOL;
    }
    exit(0);
}

try {
    $runner = new MigrationRunner(Database::connection());

    if (isset($options['status'])) {
        foreach ($runner->status() as $row) {
            echo sprintf('[%s] %s - %s', $row['status'], $row['version'], $row['description']) . PHP_EOL;
        }
        exit(0);
    }

    $executed = $runner->migrate();
    if ($executed === []) {
        echo 'Database is already up to date.' . PHP_EOL;
        exit(0);
    }

    foreach ($executed as $version) {
        echo 'Applied migration: ' . $version . PHP_EOL;
    }
    echo 'Migration completed successfully.' . PHP_EOL;
} catch (Throwable $throwable) {
    fwrite(STDERR, 'Migration failed: ' . $throwable->getMessage() . PHP_EOL);
    exit(1);
}
