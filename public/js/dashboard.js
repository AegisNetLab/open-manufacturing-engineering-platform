import { ApiClient } from './api.js';

const api = new ApiClient();

const elements = {
    metrics: document.querySelectorAll('[data-dashboard-metric]'),
    recentProjects: document.getElementById('dashboardRecentProjects'),
    latestResults: document.getElementById('dashboardLatestResults'),
    readiness: document.getElementById('dashboardReadiness'),
    refreshButton: document.getElementById('dashboardRefreshBtn'),
    createProjectButton: document.getElementById('dashboardCreateProjectBtn'),
};

function escapeHtml(value) {
    return String(value ?? '')
        .replaceAll('&', '&amp;')
        .replaceAll('<', '&lt;')
        .replaceAll('>', '&gt;')
        .replaceAll('"', '&quot;')
        .replaceAll("'", '&#039;');
}

function formatNumber(value, fractionDigits = 1) {
    const numericValue = Number(value);
    if (!Number.isFinite(numericValue)) {
        return '–';
    }

    return numericValue.toLocaleString(undefined, {
        minimumFractionDigits: 0,
        maximumFractionDigits: fractionDigits,
    });
}

function setLoading() {
    elements.metrics.forEach((metric) => {
        metric.textContent = '–';
    });
    elements.recentProjects.innerHTML = '<div class="list-group-item text-secondary">Loading...</div>';
    elements.latestResults.innerHTML = '<tr><td colspan="3" class="text-secondary">Loading...</td></tr>';
    elements.readiness.innerHTML = '<div class="list-group-item text-secondary">Loading...</div>';
}

function renderMetrics(metrics = {}) {
    elements.metrics.forEach((metric) => {
        const key = metric.dataset.dashboardMetric;
        metric.textContent = formatNumber(metrics[key] ?? 0, 0);
    });
}

function renderRecentProjects(projects = []) {
    if (projects.length === 0) {
        elements.recentProjects.innerHTML = '<div class="list-group-item text-secondary">No projects yet.</div>';
        return;
    }

    elements.recentProjects.innerHTML = projects.map((project) => `
        <button type="button" class="list-group-item list-group-item-action" data-project-id="${project.id}" data-project-name="${escapeHtml(project.name)}">
            <div class="d-flex justify-content-between gap-2">
                <span class="fw-semibold">${escapeHtml(project.name)}</span>
                <span class="badge text-bg-secondary">${escapeHtml(project.production_type)}</span>
            </div>
            <div class="small text-secondary">Updated ${escapeHtml(project.updated_at || '')}</div>
        </button>
    `).join('');
}

function renderLatestResults(results = []) {
    if (results.length === 0) {
        elements.latestResults.innerHTML = '<tr><td colspan="3" class="text-secondary">No simulation results yet.</td></tr>';
        return;
    }

    elements.latestResults.innerHTML = results.map((result) => `
        <tr>
            <td>
                <button type="button" class="btn btn-link btn-sm p-0 text-start" data-project-id="${result.project_id}" data-project-name="${escapeHtml(result.project_name)}">
                    ${escapeHtml(result.project_name)}
                </button>
                <div class="small text-secondary">${escapeHtml(result.scenario_name || '')}</div>
            </td>
            <td>${formatNumber(result.throughput_per_hour)}</td>
            <td>${formatNumber(result.oee_percent)}%</td>
        </tr>
    `).join('');
}

function renderReadiness(items = []) {
    if (items.length === 0) {
        elements.readiness.innerHTML = '<div class="list-group-item text-secondary">No projects to evaluate.</div>';
        return;
    }

    elements.readiness.innerHTML = items.map((item) => `
        <button type="button" class="list-group-item list-group-item-action" data-project-id="${item.id}" data-project-name="${escapeHtml(item.name)}">
            <div class="d-flex justify-content-between gap-2">
                <span class="fw-semibold">${escapeHtml(item.name)}</span>
                <span class="badge ${item.ready ? 'text-bg-success' : 'text-bg-warning'}">${item.ready ? 'Ready' : 'Incomplete'}</span>
            </div>
            <div class="small text-secondary">
                Layout ${item.layout_elements} · Resources ${item.resources} · Operations ${item.operations} · Connections ${item.connections} · Scenarios ${item.scenarios}
            </div>
        </button>
    `).join('');
}

function selectProject(projectId, projectName, route = 'layout') {
    const numericProjectId = Number(projectId);
    if (!numericProjectId) {
        return;
    }

    localStorage.setItem('openmep.activeProjectId', String(numericProjectId));
    localStorage.setItem('openmep.activeProjectName', projectName || `Project ${numericProjectId}`);
    window.dispatchEvent(new CustomEvent('openmep:project-selected', {
        detail: { id: numericProjectId, name: projectName || `Project ${numericProjectId}` },
    }));
    window.location.hash = route;
}

async function loadDashboard() {
    setLoading();
    const response = await api.get('/api/dashboard/summary.php');
    renderMetrics(response.data.metrics || {});
    renderRecentProjects(response.data.recent_projects || []);
    renderLatestResults(response.data.latest_results || []);
    renderReadiness(response.data.readiness || []);
}

function bindProjectPicker(container, route) {
    container.addEventListener('click', (event) => {
        const button = event.target.closest('[data-project-id]');
        if (!button) {
            return;
        }

        selectProject(button.dataset.projectId, button.dataset.projectName, route);
    });
}

elements.refreshButton?.addEventListener('click', () => loadDashboard().catch(() => {
    elements.recentProjects.innerHTML = '<div class="list-group-item text-danger">Dashboard loading failed.</div>';
}));
elements.createProjectButton?.addEventListener('click', () => {
    window.location.hash = 'projects';
    window.setTimeout(() => document.getElementById('projectName')?.focus(), 50);
});

bindProjectPicker(elements.recentProjects, 'layout');
bindProjectPicker(elements.latestResults, 'results');
bindProjectPicker(elements.readiness, 'simulation');

window.addEventListener('openmep:route-changed', (event) => {
    if (event.detail?.route === 'dashboard') {
        loadDashboard().catch(() => {
            elements.recentProjects.innerHTML = '<div class="list-group-item text-danger">Dashboard loading failed.</div>';
        });
    }
});

loadDashboard().catch(() => {
    elements.recentProjects.innerHTML = '<div class="list-group-item text-danger">Dashboard loading failed.</div>';
});
