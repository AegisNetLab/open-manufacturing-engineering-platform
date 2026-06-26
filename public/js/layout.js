import { ApiClient } from './api.js';
import { HistoryManager } from './components/historyManager.js';

const PX = 48;
const api = new ApiClient();
const state = {
    stage: null,
    layer: null,
    transformer: null,
    selected: null,
    initialized: false,
    dirty: false,
};

const history = new HistoryManager({
    onChange: ({ canUndo, canRedo }) => {
        if (elements.undoButton) elements.undoButton.disabled = !canUndo;
        if (elements.redoButton) elements.redoButton.disabled = !canRedo;
    },
});

const libraryItems = [
    { type: 'cnc_machine', name: 'CNC Machine', w: 3, h: 2, color: '#1565C0' },
    { type: 'lathe', name: 'Lathe', w: 2.5, h: 1.5, color: '#283593' },
    { type: 'laser_cutter', name: 'Laser Cutter', w: 3, h: 2, color: '#AD1457' },
    { type: 'press', name: 'Press', w: 2.5, h: 2, color: '#4A148C' },
    { type: 'assembly_station', name: 'Assembly Station', w: 4, h: 2.5, color: '#1B5E20' },
    { type: 'inspection_station', name: 'Inspection Station', w: 2, h: 1.5, color: '#006064' },
    { type: 'warehouse', name: 'Warehouse', w: 8, h: 5, color: '#33691E' },
    { type: 'wip_buffer', name: 'WIP Buffer', w: 3, h: 2, color: '#F57F17' },
    { type: 'conveyor', name: 'Conveyor', w: 7, h: 1, color: '#37474F' },
    { type: 'agv_path', name: 'AGV Path', w: 9, h: 1.5, color: '#1A237E' },
    { type: 'office', name: 'Office', w: 6, h: 4, color: '#455A64' },
];

const elements = {
    library: document.getElementById('layoutLibrary'),
    wrap: document.getElementById('layoutCanvasWrap'),
    canvas: document.getElementById('layoutCanvas'),
    status: document.getElementById('layoutStatus'),
    saveButton: document.getElementById('layoutSaveBtn'),
    loadButton: document.getElementById('layoutLoadBtn'),
    fitButton: document.getElementById('layoutFitBtn'),
    exportButton: document.getElementById('layoutExportBtn'),
    clearButton: document.getElementById('layoutClearBtn'),
    undoButton: document.getElementById('layoutUndoBtn'),
    redoButton: document.getElementById('layoutRedoBtn'),
    hint: document.getElementById('layoutCanvasHint'),
    count: document.getElementById('layoutCount'),
    x: document.getElementById('layoutX'),
    y: document.getElementById('layoutY'),
    zoom: document.getElementById('layoutZoom'),
    noSelection: document.getElementById('layoutNoSelection'),
    properties: document.getElementById('layoutProperties'),
    name: document.getElementById('layoutElementName'),
    positionX: document.getElementById('layoutElementX'),
    positionY: document.getElementById('layoutElementY'),
    width: document.getElementById('layoutElementW'),
    height: document.getElementById('layoutElementH'),
    rotation: document.getElementById('layoutElementR'),
    color: document.getElementById('layoutElementColor'),
    deleteButton: document.getElementById('layoutDeleteBtn'),
};

function activeProjectId() {
    return Number(localStorage.getItem('openmep.activeProjectId') || 0);
}

function setStatus(label, type = 'secondary') {
    elements.status.className = `badge text-bg-${type} ms-auto`;
    elements.status.textContent = label;
}

function renderLibrary() {
    elements.library.innerHTML = libraryItems.map((item) => `
        <div class="library-item" draggable="true" data-type="${item.type}">
            <div class="library-icon" style="background:${item.color}">${item.name.slice(0, 3).toUpperCase()}</div>
            <div>
                <div class="fw-semibold">${item.name}</div>
                <div class="small text-secondary">${item.w} × ${item.h} m</div>
            </div>
        </div>
    `).join('');
}

