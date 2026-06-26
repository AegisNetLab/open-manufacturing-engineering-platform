<?php

declare(strict_types=1);

$controller = require __DIR__ . '/bootstrap.php';

App\Helpers\ApiGuard::requireMethod('GET');
$controller->list();
