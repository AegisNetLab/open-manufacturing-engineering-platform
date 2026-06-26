#!/usr/bin/env php
<?php

declare(strict_types=1);

use App\Helpers\Database;
use App\Services\DemoProjectSeederService;

require dirname(__DIR__) . '/bootstrap.php';

$options = getopt('', ['name::', 'force', 'dry-run', 'help']);

if (isset($options['help'])) {
    echo <<<'TXT'
OpenMEP demo project seeder

Usage:
  php scripts/seed-demo-project.php [--name="Demo: Automotive Assembly Line"] [--force] [--dry-run]

Options:
  --name      Demo project name. Defaults to "Demo: Automotive Assembly Line".
  --force     Replace an existing project with the same name.
  --dry-run   Print the seed plan without writing to the database.
  --help      Show this help message.

TXT;
    exit(0);
}

$name = is_string($options['name'] ?? null) && trim((string) $options['name']) !== ''
    ? trim((string) $options['name'])
    : DemoProjectSeederService::DEFAULT_PROJECT_NAME;
$force = isset($options['force']);
$dryRun = isset($options['dry-run']);

$seeder = new DemoProjectSeederService();
$plan = $seeder->buildPlan($name);

echo 'Demo seed plan:' . PHP_EOL;
foreach ($plan as $key => $value) {
    echo sprintf('  - %s: %s', $key, (string) $value) . PHP_EOL;
}

if ($dryRun) {
    echo 'Dry run completed. No database changes were made.' . PHP_EOL;
    exit(0);
}

$result = $seeder->seed(Database::connection(), $name, $force);

echo PHP_EOL . 'Demo project seeded successfully:' . PHP_EOL;
foreach ($result as $key => $value) {
    echo sprintf('  - %s: %s', $key, (string) $value) . PHP_EOL;
}
