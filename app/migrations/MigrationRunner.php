<?php

declare(strict_types=1);

namespace App\Migrations;

use PDO;
use RuntimeException;

final class MigrationRunner
{
    /**
     * @var array<int, MigrationInterface>
     */
    private array $migrations;

    /**
     * @param array<int, MigrationInterface>|null $migrations
     */
    public function __construct(private readonly PDO $connection, ?array $migrations = null)
    {
        $this->migrations = $migrations ?? self::defaultMigrations();
        $this->assertUniqueVersions();
    }

    /**
     * @return array<int, MigrationInterface>
     */
    public static function defaultMigrations(): array
    {
        return [
            new Migration0001CreateCoreSchema(),
            new Migration0002CreateAuditLog(),
        ];
    }

    /**
     * @return array<int, array{version: string, description: string, status: string}>
     */
    public function status(): array
    {
        $this->ensureMigrationsTable();
        $applied = $this->appliedVersions();

        return array_map(
            static fn (MigrationInterface $migration): array => [
                'version' => $migration->version(),
                'description' => $migration->description(),
                'status' => in_array($migration->version(), $applied, true) ? 'applied' : 'pending',
            ],
            $this->migrations
        );
    }

    /**
     * @return array<int, string>
     */
    public function migrate(): array
    {
        $this->ensureMigrationsTable();
        $applied = $this->appliedVersions();
        $executed = [];

        foreach ($this->migrations as $migration) {
            if (in_array($migration->version(), $applied, true)) {
                continue;
            }

            $this->connection->beginTransaction();
            try {
                $migration->up($this->connection);
                $this->recordMigration($migration);
                $this->connection->commit();
                $executed[] = $migration->version();
            } catch (\Throwable $throwable) {
                if ($this->connection->inTransaction()) {
                    $this->connection->rollBack();
                }

                throw $throwable;
            }
        }

        return $executed;
    }

    private function ensureMigrationsTable(): void
    {
        $this->connection->exec(<<<'SQL'
CREATE TABLE IF NOT EXISTS schema_migrations (
    version VARCHAR(100) NOT NULL,
    description VARCHAR(255) NOT NULL,
    executed_at DATETIME NOT NULL,
    PRIMARY KEY (version)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL);
    }

    /**
     * @return array<int, string>
     */
    private function appliedVersions(): array
    {
        $statement = $this->connection->query('SELECT version FROM schema_migrations ORDER BY version');
        if ($statement === false) {
            return [];
        }

        return array_map(
            static fn (array $row): string => (string) $row['version'],
            $statement->fetchAll(PDO::FETCH_ASSOC)
        );
    }

    private function recordMigration(MigrationInterface $migration): void
    {
        $statement = $this->connection->prepare(
            'INSERT INTO schema_migrations (version, description, executed_at) VALUES (:version, :description, :executed_at)'
        );
        $statement->execute([
            'version' => $migration->version(),
            'description' => $migration->description(),
            'executed_at' => date('Y-m-d H:i:s'),
        ]);
    }

    private function assertUniqueVersions(): void
    {
        $versions = [];
        foreach ($this->migrations as $migration) {
            if (isset($versions[$migration->version()])) {
                throw new RuntimeException('Duplicate migration version: ' . $migration->version());
            }
            $versions[$migration->version()] = true;
        }
    }
}
