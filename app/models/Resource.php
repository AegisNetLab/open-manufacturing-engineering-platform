<?php

declare(strict_types=1);

namespace App\Models;

final class Resource
{
    public function __construct(
        public readonly ?int $id,
        public readonly int $projectId,
        public readonly string $name,
        public readonly string $resourceType,
        public readonly int $quantity,
        public readonly ?array $metadata = null,
        public readonly ?string $createdAt = null,
        public readonly ?string $updatedAt = null
    ) {
    }

    public static function fromArray(array $data): self
    {
        $metadata = $data['metadata'] ?? $data['metadata_json'] ?? null;
        if (is_string($metadata) && $metadata !== '') {
            $decoded = json_decode($metadata, true);
            $metadata = is_array($decoded) ? $decoded : null;
        }

        return new self(
            isset($data['id']) ? (int) $data['id'] : null,
            (int) ($data['project_id'] ?? $data['projectId'] ?? 0),
            (string) ($data['name'] ?? ''),
            (string) ($data['resource_type'] ?? $data['resourceType'] ?? 'machine'),
            (int) ($data['quantity'] ?? 1),
            is_array($metadata) ? $metadata : null,
            $data['created_at'] ?? null,
            $data['updated_at'] ?? null
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'project_id' => $this->projectId,
            'name' => $this->name,
            'resource_type' => $this->resourceType,
            'quantity' => $this->quantity,
            'metadata' => $this->metadata,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt,
        ];
    }
}