function initStage() {
    if (state.initialized || !elements.wrap) {
        return;
    }

    state.stage = new Konva.Stage({
        container: 'layoutCanvas',
        width: elements.wrap.clientWidth,
        height: elements.wrap.clientHeight,
    });
    state.layer = new Konva.Layer();
    state.stage.add(state.layer);

    state.transformer = new Konva.Transformer({
        rotateEnabled: false,
        borderStroke: '#00d4ff',
        anchorFill: '#00d4ff',
        anchorSize: 8,
        enabledAnchors: ['top-left', 'top-right', 'bottom-left', 'bottom-right'],
    });
    state.layer.add(state.transformer);

    state.stage.on('click tap', (event) => {
        if (event.target === state.stage) {
            selectElement(null);
        }
    });

    state.stage.on('mousemove', () => {
        const pointer = state.stage.getPointerPosition();
        if (!pointer) {
            return;
        }
        const scale = state.stage.scaleX();
        elements.x.textContent = ((pointer.x - state.stage.x()) / scale / PX).toFixed(1);
        elements.y.textContent = ((pointer.y - state.stage.y()) / scale / PX).toFixed(1);
    });

    state.stage.on('wheel', (event) => {
        event.evt.preventDefault();
        const oldScale = state.stage.scaleX();
        const pointer = state.stage.getPointerPosition();
        const nextScale = Math.max(0.25, Math.min(3, oldScale * (event.evt.deltaY < 0 ? 1.1 : 0.9)));
        const mousePointTo = {
            x: (pointer.x - state.stage.x()) / oldScale,
            y: (pointer.y - state.stage.y()) / oldScale,
        };
        state.stage.scale({ x: nextScale, y: nextScale });
        state.stage.position({
            x: pointer.x - mousePointTo.x * nextScale,
            y: pointer.y - mousePointTo.y * nextScale,
        });
        updateZoomLabel();
    });

    window.addEventListener('resize', resizeStage);
    elements.wrap.addEventListener('dragover', (event) => event.preventDefault());
    elements.wrap.addEventListener('drop', handleDrop);

    state.initialized = true;
    resizeStage();
}

function resizeStage() {
    if (!state.stage) {
        return;
    }
    state.stage.width(elements.wrap.clientWidth);
    state.stage.height(elements.wrap.clientHeight);
}

function handleDrop(event) {
    event.preventDefault();
    const type = event.dataTransfer.getData('layout-type');
    const definition = libraryItems.find((item) => item.type === type);
    if (!definition) {
        return;
    }

    const rect = elements.wrap.getBoundingClientRect();
    const scale = state.stage.scaleX();
    const x = (event.clientX - rect.left - state.stage.x()) / scale / PX;
    const y = (event.clientY - rect.top - state.stage.y()) / scale / PX;
    pushHistory();
    addElement({ ...definition, x_position: Math.max(0, x - definition.w / 2), y_position: Math.max(0, y - definition.h / 2) });
    markDirty();
}

function addElement(data) {
    const group = new Konva.Group({
        x: Number(data.x_position || 0) * PX,
        y: Number(data.y_position || 0) * PX,
        draggable: true,
        rotation: Number(data.rotation || 0),
    });

    group.openmep = {
        id: data.id || null,
        name: data.name || data.type || data.element_type,
        element_type: data.element_type || data.type,
        width: Number(data.width || data.w || 1),
        height: Number(data.height || data.h || 1),
        color: data.color || '#1565C0',
        metadata: data.metadata || {},
    };

    const rect = new Konva.Rect({
        width: group.openmep.width * PX,
        height: group.openmep.height * PX,
        fill: `${group.openmep.color}dd`,
        stroke: group.openmep.color,
        strokeWidth: 2,
        cornerRadius: 6,
    });

    const text = new Konva.Text({
        text: group.openmep.name,
        width: group.openmep.width * PX,
        height: group.openmep.height * PX,
        align: 'center',
        verticalAlign: 'middle',
        fill: '#ffffff',
        fontSize: 13,
        fontStyle: '600',
    });

    group.add(rect);
    group.add(text);
    state.layer.add(group);

    group.on('click tap', () => selectElement(group));
    group.on('dragstart transformstart', () => {
        pushHistory();
    });

    group.on('dragend transformend', () => {
        normalizeTransformedElement(group);
        syncProperties(group);
        markDirty();
    });

    selectElement(group);
    updateCount();
    state.layer.batchDraw();
}

