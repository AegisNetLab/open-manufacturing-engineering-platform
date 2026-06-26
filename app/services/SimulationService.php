<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\SimulationRepository;
use App\Validators\SimulationValidator;
use InvalidArgumentException;
use App\Simulation\EventQueue;

final class SimulationService
{
    private const EVENT_JOB_CREATED = 'JOB_CREATED';
    private const EVENT_JOB_ARRIVED = 'JOB_ARRIVED';
    private const EVENT_QUEUE_ENTER = 'QUEUE_ENTER';
    private const EVENT_PROCESS_START = 'PROCESS_START';
    private const EVENT_PROCESS_END = 'PROCESS_END';
    private const EVENT_RESOURCE_ALLOCATED = 'RESOURCE_ALLOCATED';
    private const EVENT_RESOURCE_RELEASED = 'RESOURCE_RELEASED';
    private const EVENT_SCRAP = 'SCRAP';
    private const EVENT_REWORK = 'REWORK';
    private const EVENT_JOB_COMPLETED = 'JOB_COMPLETED';
    private const EVENT_SIMULATION_END = 'SIMULATION_END';

    public function __construct(
        private readonly SimulationRepository $repository,
        private readonly SimulationValidator $validator,
    ) {
    }

    public function runSimulation(array $payload): array
    {
        $errors = $this->validator->validateRunPayload($payload);
        if ($errors !== []) {
            throw new InvalidArgumentException(json_encode($errors, JSON_THROW_ON_ERROR));
        }

        $projectId = (int) $payload['project_id'];
        $model = $this->repository->loadModel($projectId);
        $modelErrors = $this->validator->validateExecutableModel($model['operations'], $model['connections']);
        if ($modelErrors !== []) {
            throw new InvalidArgumentException(json_encode($modelErrors, JSON_THROW_ON_ERROR));
        }

        $scenario = [
            'project_id' => $projectId,
            'name' => trim((string) $payload['name']),
            'duration_minutes' => (int) $payload['duration_minutes'],
            'arrival_rate' => (float) $payload['arrival_rate'],
            'random_seed' => isset($payload['random_seed']) ? (int) $payload['random_seed'] : 42,
            'metadata' => ['distribution' => (string) ($payload['distribution'] ?? 'deterministic')],
        ];

        mt_srand((int) $scenario['random_seed']);
        $scenarioId = $this->repository->createScenario($scenario);
        $runId = $this->repository->createRun($scenarioId);
        $result = $this->execute($model, $scenario);
        $this->repository->completeRun($runId, $result);

        return ['run_id' => $runId, 'scenario_id' => $scenarioId, 'result' => $result];
    }

    public function latestRun(int $projectId): ?array
    {
        return $this->repository->latestRun($projectId);
    }

    public function results(int $projectId): array
    {
        return $this->repository->resultsByProject($projectId);
    }

