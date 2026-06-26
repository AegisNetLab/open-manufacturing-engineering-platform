import { ApiClient } from './api.js';

const api = new ApiClient();
const state = {
    projects: [],
    activeProjectId: Number(localStorage.getItem('openmep.activeProjectId') || 0),
    filters: { query: '', productionType: '', sort: 'updated_at', direction: 'DESC', page: 1, perPage: 10 },
};

const elements = {
    form: document.getElementById('projectForm'),
    id: document.getElementById('projectId'),
    name: document.getElementById('projectName'),
    description: document.getElementById('projectDescription'),
    productionType: document.getElementById('productionType'),
    shiftLength: document.getElementById('shiftLength'),
    saveButton: document.getElementById('saveProjectBtn'),
    resetButton: document.getElementById('resetProjectFormBtn'),
    refreshButton: document.getElementById('refreshProjectsBtn'),
    importButton: document.getElementById('importProjectBtn'),
    importFile: document.getElementById('projectImportFile'),
    searchInput: document.getElementById('projectSearchInput'),
    typeFilter: document.getElementById('projectTypeFilter'),
    sortSelect: document.getElementById('projectSortSelect'),
    directionSelect: document.getElementById('projectDirectionSelect'),
    perPageSelect: document.getElementById('projectPerPageSelect'),
    pagination: document.getElementById('projectPagination'),
    paginationSummary: document.getElementById('projectPaginationSummary'),
    tableBody: document.getElementById('projectTableBody'),
    status: document.getElementById('projectStatus'),
    alert: document.getElementById('apiAlert'),
};

function setStatus(label, type = 'secondary') {
    elements.status.className = `badge text-bg-${type}`;
    elements.status.textContent = label;
}

function showAlert(message, type = 'danger') {
    elements.alert.className = `alert alert-${type}`;
    elements.alert.textContent = message;
}

function clearAlert() {
    elements.alert.className = 'alert d-none';
    elements.alert.textContent = '';
}

function clearValidation() {
    document.querySelectorAll('.is-invalid').forEach((input) => input.classList.remove('is-invalid'));
    document.querySelectorAll('[data-field-error]').forEach((node) => { node.textContent = ''; });
}

function showValidation(errors = []) {
    clearValidation();
    errors.forEach((error) => {
        const feedback = document.querySelector(`[data-field-error="${error.field}"]`);
        const input = feedback?.previousElementSibling;
        if (feedback) {
            feedback.textContent = error.message;
        }
        if (input) {
            input.classList.add('is-invalid');
        }
    });
}

function formData() {
    return {
        id: elements.id.value ? Number(elements.id.value) : undefined,
        name: elements.name.value.trim(),
        description: elements.description.value.trim() || null,
        production_type: elements.productionType.value,
        shift_length_minutes: Number(elements.shiftLength.value || 480),
    };
}

function resetForm() {
    elements.form.reset();
    elements.id.value = '';
    elements.shiftLength.value = '480';
    elements.saveButton.textContent = 'Create Project';
    clearValidation();
}

function selectProject(id) {
    const project = state.projects.find((item) => Number(item.id) === Number(id));
    if (!project) {
        return;
    }

    state.activeProjectId = Number(project.id);
    localStorage.setItem('openmep.activeProjectId', String(project.id));
    localStorage.setItem('openmep.activeProjectName', project.name);
    window.dispatchEvent(new CustomEvent('openmep:project-selected', { detail: project }));
}

function editProject(id) {
    const project = state.projects.find((item) => Number(item.id) === Number(id));
    if (!project) {
        return;
    }

    elements.id.value = project.id;
    elements.name.value = project.name;
    elements.description.value = project.description || '';
    elements.productionType.value = project.production_type;
    elements.shiftLength.value = project.shift_length_minutes;
    elements.saveButton.textContent = 'Update Project';
    elements.name.focus();
}

async function duplicateProject(id) {
    const project = state.projects.find((item) => Number(item.id) === Number(id));
    if (!project) {
        return;
    }

    const name = prompt('Name for the duplicated project:', `${project.name} (Copy)`);
    if (name === null) {
        return;
    }

    clearAlert();
    setStatus('Duplicating', 'warning');
    const response = await api.post('/api/projects/duplicate.php', { id: Number(id), name: name.trim() });
    showAlert('Project duplicated.', 'success');
    resetForm();
    await loadProjects();
    const duplicatedProjectId = Number(response.data.project?.id || 0);
    if (duplicatedProjectId) {
        selectProject(duplicatedProjectId);
    }
}

