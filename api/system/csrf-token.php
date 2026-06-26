<?php

declare(strict_types=1);

require dirname(__DIR__, 2) . '/bootstrap.php';

use App\Helpers\ApiGuard;
use App\Helpers\Csrf;
use App\Helpers\JsonResponse;

ApiGuard::requireMethod('GET');

JsonResponse::success([
    'token' => Csrf::token(),
], 'CSRF token generated.');
