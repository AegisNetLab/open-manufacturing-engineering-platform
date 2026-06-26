<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Helpers\Csrf;
use Tests\Support\TestCase;

final class CsrfTest extends TestCase
{
    public function run(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_SERVER['REQUEST_URI'] = '/api/projects/create.php';
        unset($_SERVER['HTTP_X_CSRF_TOKEN']);

        $token = Csrf::token();

        $this->assertNotEmpty($token, 'CSRF token should be generated.');
        $this->assertTrue(Csrf::isValid($token), 'Generated token should be valid.');
        $this->assertFalse(Csrf::isValid('invalid-token'), 'Wrong token should be rejected.');
        $this->assertFalse(Csrf::shouldEnforce(), 'CLI test runs should not enforce CSRF.');

        $_SERVER['REQUEST_METHOD'] = 'GET';
        $this->assertFalse(Csrf::shouldEnforce(), 'GET requests should not require CSRF in CLI tests.');
    }
}
