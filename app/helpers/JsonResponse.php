<?php

declare(strict_types=1);

namespace App\Helpers;

final class JsonResponse
{
    public static function success(array $data = [], string $message = '', int $statusCode = 200): void
    {
        self::send([
            'success' => true,
            'request_id' => RequestContext::requestId(),
            'data' => $data,
            'message' => $message,
        ], $statusCode);
    }

    public static function validationError(array $errors, int $statusCode = 400): void
    {
        self::send([
            'success' => false,
            'request_id' => RequestContext::requestId(),
            'errors' => $errors,
        ], $statusCode);
    }

    public static function error(string $message, int $statusCode = 500, string $code = 'error'): void
    {
        self::send([
            'success' => false,
            'request_id' => RequestContext::requestId(),
            'message' => $message,
            'error' => [
                'code' => $code,
                'message' => $message,
            ],
        ], $statusCode);
    }

    private static function send(array $payload, int $statusCode): void
    {
        http_response_code($statusCode);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        exit;
    }
}
