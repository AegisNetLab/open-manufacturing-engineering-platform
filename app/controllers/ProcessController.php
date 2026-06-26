<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Helpers\JsonResponse;
use App\Helpers\Request;
use App\Services\ProcessService;
use InvalidArgumentException;
use JsonException;

final class ProcessController
{
    public function __construct(private readonly ProcessService $service)
    {
    }

    public function load(): void
    {
        try {
            $projectId = (int) ($_GET['project_id'] ?? 0);
            JsonResponse::success($this->service->loadProcess($projectId));
        } catch (InvalidArgumentException $exception) {
            $this->validationError($exception);
        }
    }

    public function save(): void
    {
        try {
            JsonResponse::success($this->service->saveProcess(Request::json()), 'Process saved.');
        } catch (InvalidArgumentException $exception) {
            $this->validationError($exception);
        }
    }

    public function validate(): void
    {
        try {
            JsonResponse::success($this->service->validateProcess(Request::json()), 'Process validation completed.');
        } catch (InvalidArgumentException $exception) {
            $this->validationError($exception);
        }
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
