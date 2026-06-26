<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Helpers\JsonResponse;
use App\Helpers\Request;
use App\Services\ProjectService;
use InvalidArgumentException;
use JsonException;

final class ProjectController
{
    public function __construct(private readonly ProjectService $service)
    {
    }

    public function list(): void
    {
        JsonResponse::success($this->service->listProjects([
            'query' => $_GET['query'] ?? '',
            'production_type' => $_GET['production_type'] ?? '',
            'sort' => $_GET['sort'] ?? 'updated_at',
            'direction' => $_GET['direction'] ?? 'DESC',
            'page' => $_GET['page'] ?? 1,
            'per_page' => $_GET['per_page'] ?? 10,
        ]));
    }

    public function create(): void
    {
        try {
            $project = $this->service->createProject(Request::json());
            JsonResponse::success(['project' => $project], 'Project created.', 201);
        } catch (InvalidArgumentException $exception) {
            $this->validationError($exception);
        }
    }

    public function update(): void
    {
        try {
            $project = $this->service->updateProject(Request::json());
            if ($project === []) {
                JsonResponse::error('Project not found.', 404);
            }
            JsonResponse::success(['project' => $project], 'Project updated.');
        } catch (InvalidArgumentException $exception) {
            $this->validationError($exception);
        }
    }


    public function duplicate(): void
    {
        try {
            $project = $this->service->duplicateProject(Request::json());
            if ($project === []) {
                JsonResponse::error('Project not found.', 404, 'project_not_found');
            }
            JsonResponse::success(['project' => $project], 'Project duplicated.', 201);
        } catch (InvalidArgumentException $exception) {
            $this->validationError($exception);
        }
    }

    public function delete(): void
    {
        $id = (int) (Request::json()['id'] ?? 0);
        if ($id < 1) {
            JsonResponse::validationError([['field' => 'id', 'message' => 'Project ID is required.']]);
        }

        if (!$this->service->deleteProject($id)) {
            JsonResponse::error('Project not found.', 404);
        }

        JsonResponse::success([], 'Project deleted.');
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
