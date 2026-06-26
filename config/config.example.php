<?php

declare(strict_types=1);

return [
    'app' => [
        'name' => 'Open Manufacturing Engineering Platform',
        'environment' => 'development',
        'debug' => true,
    ],
    'logging' => [
        'path' => dirname(__DIR__) . '/storage/logs',
    ],
    'database' => [
        'host' => '127.0.0.1',
        'port' => 3306,
        'name' => 'openmep',
        'user' => 'root',
        'password' => '',
        'charset' => 'utf8mb4',
    ],
];
