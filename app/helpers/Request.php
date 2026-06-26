<?php

declare(strict_types=1);

namespace App\Helpers;

use App\Exceptions\ApiException;
use JsonException;

final class Request
{
    public static function json(): array
    {
        $raw = file_get_contents('php://input');
        if ($raw === false || trim($raw) === '') {
            return [];
        }

        try {
            $data = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new ApiException('Malformed JSON request body.', 400, 'malformed_json');
        }

        if (!is_array($data)) {
            throw new ApiException('JSON request body must be an object or array.', 400, 'invalid_json_body');
        }

        return $data;
    }

    public static function intQuery(string $key, int $default = 0): int
    {
        return isset($_GET[$key]) ? (int) $_GET[$key] : $default;
    }
}
