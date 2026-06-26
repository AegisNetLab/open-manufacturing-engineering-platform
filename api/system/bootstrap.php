<?php

declare(strict_types=1);

require dirname(__DIR__, 2) . '/bootstrap.php';

use App\Controllers\SystemController;
use App\Helpers\Database;
use App\Services\SystemService;

return new SystemController(new SystemService(Database::connection()));
