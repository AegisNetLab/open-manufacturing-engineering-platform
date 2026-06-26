<?php

declare(strict_types=1);

namespace App\Validators;

final class SimulationScenarioValidator
{
    /**
     * @return array<int, array{field:string,message:string}>
     */
    public function validate(array $data, bool $requireId = false): array
    {
        $errors = [];

        if ($requireId && (int) ($data['id'] ?? 0) < 1) {
            $errors[] = ['field' => 'id', 'message' => 'Scenario ID is required.'];
        }

        if ((int) ($data['project_id'] ?? 0) < 1) {
            $errors[] = ['field' => 'project_id', 'message' => 'Project ID is required.'];
        }

        $name = trim((string) ($data['name'] ?? ''));
        if ($name === '') {
            $errors[] = ['field' => 'name', 'message' => 'Scenario name is required.'];
        } elseif (strlen($name) > 100) {
            $errors[] = ['field' => 'name', 'message' => 'Scenario name must be 100 characters or fewer.'];
        }

        $duration = (int) ($data['duration_minutes'] ?? 0);
        if ($duration < 1 || $duration > 525600) {
            $errors[] = ['field' => 'duration_minutes', 'message' => 'Duration must be between 1 and 525600 minutes.'];
        }

        $arrivalRate = (float) ($data['arrival_rate'] ?? 0);
        if ($arrivalRate <= 0) {
            $errors[] = ['field' => 'arrival_rate', 'message' => 'Arrival rate must be greater than zero.'];
        }

        if (isset($data['random_seed']) && $data['random_seed'] !== '' && !is_numeric($data['random_seed'])) {
            $errors[] = ['field' => 'random_seed', 'message' => 'Random seed must be numeric.'];
        }

        return $errors;
    }
}
