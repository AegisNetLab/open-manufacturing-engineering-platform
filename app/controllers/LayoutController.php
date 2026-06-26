<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Helpers\JsonResponse;
use App\Helpers\Request;
use App\Services\LayoutService;
use InvalidArgumentException;
use JsonException;

final class LayoutController
{
    public function __construct(private readonly LayoutService $service)
    {
    }

    public function load(): void
    {
        try {
            $projectId = (int) ($_GET['project_id'] ?? 0);
            JsonResponse::success(['elements' => $this->service->loadLayout($projectId)]);
        } catch (InvalidArgumentException $exception) {
            $this->validationError($exception);
        }
    }

    public function save(): void
    {
        try {
            $elements = $this->service->saveLayout(Request::json());
            JsonResponse::success(['elements' => $elements], 'Layout saved.');
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
