<?php

declare(strict_types=1);

namespace App\Simulation;

final class ResourcePool
{
    /** @var array<int, float> */
    private array $slots;

    public function __construct(
        public readonly string $name,
        int $capacity = 1,
    ) {
        $this->slots = array_fill(0, max(1, $capacity), 0.0);
    }

    public function capacity(): int
    {
        return count($this->slots);
    }

    /** @return array{0:int,1:float} */
    public function nextAvailableSlot(): array
    {
        $slotIndex = 0;
        $availableAt = $this->slots[0];
        foreach ($this->slots as $index => $slotAvailableAt) {
            if ($slotAvailableAt < $availableAt) {
                $slotIndex = (int) $index;
                $availableAt = (float) $slotAvailableAt;
            }
        }

        return [$slotIndex, $availableAt];
    }

    public function canStartAt(float $time): bool
    {
        return $this->nextAvailableSlot()[1] <= $time;
    }

    public function reserve(int $slotIndex, float $availableAt): void
    {
        $this->slots[$slotIndex] = $availableAt;
    }

    public function release(int $slotIndex, float $time): void
    {
        $this->slots[$slotIndex] = $time;
    }
}
