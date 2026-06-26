<?php

declare(strict_types=1);

namespace App\Models;

final class LayoutElement
{
    public function __construct(
        public readonly ?int $id,
        public readonly int $projectId,
        public readonly string $name,
        public readonly string $elementType,
        public readonly float $xPosition,
        public readonly float $yPosition,
        public readonly float $width,
        public readonly float $height,
        public readonly int $rotation,
        public readonly ?string $color,
        public readonly array $metadata,
        public readonly ?string $createdAt = null,
        public readonly ?string $updatedAt = null,
    ) {
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'project_id' => $this->projectId,
            'name' => $this->name,
            'element_type' => $this->elementType,
            'x_position' => $this->xPosition,
            'y_position' => $this->yPosition,
            'width' => $this->width,
            'height' => $this->height,
            'rotation' => $this->rotation,
            'color' => $this->color,
            'metadata' => $this->metadata,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt,
        ];
    }
}