function normalizeTransformedElement(group) {
    const rect = group.findOne('Rect');
    const text = group.findOne('Text');
    const scaleX = group.scaleX();
    const scaleY = group.scaleY();

    if (scaleX !== 1 || scaleY !== 1) {
        group.openmep.width = Math.max(0.1, Math.round((group.openmep.width * scaleX) * 10) / 10);
        group.openmep.height = Math.max(0.1, Math.round((group.openmep.height * scaleY) * 10) / 10);
        group.scale({ x: 1, y: 1 });
        rect.width(group.openmep.width * PX);
        rect.height(group.openmep.height * PX);
        text.width(group.openmep.width * PX);
        text.height(group.openmep.height * PX);
    }

    group.x(Math.max(0, group.x()));
    group.y(Math.max(0, group.y()));
}

function selectElement(group) {
    state.selected = group;
    state.transformer.nodes(group ? [group] : []);
    elements.noSelection.classList.toggle('d-none', Boolean(group));
    elements.properties.classList.toggle('d-none', !group);
    if (group) {
        syncProperties(group);
    }
    state.layer?.batchDraw();
}

function syncProperties(group) {
    elements.name.value = group.openmep.name;
    elements.positionX.value = (group.x() / PX).toFixed(1);
    elements.positionY.value = (group.y() / PX).toFixed(1);
    elements.width.value = group.openmep.width;
    elements.height.value = group.openmep.height;
    elements.rotation.value = Math.round(group.rotation() % 360);
    elements.color.value = group.openmep.color;
}

function applyProperties(event) {
    event.preventDefault();
    const group = state.selected;
    if (!group) {
        return;
    }

    pushHistory();
    group.openmep.name = elements.name.value.trim() || group.openmep.name;
    group.openmep.width = Math.max(0.1, Number(elements.width.value || group.openmep.width));
    group.openmep.height = Math.max(0.1, Number(elements.height.value || group.openmep.height));
    group.openmep.color = elements.color.value || group.openmep.color;
    group.x(Math.max(0, Number(elements.positionX.value || 0) * PX));
    group.y(Math.max(0, Number(elements.positionY.value || 0) * PX));
    group.rotation(Math.max(0, Math.min(359, Number(elements.rotation.value || 0))));

    const rect = group.findOne('Rect');
    const text = group.findOne('Text');
    rect.width(group.openmep.width * PX);
    rect.height(group.openmep.height * PX);
    rect.fill(`${group.openmep.color}dd`);
    rect.stroke(group.openmep.color);
    text.text(group.openmep.name);
    text.width(group.openmep.width * PX);
    text.height(group.openmep.height * PX);

    markDirty();
    syncProperties(group);
    state.layer.batchDraw();
}

function deleteSelected() {
    if (!state.selected || !confirm(`Delete object "${state.selected.openmep.name}"?`)) {
        return;
    }
    pushHistory();
    state.selected.destroy();
    selectElement(null);
    updateCount();
    markDirty();
}

function serializeLayout() {
    return state.layer.getChildren((node) => node.getClassName() === 'Group').map((group) => ({
        id: group.openmep.id,
        name: group.openmep.name,
        element_type: group.openmep.element_type,
        x_position: Number((group.x() / PX).toFixed(2)),
        y_position: Number((group.y() / PX).toFixed(2)),
        width: Number(group.openmep.width),
        height: Number(group.openmep.height),
        rotation: Math.round(group.rotation() % 360),
        color: group.openmep.color,
        metadata: group.openmep.metadata || {},
    }));
}

function currentLayoutSnapshot() {
    return serializeLayout();
}

function restoreLayoutSnapshot(snapshot) {
    clearCanvas(false);
    (snapshot || []).forEach((element) => addElement(element));
    selectElement(null);
    markDirty();
}

function pushHistory() {
    history.push(currentLayoutSnapshot());
}

function undoLayout() {
    const snapshot = history.undo(currentLayoutSnapshot());
    if (!snapshot) return;
    restoreLayoutSnapshot(snapshot);
    setStatus('Undo applied', 'info');
}

function redoLayout() {
    const snapshot = history.redo(currentLayoutSnapshot());
    if (!snapshot) return;
    restoreLayoutSnapshot(snapshot);
    setStatus('Redo applied', 'info');
}

