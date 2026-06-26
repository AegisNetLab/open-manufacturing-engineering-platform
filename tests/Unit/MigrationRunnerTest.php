<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Migrations\MigrationRunner;
use Tests\Support\TestCase;

final class MigrationRunnerTest extends TestCase
{
    public function run(): void
    {
        $migrations = MigrationRunner::defaultMigrations();

        $this->assertNotEmpty($migrations, 'At least one migration must be registered.');
        $this->assertSame('0001_create_core_schema', $migrations[0]->version());
        $this->assertNotEmpty($migrations[0]->description());
    }
}
