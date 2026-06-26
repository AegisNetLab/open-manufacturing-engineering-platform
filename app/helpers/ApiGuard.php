<?php

declare(strict_types=1);

namespace App\Helpers;

use App\Exceptions\ApiException;

final class ApiGuard
{
    public static function requireMethod(string $method): void
    {
        $actual = strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET'));
        $expected = strtoupper($method);

        if ($actual !== $expected) {
            throw new ApiException(
                sprintf('Method %s is not allowed for this endpoint.', $actual),
                405,
                'method_not_allowed'
            );
        }
    }

    public static function intQuery(string $key, int $minimum = 1): int
    {
        $value = Request::intQuery($key, 0);

        if ($value < $minimum) {
            throw new ApiException(
                sprintf('Query parameter "%s" is required.', $key),
                400,
                'invalid_query_parameter',
                [['field' => $key, 'message' => sprintf('%s must be at least %d.', $key, $minimum)]]
            );
        }

        return $value;
    }
}
