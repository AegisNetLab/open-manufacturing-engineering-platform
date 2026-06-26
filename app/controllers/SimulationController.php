<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Helpers\JsonResponse;
use App\Helpers\Request;
use App\Services\SimulationService;
use InvalidArgumentException;
use JsonException;

final class SimulationController
{
    public function __construct(private readonly SimulationService $service)
    {
    }

    public function run(): void
    {
        try {
            JsonResponse::success($this->service->runSimulation(Request::json()), 'Simulation completed.');
        } catch (InvalidArgumentException $exception) {
            $this->validationError($exception);
        }
    }

    public function status(): void
    {
        $projectId = (int) ($_GET['project_id'] ?? 0);
        if ($projectId < 1) {
            JsonResponse::validationError([['field' => 'project_id', 'message' => 'Project ID is required.']]);
        }

        JsonResponse::success(['latest_run' => $this->service->latestRun($projectId)]);
    }

    public function results(): void
    {
        $projectId = (int) ($_GET['project_id'] ?? 0);
        if ($projectId < 1) {
            JsonResponse::validationError([['field' => 'project_id', 'message' => 'Project ID is required.']]);
        }

        JsonResponse::success(['results' => $this->service->results($projectId)]);
    }

    private function validationError(InvalidArgumentException $exception): void
    {
        try {
            $errors = json_decode($exception->getMessage(), true, 512, JSON_THROW_ON_ERROR);
            JsonResponse::validationError($errors);
        } catch (JsonException) {
            JsonResponse::error('Validation failed.', 400);
        }
    }
}
