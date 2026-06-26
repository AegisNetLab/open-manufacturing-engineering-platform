<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Helpers\JsonResponse;
use App\Services\DashboardService;

final class DashboardController
{
    public function __construct(private readonly DashboardService $service)
    {
    }

    public function summary(): void
    {
        JsonResponse::success($this->service->summary());
    }
}
