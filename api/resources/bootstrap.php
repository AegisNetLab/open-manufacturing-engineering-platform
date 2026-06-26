<?php

declare(strict_types=1);

require dirname(__DIR__, 2) . '/bootstrap.php';

use App\Controllers\ResourceController;
use App\Helpers\Database;
use App\Repositories\AuditLogRepository;
use App\Repositories\ResourceRepository;
use App\Services\AuditLogService;
use App\Services\ResourceService;
use App\Validators\ResourceValidator;

$repository = new ResourceRepository(Database::connection());
$validator = new ResourceValidator();
$auditLog = new AuditLogService(new AuditLogRepository(Database::connection()));
$service = new ResourceService($repository, $validator, $auditLog);

return new ResourceController($service);