    private function execute(array $model, array $scenario): array
    {
        $duration = (float) $scenario['duration_minutes'];
        $arrivalInterval = 60.0 / max(0.01, (float) $scenario['arrival_rate']);
        $operationsById = $model['operations_by_id'];
        $outgoing = $this->groupConnections($model['connections']);
        $startOperationId = $this->findStartOperationId($operationsById);
        $resourceAvailability = $this->initializeResourceAvailability($model['resources']);
        $operationQueues = [];
        $jobs = [];
        $events = [];
        $leadTimes = [];
        $queueWaitTimes = [];
        $queueLengthArea = [];
        $operationBusy = [];
        $resourceBusy = [];
        $clock = 0.0;
        $lastWipUpdateAt = 0.0;
        $wipArea = 0.0;
        $wip = 0;
        $generated = 0;
        $completed = 0;
        $scrapped = 0;
        $eventSequence = 0;

        $eventQueue = new EventQueue();

        for ($arrival = 0.0; $arrival < $duration; $arrival += $arrivalInterval) {
            $generated++;
            $this->pushEvent($eventQueue, $eventSequence, $arrival, self::EVENT_JOB_CREATED, [
                'job_id' => $generated,
                'operation_id' => $startOperationId,
            ]);
        }
        $this->pushEvent($eventQueue, $eventSequence, $duration, self::EVENT_SIMULATION_END, []);

        while (!$eventQueue->isEmpty()) {
            $eventObject = $eventQueue->pop();
            if ($eventObject === null) {
                break;
            }
            $event = $eventObject->toArray();
            $clock = (float) $event['time'];
            if ($clock > $duration && $event['type'] !== self::EVENT_PROCESS_END) {
                continue;
            }

            [$wipArea, $lastWipUpdateAt] = $this->updateTimeWeightedArea(
                $wipArea,
                $lastWipUpdateAt,
                min($clock, $duration),
                $wip
            );

            switch ($event['type']) {
                case self::EVENT_JOB_CREATED:
                    $jobId = (int) $event['payload']['job_id'];
                    $jobs[$jobId] = ['id' => $jobId, 'created_at' => $clock, 'status' => 'active'];
                    $wip++;
                    $events[] = $this->event($clock, self::EVENT_JOB_CREATED, "Job {$jobId} created.");
                    $nextOperationId = $this->chooseNextOperationId($startOperationId, $outgoing, false);
                    if ($nextOperationId !== null) {
                        $this->pushEvent($eventQueue, $eventSequence, $clock, self::EVENT_JOB_ARRIVED, [
                            'job_id' => $jobId,
                            'operation_id' => $nextOperationId,
                        ]);
                    }
                    break;

                case self::EVENT_JOB_ARRIVED:
                    $operationId = (int) $event['payload']['operation_id'];
                    $jobId = (int) $event['payload']['job_id'];
                    if (!isset($jobs[$jobId], $operationsById[$operationId]) || $jobs[$jobId]['status'] !== 'active') {
                        break;
                    }

                    $operation = $operationsById[$operationId];
                    $nodeType = (string) $operation['node_type'];
                    $events[] = $this->event($clock, self::EVENT_JOB_ARRIVED, "Job {$jobId} arrived at {$operation['name']}.");

                    if ($nodeType === 'end') {
                        $jobs[$jobId]['status'] = 'completed';
                        $jobs[$jobId]['completed_at'] = $clock;
                        $completed++;
                        $wip = max(0, $wip - 1);
                        $leadTimes[] = max(0.0, $clock - (float) $jobs[$jobId]['created_at']);
                        $events[] = $this->event($clock, self::EVENT_JOB_COMPLETED, "Job {$jobId} completed.");
                        break;
                    }

                    if (in_array($nodeType, ['start', 'decision'], true)) {
                        $nextOperationId = $this->chooseNextOperationId($operationId, $outgoing, false);
                        if ($nextOperationId !== null) {
                            $this->pushEvent($eventQueue, $eventSequence, $clock, self::EVENT_JOB_ARRIVED, [
                                'job_id' => $jobId,
                                'operation_id' => $nextOperationId,
                            ]);
                        }
                        break;
                    }

                    $operationQueues[$operationId] ??= [];
                    $operationQueues[$operationId][] = ['job_id' => $jobId, 'entered_at' => $clock];
                    $queueLengthArea[$operationId] ??= ['area' => 0.0, 'last_time' => $clock, 'length' => 0];
                    $queueLengthArea[$operationId] = $this->updateQueueLengthArea($queueLengthArea[$operationId], $clock, count($operationQueues[$operationId]));
                    $events[] = $this->event($clock, self::EVENT_QUEUE_ENTER, "Job {$jobId} queued before {$operation['name']}.");
                    $this->startAvailableJobs(
                        $clock,
                        $operationId,
                        $operationsById,
                        $model['resources'],
                        $operationQueues,
                        $resourceAvailability,
                        $resourceBusy,
                        $operationBusy,
                        $queueWaitTimes,
                        $queueLengthArea,
                        $eventQueue,
                        $eventSequence,
                        $events
                    );
                    break;

                case self::EVENT_PROCESS_END:
                    $operationId = (int) $event['payload']['operation_id'];
                    $jobId = (int) $event['payload']['job_id'];
                    $resourceKey = (string) $event['payload']['resource_key'];
                    $slotIndex = (int) $event['payload']['slot_index'];
                    if (!isset($jobs[$jobId], $operationsById[$operationId])) {
                        break;
                    }

                    $operation = $operationsById[$operationId];
                    $resourceAvailability[$resourceKey][$slotIndex] = $clock;
                    $events[] = $this->event($clock, self::EVENT_RESOURCE_RELEASED, "{$resourceKey} released by job {$jobId}.");
                    $events[] = $this->event($clock, self::EVENT_PROCESS_END, "{$operation['name']} finished job {$jobId}.");

                    if ($this->probabilityHit((float) $operation['scrap_rate'])) {
                        $jobs[$jobId]['status'] = 'scrapped';
                        $scrapped++;
                        $wip = max(0, $wip - 1);
                        $events[] = $this->event($clock, self::EVENT_SCRAP, "Job {$jobId} scrapped at {$operation['name']}.");
                    } else {
                        $useRework = $this->probabilityHit((float) $operation['rework_rate']);
                        $nextOperationId = $this->chooseNextOperationId($operationId, $outgoing, $useRework);
                        if ($useRework && $nextOperationId !== null) {
                            $events[] = $this->event($clock, self::EVENT_REWORK, "Job {$jobId} sent to rework from {$operation['name']}.");
                        }

                        if ($nextOperationId !== null) {
                            $this->pushEvent($eventQueue, $eventSequence, $clock, self::EVENT_JOB_ARRIVED, [
                                'job_id' => $jobId,
                                'operation_id' => $nextOperationId,
                            ]);
                        } else {
                            $jobs[$jobId]['status'] = 'completed';
                            $completed++;
                            $wip = max(0, $wip - 1);
                            $leadTimes[] = max(0.0, $clock - (float) $jobs[$jobId]['created_at']);
                            $events[] = $this->event($clock, self::EVENT_JOB_COMPLETED, "Job {$jobId} completed.");
                        }
                    }

                    $this->startAvailableJobs(
                        $clock,
                        $operationId,
                        $operationsById,
                        $model['resources'],
                        $operationQueues,
                        $resourceAvailability,
                        $resourceBusy,
                        $operationBusy,
                        $queueWaitTimes,
                        $queueLengthArea,
                        $eventQueue,
                        $eventSequence,
                        $events
                    );
                    break;

                case self::EVENT_SIMULATION_END:
                    $events[] = $this->event($clock, self::EVENT_SIMULATION_END, 'Simulation duration reached.');
                    break 2;
            }
        }

        [$wipArea] = $this->updateTimeWeightedArea($wipArea, $lastWipUpdateAt, $duration, $wip);
        foreach ($queueLengthArea as $operationId => $queueStats) {
            $queueLengthArea[$operationId] = $this->updateQueueLengthArea($queueStats, $duration, (int) $queueStats['length']);
        }

        $throughput = $completed / max(0.01, $duration / 60.0);
        $averageLeadTime = $leadTimes === [] ? 0.0 : array_sum($leadTimes) / count($leadTimes);
        $averageWip = $wipArea / max(0.01, $duration);
        $availableResourceTime = $this->availableResourceCapacity($resourceAvailability, $resourceBusy) * $duration;
        $utilization = min(100.0, (array_sum($resourceBusy) / max(1.0, $availableResourceTime)) * 100.0);
        $averageWaitingTime = $queueWaitTimes === [] ? 0.0 : array_sum($queueWaitTimes) / count($queueWaitTimes);
        $scrapRate = $generated > 0 ? ($scrapped / $generated) * 100.0 : 0.0;
        $quality = $generated > 0 ? (($generated - $scrapped) / $generated) : 1.0;
        $performance = min(1.0, $throughput / max(0.01, (float) $scenario['arrival_rate']));
        $oee = min(100.0, ($utilization / 100.0) * $performance * $quality * 100.0);
        arsort($operationBusy);

        return [
            'throughput_per_hour' => round($throughput, 2),
            'average_lead_time_minutes' => round($averageLeadTime, 2),
            'average_wip' => round($averageWip, 2),
            'resource_utilization_percent' => round($utilization, 2),
            'oee_percent' => round($oee, 2),
            'metadata' => [
                'engine_version' => '2.0-event-queue',
                'generated_jobs' => $generated,
                'completed_jobs' => $completed,
                'scrapped_jobs' => $scrapped,
                'scrap_rate_percent' => round($scrapRate, 2),
                'average_waiting_time_minutes' => round($averageWaitingTime, 2),
                'bottleneck' => array_key_first($operationBusy),
                'operation_busy_minutes' => array_map(static fn (float $value): float => round($value, 2), $operationBusy),
                'resource_busy_minutes' => array_map(static fn (float $value): float => round($value, 2), $resourceBusy),
                'lead_time_samples' => array_slice(array_map(static fn (float $value): float => round($value, 2), $leadTimes), -100),
                'queue_lengths' => $this->summarizeQueues($queueLengthArea, $operationsById, $duration),
                'events' => array_slice($events, -120),
            ],
        ];
    }

