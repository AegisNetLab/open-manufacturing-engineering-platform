<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Validators\SimulationScenarioValidator;
use Tests\Support\TestCase;

final class SimulationScenarioValidatorTest extends TestCase
{
    public function run(): void
    {
        $this->itAcceptsAValidScenario();
        $this->itRejectsMissingRequiredFields();
        $this->itRejectsInvalidSeed();
    }

    private function itAcceptsAValidScenario(): void
    {
        $validator = new SimulationScenarioValidator();
        $errors = $validator->validate([
            'project_id' => 1,
            'name' => 'Baseline Scenario',
            'duration_minutes' => 480,
            'arrival_rate' => 10.0,
            'random_seed' => 42,
        ]);

        $this->assertSame([], $errors, 'Valid scenario should pass validation.');
    }

    private function itRejectsMissingRequiredFields(): void
    {
        $validator = new SimulationScenarioValidator();
        $errors = $validator->validate([], true);

        $this->assertTrue(count($errors) >= 5, 'Missing scenario fields should produce validation errors.');
    }

    private function itRejectsInvalidSeed(): void
    {
        $validator = new SimulationScenarioValidator();
        $errors = $validator->validate([
            'project_id' => 1,
            'name' => 'Invalid Seed Scenario',
            'duration_minutes' => 60,
            'arrival_rate' => 1,
            'random_seed' => 'abc',
        ]);

        $this->assertTrue(
            in_array('random_seed', array_column($errors, 'field'), true),
            'Non-numeric seeds should be rejected.'
        );
    }
}
