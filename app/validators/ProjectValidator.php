<?php

declare(strict_types=1);

namespace App\Validators;

final class ProjectValidator
{
    private const VALID_PRODUCTION_TYPES = ['serial', 'job_shop', 'mixed'];

    public function validate(array $data, bool $requireId = false): array
    {
        $errors = [];

        if ($requireId && empty($data['id'])) {
            $errors[] = ['field' => 'id', 'message' => 'Project ID is required.'];
        }

        if (empty(trim((string) ($data['name'] ?? '')))) {
            $errors[] = ['field' => 'name', 'message' => 'Project name is required.'];
        }

        $productionType = (string) ($data['production_type'] ?? 'serial');
        if (!in_array($productionType, self::VALID_PRODUCTION_TYPES, true)) {
            $errors[] = ['field' => 'production_type', 'message' => 'Production type is invalid.'];
        }

        $shiftLength = (int) ($data['shift_length_minutes'] ?? 480);
        if ($shiftLength < 1 || $shiftLength > 1440) {
            $errors[] = ['field' => 'shift_length_minutes', 'message' => 'Shift length must be between 1 and 1440 minutes.'];
        }

        return $errors;
    }
}
