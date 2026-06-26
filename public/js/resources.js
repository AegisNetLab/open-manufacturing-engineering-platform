import { ApiClient } from './api.js';

const api = new ApiClient();
const state = {
    activeProjectId: Number(localStorage.getItem('openmep.activeProjectId') || 0),
    resources: [],
    layoutElements: [],
};

const elements = {
    form: document.getElementById('resourceForm'),
    id: document.getElementById('resourceId'),
    name: document.getElementById('resourceName'),
    type: document.getElementById('resourceType'),
    quantity: document.getElementById('resourceQuantity'),
    availability: document.getElementById('resourceAvailability'),
    hourlyRate: document.getElementById('resourceHourlyRate'),
    layoutLink: document.getElementById('resourceLayoutLink'),
    notes: document.getElementById('resourceNotes'),
    saveButton: document.getElementById('saveResourceBtn'),
    resetButton: document.getElementById('resetResourceFormBtn'),
    refreshButton: document.getElementById('refreshResourcesBtn'),
    typeFilter: document.getElementById('resourceTypeFilter'),
    tableBody: document.getElementById('resourceTableBody'),
    status: document.getElementById('resourceStatus'),
    alert: document.getElementById('resourceAlert'),
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
    document.querySelectorAll('[data-resource-error]').forEach((node) => { node.textContent = ''; });
    elements.form.querySelectorAll('.is-invalid').forEach((input) => input.classList.remove('is-invalid'));
}

function showValidation(errors = []) {
    clearValidation();
    errors.forEach((error) => {
        const feedback = document.querySelector(`[data-resource-error="${error.field}"]`);
        const input = feedback?.previousElementSibling;
        if (feedback) {
            feedback.textContent = error.message;
        }
        if (input) {
            input.classList.add('is-invalid');
        }
    });
}

function escapeHtml(value) {
    return String(value ?? '')
        .replaceAll('&', '&amp;')
        .replaceAll('<', '&lt;')
        .replaceAll('>', '&gt;')
        .replaceAll('"', '&quot;')
        .replaceAll("'", '&#039;');
}

function resourcePayload() {
    return {
        id: elements.id.value ? Number(elements.id.value) : undefined,
        project_id: state.activeProjectId,
        name: elements.name.value.trim(),
        resource_type: elements.type.value,
        quantity: Number(elements.quantity.value || 1),
        metadata: {
            linked_layout_element_id: elements.layoutLink.value || null,
            availability_percent: Number(elements.availability.value || 100),
            hourly_rate: elements.hourlyRate.value === '' ? null : Number(elements.hourlyRate.value),
            notes: elements.notes.value.trim(),
        },
    };
}

function resetForm() {
    elements.form.reset();
    elements.id.value = '';
    elements.quantity.value = '1';
    elements.availability.value = '100';
    elements.saveButton.textContent = 'Create Resource';
    clearValidation();
}

function renderLayoutOptions() {
    const current = elements.layoutLink.value;
    elements.layoutLink.innerHTML = '<option value="">Not linked</option>' + state.layoutElements.map((item) => (
        `<option value="${item.id}">${escapeHtml(item.name)} (${escapeHtml(item.element_type)})</option>`
    )).join('');
    elements.layoutLink.value = current;
}

function layoutName(id) {
    const found = state.layoutElements.find((item) => Number(item.id) === Number(id));
    return found ? found.name : '';
}

function typeLabel(type) {
    return {
        machine: 'Machine',
        operator: 'Operator',
        tool: 'Tool',
        buffer: 'Buffer',
        transport: 'Transport Device',
    }[type] || type;
}

