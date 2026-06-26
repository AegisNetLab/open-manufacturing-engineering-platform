#!/usr/bin/env php
<?php

declare(strict_types=1);

use App\Services\MaintenanceService;

require_once dirname(__DIR__) . '/bootstrap.php';

$options = getopt('', ['retention-days::', 'keep::', 'dry-run', 'dir::']);
$retentionDays = isset($options['retention-days']) ? (int) $options['retention-days'] : 30;
$keep = isset($options['keep']) ? (int) $options['keep'] : 3;
$dryRun = array_key_exists('dry-run', $options);
$directory = isset($options['dir'])
    ? (string) $options['dir']
    : dirname(__DIR__) . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'backups';

$service = new MaintenanceService();
$plan = $service->planBackupCleanup($directory, $retentionDays, $keep);
$result = $service->cleanupBackups($directory, $retentionDays, $keep, $dryRun);

printf("Backup directory: %s\n", $directory);
printf("Retention: %d day(s), keep at least: %d file(s)\n", $retentionDays, $keep);
printf("Existing backups: %d (%s)\n", $plan['total_files'], $service->formatBytes($plan['total_bytes']));
printf(
    "%s: %d file(s), %s\n",
    $dryRun ? 'Would delete' : 'Deleted',
    $result['deleted_files'],
    $service->formatBytes($result['deleted_bytes'])
);

foreach ($result['files'] as $file) {
    echo ' - ' . $file . PHP_EOL;
}
