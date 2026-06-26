import { ApiClient } from './api.js';

const api = new ApiClient();
const state = { project: null, latestResult: null, selectedScenarioId: null, scenarios: [] };

window.addEventListener('openmep:project-selected', (event) => {
    state.project = event.detail || null;
    if (state.project?.id) {
        loadResults();
        loadScenarios();
    }
});

window.addEventListener('openmep:route-changed', (event) => {
    if (['simulation', 'results'].includes(event.detail?.route) && state.project?.id) {
        loadResults();
        if (event.detail?.route === 'simulation') {
            loadScenarios();
        }
    }
});

document.getElementById('simulationForm')?.addEventListener('submit', async (event) => {
    event.preventDefault();
    if (!state.project?.id) {
        setStatus('Select a project first.', 'danger');
        return;
    }

    clearValidation();
    setStatus('Running...', 'warning');
    document.getElementById('runSimulationBtn').disabled = true;

    try {
        const payload = {
            ...scenarioPayload(),
            random_seed: Number(document.getElementById('simulationSeed').value || 42),
            distribution: 'deterministic',
        };
        const response = await api.post('/api/simulation/run.php', payload);
        state.latestResult = response.data.result;
        renderKpis(state.latestResult);
        renderEvents(state.latestResult.metadata?.events || []);
        await loadResults();
        setStatus('Completed', 'success');
    } catch (error) {
        if (error.payload?.errors) {
            showValidation(error.payload.errors);
            setStatus('Validation failed', 'danger');
        } else {
            setStatus(error.message, 'danger');
        }
    } finally {
        document.getElementById('runSimulationBtn').disabled = false;
    }
});

document.getElementById('refreshResultsBtn')?.addEventListener('click', loadResults);
document.getElementById('refreshResultsDashboardBtn')?.addEventListener('click', loadResults);
document.getElementById('exportResultsCsvBtn')?.addEventListener('click', exportResultsCsv);
document.getElementById('openLatestReportBtn')?.addEventListener('click', () => openSimulationReport());
document.getElementById('saveScenarioBtn')?.addEventListener('click', saveScenario);
document.getElementById('refreshScenariosBtn')?.addEventListener('click', loadScenarios);


async function loadScenarios() {
    if (!state.project?.id) {
        renderScenarios([]);
        return;
    }

    try {
        const response = await api.get(`/api/scenarios/list.php?project_id=${state.project.id}`);
        state.scenarios = response.data.scenarios || [];
        renderScenarios(state.scenarios);
    } catch (error) {
        setStatus(error.message, 'danger');
    }
}

async function saveScenario() {
    if (!state.project?.id) {
        setStatus('Select a project first.', 'danger');
        return;
    }

    clearValidation();
    try {
        const payload = scenarioPayload();
        const response = await api.post('/api/scenarios/save.php', payload);
        state.selectedScenarioId = response.data.scenario.id;
        await loadScenarios();
        setStatus('Scenario saved', 'success');
    } catch (error) {
        if (error.payload?.errors) {
            showValidation(error.payload.errors);
            setStatus('Scenario validation failed', 'danger');
        } else {
            setStatus(error.message, 'danger');
        }
    }
}

async function deleteScenario(id) {
    if (!state.project?.id || !window.confirm('Delete this scenario?')) {
        return;
    }

    try {
        await api.post('/api/scenarios/delete.php', { id: Number(id), project_id: Number(state.project.id) });
        if (state.selectedScenarioId === Number(id)) {
            state.selectedScenarioId = null;
        }
        await loadScenarios();
        setStatus('Scenario deleted', 'success');
    } catch (error) {
        setStatus(error.message, 'danger');
    }
}

function scenarioPayload() {
    const payload = {
        project_id: Number(state.project.id),
        name: document.getElementById('simulationScenarioName').value.trim(),
        duration_minutes: Number(document.getElementById('simulationDuration').value),
        arrival_rate: Number(document.getElementById('simulationArrivalRate').value),
        random_seed: document.getElementById('simulationSeed').value === '' ? null : Number(document.getElementById('simulationSeed').value),
        metadata: { distribution: 'deterministic' },
    };

    if (state.selectedScenarioId) {
        payload.id = state.selectedScenarioId;
    }

    return payload;
}

function renderScenarios(scenarios) {
    const body = document.getElementById('scenarioTableBody');
    if (!body) return;

    if (scenarios.length === 0) {
        body.innerHTML = '<tr><td colspan="4" class="text-secondary p-3">No saved scenarios yet.</td></tr>';
        return;
    }

    body.innerHTML = scenarios.map((scenario) => `
        <tr class="${Number(scenario.id) === Number(state.selectedScenarioId) ? 'table-active' : ''}">
            <td>${escapeHtml(scenario.name)}</td>
            <td>${Number(scenario.duration_minutes)} min</td>
            <td>${number(scenario.arrival_rate)}/h</td>
            <td class="text-end">
                <button class="btn btn-sm btn-outline-info" type="button" data-load-scenario="${scenario.id}">Load</button>
                <button class="btn btn-sm btn-outline-danger" type="button" data-delete-scenario="${scenario.id}">Delete</button>
            </td>
        </tr>
    `).join('');

    body.querySelectorAll('[data-load-scenario]').forEach((button) => {
        button.addEventListener('click', () => loadScenarioIntoForm(Number(button.dataset.loadScenario)));
    });
    body.querySelectorAll('[data-delete-scenario]').forEach((button) => {
        button.addEventListener('click', () => deleteScenario(Number(button.dataset.deleteScenario)));
    });
}