async function deleteProject(id) {
    const project = state.projects.find((item) => Number(item.id) === Number(id));
    if (!project || !confirm(`Delete project "${project.name}"?`)) {
        return;
    }

    clearAlert();
    setStatus('Deleting', 'warning');
    await api.post('/api/projects/delete.php', { id: Number(id) });
    showAlert('Project deleted.', 'success');
    resetForm();
    await loadProjects();
}

function renderProjects() {
    if (state.projects.length === 0) {
        elements.tableBody.innerHTML = '<tr><td colspan="5" class="text-secondary">No projects found.</td></tr>';
        return;
    }

    elements.tableBody.innerHTML = state.projects.map((project) => `
        <tr>
            <td>
                <div class="fw-semibold">${escapeHtml(project.name)}</div>
                <div class="small text-secondary">${escapeHtml(project.description || '')}</div>
            </td>
            <td>${escapeHtml(project.production_type)}</td>
            <td>${project.shift_length_minutes} min</td>
            <td>${escapeHtml(project.updated_at || '')}</td>
            <td class="text-end">
                <button type="button" class="btn btn-sm btn-outline-success" data-action="open" data-id="${project.id}">Open</button>
                <button type="button" class="btn btn-sm btn-outline-secondary" data-action="export" data-id="${project.id}">Export</button>
                <button type="button" class="btn btn-sm btn-outline-secondary" data-action="duplicate" data-id="${project.id}">Duplicate</button>
                <button type="button" class="btn btn-sm btn-outline-primary" data-action="edit" data-id="${project.id}">Edit</button>
                <button type="button" class="btn btn-sm btn-outline-danger" data-action="delete" data-id="${project.id}">Delete</button>
            </td>
        </tr>
    `).join('');
}


function renderPagination(pagination = {}) {
    const page = Number(pagination.page || 1);
    const totalPages = Number(pagination.total_pages || 1);
    const totalItems = Number(pagination.total_items || 0);
    const perPage = Number(pagination.per_page || state.filters.perPage);

    if (totalItems === 0) {
        elements.paginationSummary.textContent = 'No projects match the current filters.';
        elements.pagination.innerHTML = '';
        return;
    }

    const start = ((page - 1) * perPage) + 1;
    const end = Math.min(totalItems, page * perPage);
    elements.paginationSummary.textContent = `Showing ${start}-${end} of ${totalItems} projects.`;

    const pages = visiblePageNumbers(page, totalPages);
    elements.pagination.innerHTML = [
        paginationItem('Previous', page - 1, !pagination.has_previous),
        ...pages.map((item) => item === '…'
            ? '<li class="page-item disabled"><span class="page-link">…</span></li>'
            : paginationItem(String(item), item, false, item === page)),
        paginationItem('Next', page + 1, !pagination.has_next),
    ].join('');
}

function paginationItem(label, page, disabled = false, active = false) {
    return `
        <li class="page-item ${disabled ? 'disabled' : ''} ${active ? 'active' : ''}">
            <button type="button" class="page-link" data-page="${page}" ${disabled ? 'disabled' : ''}>${label}</button>
        </li>
    `;
}

function visiblePageNumbers(currentPage, totalPages) {
    if (totalPages <= 7) {
        return Array.from({ length: totalPages }, (_, index) => index + 1);
    }

    const pages = [1];
    const start = Math.max(2, currentPage - 1);
    const end = Math.min(totalPages - 1, currentPage + 1);

    if (start > 2) {
        pages.push('…');
    }
    for (let page = start; page <= end; page += 1) {
        pages.push(page);
    }
    if (end < totalPages - 1) {
        pages.push('…');
    }
    pages.push(totalPages);

    return pages;
}

function escapeHtml(value) {
    return String(value)
        .replaceAll('&', '&amp;')
        .replaceAll('<', '&lt;')
        .replaceAll('>', '&gt;')
        .replaceAll('"', '&quot;')
        .replaceAll("'", '&#039;');
}


function exportProject(id) {
    const projectId = Number(id);
    if (!projectId) {
        showAlert('Select a project before exporting.', 'warning');
        return;
    }

    window.location.href = `/api/export/project.php?project_id=${projectId}`;
}

async function importProjectFile(file) {
    if (!file) {
        return;
    }

    clearAlert();
    setStatus('Importing', 'warning');

    try {
        const packageText = await file.text();
        const packageData = JSON.parse(packageText);
        const response = await api.post('/api/import/project.php', packageData);
        showAlert('Project package imported.', 'success');
        await loadProjects();
        const newProjectId = Number(response.data.project_id || 0);
        if (newProjectId) {
            selectProject(newProjectId);
        }
    } catch (error) {
        showAlert(error.message || 'Project import failed.');
        setStatus('Error', 'danger');
    } finally {
        elements.importFile.value = '';
    }
}

