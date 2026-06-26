<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Helpers\JsonResponse;
use App\Services\ReportService;
use InvalidArgumentException;

final class ReportController
{
    public function __construct(private readonly ReportService $service)
    {
    }

    public function simulationReport(): void
    {
        $projectId = (int) ($_GET['project_id'] ?? 0);
        $runId = isset($_GET['run_id']) && $_GET['run_id'] !== '' ? (int) $_GET['run_id'] : null;

        try {
            $html = $this->service->simulationReportHtml($projectId, $runId);
            header('Content-Type: text/html; charset=utf-8');
            echo $html;
        } catch (InvalidArgumentException $exception) {
            JsonResponse::error($exception->getMessage(), 404, 'report_not_found');
        }
    }
}
