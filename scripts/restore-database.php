#!/usr/bin/env php
<?php

declare(strict_types=1);

use App\Helpers\Database;

require_once dirname(__DIR__) . '/bootstrap.php';

$options = getopt('', ['file:', 'yes']);
$file = $options['file'] ?? null;
$confirmed = array_key_exists('yes', $options);

if (!is_string($file) || $file === '' || !is_file($file)) {
    fwrite(STDERR, 'Usage: php scripts/restore-database.php --file=/path/to/backup.sql --yes' . PHP_EOL);
    exit(1);
}

if (!$confirmed) {
    fwrite(STDERR, 'Restore is destructive. Re-run with --yes to confirm.' . PHP_EOL);
    exit(1);
}

$sql = file_get_contents($file);
if ($sql === false) {
    fwrite(STDERR, 'Could not read backup file.' . PHP_EOL);
    exit(1);
}

try {
    Database::connection()->exec($sql);
    echo 'Database restored from: ' . $file . PHP_EOL;
} catch (Throwable $throwable) {
    fwrite(STDERR, 'Restore failed: ' . $throwable->getMessage() . PHP_EOL);
    exit(1);
}
