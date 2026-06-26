<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Helpers\JsonResponse;
use App\Helpers\Request;
use App\Services\SimulationScenarioService;
use InvalidArgumentException;
use JsonException;

final class SimulationScenarioController
{
    public function __construct(private readonly SimulationScenarioService $service)
    {
    }

    public function list(): void
    {
        $projectId = (int) ($_GET['project_id'] ?? 0);
        if ($projectId < 1) {
            JsonResponse::validationError([['field' => 'project_id', 'message' => 'Project ID is required.']]);
        }

        JsonResponse::success(['scenarios' => $this->service->listScenarios($projectId)]);
    }

    public function save(): void
    {
        try {
            $scenario = $this->service->saveScenario(Request::json());
            if ($scenario === []) {
                JsonResponse::error('Scenario not found.', 404, 'scenario_not_found');
            }

            JsonResponse::success(['scenario' => $scenario], 'Scenario saved.');
        } catch (InvalidArgumentException $exception) {
            $this->validationError($exception);
        }
    }

    public function delete(): void
    {
        $data = Request::json();
        $id = (int) ($data['id'] ?? 0);
        $projectId = (int) ($data['project_id'] ?? 0);

        if ($id < 1 || $projectId < 1) {
            JsonResponse::validationError([
                ['field' => 'id', 'message' => 'Scenario ID is required.'],
                ['field' => 'project_id', 'message' => 'Project ID is required.'],
            ]);
        }

        if (!$this->service->deleteScenario($id, $projectId)) {
            JsonResponse::error('Scenario not found.', 404, 'scenario_not_found');
        }

        JsonResponse::success([], 'Scenario deleted.');
    }

    private function validationError(InvalidArgumentException $exception): void
    {
        try {
            $errors = json_decode($exception->getMessage(), true, 512, JSON_THROW_ON_ERROR);
            JsonResponse::validationError($errors);
        } catch (JsonException) {
            JsonResponse::error('Validation failed.', 400, 'validation_failed');
        }
    }
}
