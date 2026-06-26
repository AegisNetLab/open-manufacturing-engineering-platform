<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Validators\ProcessValidator;
use Tests\Support\TestCase;

final class ProcessValidatorTest extends TestCase
{
    public function run(): void
    {
        $this->savePayloadRequiresProjectAndOperations();
        $this->savePayloadRejectsDuplicateOperationCodes();
        $this->validExecutableModelPasses();
        $this->resourceIdSatisfiesAssignmentRule();
        $this->executableModelRequiresResourceForOperations();
        $this->decisionProbabilitiesMustTotalOneHundred();
    }

    private function savePayloadRequiresProjectAndOperations(): void
    {
        $validator = new ProcessValidator();
        $errors = $validator->validateSavePayload([]);

        $this->assertTrue($this->hasErrorForField($errors, 'project_id'));
        $this->assertTrue($this->hasErrorForField($errors, 'operations'));
    }

    private function savePayloadRejectsDuplicateOperationCodes(): void
    {
        $validator = new ProcessValidator();
        $errors = $validator->validateSavePayload([
            'project_id' => 1,
            'operations' => [
                $this->operation('n1', 'operation', 'OP10', 'Cutting'),
                $this->operation('n2', 'operation', 'OP10', 'Drilling'),
            ],
            'connections' => [],
        ]);

        $this->assertTrue($this->hasErrorForField($errors, 'operations.1.operation_code'));
    }

    private function validExecutableModelPasses(): void
    {
        $validator = new ProcessValidator();
        $result = $validator->validateExecutableModel([
            $this->operation('start', 'start', 'START', 'Start'),
            $this->operation('op10', 'operation', 'OP10', 'Cutting', 300, 'CNC-01', 1),
            $this->operation('end', 'end', 'END', 'End'),
        ], [
            $this->connection('start', 'op10'),
            $this->connection('op10', 'end'),
        ]);

        $this->assertTrue($result['valid']);
        $this->assertEmpty($result['errors']);
    }

    private function resourceIdSatisfiesAssignmentRule(): void
    {
        $validator = new ProcessValidator();
        $result = $validator->validateExecutableModel([
            $this->operation('start', 'start', 'START', 'Start'),
            $this->operation('op10', 'operation', 'OP10', 'Cutting', 300, '', 1, 12),
            $this->operation('end', 'end', 'END', 'End'),
        ], [
            $this->connection('start', 'op10'),
            $this->connection('op10', 'end'),
        ]);

        $this->assertTrue($result['valid']);
    }

    private function executableModelRequiresResourceForOperations(): void
    {
        $validator = new ProcessValidator();
        $result = $validator->validateExecutableModel([
            $this->operation('start', 'start', 'START', 'Start'),
            $this->operation('op10', 'operation', 'OP10', 'Cutting', 300),
            $this->operation('end', 'end', 'END', 'End'),
        ], [
            $this->connection('start', 'op10'),
            $this->connection('op10', 'end'),
        ]);

        $this->assertFalse($result['valid']);
        $this->assertNotEmpty($result['errors']);
    }

    private function decisionProbabilitiesMustTotalOneHundred(): void
    {
        $validator = new ProcessValidator();
        $result = $validator->validateExecutableModel([
            $this->operation('start', 'start', 'START', 'Start'),
            $this->operation('decision', 'decision', 'DEC10', 'Route'),
            $this->operation('op10', 'operation', 'OP10', 'Cutting', 300, 'CNC-01', 1),
            $this->operation('end', 'end', 'END', 'End'),
        ], [
            $this->connection('start', 'decision'),
            $this->connection('decision', 'op10', 60),
            $this->connection('decision', 'end', 20),
            $this->connection('op10', 'end'),
        ]);

        $this->assertFalse($result['valid']);
        $this->assertNotEmpty($result['errors']);
    }

    private function operation(
        string $nodeId,
        string $nodeType,
        string $code,
        string $name,
        float $cycleTimeSeconds = 0,
        string $resourceName = '',
        ?int $layoutElementId = null,
        ?int $resourceId = null
    ): array {
        return [
            'node_id' => $nodeId,
            'node_type' => $nodeType,
            'operation_code' => $code,
            'name' => $name,
            'cycle_time_seconds' => $cycleTimeSeconds,
            'setup_time_seconds' => 0,
            'batch_size' => 1,
            'scrap_rate' => 0,
            'rework_rate' => 0,
            'linked_layout_element_id' => $layoutElementId,
            'resource_id' => $resourceId,
            'resource_name' => $resourceName,
            'metadata' => ['resource_id' => $resourceId, 'resource_name' => $resourceName],
        ];
    }

    private function connection(string $source, string $target, float $probability = 100): array
    {
        return [
            'source_node_id' => $source,
            'target_node_id' => $target,
            'connection_type' => 'normal',
            'probability' => $probability,
        ];
    }
}
