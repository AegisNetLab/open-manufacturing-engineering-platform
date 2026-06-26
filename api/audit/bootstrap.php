<?php

declare(strict_types=1);

require dirname(__DIR__, 2) . '/bootstrap.php';

use App\Controllers\AuditLogController;
use App\Helpers\Database;
use App\Repositories\AuditLogRepository;
use App\Services\AuditLogService;

$repository = new AuditLogRepository(Database::connection());
$service = new AuditLogService($repository);

return new AuditLogController($service);
