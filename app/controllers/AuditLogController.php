<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Helpers\JsonResponse;
use App\Services\AuditLogService;

final class AuditLogController
{
    public function __construct(private readonly AuditLogService $service)
    {
    }

    public function list(): void
    {
        $projectId = isset($_GET['project_id']) ? max(1, (int) $_GET['project_id']) : null;
        $limit = isset($_GET['limit']) ? (int) $_GET['limit'] : 100;

        JsonResponse::success([
            'events' => $this->service->recent($projectId, $limit),
        ]);
    }
}
