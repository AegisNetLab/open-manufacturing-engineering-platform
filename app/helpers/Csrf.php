<?php

declare(strict_types=1);

namespace App\Helpers;

final class Csrf
{
    private const SESSION_KEY = 'openmep_csrf_token';

    public static function token(): string
    {
        self::ensureSession();

        if (!isset($_SESSION[self::SESSION_KEY]) || !is_string($_SESSION[self::SESSION_KEY])) {
            $_SESSION[self::SESSION_KEY] = bin2hex(random_bytes(32));
        }

        return $_SESSION[self::SESSION_KEY];
    }

    public static function isValid(?string $token): bool
    {
        if ($token === null || $token === '') {
            return false;
        }

        self::ensureSession();
        $expected = $_SESSION[self::SESSION_KEY] ?? '';

        return is_string($expected) && hash_equals($expected, $token);
    }

    public static function shouldEnforce(): bool
    {
        if (PHP_SAPI === 'cli') {
            return false;
        }

        $method = strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET'));
        if (in_array($method, ['GET', 'HEAD', 'OPTIONS'], true)) {
            return false;
        }

        $uri = (string) ($_SERVER['REQUEST_URI'] ?? '');
        if (str_contains($uri, '/api/system/csrf-token.php')) {
            return false;
        }

        return str_contains($uri, '/api/');
    }

    public static function requestToken(): ?string
    {
        $headerToken = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null;
        if (is_string($headerToken) && $headerToken !== '') {
            return $headerToken;
        }

        $formToken = $_POST['_csrf_token'] ?? null;
        return is_string($formToken) ? $formToken : null;
    }

    private static function ensureSession(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start([
                'cookie_httponly' => true,
                'cookie_samesite' => 'Lax',
            ]);
        }
    }
}
