<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\SimulationRepository;
use InvalidArgumentException;

final class ReportService
{
    public function __construct(private readonly SimulationRepository $repository)
    {
    }

    public function simulationReportHtml(int $projectId, ?int $runId = null): string
    {
        if ($projectId < 1) {
            throw new InvalidArgumentException('Project ID is required.');
        }

        $result = $runId !== null
            ? $this->repository->resultByProjectAndRun($projectId, $runId)
            : $this->repository->latestResultByProject($projectId);

        if ($result === null) {
            throw new InvalidArgumentException('Simulation result was not found.');
        }

        return $this->renderSimulationReport($result);
    }

    /**
     * @param array<string, mixed> $result
     */
    public function renderSimulationReport(array $result): string
    {
        $metadata = is_array($result['metadata'] ?? null) ? $result['metadata'] : [];
        $scenarioName = $this->escape((string) ($result['scenario_name'] ?? 'Simulation Scenario'));
        $runId = (int) ($result['run_id'] ?? 0);
        $finishedAt = $this->escape((string) ($result['finished_at'] ?? ''));
        $bottleneck = $this->escape((string) ($metadata['bottleneck'] ?? 'Not available'));
        $generated = (int) ($metadata['generated_jobs'] ?? 0);
        $completed = (int) ($metadata['completed_jobs'] ?? 0);
        $scrapped = (int) ($metadata['scrapped_jobs'] ?? 0);
        $reworked = (int) ($metadata['reworked_jobs'] ?? 0);

        $utilizationRows = $this->tableRows($metadata['resource_utilization'] ?? [], '%');
        $queueRows = $this->tableRows($metadata['queue_summary'] ?? [], ' jobs');
        $events = array_slice(is_array($metadata['events'] ?? null) ? $metadata['events'] : [], -25);
        $eventRows = $events === []
            ? '<tr><td colspan="3">No event log entries are available.</td></tr>'
            : implode('', array_map(fn (array $event): string => sprintf(
                '<tr><td>%s</td><td>%s</td><td>%s</td></tr>',
                $this->escape((string) ($event['time'] ?? '')),
                $this->escape((string) ($event['type'] ?? '')),
                $this->escape((string) ($event['message'] ?? ''))
            ), $events));

        return '<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>OpenMEP Simulation Report</title>
<style>
:root{color-scheme:light;--border:#d9dee8;--text:#1f2937;--muted:#6b7280;--accent:#0f6b8f;--bg:#f6f8fb;--card:#fff}
*{box-sizing:border-box}body{margin:0;font-family:Inter,Arial,sans-serif;background:var(--bg);color:var(--text);line-height:1.45}.page{max-width:1120px;margin:0 auto;padding:32px}.header{display:flex;justify-content:space-between;gap:24px;align-items:flex-start;margin-bottom:24px}.brand{font-size:13px;text-transform:uppercase;letter-spacing:.14em;color:var(--accent);font-weight:700}.title{font-size:30px;margin:4px 0 0}.muted{color:var(--muted)}.print-btn{border:1px solid var(--border);background:#fff;border-radius:8px;padding:9px 14px;cursor:pointer}.grid{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:14px;margin:20px 0}.card{background:var(--card);border:1px solid var(--border);border-radius:12px;padding:16px;box-shadow:0 8px 24px rgba(15,23,42,.04)}.kpi-label{font-size:12px;color:var(--muted);text-transform:uppercase;letter-spacing:.08em}.kpi-value{font-size:28px;font-weight:700;margin-top:4px}.section{margin-top:18px}h2{font-size:18px;margin:0 0 10px}table{width:100%;border-collapse:collapse;background:#fff;border:1px solid var(--border);border-radius:10px;overflow:hidden}th,td{text-align:left;padding:10px 12px;border-bottom:1px solid var(--border);font-size:14px}th{background:#eef3f8;font-size:12px;text-transform:uppercase;letter-spacing:.06em;color:#4b5563}tr:last-child td{border-bottom:none}.summary{display:grid;grid-template-columns:1.2fr .8fr;gap:14px}.footer{margin-top:24px;font-size:12px;color:var(--muted)}@media print{body{background:#fff}.page{max-width:none;padding:0}.print-btn{display:none}.card,table{box-shadow:none}.grid{break-inside:avoid}}
</style>
</head>
<body>
<div class="page">
  <div class="header">
    <div>
      <div class="brand">OpenMEP</div>
      <h1 class="title">Simulation Report</h1>
      <div class="muted">Scenario: ' . $scenarioName . ' · Run #' . $runId . ($finishedAt !== '' ? ' · Finished: ' . $finishedAt : '') . '</div>
    </div>
    <button class="print-btn" onclick="window.print()">Print / Save as PDF</button>
  </div>

  <div class="grid">
    ' . $this->kpiCard('Throughput', $this->formatNumber($result['throughput_per_hour'] ?? 0), 'jobs/hour') . '
    ' . $this->kpiCard('Lead Time', $this->formatNumber($result['average_lead_time_minutes'] ?? 0), 'minutes') . '
    ' . $this->kpiCard('Average WIP', $this->formatNumber($result['average_wip'] ?? 0), 'jobs') . '
    ' . $this->kpiCard('OEE', $this->formatNumber($result['oee_percent'] ?? 0), '%') . '
  </div>

  <div class="summary section">
    <div class="card">
      <h2>Run Summary</h2>
      <table><tbody>
        <tr><th>Bottleneck</th><td>' . $bottleneck . '</td></tr>
        <tr><th>Generated Jobs</th><td>' . $generated . '</td></tr>
        <tr><th>Completed Jobs</th><td>' . $completed . '</td></tr>
        <tr><th>Scrapped Jobs</th><td>' . $scrapped . '</td></tr>
        <tr><th>Reworked Jobs</th><td>' . $reworked . '</td></tr>
      </tbody></table>
    </div>
    <div class="card">
      <h2>Scenario Inputs</h2>
      <table><tbody>
        <tr><th>Duration</th><td>' . $this->formatNumber($result['duration_minutes'] ?? 0) . ' minutes</td></tr>
        <tr><th>Arrival Rate</th><td>' . $this->formatNumber($result['arrival_rate'] ?? 0) . ' jobs/hour</td></tr>
        <tr><th>Status</th><td>' . $this->escape((string) ($result['status'] ?? '')) . '</td></tr>
      </tbody></table>
    </div>
  </div>

  <div class="section card">
    <h2>Resource Utilization</h2>
    <table><thead><tr><th>Resource</th><th>Utilization</th></tr></thead><tbody>' . $utilizationRows . '</tbody></table>
  </div>

  <div class="section card">
    <h2>Queue Summary</h2>
    <table><thead><tr><th>Operation</th><th>Average Queue Length</th></tr></thead><tbody>' . $queueRows . '</tbody></table>
  </div>

  <div class="section card">
    <h2>Recent Event Log</h2>
    <table><thead><tr><th>Time</th><th>Event</th><th>Message</th></tr></thead><tbody>' . $eventRows . '</tbody></table>
  </div>

  <div class="footer">Generated by Open Manufacturing Engineering Platform. This MVP report is intended for engineering review and scenario comparison.</div>
</div>
</body>
</html>';
    }

    private function kpiCard(string $label, string $value, string $unit): string
    {
        return '<div class="card"><div class="kpi-label">' . $this->escape($label) . '</div><div class="kpi-value">' . $this->escape($value) . '</div><div class="muted">' . $this->escape($unit) . '</div></div>';
    }

    /**
     * @param mixed $data
     */
    private function tableRows(mixed $data, string $suffix): string
    {
        if (!is_array($data) || $data === []) {
            return '<tr><td colspan="2">No data available.</td></tr>';
        }

        $rows = [];
        foreach ($data as $name => $value) {
            $rows[] = '<tr><td>' . $this->escape((string) $name) . '</td><td>' . $this->formatNumber($value) . $this->escape($suffix) . '</td></tr>';
        }

        return implode('', $rows);
    }

    /**
     * @param mixed $value
     */
    private function formatNumber(mixed $value): string
    {
        return number_format((float) $value, 2, '.', '');
    }

    private function escape(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}
