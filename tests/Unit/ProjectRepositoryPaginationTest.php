<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Repositories\ProjectRepository;
use PDO;
use Tests\Support\TestCase;

final class ProjectRepositoryPaginationTest extends TestCase
{
    public function run(): void
    {
        if (!in_array('sqlite', PDO::getAvailableDrivers(), true)) {
            $this->assertTrue(true, 'SQLite PDO is optional for this lightweight test.');
            return;
        }

        $pdo = new PDO('sqlite::memory:');
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        $pdo->exec(
            'CREATE TABLE projects (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name TEXT NOT NULL,
                description TEXT,
                production_type TEXT NOT NULL,
                shift_length_minutes INTEGER NOT NULL,
                created_at TEXT,
                updated_at TEXT
            )'
        );

        $insert = $pdo->prepare(
            'INSERT INTO projects (name, description, production_type, shift_length_minutes, created_at, updated_at)
             VALUES (:name, :description, :production_type, :shift_length_minutes, :created_at, :updated_at)'
        );
        foreach (range(1, 12) as $index) {
            $insert->execute([
                'name' => sprintf('Project %02d', $index),
                'description' => $index % 2 === 0 ? 'Assembly line' : 'Machining cell',
                'production_type' => $index % 2 === 0 ? 'serial' : 'job_shop',
                'shift_length_minutes' => 480,
                'created_at' => '2026-01-01 00:00:00',
                'updated_at' => sprintf('2026-01-%02d 00:00:00', $index),
            ]);
        }

        $repository = new ProjectRepository($pdo);

        $this->assertSame(12, $repository->countAll());
        $this->assertSame(6, $repository->countAll(['production_type' => 'serial']));

        $firstPage = $repository->findPage(['sort' => 'name', 'direction' => 'ASC'], 5, 0);
        $secondPage = $repository->findPage(['sort' => 'name', 'direction' => 'ASC'], 5, 5);

        $this->assertSame(5, count($firstPage));
        $this->assertSame('Project 01', $firstPage[0]->name);
        $this->assertSame('Project 06', $secondPage[0]->name);

        $filtered = $repository->findPage(['query' => 'Assembly', 'sort' => 'name', 'direction' => 'ASC'], 10, 0);
        $this->assertSame(6, count($filtered));
        $this->assertSame('serial', $filtered[0]->productionType);
    }
}
