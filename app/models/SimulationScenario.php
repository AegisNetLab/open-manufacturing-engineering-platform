<?php

declare(strict_types=1);

namespace App\Models;

final class SimulationScenario
{
    public function __construct(
        public readonly ?int $id,
        public readonly int $projectId,
        public readonly string $name,
        public readonly int $durationMinutes,
        public readonly float $arrivalRate,
        public readonly ?int $randomSeed,
        public readonly array $metadata = [],
        public readonly ?string $createdAt = null,
        public readonly ?string $updatedAt = null
    ) {
    }

    public static function fromArray(array $data): self
    {
        $metadata = $data['metadata'] ?? $data['metadata_json'] ?? [];
        if (is_string($metadata) && $metadata !== '') {
            $decoded = json_decode($metadata, true);
            $metadata = is_array($decoded) ? $decoded : [];
        }

        return new self(
            isset($data['id']) ? (int) $data['id'] : null,
            (int) ($data['project_id'] ?? $data['projectId'] ?? 0),
            (string) ($data['name'] ?? ''),
            (int) ($data['duration_minutes'] ?? $data['durationMinutes'] ?? 0),
            (float) ($data['arrival_rate'] ?? $data['arrivalRate'] ?? 0),
            isset($data['random_seed']) && $data['random_seed'] !== null ? (int) $data['random_seed'] : null,
            is_array($metadata) ? $metadata : [],
            isset($data['created_at']) ? (string) $data['created_at'] : null,
            isset($data['updated_at']) ? (string) $data['updated_at'] : null
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'project_id' => $this->projectId,
            'name' => $this->name,
            'duration_minutes' => $this->durationMinutes,
            'arrival_rate' => $this->arrivalRate,
            'random_seed' => $this->randomSeed,
            'metadata' => $this->metadata,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt,
        ];
    }
}
