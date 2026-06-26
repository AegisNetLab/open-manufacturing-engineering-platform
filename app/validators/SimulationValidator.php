<?php

declare(strict_types=1);

namespace App\Validators;

final class SimulationValidator
{
    public function validateRunPayload(array $payload): array
    {
        $errors = [];

        if ((int) ($payload['project_id'] ?? 0) < 1) {
            $errors[] = ['field' => 'project_id', 'message' => 'Project ID is required.'];
        }

        $name = trim((string) ($payload['name'] ?? ''));
        if ($name === '') {
            $errors[] = ['field' => 'name', 'message' => 'Scenario name is required.'];
        }

        $duration = (int) ($payload['duration_minutes'] ?? 0);
        if ($duration < 1 || $duration > 43200) {
            $errors[] = ['field' => 'duration_minutes', 'message' => 'Duration must be between 1 and 43200 minutes.'];
        }

        $arrivalRate = (float) ($payload['arrival_rate'] ?? 0);
        if ($arrivalRate <= 0) {
            $errors[] = ['field' => 'arrival_rate', 'message' => 'Arrival rate must be greater than zero.'];
        }

        return $errors;
    }

    public function validateExecutableModel(array $operations, array $connections): array
    {
        $errors = [];
        if ($operations === []) {
            $errors[] = ['field' => 'process', 'message' => 'The project has no saved process model.'];
            return $errors;
        }

        $start = array_filter($operations, static fn (array $operation): bool => $operation['node_type'] === 'start');
        $end = array_filter($operations, static fn (array $operation): bool => $operation['node_type'] === 'end');
        if (count($start) !== 1) {
            $errors[] = ['field' => 'process', 'message' => 'The process must contain exactly one Start operation.'];
        }
        if (count($end) < 1) {
            $errors[] = ['field' => 'process', 'message' => 'The process must contain at least one End operation.'];
        }
        if ($connections === []) {
            $errors[] = ['field' => 'process', 'message' => 'The process graph has no connections.'];
        }

        foreach ($operations as $operation) {
            if (!in_array($operation['node_type'], ['start', 'end', 'buffer', 'decision'], true)
                && (float) $operation['cycle_time_seconds'] <= 0) {
                $errors[] = ['field' => 'process', 'message' => "Operation {$operation['operation_code']} is missing cycle time."];
            }
            if (in_array($operation['node_type'], ['operation', 'inspection', 'transport'], true)) {
                $resourceId = (int) ($operation['resource_id'] ?? 0);
                $resourceName = trim((string) ($operation['resource_name'] ?? ''));
                if ($resourceId < 1 && $resourceName === '') {
                    $errors[] = ['field' => 'process', 'message' => "Operation {$operation['operation_code']} is missing a required resource."];
                }
            }
        }

        return $errors;
    }
}
