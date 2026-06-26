<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Validators\ResourceValidator;
use Tests\Support\TestCase;

final class ResourceValidatorTest extends TestCase
{
    public function run(): void
    {
        $this->validResourcePasses();
        $this->resourceRequiresProjectNameAndType();
        $this->resourceRejectsInvalidQuantity();
        $this->deleteRequiresIdentifier();
    }

    private function validResourcePasses(): void
    {
        $validator = new ResourceValidator();
        $errors = $validator->validate([
            'project_id' => 1,
            'name' => 'CNC-01',
            'resource_type' => 'machine',
            'quantity' => 2,
        ]);

        $this->assertEmpty($errors);
    }

    private function resourceRequiresProjectNameAndType(): void
    {
        $validator = new ResourceValidator();
        $errors = $validator->validate([
            'project_id' => 0,
            'name' => '',
            'resource_type' => 'invalid',
            'quantity' => 1,
        ]);

        $this->assertTrue($this->hasErrorForField($errors, 'project_id'));
        $this->assertTrue($this->hasErrorForField($errors, 'name'));
        $this->assertTrue($this->hasErrorForField($errors, 'resource_type'));
    }

    private function resourceRejectsInvalidQuantity(): void
    {
        $validator = new ResourceValidator();
        $errors = $validator->validate([
            'project_id' => 1,
            'name' => 'Operator A',
            'resource_type' => 'operator',
            'quantity' => 0,
        ]);

        $this->assertTrue($this->hasErrorForField($errors, 'quantity'));
    }

    private function deleteRequiresIdentifier(): void
    {
        $validator = new ResourceValidator();
        $errors = $validator->validate([
            'project_id' => 1,
            'name' => 'Tool A',
            'resource_type' => 'tool',
            'quantity' => 1,
        ], true);

        $this->assertTrue($this->hasErrorForField($errors, 'id'));
    }
}
