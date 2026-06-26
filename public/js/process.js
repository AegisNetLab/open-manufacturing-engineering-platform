import { ApiClient } from './api.js';
import { HistoryManager } from './components/historyManager.js';

const api = new ApiClient();
const history = new HistoryManager({
    onChange: ({ canUndo, canRedo }) => {
        document.getElementById('processUndoBtn')?.toggleAttribute('disabled', !canUndo);
        document.getElementById('processRedoBtn')?.toggleAttribute('disabled', !canRedo);
    },
});

const nodeTypes = [
    { type: 'start', label: 'Start', code: 'START', color: '#1B5E20', cycle: 0 },
    { type: 'operation', label: 'Operation', code: 'OP', color: '#1565C0', cycle: 5 },
    { type: 'inspection', label: 'Inspection', code: 'QC', color: '#F57F17', cycle: 1.5 },
    { type: 'transport', label: 'Transport', code: 'TR', color: '#37474F', cycle: 2 },
    { type: 'buffer', label: 'Buffer', code: 'BUF', color: '#795548', cycle: 0 },
    { type: 'decision', label: 'Decision', code: 'DEC', color: '#E65100', cycle: 0 },
    { type: 'delay', label: 'Delay', code: 'DLY', color: '#6A1B9A', cycle: 30 },
    { type: 'end', label: 'End', code: 'END', color: '#B71C1C', cycle: 0 },
];

const state = {
    project: null,
    nodes: [],
    connections: [],
    selectedId: null,
    connectionStartId: null,
    sequence: 0,
    layoutElements: [],
    resources: [],
};

const elements = {
    library: document.getElementById('processNodeLibrary'),
    canvas: document.getElementById('processCanvas'),
    wrap: document.getElementById('processCanvasWrap'),
    svg: document.getElementById('processSvgLayer'),
    hint: document.getElementById('processCanvasHint'),
    status: document.getElementById('processStatus'),
    validation: document.getElementById('processValidationPanel'),
    noSelection: document.getElementById('processNoSelection'),
    form: document.getElementById('processProperties'),
    layoutLink: document.getElementById('processLayoutLink'),
    resourceLink: document.getElementById('processResourceLink'),
    countNodes: document.getElementById('processNodeCount'),
    countConnections: document.getElementById('processConnectionCount'),
    cycleTime: document.getElementById('processCycleTime'),
};

function setStatus(text, variant = 'secondary') {
    elements.status.textContent = text;
    elements.status.className = `badge text-bg-${variant} ms-auto`;
}

function renderLibrary() {
    if (!elements.library) return;
    elements.library.innerHTML = nodeTypes.map((node) => `
        <div class="library-item process-library-item" draggable="true" data-node-type="${node.type}">
            <div class="library-icon" style="background:${node.color}">${node.code}</div>
            <div>
                <div class="fw-semibold small">${node.label}</div>
                <div class="text-secondary small">${node.cycle} min default</div>
            </div>
        </div>
    `).join('');

    elements.library.querySelectorAll('[data-node-type]').forEach((item) => {
        item.addEventListener('dragstart', (event) => {
            event.dataTransfer.setData('openmep-node-type', item.dataset.nodeType);
        });
    });
}

function nextCode(type) {
    const count = state.nodes.filter((node) => node.node_type === type).length + 1;
    const base = nodeTypes.find((node) => node.type === type)?.code || 'OP';
    return ['START', 'END'].includes(base) ? base : `${base}${String(count).padStart(2, '0')}`;
}

function createNode(type, x, y) {
    const definition = nodeTypes.find((node) => node.type === type) || nodeTypes[1];
    const node = {
        node_id: `node_${Date.now()}_${++state.sequence}`,
        node_type: type,
        operation_code: nextCode(type),
        name: definition.label,
        cycle_time_minutes: definition.cycle,
        setup_time_minutes: 0,
        batch_size: 1,
        scrap_rate: 0,
        rework_rate: 0,
        linked_layout_element_id: null,
        resource_id: null,
        resource_name: '',
        required_quantity: 1,
        mtbf_hours: 0,
        mttr_hours: 0,
        notes: '',
        x,
        y,
        color: definition.color,
        metadata: {},
    };
    pushHistory();
    state.nodes.push(node);
    selectNode(node.node_id);
    render();
}

