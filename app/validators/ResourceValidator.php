<?php

declare(strict_types=1);

namespace App\Validators;

final class ResourceValidator
{
    private const ALLOWED_TYPES = ['machine', 'operator', 'tool', 'buffer', 'transport'];

    public function validate(array $data, bool $requireId = false): array
    {
        $errors = [];

        if ($requireId && (int) ($data['id'] ?? 0) < 1) {
            $errors[] = ['field' => 'id', 'message' => 'Resource ID is required.'];
        }

        if ((int) ($data['project_id'] ?? 0) < 1) {
            $errors[] = ['field' => 'project_id', 'message' => 'Project ID is required.'];
        }

        $name = trim((string) ($data['name'] ?? ''));
        if ($name === '') {
            $errors[] = ['field' => 'name', 'message' => 'Resource name is required.'];
        } elseif (strlen($name) > 100) {
            $errors[] = ['field' => 'name', 'message' => 'Resource name must be 100 characters or shorter.'];
        }

        $type = (string) ($data['resource_type'] ?? '');
        if (!in_array($type, self::ALLOWED_TYPES, true)) {
            $errors[] = ['field' => 'resource_type', 'message' => 'Resource type is invalid.'];
        }

        $quantity = (int) ($data['quantity'] ?? 0);
        if ($quantity < 1) {
            $errors[] = ['field' => 'quantity', 'message' => 'Quantity must be at least 1.'];
        }

        return $errors;
    }
}
