# Reporting

OpenMEP Reporting v1 provides a printable simulation report for saved simulation runs.

## Scope

The MVP report is intentionally lightweight and browser-based. It produces an HTML document that can be printed or saved as PDF by the browser. This avoids introducing server-side PDF dependencies while still giving engineers a shareable report format.

## Endpoint

```http
GET /api/reports/simulation.php?project_id={projectId}&run_id={runId}
```

`run_id` is optional. When it is omitted, the latest simulation result for the selected project is used.

## Report Contents

The report includes:

- scenario and run metadata;
- throughput, lead time, average WIP and OEE KPI cards;
- generated, completed, scrapped and reworked job counts;
- bottleneck operation/resource summary;
- resource utilization table;
- queue length summary;
- recent simulation events.

## Design Notes

The report is read-only. It does not mutate engineering data and does not create additional database records. It reads persisted `simulation_results.metadata_json`, so future KPI fields can be shown without changing the core results table.

## Future Extensions

- branded PDF generation;
- report templates;
- comparison reports for multiple scenarios;
- chart images embedded in reports;
- report export API with archived report files.
