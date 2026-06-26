<?php

declare(strict_types=1);

require dirname(__DIR__, 2) . '/bootstrap.php';

use App\Controllers\ProjectDiagnosticsController;
use App\Helpers\Database;
use App\Repositories\ProcessRepository;
use App\Repositories\ProjectDiagnosticsRepository;
use App\Services\ProjectDiagnosticsService;
use App\Validators\ProcessValidator;

$connection = Database::connection();

return new ProjectDiagnosticsController(new ProjectDiagnosticsService(
    new ProjectDiagnosticsRepository($connection),
    new ProcessRepository($connection),
    new ProcessValidator()
));
