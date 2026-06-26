<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\LayoutElementRepository;
use App\Validators\LayoutElementValidator;
use InvalidArgumentException;

final class LayoutService
{
    public function __construct(
        private readonly LayoutElementRepository $repository,
        private readonly LayoutElementValidator $validator,
    ) {
    }

    public function loadLayout(int $projectId): array
    {
        if ($projectId < 1) {
            throw new InvalidArgumentException(json_encode([
                ['field' => 'project_id', 'message' => 'Project ID is required.'],
            ], JSON_THROW_ON_ERROR));
        }

        return $this->repository->findByProjectId($projectId);
    }

    public function saveLayout(array $payload): array
    {
        $errors = $this->validator->validateSavePayload($payload);
        if ($errors !== []) {
            throw new InvalidArgumentException(json_encode($errors, JSON_THROW_ON_ERROR));
        }

        $projectId = (int) $payload['project_id'];
        $elements = array_map(static function (array $element): array {
            return [
                'name' => trim((string) $element['name']),
                'element_type' => trim((string) $element['element_type']),
                'x_position' => round((float) $element['x_position'], 2),
                'y_position' => round((float) $element['y_position'], 2),
                'width' => round((float) $element['width'], 2),
                'height' => round((float) $element['height'], 2),
                'rotation' => (int) $element['rotation'],
                'color' => $element['color'] ?? null,
                'metadata' => is_array($element['metadata'] ?? null) ? $element['metadata'] : [],
            ];
        }, $payload['elements']);

        return $this->repository->replaceProjectLayout($projectId, $elements);
    }
}
