<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Helpers\JsonResponse;
use App\Helpers\Request;
use App\Services\ProjectDiagnosticsService;
use InvalidArgumentException;
use JsonException;

final class ProjectDiagnosticsController
{
    public function __construct(private readonly ProjectDiagnosticsService $service)
    {
    }

    public function diagnose(): void
    {
        try {
            JsonResponse::success(
                $this->service->diagnose(Request::intQuery('project_id')),
                'Project diagnostics completed.'
            );
        } catch (InvalidArgumentException $exception) {
            try {
                JsonResponse::validationError(json_decode($exception->getMessage(), true, 512, JSON_THROW_ON_ERROR));
            } catch (JsonException) {
                JsonResponse::error('Project diagnostics failed.', 400, 'project_diagnostics_failed');
            }
        }
    }
}
