<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Helpers\JsonResponse;
use App\Helpers\Request;
use App\Services\ResourceService;
use InvalidArgumentException;
use JsonException;

final class ResourceController
{
    public function __construct(private readonly ResourceService $service)
    {
    }

    public function list(): void
    {
        $projectId = (int) ($_GET['project_id'] ?? 0);
        if ($projectId < 1) {
            JsonResponse::validationError([['field' => 'project_id', 'message' => 'Project ID is required.']]);
        }

        JsonResponse::success(['resources' => $this->service->listResources($projectId)]);
    }

    public function save(): void
    {
        try {
            $resource = $this->service->saveResource(Request::json());
            if ($resource === []) {
                JsonResponse::error('Resource not found.', 404);
            }

            JsonResponse::success(['resource' => $resource], 'Resource saved.');
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
                ['field' => 'id', 'message' => 'Resource ID is required.'],
                ['field' => 'project_id', 'message' => 'Project ID is required.'],
            ]);
        }

        if (!$this->service->deleteResource($id, $projectId)) {
            JsonResponse::error('Resource not found.', 404);
        }

        JsonResponse::success([], 'Resource deleted.');
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
