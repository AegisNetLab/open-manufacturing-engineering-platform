<?php

declare(strict_types=1);

use App\Services\ReleasePackageService;

require_once dirname(__DIR__) . '/bootstrap.php';

$options = getopt('', ['output::', 'version::', 'help']);

if (isset($options['help'])) {
    echo "OpenMEP release builder\n";
    echo "Usage: php scripts/build-release.php [--output=release-dir] [--version=1.0.0]\n";
    exit(0);
}

$root = dirname(__DIR__);
$output = isset($options['output']) && is_string($options['output'])
    ? $options['output']
    : $root . DIRECTORY_SEPARATOR . 'releases';
$version = isset($options['version']) && is_string($options['version']) ? $options['version'] : null;

try {
    $service = new ReleasePackageService();
    $result = $service->build($root, $output, $version);

    echo 'Release archive: ' . $result['archive'] . PHP_EOL;
    echo 'Checksum file:   ' . $result['checksum'] . PHP_EOL;
    echo 'Version:         ' . $result['version'] . PHP_EOL;
    echo 'Files:           ' . $result['files'] . PHP_EOL;
    echo 'Bytes:           ' . $result['bytes'] . PHP_EOL;
} catch (Throwable $throwable) {
    fwrite(STDERR, '[ERROR] ' . $throwable->getMessage() . PHP_EOL);
    exit(1);
}
