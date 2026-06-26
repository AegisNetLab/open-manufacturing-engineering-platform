#!/usr/bin/env php
<?php

declare(strict_types=1);

use App\Services\ApiSmokeTestService;

require dirname(__DIR__) . '/bootstrap.php';

$options = getopt('', [
    'base-url:',
    'timeout::',
    'skip-db',
    'json',
    'help',
]);

if (isset($options['help']) || !isset($options['base-url'])) {
    echo <<<'TXT'
OpenMEP API Smoke Test

Usage:
  php scripts/api-smoke-test.php --base-url=http://localhost/openmep [--timeout=5] [--skip-db] [--json]

Options:
  --base-url   Root URL of the running OpenMEP application.
  --timeout    Request timeout in seconds. Default: 5.
  --skip-db    Only test endpoints that do not require a configured database.
  --json       Print machine-readable JSON output.
  --help       Show this help text.

TXT;
    exit(isset($options['base-url']) ? 0 : 1);
}

$baseUrl = (string) $options['base-url'];
$timeout = max(1, (int) ($options['timeout'] ?? 5));
$skipDatabaseChecks = isset($options['skip-db']);
$asJson = isset($options['json']);

$service = new ApiSmokeTestService($baseUrl, $timeout);
$result = $service->run(ApiSmokeTestService::defaultChecks($skipDatabaseChecks));

if ($asJson) {
    echo json_encode($result, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . PHP_EOL;
    exit($result['success'] ? 0 : 1);
}

echo 'OpenMEP API smoke test' . PHP_EOL;
echo 'Base URL: ' . ApiSmokeTestService::normalizeBaseUrl($baseUrl) . PHP_EOL;
echo str_repeat('-', 72) . PHP_EOL;

foreach ($result['checks'] as $check) {
    $status = $check['passed'] ? 'PASS' : 'FAIL';
    $httpStatus = $check['status'] === null ? '---' : (string) $check['status'];
    printf(
        '[%s] %s %s (%s ms, HTTP %s)%s',
        $status,
        $check['method'],
        $check['url'],
        $check['duration_ms'],
        $httpStatus,
        PHP_EOL
    );

    if (!$check['passed']) {
        echo '      ' . $check['message'] . PHP_EOL;
    }
}

echo str_repeat('-', 72) . PHP_EOL;
printf(
    'Result: %d/%d passed, %d failed.%s',
    $result['passed'],
    $result['total'],
    $result['failed'],
    PHP_EOL
);

exit($result['success'] ? 0 : 1);