    /**
     * @param array<int, array<string, mixed>> $operationsById
     */
    private function findStartOperationId(array $operationsById): int
    {
        foreach ($operationsById as $id => $operation) {
            if (($operation['node_type'] ?? '') === 'start') {
                return (int) $id;
            }
        }

        return (int) array_key_first($operationsById);
    }

    /**
     * @param array<int, array<string, mixed>> $connections
     * @return array<int, array<int, array<string, mixed>>>
     */
    private function groupConnections(array $connections): array
    {
        $outgoing = [];
        foreach ($connections as $connection) {
            $sourceId = (int) $connection['source_operation_id'];
            $outgoing[$sourceId][] = $connection;
        }

        foreach ($outgoing as $sourceId => $sourceConnections) {
            usort($sourceConnections, static fn (array $a, array $b): int => $b['probability'] <=> $a['probability']);
            $outgoing[$sourceId] = $sourceConnections;
        }

        return $outgoing;
    }

    /**
     * @param array<int, array<int, array<string, mixed>>> $outgoing
     */
    private function chooseNextOperationId(int $operationId, array $outgoing, bool $preferRework): ?int
    {
        $connections = $outgoing[$operationId] ?? [];
        if ($connections === []) {
            return null;
        }

        if ($preferRework) {
            foreach ($connections as $connection) {
                if (strtolower((string) $connection['connection_type']) === 'rework') {
                    return (int) $connection['target_operation_id'];
                }
            }
        }

        $normalConnections = array_values(array_filter(
            $connections,
            static fn (array $connection): bool => strtolower((string) $connection['connection_type']) !== 'rework'
        ));
        if ($normalConnections === []) {
            return (int) $connections[0]['target_operation_id'];
        }

        $totalProbability = array_sum(array_map(static fn (array $connection): float => (float) $connection['probability'], $normalConnections));
        if ($totalProbability <= 0.0) {
            return (int) $normalConnections[0]['target_operation_id'];
        }

        $roll = mt_rand(1, 10000) / 100.0;
        $cumulative = 0.0;
        foreach ($normalConnections as $connection) {
            $cumulative += ((float) $connection['probability'] / $totalProbability) * 100.0;
            if ($roll <= $cumulative) {
                return (int) $connection['target_operation_id'];
            }
        }

        return (int) $normalConnections[array_key_last($normalConnections)]['target_operation_id'];
    }