async function loadLayout() {
    const projectId = activeProjectId();
    if (projectId < 1) {
        setStatus('Select a project first', 'warning');
        return;
    }

    initStage();
    setStatus('Loading', 'info');
    const response = await api.get(`/api/layout/load.php?project_id=${projectId}`);
    clearCanvas(false);
    response.data.elements.forEach((element) => addElement(element));
    selectElement(null);
    state.dirty = false;
    history.clear();
    updateCount();
    setStatus('Loaded', 'success');
}

async function saveLayout() {
    const projectId = activeProjectId();
    if (projectId < 1) {
        setStatus('Select a project first', 'warning');
        return;
    }

    setStatus('Saving', 'warning');
    const response = await api.post('/api/layout/save.php', {
        project_id: projectId,
        elements: serializeLayout(),
    });

    clearCanvas(false);
    response.data.elements.forEach((element) => addElement(element));
    selectElement(null);
    state.dirty = false;
    history.clear();
    setStatus('Saved', 'success');
}

function clearCanvas(confirmClear = true) {
    if (confirmClear && !confirm('Clear all layout objects?')) {
        return;
    }
    if (confirmClear) {
        pushHistory();
    }
    state.layer.getChildren((node) => node.getClassName() === 'Group').forEach((group) => group.destroy());
    selectElement(null);
    updateCount();
    if (confirmClear) {
        markDirty();
    }
}

function fitLayout() {
    const groups = state.layer.getChildren((node) => node.getClassName() === 'Group');
    if (groups.length === 0) {
        return;
    }

    const box = groups.reduce((acc, group) => {
        const rect = group.getClientRect();
        return {
            minX: Math.min(acc.minX, rect.x),
            minY: Math.min(acc.minY, rect.y),
            maxX: Math.max(acc.maxX, rect.x + rect.width),
            maxY: Math.max(acc.maxY, rect.y + rect.height),
        };
    }, { minX: Infinity, minY: Infinity, maxX: -Infinity, maxY: -Infinity });

    const padding = 80;
    const scale = Math.min(
        2,
        (state.stage.width() - padding * 2) / Math.max(1, box.maxX - box.minX),
        (state.stage.height() - padding * 2) / Math.max(1, box.maxY - box.minY),
    );
    state.stage.scale({ x: scale, y: scale });
    state.stage.position({
        x: (state.stage.width() - (box.maxX - box.minX) * scale) / 2 - box.minX * scale,
        y: (state.stage.height() - (box.maxY - box.minY) * scale) / 2 - box.minY * scale,
    });
    updateZoomLabel();
}

function exportPng() {
    const anchor = document.createElement('a');
    anchor.href = state.stage.toDataURL({ pixelRatio: 2 });
    anchor.download = 'openmep-layout.png';
    anchor.click();
}

function updateCount() {
    const count = state.layer?.getChildren((node) => node.getClassName() === 'Group').length || 0;
    elements.count.textContent = String(count);
    elements.hint.style.display = count === 0 ? 'flex' : 'none';
}

function updateZoomLabel() {
    elements.zoom.textContent = String(Math.round(state.stage.scaleX() * 100));
}

function markDirty() {
    state.dirty = true;
    setStatus('Unsaved changes', 'warning');
    updateCount();
}

renderLibrary();
elements.library.addEventListener('dragstart', (event) => {
    const item = event.target.closest('[data-type]');
    if (item) {
        event.dataTransfer.setData('layout-type', item.dataset.type);
    }
});

elements.properties.addEventListener('submit', applyProperties);
elements.deleteButton.addEventListener('click', deleteSelected);
elements.saveButton.addEventListener('click', () => saveLayout().catch((error) => setStatus(error.message, 'danger')));
elements.loadButton.addEventListener('click', () => loadLayout().catch((error) => setStatus(error.message, 'danger')));
elements.clearButton.addEventListener('click', () => clearCanvas(true));
elements.undoButton?.addEventListener('click', undoLayout);
elements.redoButton?.addEventListener('click', redoLayout);
elements.fitButton.addEventListener('click', fitLayout);
elements.exportButton.addEventListener('click', exportPng);

window.addEventListener('openmep:route-changed', (event) => {
    if (event.detail?.route === 'layout') {
        initStage();
        resizeStage();
        if (activeProjectId() > 0 && !state.dirty) {
            loadLayout().catch((error) => setStatus(error.message, 'danger'));
        }
    }
});
