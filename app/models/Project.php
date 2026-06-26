<?php

declare(strict_types=1);

namespace App\Models;

final class Project
{
    public function __construct(
        public readonly ?int $id,
        public readonly string $name,
        public readonly ?string $description,
        public readonly string $productionType,
        public readonly int $shiftLengthMinutes,
        public readonly ?string $createdAt = null,
        public readonly ?string $updatedAt = null
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            isset($data['id']) ? (int) $data['id'] : null,
            (string) ($data['name'] ?? ''),
            isset($data['description']) ? (string) $data['description'] : null,
            (string) ($data['production_type'] ?? 'serial'),
            (int) ($data['shift_length_minutes'] ?? 480),
            isset($data['created_at']) ? (string) $data['created_at'] : null,
            isset($data['updated_at']) ? (string) $data['updated_at'] : null
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'production_type' => $this->productionType,
            'shift_length_minutes' => $this->shiftLengthMinutes,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt,
        ];
    }
}