function loadScenarioIntoForm(id) {
    const scenario = state.scenarios.find((item) => Number(item.id) === Number(id));
    if (!scenario) return;

    state.selectedScenarioId = Number(id);
    document.getElementById('simulationScenarioName').value = scenario.name;
    document.getElementById('simulationDuration').value = scenario.duration_minutes;
    document.getElementById('simulationArrivalRate').value = scenario.arrival_rate;
    document.getElementById('simulationSeed').value = scenario.random_seed ?? '';
    renderScenarios(state.scenarios);
    setStatus('Scenario loaded', 'info');
}

function exportResultsCsv() {
    if (!state.project?.id) {
        setStatus('Select a project first.', 'danger');
        return;
    }

    window.location.href = `/api/results/export_csv.php?project_id=${state.project.id}`;
}

function openSimulationReport(runId = null) {
    if (!state.project?.id) {
        setStatus('Select a project first.', 'danger');
        return;
    }

    const query = new URLSearchParams({ project_id: String(state.project.id) });
    if (runId) {
        query.set('run_id', String(runId));
    }

    window.open(`/api/reports/simulation.php?${query.toString()}`, '_blank', 'noopener');
}

async function loadResults() {
    if (!state.project?.id) {
        return;
    }

    try {
        const response = await api.get(`/api/simulation/results.php?project_id=${state.project.id}`);
        const results = response.data.results || [];
        renderResultsTable(results);
        if (results.length > 0) {
            const latest = normalizeStoredResult(results[0]);
            renderKpis(latest);
            renderEvents(latest.metadata?.events || []);
        }
    } catch (error) {
        setStatus(error.message, 'danger');
    }
}

function normalizeStoredResult(row) {
    return {
        throughput_per_hour: Number(row.throughput_per_hour),
        average_lead_time_minutes: Number(row.average_lead_time_minutes),
        average_wip: Number(row.average_wip),
        resource_utilization_percent: Number(row.resource_utilization_percent),
        oee_percent: Number(row.oee_percent),
        metadata: row.metadata || {},
    };
}

function renderKpis(result) {
    document.getElementById('kpiThroughput').textContent = number(result.throughput_per_hour);
    document.getElementById('kpiLeadTime').textContent = number(result.average_lead_time_minutes);
    document.getElementById('kpiWip').textContent = number(result.average_wip);
    document.getElementById('kpiOee').textContent = number(result.oee_percent);
}

function renderEvents(events) {
    const log = document.getElementById('simulationEventLog');
    if (!log) return;
    if (events.length === 0) {
        log.textContent = 'No simulation events available.';
        return;
    }

    log.innerHTML = events.map((event) => `
        <div class="simulation-log-item">
            <span class="text-info">${escapeHtml(String(event.time))} min</span>
            <span class="text-secondary">${escapeHtml(event.type)}</span>
            <span>${escapeHtml(event.message)}</span>
        </div>
    `).join('');
}

function renderResultsTable(results) {
    const body = document.getElementById('resultsTableBody');
    if (!body) return;
    if (results.length === 0) {
        body.innerHTML = '<tr><td colspan="10" class="text-secondary">No simulation results yet.</td></tr>';
        return;
    }

    body.innerHTML = results.map((row) => `
        <tr>
            <td>#${row.run_id}</td>
            <td>${escapeHtml(row.scenario_name)}</td>
            <td>${number(row.throughput_per_hour)}</td>
            <td>${number(row.average_lead_time_minutes)}</td>
            <td>${number(row.average_wip)}</td>
            <td>${number(row.resource_utilization_percent)}%</td>
            <td>${number(row.oee_percent)}%</td>
            <td>${escapeHtml(row.metadata?.bottleneck || '–')}</td>
            <td>${escapeHtml(row.finished_at || '–')}</td>
            <td class="text-end"><button class="btn btn-sm btn-outline-primary" type="button" data-open-report="${row.run_id}">Open</button></td>
        </tr>
    `).join('');

    body.querySelectorAll('[data-open-report]').forEach((button) => {
        button.addEventListener('click', () => openSimulationReport(Number(button.dataset.openReport)));
    });
}

function setStatus(text, type = 'secondary') {
    const status = document.getElementById('simulationStatus');
    if (!status) return;
    status.className = `badge text-bg-${type}`;
    status.textContent = text;
}

function showValidation(errors) {
    clearValidation();
    errors.forEach((error) => {
        const target = document.querySelector(`[data-simulation-error="${error.field}"]`);
        if (target) {
            target.textContent = error.message;
            target.closest('.mb-3')?.querySelector('.form-control')?.classList.add('is-invalid');
        }
    });
}

function clearValidation() {
    document.querySelectorAll('[data-simulation-error]').forEach((element) => {
        element.textContent = '';
    });
    document.querySelectorAll('#simulationForm .is-invalid').forEach((element) => element.classList.remove('is-invalid'));
}

function number(value) {
    const n = Number(value || 0);
    return n.toFixed(2);
}

function escapeHtml(value) {
    return String(value).replace(/[&<>'"]/g, (char) => ({
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        "'": '&#039;',
        '"': '&quot;',
    }[char]));
}
