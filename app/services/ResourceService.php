<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Resource;
use App\Repositories\ResourceRepository;
use App\Validators\ResourceValidator;
use InvalidArgumentException;

final class ResourceService
{
    public function __construct(
        private readonly ResourceRepository $repository,
        private readonly ResourceValidator $validator,
        private readonly ?AuditLogService $auditLog = null
    ) {
    }

    public function listResources(int $projectId): array
    {
        return array_map(
            static fn (Resource $resource): array => $resource->toArray(),
            $this->repository->findByProject($projectId)
        );
    }

    public function saveResource(array $data): array
    {
        $requireId = isset($data['id']) && (int) $data['id'] > 0;
        $errors = $this->validator->validate($data, $requireId);
        if ($errors !== []) {
            throw new InvalidArgumentException(json_encode($errors, JSON_THROW_ON_ERROR));
        }

        $resource = Resource::fromArray([
            'id' => $requireId ? (int) $data['id'] : null,
            'project_id' => (int) $data['project_id'],
            'name' => trim((string) $data['name']),
            'resource_type' => (string) $data['resource_type'],
            'quantity' => (int) $data['quantity'],
            'metadata' => $this->normalizeMetadata($data['metadata'] ?? []),
        ]);

        if ($requireId) {
            $updated = $this->repository->update($resource);
            if ($updated !== null) {
                $this->auditLog?->record(
                    $updated->projectId,
                    'resource',
                    $updated->id,
                    'updated',
                    'Resource updated: ' . $updated->name,
                    ['resource_type' => $updated->resourceType, 'quantity' => $updated->quantity]
                );
            }

            return $updated?->toArray() ?? [];
        }

        $created = $this->repository->create($resource);
        $this->auditLog?->record(
            $created->projectId,
            'resource',
            $created->id,
            'created',
            'Resource created: ' . $created->name,
            ['resource_type' => $created->resourceType, 'quantity' => $created->quantity]
        );

        return $created->toArray();
    }

    public function deleteResource(int $id, int $projectId): bool
    {
        $deleted = $this->repository->delete($id, $projectId);
        if ($deleted) {
            $this->auditLog?->record(
                $projectId,
                'resource',
                $id,
                'deleted',
                'Resource deleted.',
                []
            );
        }

        return $deleted;
    }

    private function normalizeMetadata(mixed $metadata): array
    {
        if (!is_array($metadata)) {
            return [];
        }

        return [
            'linked_layout_element_id' => isset($metadata['linked_layout_element_id']) && $metadata['linked_layout_element_id'] !== ''
                ? (int) $metadata['linked_layout_element_id']
                : null,
            'availability_percent' => isset($metadata['availability_percent']) ? (float) $metadata['availability_percent'] : 100.0,
            'hourly_rate' => isset($metadata['hourly_rate']) && $metadata['hourly_rate'] !== '' ? (float) $metadata['hourly_rate'] : null,
            'notes' => trim((string) ($metadata['notes'] ?? '')),
        ];
    }
}
