<?php

declare(strict_types=1);

require __DIR__ . '/app/helpers/Autoloader.php';

App\Helpers\Autoloader::register();

use App\Exceptions\ApiException;
use App\Helpers\Csrf;
use App\Helpers\JsonResponse;
use App\Helpers\LoggerFactory;
use App\Helpers\RequestContext;
use App\Helpers\RateLimiter;
use App\Helpers\SecurityHeaders;

RequestContext::requestId();
SecurityHeaders::apply();
RateLimiter::enforceForCurrentRequest();

set_error_handler(static function (int $severity, string $message, string $file, int $line): bool {
    if (!(error_reporting() & $severity)) {
        return false;
    }

    throw new ErrorException($message, 0, $severity, $file, $line);
});

set_exception_handler(static function (Throwable $throwable): void {
    $logger = LoggerFactory::create();

    if ($throwable instanceof ApiException) {
        $logger->warning($throwable->getMessage(), [
            'status_code' => $throwable->statusCode(),
            'error_code' => $throwable->errorCode(),
            'errors' => $throwable->errors(),
            'request_method' => $_SERVER['REQUEST_METHOD'] ?? 'CLI',
            'request_uri' => $_SERVER['REQUEST_URI'] ?? '',
        ]);

        if ($throwable->errors() !== []) {
            JsonResponse::validationError($throwable->errors(), $throwable->statusCode());
        }

        JsonResponse::error(
            $throwable->getMessage(),
            $throwable->statusCode(),
            $throwable->errorCode()
        );
    }

    $logger->exception($throwable, [
        'request_method' => $_SERVER['REQUEST_METHOD'] ?? 'CLI',
        'request_uri' => $_SERVER['REQUEST_URI'] ?? '',
    ]);

    JsonResponse::error('An unexpected server error occurred.', 500, 'internal_server_error');
});
if (Csrf::shouldEnforce() && !Csrf::isValid(Csrf::requestToken())) {
    throw new ApiException(
        'Invalid or missing CSRF token.',
        419,
        'csrf_token_invalid'
    );
}

