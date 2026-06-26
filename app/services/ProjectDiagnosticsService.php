<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\ProcessRepository;
use App\Repositories\ProjectDiagnosticsRepository;
use App\Validators\ProcessValidator;
use InvalidArgumentException;

final class ProjectDiagnosticsService
{
    public function __construct(
        private readonly ProjectDiagnosticsRepository $diagnosticsRepository,
        private readonly ProcessRepository $processRepository,
        private readonly ProcessValidator $processValidator,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function diagnose(int $projectId): array
    {
        if ($projectId < 1) {
            throw new InvalidArgumentException(json_encode([
                ['field' => 'project_id', 'message' => 'Project ID is required.'],
            ], JSON_THROW_ON_ERROR));
        }

        if (!$this->diagnosticsRepository->projectExists($projectId)) {
            throw new InvalidArgumentException(json_encode([
                ['field' => 'project_id', 'message' => 'Project was not found.'],
            ], JSON_THROW_ON_ERROR));
        }

        $process = $this->processRepository->loadByProjectId($projectId);
        $processValidation = $this->processValidator->validateExecutableModel(
            $process['operations'] ?? [],
            $process['connections'] ?? []
        );

        return $this->buildReport(
            $projectId,
            $this->diagnosticsRepository->collectCounts($projectId),
            $processValidation
        );
    }

    /**
     * @param array<string, int> $counts
     * @param array<string, mixed> $processValidation
     * @return array<string, mixed>
     */
    public function buildReport(int $projectId, array $counts, array $processValidation): array
    {
        $checks = [];
        $checks[] = $this->check('layout', 'At least one layout element exists.', ($counts['layout_elements'] ?? 0) > 0, 'warning');
        $checks[] = $this->check('resources', 'At least one resource exists.', ($counts['resources'] ?? 0) > 0, 'error');
        $checks[] = $this->check('process_operations', 'The process model contains operations.', ($counts['operations'] ?? 0) > 0, 'error');
        $checks[] = $this->check('process_connections', 'The process model contains routing connections.', ($counts['process_connections'] ?? 0) > 0, 'error');
        $checks[] = $this->check('process_validation', 'The process model passes executable-model validation.', (bool) ($processValidation['valid'] ?? false), 'error');
        $checks[] = $this->check('simulation_scenarios', 'At least one simulation scenario exists.', ($counts['simulation_scenarios'] ?? 0) > 0, 'error');
        $checks[] = $this->check('simulation_results', 'At least one simulation result exists.', ($counts['simulation_results'] ?? 0) > 0, 'warning');

        $blockingFailures = array_values(array_filter(
            $checks,
            static fn (array $check): bool => $check['status'] === 'failed' && $check['severity'] === 'error'
        ));
        $warnings = array_values(array_filter(
            $checks,
            static fn (array $check): bool => $check['status'] === 'failed' && $check['severity'] === 'warning'
        ));

        return [
            'project_id' => $projectId,
            'readiness' => $blockingFailures === [] ? 'ready' : 'not_ready',
            'can_run_simulation' => $blockingFailures === [],
            'counts' => $counts,
            'checks' => $checks,
            'process_validation' => $processValidation,
            'summary' => [
                'error_count' => count($blockingFailures) + count((array) ($processValidation['errors'] ?? [])),
                'warning_count' => count($warnings) + count((array) ($processValidation['warnings'] ?? [])),
                'next_action' => $this->nextAction($checks, $processValidation),
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    private function check(string $code, string $message, bool $passed, string $severity): array
    {
        return [
            'code' => $code,
            'status' => $passed ? 'passed' : 'failed',
            'severity' => $severity,
            'message' => $message,
        ];
    }

    /**
     * @param array<int, array<string, string>> $checks
     * @param array<string, mixed> $processValidation
     */
    private function nextAction(array $checks, array $processValidation): string
    {
        foreach ($checks as $check) {
            if ($check['status'] === 'failed' && $check['severity'] === 'error') {
                return match ($check['code']) {
                    'resources' => 'Create at least one resource in the Resource Manager.',
                    'process_operations' => 'Create a process model in the Process Designer.',
                    'process_connections' => 'Connect process nodes in the Process Designer.',
                    'process_validation' => 'Fix Process Designer validation errors before running simulation.',
                    'simulation_scenarios' => 'Create or save a simulation scenario.',
                    default => 'Fix the failed readiness check.',
                };
            }
        }

        if (!empty($processValidation['warnings'])) {
            return 'Review non-blocking process warnings, then run or rerun the simulation.';
        }

        foreach ($checks as $check) {
            if ($check['status'] === 'failed' && $check['severity'] === 'warning') {
                return match ($check['code']) {
                    'layout' => 'Add layout elements when physical placement is relevant for the study.',
                    'simulation_results' => 'Run a simulation to generate persistent results.',
                    default => 'Review the warning before publishing the project.',
                };
            }
        }

        return 'Project is ready for simulation and reporting.';
    }
}
