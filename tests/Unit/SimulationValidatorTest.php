<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Validators\SimulationValidator;
use Tests\Support\TestCase;

final class SimulationValidatorTest extends TestCase
{
    public function run(): void
    {
        $this->validRunPayloadPasses();
        $this->runPayloadRequiresPositiveDurationAndArrivalRate();
        $this->executableModelRequiresStartEndAndConnections();
        $this->resourceIdSatisfiesSimulationResourceRule();
        $this->executableModelRequiresCycleTimeAndResource();
    }

    private function validRunPayloadPasses(): void
    {
        $validator = new SimulationValidator();
        $errors = $validator->validateRunPayload([
            'project_id' => 1,
            'name' => 'Baseline',
            'duration_minutes' => 480,
            'arrival_rate' => 10,
        ]);

        $this->assertEmpty($errors);
    }

    private function runPayloadRequiresPositiveDurationAndArrivalRate(): void
    {
        $validator = new SimulationValidator();
        $errors = $validator->validateRunPayload([
            'project_id' => 0,
            'name' => '',
            'duration_minutes' => 0,
            'arrival_rate' => 0,
        ]);

        $this->assertTrue($this->hasErrorForField($errors, 'project_id'));
        $this->assertTrue($this->hasErrorForField($errors, 'name'));
        $this->assertTrue($this->hasErrorForField($errors, 'duration_minutes'));
        $this->assertTrue($this->hasErrorForField($errors, 'arrival_rate'));
    }

    private function executableModelRequiresStartEndAndConnections(): void
    {
        $validator = new SimulationValidator();
        $errors = $validator->validateExecutableModel([
            ['node_type' => 'operation', 'operation_code' => 'OP10', 'cycle_time_seconds' => 60, 'resource_name' => 'CNC-01'],
        ], []);

        $this->assertNotEmpty($errors);
    }

    private function resourceIdSatisfiesSimulationResourceRule(): void
    {
        $validator = new SimulationValidator();
        $errors = $validator->validateExecutableModel([
            ['node_type' => 'start', 'operation_code' => 'START', 'cycle_time_seconds' => 0, 'resource_name' => ''],
            ['node_type' => 'operation', 'operation_code' => 'OP10', 'cycle_time_seconds' => 60, 'resource_id' => 12, 'resource_name' => ''],
            ['node_type' => 'end', 'operation_code' => 'END', 'cycle_time_seconds' => 0, 'resource_name' => ''],
        ], [
            ['source_node_id' => 'start', 'target_node_id' => 'op10'],
            ['source_node_id' => 'op10', 'target_node_id' => 'end'],
        ]);

        $this->assertEmpty($errors);
    }

    private function executableModelRequiresCycleTimeAndResource(): void
    {
        $validator = new SimulationValidator();
        $errors = $validator->validateExecutableModel([
            ['node_type' => 'start', 'operation_code' => 'START', 'cycle_time_seconds' => 0, 'resource_name' => ''],
            ['node_type' => 'operation', 'operation_code' => 'OP10', 'cycle_time_seconds' => 0, 'resource_name' => ''],
            ['node_type' => 'end', 'operation_code' => 'END', 'cycle_time_seconds' => 0, 'resource_name' => ''],
        ], [
            ['source_node_id' => 'start', 'target_node_id' => 'op10'],
            ['source_node_id' => 'op10', 'target_node_id' => 'end'],
        ]);

        $this->assertNotEmpty($errors);
    }
}