function buildProjectListUrl() {
    const params = new URLSearchParams();
    if (state.filters.query) {
        params.set('query', state.filters.query);
    }
    if (state.filters.productionType) {
        params.set('production_type', state.filters.productionType);
    }
    params.set('sort', state.filters.sort || 'updated_at');
    params.set('direction', state.filters.direction || 'DESC');
    params.set('page', String(state.filters.page || 1));
    params.set('per_page', String(state.filters.perPage || 10));

    return `/api/projects/list.php?${params.toString()}`;
}

async function loadProjects() {
    clearAlert();
    setStatus('Loading', 'info');
    const response = await api.get(buildProjectListUrl());
    state.projects = response.data.projects || [];
    state.filters.page = Number(response.data.pagination?.page || state.filters.page || 1);
    renderProjects();
    renderPagination(response.data.pagination || {});
    const active = state.projects.find((item) => Number(item.id) === state.activeProjectId);
    if (active) {
        window.dispatchEvent(new CustomEvent('openmep:project-selected', { detail: active }));
    }
    setStatus('Idle', 'secondary');
}

function scheduleProjectReload() {
    clearTimeout(scheduleProjectReload.timer);
    scheduleProjectReload.timer = setTimeout(() => {
        loadProjects().catch((error) => showAlert(error.message));
    }, 250);
}

async function saveProject(event) {
    event.preventDefault();
    clearAlert();
    clearValidation();
    setStatus('Saving', 'warning');

    try {
        const data = formData();
        const endpoint = data.id ? '/api/projects/update.php' : '/api/projects/create.php';
        await api.post(endpoint, data);
        showAlert(data.id ? 'Project updated.' : 'Project created.', 'success');
        resetForm();
        await loadProjects();
    } catch (error) {
        if (error.payload?.errors) {
            showValidation(error.payload.errors);
            showAlert('Please correct the highlighted fields.', 'warning');
        } else {
            showAlert(error.message);
        }
        setStatus('Error', 'danger');
    }
}

elements.form.addEventListener('submit', saveProject);
elements.resetButton.addEventListener('click', resetForm);
elements.refreshButton.addEventListener('click', () => loadProjects().catch((error) => showAlert(error.message)));
elements.importButton.addEventListener('click', () => elements.importFile.click());
elements.importFile.addEventListener('change', () => importProjectFile(elements.importFile.files?.[0]).catch((error) => showAlert(error.message)));
elements.searchInput.addEventListener('input', () => {
    state.filters.query = elements.searchInput.value.trim();
    state.filters.page = 1;
    scheduleProjectReload();
});
elements.typeFilter.addEventListener('change', () => {
    state.filters.productionType = elements.typeFilter.value;
    state.filters.page = 1;
    scheduleProjectReload();
});
elements.sortSelect.addEventListener('change', () => {
    state.filters.sort = elements.sortSelect.value;
    state.filters.page = 1;
    scheduleProjectReload();
});
elements.directionSelect.addEventListener('change', () => {
    state.filters.direction = elements.directionSelect.value;
    state.filters.page = 1;
    scheduleProjectReload();
});

elements.perPageSelect.addEventListener('change', () => {
    state.filters.perPage = Number(elements.perPageSelect.value || 10);
    state.filters.page = 1;
    scheduleProjectReload();
});

elements.pagination.addEventListener('click', (event) => {
    const button = event.target.closest('button[data-page]');
    if (!button || button.disabled) {
        return;
    }

    const nextPage = Number(button.dataset.page || 1);
    if (nextPage < 1 || nextPage === state.filters.page) {
        return;
    }

    state.filters.page = nextPage;
    loadProjects().catch((error) => showAlert(error.message));
});

elements.tableBody.addEventListener('click', (event) => {
    const button = event.target.closest('button[data-action]');
    if (!button) {
        return;
    }

    if (button.dataset.action === 'open') {
        selectProject(button.dataset.id);
        location.hash = '#layout';
    }

    if (button.dataset.action === 'export') {
        exportProject(button.dataset.id);
    }

    if (button.dataset.action === 'duplicate') {
        duplicateProject(button.dataset.id).catch((error) => showAlert(error.message));
    }

    if (button.dataset.action === 'edit') {
        editProject(button.dataset.id);
    }

    if (button.dataset.action === 'delete') {
        deleteProject(button.dataset.id).catch((error) => showAlert(error.message));
    }
});

loadProjects().catch((error) => {
    elements.tableBody.innerHTML = '<tr><td colspan="5" class="text-danger">Failed to load projects.</td></tr>';
    elements.pagination.innerHTML = '';
    elements.paginationSummary.textContent = 'Project loading failed.';
    showAlert(error.message);
    setStatus('Error', 'danger');
});