    /**
     * @param array<string, array<string, mixed>> $resources
     * @return array<string, array<int, float>>
     */
    private function initializeResourceAvailability(array $resources): array
    {
        $availability = [];
        foreach ($resources as $name => $resource) {
            $availability[$name] = array_fill(0, max(1, (int) $resource['quantity']), 0.0);
        }

        return $availability;
    }

    /**
     * @param array<int, array<string, mixed>> $operationsById
     * @param array<string, array<string, mixed>> $resources
     * @param array<int, array<int, array<string, mixed>>> $operationQueues
     * @param array<string, array<int, float>> $resourceAvailability
     * @param array<string, float> $resourceBusy
     * @param array<string, float> $operationBusy
     * @param array<int, float> $queueWaitTimes
     * @param array<int, array<string, float|int>> $queueLengthArea
     * @param array<int, array<string, mixed>> $events
     */
    private function startAvailableJobs(
        float $clock,
        int $operationId,
        array $operationsById,
        array $resources,
        array &$operationQueues,
        array &$resourceAvailability,
        array &$resourceBusy,
        array &$operationBusy,
        array &$queueWaitTimes,
        array &$queueLengthArea,
        EventQueue $eventQueue,
        int &$eventSequence,
        array &$events
    ): void {
        if (!isset($operationsById[$operationId])) {
            return;
        }

        $operation = $operationsById[$operationId];
        $resourceKey = $this->resourceKey($operation, $resources);
        $resourceAvailability[$resourceKey] ??= [0.0];
        $operationQueues[$operationId] ??= [];

        while ($operationQueues[$operationId] !== []) {
            $requiredQuantity = max(1, (int) ($operation['required_quantity'] ?? 1));
            $slotIndices = $this->availableResourceSlots($resourceAvailability[$resourceKey], $clock, $requiredQuantity);
            if (count($slotIndices) < $requiredQuantity) {
                return;
            }

            $queueItem = array_shift($operationQueues[$operationId]);
            $jobId = (int) $queueItem['job_id'];
            $enteredAt = (float) $queueItem['entered_at'];
            $queueWaitTimes[] = max(0.0, $clock - $enteredAt);
            $queueLengthArea[$operationId] = $this->updateQueueLengthArea(
                $queueLengthArea[$operationId] ?? ['area' => 0.0, 'last_time' => $clock, 'length' => 0],
                $clock,
                count($operationQueues[$operationId])
            );

            $processMinutes = $this->operationDurationMinutes($operation);
            $finish = $clock + $processMinutes;
            foreach ($slotIndices as $slotIndex) {
                $resourceAvailability[$resourceKey][$slotIndex] = $finish;
            }
            $resourceBusy[$resourceKey] = ($resourceBusy[$resourceKey] ?? 0.0) + ($processMinutes * $requiredQuantity);
            $operationBusy[(string) $operation['name']] = ($operationBusy[(string) $operation['name']] ?? 0.0) + $processMinutes;

            $events[] = $this->event($clock, self::EVENT_RESOURCE_ALLOCATED, "{$resourceKey} x{$requiredQuantity} allocated to job {$jobId}.");
            $events[] = $this->event($clock, self::EVENT_PROCESS_START, "{$operation['name']} started job {$jobId}.");
            $this->pushEvent($eventQueue, $eventSequence, $finish, self::EVENT_PROCESS_END, [
                'job_id' => $jobId,
                'operation_id' => $operationId,
                'resource_key' => $resourceKey,
                'slot_indices' => $slotIndices,
            ]);
        }
    }

