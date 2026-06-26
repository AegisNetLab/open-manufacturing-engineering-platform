<?php

declare(strict_types=1);

namespace App\Validators;

final class ProcessValidator
{
    private const NODE_TYPES = ['start', 'operation', 'inspection', 'transport', 'buffer', 'decision', 'delay', 'end'];

    public function validateSavePayload(array $payload): array
    {
        $errors = [];
        if ((int) ($payload['project_id'] ?? 0) < 1) {
            $errors[] = ['field' => 'project_id', 'message' => 'Project ID is required.'];
        }
        if (!isset($payload['operations']) || !is_array($payload['operations'])) {
            $errors[] = ['field' => 'operations', 'message' => 'Operations must be provided.'];
            return $errors;
        }
        if (!isset($payload['connections']) || !is_array($payload['connections'])) {
            $errors[] = ['field' => 'connections', 'message' => 'Connections must be provided.'];
        }

        $codes = [];
        foreach ($payload['operations'] as $index => $operation) {
            $prefix = "operations.$index";
            if (trim((string) ($operation['node_id'] ?? '')) === '') {
                $errors[] = ['field' => "$prefix.node_id", 'message' => 'Node ID is required.'];
            }
            if (!in_array((string) ($operation['node_type'] ?? ''), self::NODE_TYPES, true)) {
                $errors[] = ['field' => "$prefix.node_type", 'message' => 'Node type is invalid.'];
            }
            $code = strtoupper(trim((string) ($operation['operation_code'] ?? '')));
            if ($code === '') {
                $errors[] = ['field' => "$prefix.operation_code", 'message' => 'Operation code is required.'];
            } elseif (isset($codes[$code])) {
                $errors[] = ['field' => "$prefix.operation_code", 'message' => 'Operation code must be unique in the project.'];
            }
            $codes[$code] = true;
            if (trim((string) ($operation['name'] ?? '')) === '') {
                $errors[] = ['field' => "$prefix.name", 'message' => 'Operation name is required.'];
            }
            if ((float) ($operation['cycle_time_minutes'] ?? 0) < 0) {
                $errors[] = ['field' => "$prefix.cycle_time_minutes", 'message' => 'Cycle time cannot be negative.'];
            }
            if ((int) ($operation['batch_size'] ?? 0) < 1) {
                $errors[] = ['field' => "$prefix.batch_size", 'message' => 'Batch size must be at least 1.'];
            }
            foreach (['scrap_rate', 'rework_rate'] as $rateField) {
                $rate = (float) ($operation[$rateField] ?? 0);
                if ($rate < 0 || $rate > 100) {
                    $errors[] = ['field' => "$prefix.$rateField", 'message' => ucfirst(str_replace('_', ' ', $rateField)) . ' must be between 0 and 100.'];
                }
            }
        }

        foreach (($payload['connections'] ?? []) as $index => $connection) {
            $prefix = "connections.$index";
            if (trim((string) ($connection['source_node_id'] ?? '')) === '') {
                $errors[] = ['field' => "$prefix.source_node_id", 'message' => 'Source node is required.'];
            }
            if (trim((string) ($connection['target_node_id'] ?? '')) === '') {
                $errors[] = ['field' => "$prefix.target_node_id", 'message' => 'Target node is required.'];
            }
            $probability = (float) ($connection['probability'] ?? 100);
            if ($probability <= 0 || $probability > 100) {
                $errors[] = ['field' => "$prefix.probability", 'message' => 'Connection probability must be greater than 0 and at most 100.'];
            }
        }

        return $errors;
    }

