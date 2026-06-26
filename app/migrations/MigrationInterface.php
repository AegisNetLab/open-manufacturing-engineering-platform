<?php

declare(strict_types=1);

namespace App\Migrations;

use PDO;

interface MigrationInterface
{
    public function version(): string;

    public function description(): string;

    public function up(PDO $connection): void;
}
