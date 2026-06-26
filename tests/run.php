<?php

declare(strict_types=1);

use Tests\Support\TestCase;

require_once dirname(__DIR__) . '/bootstrap.php';

spl_autoload_register(static function (string $class): void {
    $prefix = 'Tests\\';
    if (!str_starts_with($class, $prefix)) {
        return;
    }

    $relative = str_replace('\\', DIRECTORY_SEPARATOR, substr($class, strlen($prefix)));
    $file = __DIR__ . DIRECTORY_SEPARATOR . $relative . '.php';
    if (is_file($file)) {
        require_once $file;
    }
});

$testFiles = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(__DIR__ . '/Unit'));
$classes = [];
foreach ($testFiles as $file) {
    if (!$file->isFile() || $file->getExtension() !== 'php') {
        continue;
    }

    $relative = substr($file->getPathname(), strlen(__DIR__ . DIRECTORY_SEPARATOR));
    $class = 'Tests\\' . str_replace([DIRECTORY_SEPARATOR, '.php'], ['\\', ''], $relative);
    $classes[] = $class;
}

sort($classes);
$totalAssertions = 0;
$failures = [];
$progress = '';

foreach ($classes as $class) {
    if (!class_exists($class)) {
        $failures[] = [$class, 'Test class could not be loaded.'];
        continue;
    }

    $test = new $class();
    if (!$test instanceof TestCase) {
        $failures[] = [$class, 'Test class must extend Tests\\Support\\TestCase.'];
        continue;
    }

    try {
        $test->run();
        $totalAssertions += $test->assertions();
        $progress .= '.';
    } catch (Throwable $throwable) {
        $failures[] = [$class, $throwable->getMessage()];
        $progress .= 'F';
    }
}

echo $progress . PHP_EOL;

if ($failures !== []) {
    foreach ($failures as [$class, $message]) {
        fwrite(STDERR, "[FAIL] {$class}: {$message}" . PHP_EOL);
    }
    fwrite(STDERR, count($failures) . " test class(es) failed." . PHP_EOL);
    exit(1);
}

echo count($classes) . " test class(es), {$totalAssertions} assertion(s) passed." . PHP_EOL;
