<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\SimulationScenario;
use PDO;

final class SimulationScenarioRepository
{
    public function __construct(private readonly PDO $connection)
    {
    }

    /** @return SimulationScenario[] */
    public function findByProject(int $projectId): array
    {
        $statement = $this->connection->prepare(
            'SELECT id, project_id, name, duration_minutes, arrival_rate, random_seed, metadata_json, created_at, updated_at
             FROM simulation_scenarios
             WHERE project_id = :project_id
             ORDER BY updated_at DESC, id DESC'
        );
        $statement->execute(['project_id' => $projectId]);

        return array_map(
            static fn (array $row): SimulationScenario => SimulationScenario::fromArray($row),
            $statement->fetchAll()
        );
    }

    public function findById(int $id): ?SimulationScenario
    {
        $statement = $this->connection->prepare(
            'SELECT id, project_id, name, duration_minutes, arrival_rate, random_seed, metadata_json, created_at, updated_at
             FROM simulation_scenarios
             WHERE id = :id'
        );
        $statement->execute(['id' => $id]);
        $row = $statement->fetch();

        return $row ? SimulationScenario::fromArray($row) : null;
    }

    public function create(SimulationScenario $scenario): SimulationScenario
    {
        $statement = $this->connection->prepare(
            'INSERT INTO simulation_scenarios (project_id, name, duration_minutes, arrival_rate, random_seed, metadata_json, created_at, updated_at)
             VALUES (:project_id, :name, :duration_minutes, :arrival_rate, :random_seed, :metadata_json, NOW(), NOW())'
        );
        $statement->execute([
            'project_id' => $scenario->projectId,
            'name' => $scenario->name,
            'duration_minutes' => $scenario->durationMinutes,
            'arrival_rate' => $scenario->arrivalRate,
            'random_seed' => $scenario->randomSeed,
            'metadata_json' => $scenario->metadata === [] ? null : json_encode($scenario->metadata, JSON_THROW_ON_ERROR),
        ]);

        return $this->findById((int) $this->connection->lastInsertId());
    }

    public function update(SimulationScenario $scenario): ?SimulationScenario
    {
        $statement = $this->connection->prepare(
            'UPDATE simulation_scenarios
             SET name = :name,
                 duration_minutes = :duration_minutes,
                 arrival_rate = :arrival_rate,
                 random_seed = :random_seed,
                 metadata_json = :metadata_json,
                 updated_at = NOW()
             WHERE id = :id AND project_id = :project_id'
        );
        $statement->execute([
            'id' => $scenario->id,
            'project_id' => $scenario->projectId,
            'name' => $scenario->name,
            'duration_minutes' => $scenario->durationMinutes,
            'arrival_rate' => $scenario->arrivalRate,
            'random_seed' => $scenario->randomSeed,
            'metadata_json' => $scenario->metadata === [] ? null : json_encode($scenario->metadata, JSON_THROW_ON_ERROR),
        ]);

        return $this->findById((int) $scenario->id);
    }

    public function delete(int $id, int $projectId): bool
    {
        $statement = $this->connection->prepare('DELETE FROM simulation_scenarios WHERE id = :id AND project_id = :project_id');
        $statement->execute(['id' => $id, 'project_id' => $projectId]);

        return $statement->rowCount() > 0;
    }
}
