#!/usr/bin/env php
<?php

declare(strict_types=1);

use App\Helpers\Database;
use App\Services\DatabaseBackupService;

require_once dirname(__DIR__) . '/bootstrap.php';

$options = getopt('', ['output::']);
$outputDirectory = $options['output'] ?? (dirname(__DIR__) . '/storage/backups');

try {
    $service = new DatabaseBackupService(Database::connection());
    $result = $service->createBackup((string) $outputDirectory);

    echo 'Backup created: ' . $result['file'] . PHP_EOL;
    echo 'Tables: ' . $result['tables'] . PHP_EOL;
    echo 'Rows: ' . $result['rows'] . PHP_EOL;
    echo 'Bytes: ' . $result['bytes'] . PHP_EOL;
} catch (Throwable $throwable) {
    fwrite(STDERR, 'Backup failed: ' . $throwable->getMessage() . PHP_EOL);
    exit(1);
}