function render() {
    renderNodes();
    renderConnections();
    updateStats();
    elements.hint?.classList.toggle('d-none', state.nodes.length > 0);
}

function renderNodes() {
    elements.canvas.innerHTML = '';
    state.nodes.forEach((node) => {
        const item = document.createElement('div');
        item.className = `process-node${node.node_id === state.selectedId ? ' selected' : ''}`;
        item.dataset.nodeId = node.node_id;
        item.style.left = `${node.x}px`;
        item.style.top = `${node.y}px`;
        item.style.borderColor = node.color;
        item.style.background = `${node.color}dd`;
        item.innerHTML = `
            <button type="button" class="process-port process-port-in" title="Input"></button>
            <div class="process-node-title">${escapeHtml(node.name)}</div>
            <div class="process-node-code">${escapeHtml(node.operation_code)} · ${escapeHtml(node.node_type)}</div>
            <div class="process-node-time">${Number(node.cycle_time_minutes).toFixed(1)} min</div>
            <button type="button" class="process-port process-port-out" title="Output"></button>
        `;
        elements.canvas.appendChild(item);
        enableNodeEvents(item, node);
    });
}

function enableNodeEvents(item, node) {
    item.addEventListener('click', (event) => {
        if (event.target.classList.contains('process-port-out')) {
            state.connectionStartId = node.node_id;
            setStatus('Select target node', 'info');
            return;
        }
        if (event.target.classList.contains('process-port-in') && state.connectionStartId) {
            addConnection(state.connectionStartId, node.node_id);
            state.connectionStartId = null;
            setStatus('Connection added', 'success');
            return;
        }
        selectNode(node.node_id);
    });

    item.addEventListener('mousedown', (event) => {
        if (event.target.classList.contains('process-port')) return;
        const rect = elements.wrap.getBoundingClientRect();
        const offsetX = event.clientX - rect.left - node.x;
        const offsetY = event.clientY - rect.top - node.y;
        selectNode(node.node_id);

        pushHistory();
        const onMove = (moveEvent) => {
            node.x = Math.max(0, moveEvent.clientX - rect.left - offsetX);
            node.y = Math.max(0, moveEvent.clientY - rect.top - offsetY);
            item.style.left = `${node.x}px`;
            item.style.top = `${node.y}px`;
            renderConnections();
        };
        const onUp = () => {
            document.removeEventListener('mousemove', onMove);
            document.removeEventListener('mouseup', onUp);
        };
        document.addEventListener('mousemove', onMove);
        document.addEventListener('mouseup', onUp);
    });
}

function addConnection(sourceNodeId, targetNodeId) {
    if (sourceNodeId === targetNodeId) return;
    if (state.connections.some((connection) => connection.source_node_id === sourceNodeId && connection.target_node_id === targetNodeId)) {
        return;
    }
    pushHistory();
    state.connections.push({
        source_node_id: sourceNodeId,
        target_node_id: targetNodeId,
        connection_type: 'normal',
        probability: 100,
        metadata: {},
    });
    render();
}

function renderConnections() {
    elements.svg.innerHTML = '';
    state.connections.forEach((connection, index) => {
        const source = state.nodes.find((node) => node.node_id === connection.source_node_id);
        const target = state.nodes.find((node) => node.node_id === connection.target_node_id);
        if (!source || !target) return;
        const x1 = source.x + 150;
        const y1 = source.y + 40;
        const x2 = target.x;
        const y2 = target.y + 40;
        const cp = Math.max(45, Math.abs(x2 - x1) * 0.45);
        const path = document.createElementNS('http://www.w3.org/2000/svg', 'path');
        path.setAttribute('d', `M${x1},${y1} C${x1 + cp},${y1} ${x2 - cp},${y2} ${x2},${y2}`);
        path.setAttribute('class', 'process-connection');
        path.addEventListener('click', () => {
            if (confirm('Delete this process connection?')) {
                pushHistory();
                state.connections.splice(index, 1);
                render();
            }
        });
        elements.svg.appendChild(path);
    });
}

