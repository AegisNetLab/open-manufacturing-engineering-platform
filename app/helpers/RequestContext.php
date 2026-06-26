<?php

declare(strict_types=1);

namespace App\Helpers;

final class RequestContext
{
    private static ?string $requestId = null;

    public static function requestId(): string
    {
        if (self::$requestId === null) {
            self::$requestId = self::generateRequestId();
        }

        return self::$requestId;
    }

    public static function reset(?string $requestId = null): void
    {
        self::$requestId = $requestId;
    }

    private static function generateRequestId(): string
    {
        try {
            return bin2hex(random_bytes(8));
        } catch (\Throwable) {
            return str_replace('.', '', uniqid('', true));
        }
    }
}
