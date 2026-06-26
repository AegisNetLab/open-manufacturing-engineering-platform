<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\SimulationScenario;
use App\Repositories\SimulationScenarioRepository;
use App\Validators\SimulationScenarioValidator;
use InvalidArgumentException;

final class SimulationScenarioService
{
    public function __construct(
        private readonly SimulationScenarioRepository $repository,
        private readonly SimulationScenarioValidator $validator
    ) {
    }

    public function listScenarios(int $projectId): array
    {
        return array_map(
            static fn (SimulationScenario $scenario): array => $scenario->toArray(),
            $this->repository->findByProject($projectId)
        );
    }

    public function saveScenario(array $data): array
    {
        $requireId = isset($data['id']) && (int) $data['id'] > 0;
        $errors = $this->validator->validate($data, $requireId);
        if ($errors !== []) {
            throw new InvalidArgumentException(json_encode($errors, JSON_THROW_ON_ERROR));
        }

        $scenario = SimulationScenario::fromArray([
            'id' => $requireId ? (int) $data['id'] : null,
            'project_id' => (int) $data['project_id'],
            'name' => trim((string) $data['name']),
            'duration_minutes' => (int) $data['duration_minutes'],
            'arrival_rate' => (float) $data['arrival_rate'],
            'random_seed' => isset($data['random_seed']) && $data['random_seed'] !== '' ? (int) $data['random_seed'] : null,
            'metadata' => is_array($data['metadata'] ?? null) ? $data['metadata'] : [],
        ]);

        if ($requireId) {
            return $this->repository->update($scenario)?->toArray() ?? [];
        }

        return $this->repository->create($scenario)->toArray();
    }

    public function deleteScenario(int $id, int $projectId): bool
    {
        return $this->repository->delete($id, $projectId);
    }
}