function renderResources() {
    if (state.activeProjectId < 1) {
        elements.tableBody.innerHTML = '<tr><td colspan="6" class="text-secondary">Select a project to manage resources.</td></tr>';
        return;
    }

    const filter = elements.typeFilter.value;
    const resources = filter ? state.resources.filter((resource) => resource.resource_type === filter) : state.resources;

    if (resources.length === 0) {
        elements.tableBody.innerHTML = '<tr><td colspan="6" class="text-secondary">No resources found.</td></tr>';
        return;
    }

    elements.tableBody.innerHTML = resources.map((resource) => {
        const metadata = resource.metadata || {};
        const linkedName = layoutName(metadata.linked_layout_element_id);
        return `
            <tr>
                <td>
                    <div class="fw-semibold">${escapeHtml(resource.name)}</div>
                    <div class="small text-secondary">${escapeHtml(metadata.notes || '')}</div>
                </td>
                <td>${escapeHtml(typeLabel(resource.resource_type))}</td>
                <td>${Number(resource.quantity || 1)}</td>
                <td>${Number(metadata.availability_percent ?? 100).toFixed(1)}%</td>
                <td>${linkedName ? escapeHtml(linkedName) : '<span class="text-secondary">Not linked</span>'}</td>
                <td class="text-end">
                    <button class="btn btn-sm btn-outline-primary" type="button" data-action="edit" data-id="${resource.id}">Edit</button>
                    <button class="btn btn-sm btn-outline-danger" type="button" data-action="delete" data-id="${resource.id}">Delete</button>
                </td>
            </tr>
        `;
    }).join('');
}

function editResource(id) {
    const resource = state.resources.find((item) => Number(item.id) === Number(id));
    if (!resource) {
        return;
    }

    const metadata = resource.metadata || {};
    elements.id.value = resource.id;
    elements.name.value = resource.name;
    elements.type.value = resource.resource_type;
    elements.quantity.value = resource.quantity;
    elements.availability.value = metadata.availability_percent ?? 100;
    elements.hourlyRate.value = metadata.hourly_rate ?? '';
    elements.layoutLink.value = metadata.linked_layout_element_id ?? '';
    elements.notes.value = metadata.notes ?? '';
    elements.saveButton.textContent = 'Update Resource';
    elements.name.focus();
}

async function loadLayoutElements() {
    if (state.activeProjectId < 1) {
        state.layoutElements = [];
        renderLayoutOptions();
        return;
    }

    const response = await api.get(`/api/layout/load.php?project_id=${state.activeProjectId}`);
    state.layoutElements = response.data.elements || [];
    renderLayoutOptions();
}

async function loadResources() {
    if (state.activeProjectId < 1) {
        renderResources();
        return;
    }

    clearAlert();
    setStatus('Loading', 'info');
    await loadLayoutElements();
    const response = await api.get(`/api/resources/list.php?project_id=${state.activeProjectId}`);
    state.resources = response.data.resources || [];
    renderResources();
    setStatus('Idle', 'secondary');
}

async function saveResource(event) {
    event.preventDefault();

    if (state.activeProjectId < 1) {
        showAlert('Select a project before creating resources.', 'warning');
        return;
    }

    clearAlert();
    clearValidation();
    setStatus('Saving', 'warning');

    try {
        await api.post('/api/resources/save.php', resourcePayload());
        showAlert('Resource saved.', 'success');
        resetForm();
        await loadResources();
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

async function deleteResource(id) {
    const resource = state.resources.find((item) => Number(item.id) === Number(id));
    if (!resource || !confirm(`Delete resource "${resource.name}"?`)) {
        return;
    }

    clearAlert();
    setStatus('Deleting', 'warning');
    await api.post('/api/resources/delete.php', { id: Number(id), project_id: state.activeProjectId });
    showAlert('Resource deleted.', 'success');
    resetForm();
    await loadResources();
}

elements.form.addEventListener('submit', saveResource);
elements.resetButton.addEventListener('click', resetForm);
elements.refreshButton.addEventListener('click', () => loadResources().catch((error) => showAlert(error.message)));
elements.typeFilter.addEventListener('change', renderResources);
elements.tableBody.addEventListener('click', (event) => {
    const button = event.target.closest('button[data-action]');
    if (!button) {
        return;
    }

    if (button.dataset.action === 'edit') {
        editResource(button.dataset.id);
    }

    if (button.dataset.action === 'delete') {
        deleteResource(button.dataset.id).catch((error) => showAlert(error.message));
    }
});

window.addEventListener('openmep:project-selected', (event) => {
    state.activeProjectId = Number(event.detail?.id || 0);
    resetForm();
    loadResources().catch((error) => showAlert(error.message));
});

window.addEventListener('openmep:route-changed', (event) => {
    if (event.detail?.route === 'resources') {
        loadResources().catch((error) => showAlert(error.message));
    }
});

if (state.activeProjectId > 0) {
    loadResources().catch((error) => showAlert(error.message));
}
