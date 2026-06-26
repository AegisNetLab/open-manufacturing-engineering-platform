<?php

declare(strict_types=1);

use App\Controllers\ProcessController;
use App\Helpers\Database;
use App\Repositories\ProcessRepository;
use App\Services\ProcessService;
use App\Validators\ProcessValidator;

require dirname(__DIR__, 2) . '/bootstrap.php';

$connection = Database::connection();
$controller = new ProcessController(
    new ProcessService(
        new ProcessRepository($connection),
        new ProcessValidator()
    )
);
