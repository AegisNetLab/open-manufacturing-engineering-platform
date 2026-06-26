<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Helpers\JsonResponse;
use App\Services\SystemService;

final class SystemController
{
    public function __construct(private readonly SystemService $service)
    {
    }

    public function health(): void
    {
        $health = $this->service->health();
        $statusCode = $health['status'] === 'ok' ? 200 : 503;

        JsonResponse::success($health, 'System health check completed.', $statusCode);
    }
}