    /**
     * @param array<int, float> $slots
     * @return array<int, int>
     */
    private function availableResourceSlots(array $slots, float $clock, int $requiredQuantity): array
    {
        $available = [];
        foreach ($slots as $index => $availableAt) {
            if ((float) $availableAt <= $clock) {
                $available[] = (int) $index;
                if (count($available) >= $requiredQuantity) {
                    break;
                }
            }
        }

        return $available;
    }

    /**
     * @param array<string, mixed> $operation
     */
    private function operationDurationMinutes(array $operation): float
    {
        $baseMinutes = (((float) $operation['setup_time_seconds'] + (float) $operation['cycle_time_seconds']) / 60.0);
        $batchSize = max(1, (int) ($operation['batch_size'] ?? 1));
        $duration = max(0.01, $baseMinutes * $this->variationFactor());

        return $batchSize > 1 ? $duration / $batchSize : $duration;
    }

    /**
     * @param array<string, mixed> $operation
     * @param array<string, array<string, mixed>> $resources
     */
    private function resourceKey(array $operation, array $resources): string
    {
        if ($operation['resource_name'] !== '' && isset($resources[$operation['resource_name']])) {
            return (string) $operation['resource_name'];
        }

        return 'operation:' . $operation['id'];
    }

    /**
     * @param EventQueue $queue
     * @param array<string, mixed> $payload
     */
    private function pushEvent(EventQueue $queue, int &$sequence, float $time, string $type, array $payload): void
    {
        $sequence++;
        $queue->schedule($time, $type, $payload);
    }

    private function variationFactor(): float
    {
        return 0.9 + (mt_rand(0, 2000) / 10000.0);
    }

    private function probabilityHit(float $percent): bool
    {
        return $percent > 0.0 && mt_rand(1, 10000) <= (int) round($percent * 100.0);
    }

    private function event(float $time, string $type, string $message): array
    {
        return ['time' => round($time, 2), 'type' => $type, 'message' => $message];
    }

    /**
     * @return array{0:float,1:float}
     */
    private function updateTimeWeightedArea(float $area, float $lastTime, float $time, int $value): array
    {
        if ($time > $lastTime) {
            $area += ($time - $lastTime) * max(0, $value);
        }

        return [$area, $time];
    }

    /**
     * @param array<string, float|int> $queueStats
     * @return array<string, float|int>
     */
    private function updateQueueLengthArea(array $queueStats, float $time, int $newLength): array
    {
        $lastTime = (float) ($queueStats['last_time'] ?? $time);
        $length = (int) ($queueStats['length'] ?? 0);
        if ($time > $lastTime) {
            $queueStats['area'] = (float) ($queueStats['area'] ?? 0.0) + (($time - $lastTime) * $length);
        }
        $queueStats['last_time'] = $time;
        $queueStats['length'] = $newLength;

        return $queueStats;
    }

    /**
     * @param array<string, array<int, float>> $resourceAvailability
     * @param array<string, float> $resourceBusy
     */
    private function availableResourceCapacity(array $resourceAvailability, array $resourceBusy): int
    {
        $capacity = 0;
        foreach ($resourceAvailability as $resourceKey => $slots) {
            if (isset($resourceBusy[$resourceKey])) {
                $capacity += max(1, count($slots));
            }
        }

        return max(1, $capacity);
    }

    /**
     * @param array<int, array<string, float|int>> $queueLengthArea
     * @param array<int, array<string, mixed>> $operationsById
     * @return array<string, float>
     */
    private function summarizeQueues(array $queueLengthArea, array $operationsById, float $duration): array
    {
        $summary = [];
        foreach ($queueLengthArea as $operationId => $queueStats) {
            $name = (string) ($operationsById[$operationId]['name'] ?? ('Operation ' . $operationId));
            $summary[$name] = round(((float) ($queueStats['area'] ?? 0.0)) / max(0.01, $duration), 2);
        }

        arsort($summary);
        return $summary;
    }
}
