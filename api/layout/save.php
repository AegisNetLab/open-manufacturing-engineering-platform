<?php

/** @var App\Controllers\LayoutController $controller */
$controller = require __DIR__ . '/bootstrap.php';

App\Helpers\ApiGuard::requireMethod('POST');
$controller->save();