function selectNode(nodeId) {
    state.selectedId = nodeId;
    const node = state.nodes.find((item) => item.node_id === nodeId);
    if (!node) return;
    elements.noSelection.classList.add('d-none');
    elements.form.classList.remove('d-none');
    document.getElementById('processNodeId').value = node.node_id;
    document.getElementById('processCode').value = node.operation_code;
    document.getElementById('processName').value = node.name;
    document.getElementById('processNodeType').value = node.node_type;
    document.getElementById('processCycle').value = node.cycle_time_minutes;
    document.getElementById('processSetup').value = node.setup_time_minutes;
    document.getElementById('processBatch').value = node.batch_size;
    document.getElementById('processScrap').value = node.scrap_rate;
    document.getElementById('processRework').value = node.rework_rate;
    document.getElementById('processMtbf').value = node.mtbf_hours;
    document.getElementById('processMttr').value = node.mttr_hours;
    document.getElementById('processColor').value = node.color;
    document.getElementById('processResourceLink').value = node.resource_id || '';
    document.getElementById('processRequiredQuantity').value = node.required_quantity || 1;
    document.getElementById('processLayoutLink').value = node.linked_layout_element_id || '';
    document.getElementById('processNotes').value = node.notes || '';
    renderNodes();
}

function applyProperties(event) {
    event.preventDefault();
    const node = state.nodes.find((item) => item.node_id === state.selectedId);
    if (!node) return;
    pushHistory();
    node.operation_code = document.getElementById('processCode').value.trim().toUpperCase();
    node.name = document.getElementById('processName').value.trim();
    node.node_type = document.getElementById('processNodeType').value;
    node.cycle_time_minutes = Number(document.getElementById('processCycle').value || 0);
    node.setup_time_minutes = Number(document.getElementById('processSetup').value || 0);
    node.batch_size = Number(document.getElementById('processBatch').value || 1);
    node.scrap_rate = Number(document.getElementById('processScrap').value || 0);
    node.rework_rate = Number(document.getElementById('processRework').value || 0);
    node.mtbf_hours = Number(document.getElementById('processMtbf').value || 0);
    node.mttr_hours = Number(document.getElementById('processMttr').value || 0);
    node.color = document.getElementById('processColor').value;
    const selectedResourceId = document.getElementById('processResourceLink').value || '';
    const selectedResource = state.resources.find((resource) => String(resource.id) === String(selectedResourceId));
    node.resource_id = selectedResource ? Number(selectedResource.id) : null;
    node.resource_name = selectedResource ? selectedResource.name : '';
    node.required_quantity = Number(document.getElementById('processRequiredQuantity').value || 1);
    node.linked_layout_element_id = document.getElementById('processLayoutLink').value || null;
    node.notes = document.getElementById('processNotes').value.trim();
    setStatus('Node updated', 'success');
    render();
}

function deleteSelectedNode() {
    if (!state.selectedId) return;
    pushHistory();
    state.nodes = state.nodes.filter((node) => node.node_id !== state.selectedId);
    state.connections = state.connections.filter((connection) => (
        connection.source_node_id !== state.selectedId && connection.target_node_id !== state.selectedId
    ));
    state.selectedId = null;
    elements.form.classList.add('d-none');
    elements.noSelection.classList.remove('d-none');
    render();
}

async function loadLayoutElements() {
    if (!state.project) return;
    try {
        const response = await api.get(`/api/layout/load.php?project_id=${state.project.id}`);
        state.layoutElements = response.data.elements || [];
        elements.layoutLink.innerHTML = '<option value="">Not linked</option>' + state.layoutElements.map((element) => (
            `<option value="${element.id}">${escapeHtml(element.name)}</option>`
        )).join('');
    } catch (error) {
        state.layoutElements = [];
    }
}

