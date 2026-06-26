<?php

declare(strict_types=1);

require dirname(__DIR__, 2) . '/bootstrap.php';

use App\Controllers\SimulationScenarioController;
use App\Helpers\Database;
use App\Repositories\SimulationScenarioRepository;
use App\Services\SimulationScenarioService;
use App\Validators\SimulationScenarioValidator;

$repository = new SimulationScenarioRepository(Database::connection());
$validator = new SimulationScenarioValidator();
$service = new SimulationScenarioService($repository, $validator);

return new SimulationScenarioController($service);
