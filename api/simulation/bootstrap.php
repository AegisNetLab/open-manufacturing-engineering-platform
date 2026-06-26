<?php

declare(strict_types=1);

require dirname(__DIR__, 2) . '/bootstrap.php';

use App\Controllers\SimulationController;
use App\Helpers\Database;
use App\Repositories\SimulationRepository;
use App\Services\SimulationService;
use App\Validators\SimulationValidator;

$repository = new SimulationRepository(Database::connection());
$validator = new SimulationValidator();
$service = new SimulationService($repository, $validator);

return new SimulationController($service);
