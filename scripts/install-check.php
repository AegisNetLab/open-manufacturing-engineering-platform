<?php

declare(strict_types=1);

require dirname(__DIR__) . '/bootstrap.php';

use App\Helpers\Database;
use App\Services\SystemService;

$connection = null;
try {
    $connection = Database::connection();
} catch (Throwable) {
    // The health service reports database status when no connection is available.
}

$service = new SystemService($connection);
$health = $service->health();

foreach ($health['checks'] as $name => $check) {
    $status = strtoupper((string) ($check['status'] ?? 'unknown'));
    echo sprintf("[%s] %s%s", $status, $name, PHP_EOL);
}

echo sprintf("Overall status: %s%s", strtoupper((string) $health['status']), PHP_EOL);

exit($health['status'] === 'ok' ? 0 : 1);