async function loadResources() {
    if (!state.project) return;
    try {
        const response = await api.get(`/api/resources/list.php?project_id=${state.project.id}`);
        state.resources = response.data.resources || [];
        elements.resourceLink.innerHTML = '<option value="">Not assigned</option>' + state.resources.map((resource) => (
            `<option value="${Number(resource.id)}">${escapeHtml(resource.name)} · ${escapeHtml(resource.resource_type)} · cap ${Number(resource.quantity)}</option>`
        )).join('');
    } catch (error) {
        state.resources = [];
        elements.resourceLink.innerHTML = '<option value="">Not assigned</option>';
    }
}


function resolveResourceAssignments() {
    state.nodes.forEach((node) => {
        if (!node.resource_id && node.resource_name) {
            const resource = state.resources.find((item) => item.name === node.resource_name);
            if (resource) {
                node.resource_id = Number(resource.id);
            }
        }
        node.required_quantity = Number(node.required_quantity || 1);
    });
}

async function loadProcess() {
    if (!ensureProject()) return;
    setStatus('Loading...', 'secondary');
    await Promise.all([loadLayoutElements(), loadResources()]);
    const response = await api.get(`/api/process/load.php?project_id=${state.project.id}`);
    state.nodes = response.data.operations || [];
    state.connections = response.data.connections || [];
    resolveResourceAssignments();
    history.clear();
    state.selectedId = null;
    elements.form.classList.add('d-none');
    elements.noSelection.classList.remove('d-none');
    setStatus('Loaded', 'success');
    render();
}

async function saveProcess() {
    if (!ensureProject()) return;
    setStatus('Saving...', 'secondary');
    const response = await api.post('/api/process/save.php', buildPayload());
    state.nodes = response.data.operations || state.nodes;
    state.connections = response.data.connections || state.connections;
    history.clear();
    renderValidation(response.data.validation || { valid: true, errors: [], warnings: [] });
    setStatus('Saved', 'success');
    render();
}

async function validateProcess() {
    if (!ensureProject()) return;
    setStatus('Validating...', 'secondary');
    const response = await api.post('/api/process/validate.php', buildPayload());
    renderValidation(response.data);
    setStatus(response.data.valid ? 'Valid' : 'Invalid', response.data.valid ? 'success' : 'danger');
}

function buildPayload() {
    return {
        project_id: state.project.id,
        operations: state.nodes,
        connections: state.connections,
        version: 1,
    };
}

function renderValidation(result) {
    const errors = result.errors || [];
    const warnings = result.warnings || [];
    if (errors.length === 0 && warnings.length === 0) {
        elements.validation.innerHTML = '<div class="text-success">The process model is valid.</div>';
        return;
    }
    elements.validation.innerHTML = [
        ...errors.map((error) => `<div class="text-danger mb-2">${escapeHtml(error.message)}</div>`),
        ...warnings.map((warning) => `<div class="text-warning mb-2">${escapeHtml(warning.message)}</div>`),
    ].join('');
}

function currentProcessSnapshot() {
    return {
        nodes: JSON.parse(JSON.stringify(state.nodes)),
        connections: JSON.parse(JSON.stringify(state.connections)),
        selectedId: state.selectedId,
        sequence: state.sequence,
    };
}

function restoreProcessSnapshot(snapshot) {
    state.nodes = JSON.parse(JSON.stringify(snapshot.nodes || []));
    state.connections = JSON.parse(JSON.stringify(snapshot.connections || []));
    state.selectedId = snapshot.selectedId || null;
    state.sequence = snapshot.sequence || state.sequence;
    render();
    if (state.selectedId) {
        selectNode(state.selectedId);
    } else {
        elements.form.classList.add('d-none');
        elements.noSelection.classList.remove('d-none');
    }
}

