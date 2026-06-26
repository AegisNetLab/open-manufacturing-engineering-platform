<?php

declare(strict_types=1);

require dirname(__DIR__, 2) . '/bootstrap.php';

use App\Controllers\ReportController;
use App\Helpers\Database;
use App\Repositories\SimulationRepository;
use App\Services\ReportService;

$repository = new SimulationRepository(Database::connection());
$service = new ReportService($repository);

return new ReportController($service);
