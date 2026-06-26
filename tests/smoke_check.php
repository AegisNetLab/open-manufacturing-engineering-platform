<?php

declare(strict_types=1);

$root = dirname(__DIR__);
$requiredFiles = [
    'index.php',
    'bootstrap.php',
    'database/schema.sql',
    'app/helpers/Database.php',
    'app/helpers/JsonResponse.php',
    'app/controllers/ProjectController.php',
    'app/controllers/LayoutController.php',
    'app/controllers/ResourceController.php',
    'app/controllers/ProcessController.php',
    'app/controllers/SimulationController.php',
    'public/js/api.js',
    'public/js/projects.js',
    'public/js/layout.js',
    'public/js/resources.js',
    'public/js/process.js',
    'public/js/simulation.js',
];

$missing = [];
foreach ($requiredFiles as $file) {
    if (!is_file($root . DIRECTORY_SEPARATOR . $file)) {
        $missing[] = $file;
    }
}

if ($missing !== []) {
    fwrite(STDERR, "Missing required files:\n- " . implode("\n- ", $missing) . "\n");
    exit(1);
}

$phpFiles = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root));
$errors = [];
foreach ($phpFiles as $file) {
    if (!$file->isFile() || $file->getExtension() !== 'php') {
        continue;
    }

    $command = 'php -l ' . escapeshellarg($file->getPathname()) . ' 2>&1';
    exec($command, $output, $exitCode);
    if ($exitCode !== 0) {
        $errors[] = $file->getPathname() . PHP_EOL . implode(PHP_EOL, $output);
    }
}

if ($errors !== []) {
    fwrite(STDERR, implode(PHP_EOL, $errors) . PHP_EOL);
    exit(1);
}

echo "OpenMEP smoke check passed.\n";
