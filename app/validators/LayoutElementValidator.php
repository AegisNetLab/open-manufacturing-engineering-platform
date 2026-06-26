<?php

declare(strict_types=1);

namespace App\Validators;

final class LayoutElementValidator
{
    public function validateSavePayload(array $payload): array
    {
        $errors = [];

        if ((int) ($payload['project_id'] ?? 0) < 1) {
            $errors[] = ['field' => 'project_id', 'message' => 'Project ID is required.'];
        }

        if (!isset($payload['elements']) || !is_array($payload['elements'])) {
            $errors[] = ['field' => 'elements', 'message' => 'Layout elements must be provided as an array.'];
            return $errors;
        }

        foreach ($payload['elements'] as $index => $element) {
            $prefix = "elements.$index";
            $name = trim((string) ($element['name'] ?? ''));
            $type = trim((string) ($element['element_type'] ?? ''));
            $x = (float) ($element['x_position'] ?? -1);
            $y = (float) ($element['y_position'] ?? -1);
            $width = (float) ($element['width'] ?? 0);
            $height = (float) ($element['height'] ?? 0);
            $rotation = (int) ($element['rotation'] ?? 0);

            if ($name === '') {
                $errors[] = ['field' => "$prefix.name", 'message' => 'Element name is required.'];
            }

            if ($type === '') {
                $errors[] = ['field' => "$prefix.element_type", 'message' => 'Element type is required.'];
            }

            if ($x < 0 || $y < 0) {
                $errors[] = ['field' => "$prefix.position", 'message' => 'Coordinates cannot be negative.'];
            }

            if ($width <= 0 || $height <= 0) {
                $errors[] = ['field' => "$prefix.size", 'message' => 'Width and height must be positive.'];
            }

            if ($rotation < 0 || $rotation > 359) {
                $errors[] = ['field' => "$prefix.rotation", 'message' => 'Rotation must be between 0 and 359 degrees.'];
            }
        }

        return $errors;
    }
}
