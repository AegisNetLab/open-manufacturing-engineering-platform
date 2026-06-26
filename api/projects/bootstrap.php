<?php

declare(strict_types=1);

require dirname(__DIR__, 2) . '/bootstrap.php';

use App\Controllers\ProjectController;
use App\Helpers\Database;
use App\Repositories\AuditLogRepository;
use App\Repositories\ProjectRepository;
use App\Services\AuditLogService;
use App\Services\ProjectService;
use App\Validators\ProjectValidator;

$repository = new ProjectRepository(Database::connection());
$validator = new ProjectValidator();
$auditLog = new AuditLogService(new AuditLogRepository(Database::connection()));
$service = new ProjectService($repository, $validator, $auditLog);

return new ProjectController($service);
