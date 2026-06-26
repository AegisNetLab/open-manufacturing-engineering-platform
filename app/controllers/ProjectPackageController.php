<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Helpers\JsonResponse;
use App\Helpers\Request;
use App\Services\ProjectPackageService;
use InvalidArgumentException;

final class ProjectPackageController
{
    public function __construct(private readonly ProjectPackageService $service)
    {
    }

    public function exportProject(): void
    {
        try {
            $projectId = Request::intQuery('project_id');
            $package = $this->service->exportProject($projectId);
            $filename = $this->safeFilename((string) $package['project']['name']) . '.openmep.json';

            header('Content-Type: application/json; charset=utf-8');
            header('Content-Disposition: attachment; filename="' . $filename . '"');
            echo json_encode($package, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
            exit;
        } catch (InvalidArgumentException $exception) {
            JsonResponse::error($exception->getMessage(), $exception->getMessage() === 'Project not found.' ? 404 : 400);
        }
    }

    public function importProject(): void
    {
        try {
            $newProjectId = $this->service->importProject(Request::json());
            JsonResponse::success(['project_id' => $newProjectId], 'Project package imported.', 201);
        } catch (InvalidArgumentException $exception) {
            JsonResponse::error($exception->getMessage(), 400);
        }
    }

    public function exportResultsCsv(): void
    {
        try {
            $projectId = Request::intQuery('project_id');
            $csv = $this->service->resultsCsv($projectId);

            header('Content-Type: text/csv; charset=utf-8');
            header('Content-Disposition: attachment; filename="openmep-results-project-' . $projectId . '.csv"');
            echo $csv;
            exit;
        } catch (InvalidArgumentException $exception) {
            JsonResponse::error($exception->getMessage(), 400);
        }
    }

    private function safeFilename(string $name): string
    {
        $normalized = strtolower(trim((string) preg_replace('/[^A-Za-z0-9]+/', '-', $name), '-'));
        return $normalized !== '' ? $normalized : 'openmep-project';
    }
}
