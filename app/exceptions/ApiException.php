<?php

declare(strict_types=1);

namespace App\Exceptions;

use RuntimeException;

final class ApiException extends RuntimeException
{
    /**
     * @param array<int, array{field:string,message:string}> $errors
     */
    public function __construct(
        string $message,
        private readonly int $statusCode = 500,
        private readonly string $errorCode = 'api_error',
        private readonly array $errors = []
    ) {
        parent::__construct($message, $statusCode);
    }

    public function statusCode(): int
    {
        return $this->statusCode;
    }

    public function errorCode(): string
    {
        return $this->errorCode;
    }

    /**
     * @return array<int, array{field:string,message:string}>
     */
    public function errors(): array
    {
        return $this->errors;
    }
}
