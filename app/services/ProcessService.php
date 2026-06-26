<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\ProcessRepository;
use App\Validators\ProcessValidator;
use InvalidArgumentException;

final class ProcessService
{
    public function __construct(
        private readonly ProcessRepository $repository,
        private readonly ProcessValidator $validator,
    ) {
    }

    public function loadProcess(int $projectId): array
    {
        if ($projectId < 1) {
            throw new InvalidArgumentException(json_encode([
                ['field' => 'project_id', 'message' => 'Project ID is required.'],
            ], JSON_THROW_ON_ERROR));
        }

        return $this->repository->loadByProjectId($projectId);
    }

    public function saveProcess(array $payload): array
    {
        $errors = $this->validator->validateSavePayload($payload);
        if ($errors !== []) {
            throw new InvalidArgumentException(json_encode($errors, JSON_THROW_ON_ERROR));
        }

        $normalized = $this->normalize($payload);
        $validation = $this->validator->validateExecutableModel($normalized['operations'], $normalized['connections']);

        return $this->repository->replaceProjectProcess(
            (int) $payload['project_id'],
            $normalized['operations'],
            $normalized['connections'],
            $validation['valid']
        ) + ['validation' => $validation];
    }

    public function validateProcess(array $payload): array
    {
        $errors = $this->validator->validateSavePayload($payload);
        if ($errors !== []) {
            throw new InvalidArgumentException(json_encode($errors, JSON_THROW_ON_ERROR));
        }

        $normalized = $this->normalize($payload);
        return $this->validator->validateExecutableModel($normalized['operations'], $normalized['connections']);
    }

    private function normalize(array $payload): array
    {
        $operations = array_map(static function (array $operation): array {
            $metadata = is_array($operation['metadata'] ?? null) ? $operation['metadata'] : [];
            $metadata['node_id'] = (string) $operation['node_id'];
            $metadata['node_type'] = (string) $operation['node_type'];
            $metadata['x'] = round((float) $operation['x'], 2);
            $metadata['y'] = round((float) $operation['y'], 2);
            $metadata['color'] = (string) ($operation['color'] ?? '#1565C0');
            $resourceId = (int) ($operation['resource_id'] ?? 0);
            $resourceName = trim((string) ($operation['resource_name'] ?? ($operation['required_resource'] ?? '')));
            $metadata['resource_id'] = $resourceId > 0 ? $resourceId : null;
            $metadata['resource_name'] = $resourceName;
            $metadata['required_quantity'] = max(1, (int) ($operation['required_quantity'] ?? 1));
            $metadata['mtbf_hours'] = (float) ($operation['mtbf_hours'] ?? 0);
            $metadata['mttr_hours'] = (float) ($operation['mttr_hours'] ?? 0);
            $metadata['notes'] = trim((string) ($operation['notes'] ?? ''));

            return [
                'node_id' => (string) $operation['node_id'],
                'node_type' => (string) $operation['node_type'],
                'operation_code' => strtoupper(trim((string) $operation['operation_code'])),
                'name' => trim((string) $operation['name']),
                'linked_layout_element_id' => !empty($operation['linked_layout_element_id'])
                    ? (int) $operation['linked_layout_element_id']
                    : null,
                'resource_id' => $resourceId > 0 ? $resourceId : null,
                'resource_name' => $resourceName,
                'required_quantity' => max(1, (int) ($operation['required_quantity'] ?? 1)),
                'cycle_time_seconds' => round(((float) $operation['cycle_time_minutes']) * 60, 2),
                'setup_time_seconds' => round(((float) ($operation['setup_time_minutes'] ?? 0)) * 60, 2),
                'batch_size' => (int) $operation['batch_size'],
                'scrap_rate' => round((float) ($operation['scrap_rate'] ?? 0), 2),
                'rework_rate' => round((float) ($operation['rework_rate'] ?? 0), 2),
                'metadata' => $metadata,
            ];
        }, $payload['operations']);

        $connections = array_map(static fn (array $connection): array => [
            'source_node_id' => (string) $connection['source_node_id'],
            'target_node_id' => (string) $connection['target_node_id'],
            'connection_type' => trim((string) ($connection['connection_type'] ?? 'normal')) ?: 'normal',
            'probability' => round((float) ($connection['probability'] ?? 100), 2),
            'metadata' => is_array($connection['metadata'] ?? null) ? $connection['metadata'] : [],
        ], $payload['connections']);

        return ['operations' => $operations, 'connections' => $connections];
    }
}
