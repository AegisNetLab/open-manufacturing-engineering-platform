<?php

declare(strict_types=1);

require dirname(__DIR__, 2) . '/bootstrap.php';

use App\Controllers\ProjectPackageController;
use App\Helpers\Database;
use App\Repositories\ProjectPackageRepository;
use App\Services\ProjectPackageService;

$repository = new ProjectPackageRepository(Database::connection());
$service = new ProjectPackageService($repository);

return new ProjectPackageController($service);