    public function validateExecutableModel(array $operations, array $connections): array
    {
        $errors = [];
        $warnings = [];
        $byNodeId = [];
        foreach ($operations as $operation) {
            $byNodeId[$operation['node_id']] = $operation;
        }

        if ($operations === []) {
            return [
                'valid' => false,
                'errors' => [['field' => 'operations', 'message' => 'The process model must contain at least one node.']],
                'warnings' => [],
            ];
        }

        $startNodes = array_filter($operations, static fn (array $op): bool => $op['node_type'] === 'start');
        $endNodes = array_filter($operations, static fn (array $op): bool => $op['node_type'] === 'end');
        if (count($startNodes) !== 1) {
            $errors[] = ['field' => 'operations', 'message' => 'The process must contain exactly one Start node.'];
        }
        if (count($endNodes) < 1) {
            $errors[] = ['field' => 'operations', 'message' => 'The process must contain at least one End node.'];
        }

        $incoming = [];
        $outgoing = [];
        foreach ($operations as $operation) {
            $incoming[$operation['node_id']] = [];
            $outgoing[$operation['node_id']] = [];
        }

        foreach ($operations as $operation) {
            $type = $operation['node_type'];
            if (!in_array($type, ['start', 'end', 'buffer', 'decision'], true)
                && (float) $operation['cycle_time_seconds'] <= 0
            ) {
                $errors[] = [
                    'field' => $operation['operation_code'],
                    'message' => "Operation {$operation['operation_code']} requires a positive cycle time.",
                ];
            }
            if (in_array($type, ['operation', 'inspection', 'transport'], true)
                && (int) ($operation['resource_id'] ?? $operation['metadata']['resource_id'] ?? 0) < 1
                && trim((string) ($operation['resource_name'] ?? $operation['metadata']['resource_name'] ?? '')) === ''
            ) {
                $errors[] = [
                    'field' => $operation['operation_code'],
                    'message' => "Operation {$operation['operation_code']} requires an assigned resource.",
                ];
            }
            if ($type === 'operation' && empty($operation['linked_layout_element_id'])) {
                $warnings[] = [
                    'field' => $operation['operation_code'],
                    'message' => "Operation {$operation['operation_code']} is not linked to a layout element.",
                ];
            }
        }

        foreach ($connections as $connection) {
            if (!isset($byNodeId[$connection['source_node_id']], $byNodeId[$connection['target_node_id']])) {
                $errors[] = ['field' => 'connections', 'message' => 'Connection references an unknown node.'];
                continue;
            }
            $outgoing[$connection['source_node_id']][] = $connection;
            $incoming[$connection['target_node_id']][] = $connection;
        }

        foreach ($operations as $operation) {
            $nodeId = $operation['node_id'];
            if ($operation['node_type'] !== 'start' && $incoming[$nodeId] === []) {
                $errors[] = ['field' => $nodeId, 'message' => "Node {$operation['operation_code']} has no incoming connection."];
            }
            if ($operation['node_type'] !== 'end' && $outgoing[$nodeId] === []) {
                $errors[] = ['field' => $nodeId, 'message' => "Node {$operation['operation_code']} has no outgoing connection."];
            }
        }

        foreach ($operations as $operation) {
            if ($operation['node_type'] !== 'decision') {
                continue;
            }
            $sum = array_sum(array_map(static fn (array $connection): float => (float) $connection['probability'], $outgoing[$operation['node_id']]));
            if ($outgoing[$operation['node_id']] !== [] && abs($sum - 100.0) > 0.01) {
                $errors[] = [
                    'field' => $operation['operation_code'],
                    'message' => "Decision node {$operation['operation_code']} outgoing probabilities must total 100%.",
                ];
            }
        }

        $reachable = $this->reachableFromStart($startNodes, $outgoing);
        foreach ($operations as $operation) {
            if (!isset($reachable[$operation['node_id']])) {
                $errors[] = ['field' => $operation['node_id'], 'message' => "Node {$operation['operation_code']} is not reachable from Start."];
            }
        }

        return [
            'valid' => $errors === [],
            'errors' => $errors,
            'warnings' => $warnings,
        ];
    }

    /**
     * @param array<int, array<string, mixed>> $startNodes
     * @param array<string, array<int, array<string, mixed>>> $outgoing
     * @return array<string, bool>
     */
    private function reachableFromStart(array $startNodes, array $outgoing): array
    {
        $firstStart = reset($startNodes);
        if (!is_array($firstStart)) {
            return [];
        }

        $queue = [(string) $firstStart['node_id']];
        $seen = [];
        while ($queue !== []) {
            $nodeId = array_shift($queue);
            if (isset($seen[$nodeId])) {
                continue;
            }
            $seen[$nodeId] = true;
            foreach ($outgoing[$nodeId] ?? [] as $connection) {
                $queue[] = (string) $connection['target_node_id'];
            }
        }

        return $seen;
    }
}
