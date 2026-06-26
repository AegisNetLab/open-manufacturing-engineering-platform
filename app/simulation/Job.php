<?php

declare(strict_types=1);

namespace App\Simulation;

final class Job
{
    public const STATUS_ACTIVE = 'active';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_SCRAPPED = 'scrapped';

    private string $status = self::STATUS_ACTIVE;
    private ?float $completedAt = null;

    public function __construct(
        public readonly int $id,
        public readonly float $createdAt,
    ) {
    }

    public function status(): string
    {
        return $this->status;
    }

    public function complete(float $time): void
    {
        $this->status = self::STATUS_COMPLETED;
        $this->completedAt = $time;
    }

    public function scrap(float $time): void
    {
        $this->status = self::STATUS_SCRAPPED;
        $this->completedAt = $time;
    }

    public function leadTime(): ?float
    {
        if ($this->completedAt === null) {
            return null;
        }

        return max(0.0, $this->completedAt - $this->createdAt);
    }
}