function pushHistory() {
    history.push(currentProcessSnapshot());
}

function undoProcess() {
    const snapshot = history.undo(currentProcessSnapshot());
    if (!snapshot) return;
    restoreProcessSnapshot(snapshot);
    setStatus('Undo applied', 'info');
}

function redoProcess() {
    const snapshot = history.redo(currentProcessSnapshot());
    if (!snapshot) return;
    restoreProcessSnapshot(snapshot);
    setStatus('Redo applied', 'info');
}

function autoLayout() {
    pushHistory();
    const columns = ['start', 'operation', 'inspection', 'transport', 'buffer', 'decision', 'delay', 'end'];
    const grouped = new Map();
    state.nodes.forEach((node) => {
        const index = Math.max(0, columns.indexOf(node.node_type));
        if (!grouped.has(index)) grouped.set(index, []);
        grouped.get(index).push(node);
    });
    [...grouped.entries()].sort((a, b) => a[0] - b[0]).forEach(([column, nodes]) => {
        nodes.forEach((node, row) => {
            node.x = 60 + column * 190;
            node.y = 80 + row * 130;
        });
    });
    render();
}

function clearProcess() {
    if (!confirm('Clear all process nodes and connections?')) return;
    pushHistory();
    state.nodes = [];
    state.connections = [];
    state.selectedId = null;
    state.connectionStartId = null;
    render();
}

function updateStats() {
    elements.countNodes.textContent = state.nodes.length;
    elements.countConnections.textContent = state.connections.length;
    elements.cycleTime.textContent = state.nodes.reduce((sum, node) => sum + Number(node.cycle_time_minutes || 0), 0).toFixed(1);
}

function ensureProject() {
    if (state.project) return true;
    setStatus('Select a project first', 'danger');
    return false;
}

function escapeHtml(value) {
    return String(value ?? '').replace(/[&<>'"]/g, (char) => ({
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        "'": '&#039;',
        '"': '&quot;',
    }[char]));
}

function bindEvents() {
    elements.wrap?.addEventListener('dragover', (event) => event.preventDefault());
    elements.wrap?.addEventListener('drop', (event) => {
        event.preventDefault();
        const type = event.dataTransfer.getData('openmep-node-type');
        if (!type) return;
        const rect = elements.wrap.getBoundingClientRect();
        createNode(type, event.clientX - rect.left - 75, event.clientY - rect.top - 40);
    });
    elements.form?.addEventListener('submit', applyProperties);
    document.getElementById('processDeleteBtn')?.addEventListener('click', deleteSelectedNode);
    document.getElementById('processSaveBtn')?.addEventListener('click', () => saveProcess().catch(handleError));
    document.getElementById('processLoadBtn')?.addEventListener('click', () => loadProcess().catch(handleError));
    document.getElementById('processValidateBtn')?.addEventListener('click', () => validateProcess().catch(handleError));
    document.getElementById('processUndoBtn')?.addEventListener('click', undoProcess);
    document.getElementById('processRedoBtn')?.addEventListener('click', redoProcess);
    document.getElementById('processAutoLayoutBtn')?.addEventListener('click', autoLayout);
    document.getElementById('processClearBtn')?.addEventListener('click', clearProcess);
    window.addEventListener('openmep:project-selected', (event) => {
        state.project = event.detail;
        if (!document.getElementById('processView')?.classList.contains('d-none')) {
            loadProcess().catch(handleError);
        }
    });
    window.addEventListener('openmep:route-changed', (event) => {
        if (event.detail?.route === 'process' && state.project) {
            loadProcess().catch(handleError);
        }
    });
}

function handleError(error) {
    console.error(error);
    setStatus(error.payload?.message || error.message || 'Error', 'danger');
    const details = error.payload?.errors?.map((item) => item.message).join('<br>');
    if (details) elements.validation.innerHTML = `<div class="text-danger">${details}</div>`;
}

renderLibrary();
bindEvents();
render();
