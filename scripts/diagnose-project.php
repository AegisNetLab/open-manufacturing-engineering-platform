<?php

declare(strict_types=1);

require dirname(__DIR__) . '/bootstrap.php';

use App\Helpers\Database;
use App\Repositories\ProcessRepository;
use App\Repositories\ProjectDiagnosticsRepository;
use App\Services\ProjectDiagnosticsService;
use App\Validators\ProcessValidator;

$projectId = 0;
foreach ($argv as $argument) {
    if (str_starts_with($argument, '--project-id=')) {
        $projectId = (int) substr($argument, strlen('--project-id='));
    }
}

if ($projectId < 1) {
    fwrite(STDERR, "Usage: php scripts/diagnose-project.php --project-id=1\n");
    exit(1);
}

$connection = Database::connection();
$service = new ProjectDiagnosticsService(
    new ProjectDiagnosticsRepository($connection),
    new ProcessRepository($connection),
    new ProcessValidator()
);

try {
    $report = $service->diagnose($projectId);
} catch (InvalidArgumentException $exception) {
    fwrite(STDERR, $exception->getMessage() . PHP_EOL);
    exit(1);
}

echo 'OpenMEP Project Diagnostics' . PHP_EOL;
echo 'Project ID: ' . $report['project_id'] . PHP_EOL;
echo 'Readiness: ' . strtoupper((string) $report['readiness']) . PHP_EOL;
echo PHP_EOL . 'Checks:' . PHP_EOL;
foreach ($report['checks'] as $check) {
    $mark = $check['status'] === 'passed' ? 'OK ' : 'FAIL';
    echo sprintf('- [%s] %s (%s)%s', $mark, $check['message'], $check['severity'], PHP_EOL);
}

echo PHP_EOL . 'Counts:' . PHP_EOL;
foreach ($report['counts'] as $name => $count) {
    echo sprintf('- %s: %d%s', $name, $count, PHP_EOL);
}

echo PHP_EOL . 'Next action: ' . $report['summary']['next_action'] . PHP_EOL;

exit($report['can_run_simulation'] ? 0 : 2);
