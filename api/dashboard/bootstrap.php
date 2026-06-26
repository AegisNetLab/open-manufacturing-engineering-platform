<?php

declare(strict_types=1);

require dirname(__DIR__, 2) . '/bootstrap.php';

use App\Controllers\DashboardController;
use App\Helpers\Database;
use App\Repositories\DashboardRepository;
use App\Services\DashboardService;

$repository = new DashboardRepository(Database::connection());
$service = new DashboardService($repository);

return new DashboardController($service);
