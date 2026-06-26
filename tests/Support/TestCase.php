<?php

declare(strict_types=1);

namespace Tests\Support;

use RuntimeException;

abstract class TestCase
{
    private int $assertions = 0;

    abstract public function run(): void;

    public function assertions(): int
    {
        return $this->assertions;
    }

    protected function assertTrue(bool $condition, string $message = 'Expected condition to be true.'): void
    {
        $this->assertions++;
        if (!$condition) {
            throw new RuntimeException($message);
        }
    }

    protected function assertFalse(bool $condition, string $message = 'Expected condition to be false.'): void
    {
        $this->assertions++;
        if ($condition) {
            throw new RuntimeException($message);
        }
    }

    protected function assertSame(mixed $expected, mixed $actual, string $message = 'Values are not identical.'): void
    {
        $this->assertions++;
        if ($expected !== $actual) {
            throw new RuntimeException($message . ' Expected: ' . var_export($expected, true) . ', actual: ' . var_export($actual, true));
        }
    }

    protected function assertNotEmpty(array|string $value, string $message = 'Expected value not to be empty.'): void
    {
        $this->assertions++;
        if ($value === [] || $value === '') {
            throw new RuntimeException($message);
        }
    }

    protected function assertEmpty(array|string $value, string $message = 'Expected value to be empty.'): void
    {
        $this->assertions++;
        if ($value !== [] && $value !== '') {
            throw new RuntimeException($message . ' Actual: ' . var_export($value, true));
        }
    }

    protected function hasErrorForField(array $errors, string $field): bool
    {
        foreach ($errors as $error) {
            if (($error['field'] ?? null) === $field) {
                return true;
            }
        }

        return false;
    }
}
