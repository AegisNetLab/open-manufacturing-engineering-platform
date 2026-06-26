<?php

declare(strict_types=1);

require dirname(__DIR__, 2) . '/bootstrap.php';

use App\Controllers\LayoutController;
use App\Helpers\Database;
use App\Repositories\LayoutElementRepository;
use App\Services\LayoutService;
use App\Validators\LayoutElementValidator;

$repository = new LayoutElementRepository(Database::connection());
$validator = new LayoutElementValidator();
$service = new LayoutService($repository, $validator);

return new LayoutController($service);
