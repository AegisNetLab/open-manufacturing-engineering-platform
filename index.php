<?php

declare(strict_types=1);

$config = require __DIR__ . '/config/config.php';
$appName = htmlspecialchars($config['app']['name'], ENT_QUOTES, 'UTF-8');
?>
<!doctype html>
<html lang="en" data-bs-theme="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= $appName ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="/public/css/app.css" rel="stylesheet">
</head>
<body>
<a class="skip-link" href="#mainContent">Skip to main content</a>
<nav class="navbar navbar-expand-lg border-bottom app-navbar" role="navigation" aria-label="Main navigation">
    <div class="container-fluid">
        <a class="navbar-brand fw-semibold" href="#projects">OpenMEP</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNav" aria-controls="mainNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="mainNav">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                <li class="nav-item"><a class="nav-link active" data-route="dashboard" href="#dashboard">Dashboard</a></li>
                <li class="nav-item"><a class="nav-link" data-route="projects" href="#projects">Projects</a></li>
                <li class="nav-item"><a class="nav-link" data-route="layout" href="#layout">Layout</a></li>
                <li class="nav-item"><a class="nav-link" data-route="resources" href="#resources">Resources</a></li>
                <li class="nav-item"><a class="nav-link" data-route="process" href="#process">Process</a></li>
                <li class="nav-item"><a class="nav-link" data-route="simulation" href="#simulation">Simulation</a></li>
                <li class="nav-item"><a class="nav-link" data-route="results" href="#results">Results</a></li>
                <li class="nav-item"><a class="nav-link" data-route="settings" href="#settings">Settings</a></li>
            </ul>
            <span class="navbar-text small text-secondary" id="activeProjectLabel">No active project</span>
        </div>
    </div>
</nav>

<main class="container-fluid py-4 app-main" id="mainContent" tabindex="-1">

    <section id="dashboardView" class="app-view" aria-labelledby="dashboardHeading">
        <div class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-3 mb-4">
            <div>
                <h1 class="h3 mb-1" id="dashboardHeading">Dashboard</h1>
                <p class="text-secondary mb-0">Project overview, simulation status and quick actions.</p>
            </div>
            <div class="btn-group">
                <button type="button" class="btn btn-primary" id="dashboardCreateProjectBtn">Create Project</button>
                <button type="button" class="btn btn-outline-secondary" id="dashboardRefreshBtn">Refresh</button>
            </div>
        </div>

        <div class="row g-3 mb-4" id="dashboardMetricCards" aria-label="Application metrics">
            <div class="col-6 col-xl-2"><div class="card app-card h-100"><div class="card-body"><div class="text-secondary small">Projects</div><div class="h3 mb-0" data-dashboard-metric="projects">–</div></div></div></div>
            <div class="col-6 col-xl-2"><div class="card app-card h-100"><div class="card-body"><div class="text-secondary small">Layout Objects</div><div class="h3 mb-0" data-dashboard-metric="layout_elements">–</div></div></div></div>
            <div class="col-6 col-xl-2"><div class="card app-card h-100"><div class="card-body"><div class="text-secondary small">Operations</div><div class="h3 mb-0" data-dashboard-metric="operations">–</div></div></div></div>
            <div class="col-6 col-xl-2"><div class="card app-card h-100"><div class="card-body"><div class="text-secondary small">Resources</div><div class="h3 mb-0" data-dashboard-metric="resources">–</div></div></div></div>
            <div class="col-6 col-xl-2"><div class="card app-card h-100"><div class="card-body"><div class="text-secondary small">Simulation Runs</div><div class="h3 mb-0" data-dashboard-metric="simulation_runs">–</div></div></div></div>
            <div class="col-6 col-xl-2"><div class="card app-card h-100"><div class="card-body"><div class="text-secondary small">Results</div><div class="h3 mb-0" data-dashboard-metric="simulation_results">–</div></div></div></div>
        </div>

        <div class="row g-4">
            <div class="col-12 col-xl-4">
                <div class="card app-card h-100">
                    <div class="card-header">Recent Projects</div>
                    <div class="list-group list-group-flush" id="dashboardRecentProjects">
                        <div class="list-group-item text-secondary">Loading...</div>
                    </div>
                </div>
            </div>
            <div class="col-12 col-xl-4">
                <div class="card app-card h-100">
                    <div class="card-header">Simulation Results</div>
                    <div class="table-responsive">
                        <table class="table table-sm align-middle mb-0">
                            <thead><tr><th>Project</th><th>TH</th><th>OEE</th></tr></thead>
                            <tbody id="dashboardLatestResults"><tr><td colspan="3" class="text-secondary">Loading...</td></tr></tbody>
                        </table>
                    </div>
                </div>
            </div>
            <div class="col-12 col-xl-4">
                <div class="card app-card h-100">
                    <div class="card-header">Simulation Readiness</div>
                    <div class="list-group list-group-flush" id="dashboardReadiness">
                        <div class="list-group-item text-secondary">Loading...</div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section id="projectsView" class="app-view d-none" aria-labelledby="projectsHeading">
        <h1 class="visually-hidden" id="projectsHeading">Project Manager</h1>
        <div class="row g-4">
            <div class="col-12 col-xl-4">
                <div class="card h-100 app-card">
                    <div class="card-header d-flex align-items-center justify-content-between">
                        <span>Project Manager</span>
                        <span id="projectStatus" class="badge text-bg-secondary">Idle</span>
                    </div>
                    <div class="card-body">
                        <form id="projectForm" novalidate>
                            <input type="hidden" id="projectId">
                            <div class="mb-3">
                                <label for="projectName" class="form-label">Project name</label>
                                <input type="text" class="form-control" id="projectName" required maxlength="150">
                                <div class="invalid-feedback" data-field-error="name"></div>
                            </div>
                            <div class="mb-3">
                                <label for="projectDescription" class="form-label">Description</label>
                                <textarea class="form-control" id="projectDescription" rows="3"></textarea>
                            </div>
                            <div class="mb-3">
                                <label for="productionType" class="form-label">Production type</label>
                                <select class="form-select" id="productionType">
                                    <option value="serial">Serial</option>
                                    <option value="job_shop">Job Shop</option>
                                    <option value="mixed">Mixed</option>
                                </select>
                                <div class="invalid-feedback" data-field-error="production_type"></div>
                            </div>
                            <div class="mb-3">
                                <label for="shiftLength" class="form-label">Shift length (minutes)</label>
                                <input type="number" class="form-control" id="shiftLength" min="1" max="1440" value="480">
                                <div class="invalid-feedback" data-field-error="shift_length_minutes"></div>
                            </div>
                            <div class="d-flex gap-2">
                                <button type="submit" class="btn btn-primary" id="saveProjectBtn">Create Project</button>
                                <button type="button" class="btn btn-outline-secondary" id="resetProjectFormBtn">Reset</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-12 col-xl-8">
                <div class="card h-100 app-card">
                    <div class="card-header d-flex align-items-center justify-content-between">
                        <span>Projects</span>
                        <div class="btn-group btn-group-sm">
                            <button type="button" class="btn btn-outline-info" id="refreshProjectsBtn">Refresh</button>
                            <button type="button" class="btn btn-outline-secondary" id="importProjectBtn">Import JSON</button>
                        </div>
                    </div>
                    <div class="card-body">
                        <div id="apiAlert" class="alert d-none" role="alert"></div>
                        <input type="file" id="projectImportFile" class="d-none" accept="application/json,.json,.openmep.json">
                        <div class="row g-2 align-items-end mb-3" aria-label="Project filters">
                            <div class="col-12 col-md-5">
                                <label class="form-label small" for="projectSearchInput">Search</label>
                                <input type="search" class="form-control form-control-sm" id="projectSearchInput" placeholder="Search name or description">
                            </div>
                            <div class="col-6 col-md-3">
                                <label class="form-label small" for="projectTypeFilter">Type</label>
                                <select class="form-select form-select-sm" id="projectTypeFilter">
                                    <option value="">All types</option>
                                    <option value="serial">Serial</option>
                                    <option value="job_shop">Job Shop</option>
                                    <option value="mixed">Mixed</option>
                                </select>
                            </div>
                            <div class="col-6 col-md-2">
                                <label class="form-label small" for="projectSortSelect">Sort</label>
                                <select class="form-select form-select-sm" id="projectSortSelect">
                                    <option value="updated_at">Updated</option>
                                    <option value="created_at">Created</option>
                                    <option value="name">Name</option>
                                    <option value="production_type">Type</option>
                                    <option value="shift_length_minutes">Shift</option>
                                </select>
                            </div>
                            <div class="col-6 col-md-2">
                                <label class="form-label small" for="projectDirectionSelect">Direction</label>
                                <select class="form-select form-select-sm" id="projectDirectionSelect">
                                    <option value="DESC">Descending</option>
                                    <option value="ASC">Ascending</option>
                                </select>
                            </div>
                            <div class="col-6 col-md-2">
                                <label class="form-label small" for="projectPerPageSelect">Rows</label>
                                <select class="form-select form-select-sm" id="projectPerPageSelect">
                                    <option value="10">10</option>
                                    <option value="25">25</option>
                                    <option value="50">50</option>
                                    <option value="100">100</option>
                                </select>
                            </div>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle">
                                <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Production Type</th>
                                    <th>Shift</th>
                                    <th>Updated</th>
                                    <th class="text-end">Actions</th>
                                </tr>
                                </thead>
                                <tbody id="projectTableBody">
                                <tr><td colspan="5" class="text-secondary">Loading projects...</td></tr>
                                </tbody>
                            </table>
                        </div>
                        <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-2 mt-3">
                            <div class="small text-secondary" id="projectPaginationSummary">No pagination data.</div>
                            <nav aria-label="Project pagination">
                                <ul class="pagination pagination-sm mb-0" id="projectPagination"></ul>
                            </nav>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section id="layoutView" class="app-view d-none" aria-labelledby="layoutHeading">
        <h1 class="visually-hidden" id="layoutHeading">Layout Designer</h1>
        <div class="engineering-shell">
            <aside class="engineering-panel layout-library">
                <div class="panel-title">Component Library</div>
                <div id="layoutLibrary"></div>
            </aside>

            <section class="engineering-workspace">
                <div class="engineering-toolbar border-bottom">
                    <div class="btn-group btn-group-sm">
                        <button type="button" class="btn btn-outline-info" id="layoutSaveBtn">Save Layout</button>
                        <button type="button" class="btn btn-outline-secondary" id="layoutLoadBtn">Reload</button>
                        <button type="button" class="btn btn-outline-secondary" id="layoutUndoBtn" disabled>Undo</button>
                        <button type="button" class="btn btn-outline-secondary" id="layoutRedoBtn" disabled>Redo</button>
                        <button type="button" class="btn btn-outline-secondary" id="layoutFitBtn">Fit</button>
                        <button type="button" class="btn btn-outline-secondary" id="layoutExportBtn">Export PNG</button>
                        <button type="button" class="btn btn-outline-danger" id="layoutClearBtn">Clear</button>
                    </div>
                    <span class="badge text-bg-secondary ms-auto" id="layoutStatus">Idle</span>
                </div>
                <div id="layoutCanvasWrap" class="layout-canvas-wrap">
                    <div id="layoutCanvas"></div>
                    <div class="canvas-hint" id="layoutCanvasHint">Drag components from the library to the factory canvas.</div>
                    <div class="layout-statusbar">
                        X: <span id="layoutX">0.0</span> m · Y: <span id="layoutY">0.0</span> m · Zoom: <span id="layoutZoom">100</span>% · Objects: <span id="layoutCount">0</span>
                    </div>
                </div>
            </section>

            <aside class="engineering-panel">
                <div class="panel-title">Properties</div>
                <div id="layoutNoSelection" class="empty-state">Select an object to edit its properties.</div>
                <form id="layoutProperties" class="d-none p-3" novalidate>
                    <div class="mb-3">
                        <label class="form-label" for="layoutElementName">Name</label>
                        <input class="form-control form-control-sm" id="layoutElementName" required>
                    </div>
                    <div class="row g-2">
                        <div class="col-6"><label class="form-label" for="layoutElementX">X</label><input class="form-control form-control-sm" id="layoutElementX" type="number" step="0.1" min="0"></div>
                        <div class="col-6"><label class="form-label" for="layoutElementY">Y</label><input class="form-control form-control-sm" id="layoutElementY" type="number" step="0.1" min="0"></div>
                        <div class="col-6"><label class="form-label" for="layoutElementW">Width</label><input class="form-control form-control-sm" id="layoutElementW" type="number" step="0.1" min="0.1"></div>
                        <div class="col-6"><label class="form-label" for="layoutElementH">Height</label><input class="form-control form-control-sm" id="layoutElementH" type="number" step="0.1" min="0.1"></div>
                        <div class="col-6"><label class="form-label" for="layoutElementR">Rotation</label><input class="form-control form-control-sm" id="layoutElementR" type="number" min="0" max="359"></div>
                        <div class="col-6"><label class="form-label" for="layoutElementColor">Color</label><input class="form-control form-control-color form-control-sm w-100" id="layoutElementColor" type="color"></div>
                    </div>
                    <div class="d-grid gap-2 mt-3">
                        <button type="submit" class="btn btn-sm btn-primary">Apply</button>
                        <button type="button" class="btn btn-sm btn-outline-danger" id="layoutDeleteBtn">Delete Object</button>
                    </div>
                </form>
            </aside>
        </div>
    </section>


    <section id="resourcesView" class="app-view d-none" aria-labelledby="resourcesHeading">
        <h1 class="visually-hidden" id="resourcesHeading">Resource Manager</h1>
        <div class="row g-4">
            <div class="col-12 col-xl-4">
                <div class="card h-100 app-card">
                    <div class="card-header d-flex align-items-center justify-content-between">
                        <span>Resource Manager</span>
                        <span id="resourceStatus" class="badge text-bg-secondary">Idle</span>
                    </div>
                    <div class="card-body">
                        <form id="resourceForm" novalidate>
                            <input type="hidden" id="resourceId">
                            <div class="mb-3">
                                <label class="form-label" for="resourceName">Resource name</label>
                                <input class="form-control" id="resourceName" maxlength="100" required>
                                <div class="invalid-feedback" data-resource-error="name"></div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label" for="resourceType">Resource type</label>
                                <select class="form-select" id="resourceType">
                                    <option value="machine">Machine</option>
                                    <option value="operator">Operator</option>
                                    <option value="tool">Tool</option>
                                    <option value="buffer">Buffer</option>
                                    <option value="transport">Transport Device</option>
                                </select>
                                <div class="invalid-feedback" data-resource-error="resource_type"></div>
                            </div>
                            <div class="row g-3">
                                <div class="col-6">
                                    <label class="form-label" for="resourceQuantity">Quantity</label>
                                    <input class="form-control" id="resourceQuantity" type="number" min="1" value="1">
                                    <div class="invalid-feedback" data-resource-error="quantity"></div>
                                </div>
                                <div class="col-6">
                                    <label class="form-label" for="resourceAvailability">Availability %</label>
                                    <input class="form-control" id="resourceAvailability" type="number" min="0" max="100" step="0.01" value="100">
                                </div>
                            </div>
                            <div class="row g-3 mt-0">
                                <div class="col-6">
                                    <label class="form-label" for="resourceHourlyRate">Hourly rate</label>
                                    <input class="form-control" id="resourceHourlyRate" type="number" min="0" step="0.01">
                                </div>
                                <div class="col-6">
                                    <label class="form-label" for="resourceLayoutLink">Layout element</label>
                                    <select class="form-select" id="resourceLayoutLink"><option value="">Not linked</option></select>
                                </div>
                            </div>
                            <div class="mb-3 mt-3">
                                <label class="form-label" for="resourceNotes">Notes</label>
                                <textarea class="form-control" id="resourceNotes" rows="3"></textarea>
                            </div>
                            <div class="d-flex gap-2">
                                <button class="btn btn-primary" type="submit" id="saveResourceBtn">Create Resource</button>
                                <button class="btn btn-outline-secondary" type="button" id="resetResourceFormBtn">Reset</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-12 col-xl-8">
                <div class="card h-100 app-card">
                    <div class="card-header d-flex align-items-center gap-2">
                        <span>Resources</span>
                        <select class="form-select form-select-sm ms-auto resource-filter" id="resourceTypeFilter">
                            <option value="">All types</option>
                            <option value="machine">Machine</option>
                            <option value="operator">Operator</option>
                            <option value="tool">Tool</option>
                            <option value="buffer">Buffer</option>
                            <option value="transport">Transport Device</option>
                        </select>
                        <button class="btn btn-sm btn-outline-info" type="button" id="refreshResourcesBtn">Refresh</button>
                    </div>
                    <div class="card-body">
                        <div id="resourceAlert" class="alert d-none" role="alert"></div>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle">
                                <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Type</th>
                                    <th>Qty</th>
                                    <th>Availability</th>
                                    <th>Layout Link</th>
                                    <th class="text-end">Actions</th>
                                </tr>
                                </thead>
                                <tbody id="resourceTableBody">
                                <tr><td colspan="6" class="text-secondary">Select a project to manage resources.</td></tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section id="processView" class="app-view d-none" aria-labelledby="processHeading">
        <h1 class="visually-hidden" id="processHeading">Process Designer</h1>
        <div class="engineering-shell process-shell">
            <aside class="engineering-panel process-library">
                <div class="panel-title">Node Library</div>
                <div id="processNodeLibrary"></div>
                <div class="panel-title border-top">Validation</div>
                <div id="processValidationPanel" class="small p-3 text-secondary">Validate the process before simulation.</div>
            </aside>

            <section class="engineering-workspace">
                <div class="engineering-toolbar border-bottom">
                    <div class="btn-group btn-group-sm">
                        <button type="button" class="btn btn-outline-info" id="processSaveBtn">Save Process</button>
                        <button type="button" class="btn btn-outline-secondary" id="processLoadBtn">Reload</button>
                        <button type="button" class="btn btn-outline-secondary" id="processValidateBtn">Validate</button>
                        <button type="button" class="btn btn-outline-secondary" id="processUndoBtn" disabled>Undo</button>
                        <button type="button" class="btn btn-outline-secondary" id="processRedoBtn" disabled>Redo</button>
                        <button type="button" class="btn btn-outline-secondary" id="processAutoLayoutBtn">Auto Layout</button>
                        <button type="button" class="btn btn-outline-danger" id="processClearBtn">Clear</button>
                    </div>
                    <span class="badge text-bg-secondary ms-auto" id="processStatus">Idle</span>
                </div>
                <div id="processCanvasWrap" class="process-canvas-wrap">
                    <svg id="processSvgLayer"></svg>
                    <div id="processCanvas"></div>
                    <div class="canvas-hint" id="processCanvasHint">Drag process nodes from the library and connect them with output/input ports.</div>
                    <div class="layout-statusbar">
                        Nodes: <span id="processNodeCount">0</span> · Connections: <span id="processConnectionCount">0</span> · Cycle Time: <span id="processCycleTime">0.0</span> min
                    </div>
                </div>
            </section>

            <aside class="engineering-panel">
                <div class="panel-title">Operation Properties</div>
                <div id="processNoSelection" class="empty-state">Select a process node to edit its properties.</div>
                <form id="processProperties" class="d-none p-3" novalidate>
                    <input type="hidden" id="processNodeId">
                    <div class="mb-2">
                        <label class="form-label" for="processCode">Operation Code</label>
                        <input class="form-control form-control-sm" id="processCode" maxlength="20">
                    </div>
                    <div class="mb-2">
                        <label class="form-label" for="processName">Name</label>
                        <input class="form-control form-control-sm" id="processName" required maxlength="120">
                    </div>
                    <div class="mb-2">
                        <label class="form-label" for="processNodeType">Node Type</label>
                        <select class="form-select form-select-sm" id="processNodeType">
                            <option value="start">Start</option>
                            <option value="operation">Operation</option>
                            <option value="inspection">Inspection</option>
                            <option value="transport">Transport</option>
                            <option value="buffer">Buffer</option>
                            <option value="decision">Decision</option>
                            <option value="delay">Delay</option>
                            <option value="end">End</option>
                        </select>
                    </div>
                    <div class="row g-2">
                        <div class="col-6"><label class="form-label" for="processCycle">Cycle Time (min)</label><input class="form-control form-control-sm" id="processCycle" type="number" step="0.1" min="0"></div>
                        <div class="col-6"><label class="form-label" for="processSetup">Setup Time (min)</label><input class="form-control form-control-sm" id="processSetup" type="number" step="0.1" min="0"></div>
                        <div class="col-6"><label class="form-label" for="processBatch">Batch Size</label><input class="form-control form-control-sm" id="processBatch" type="number" min="1"></div>
                        <div class="col-6"><label class="form-label" for="processScrap">Scrap %</label><input class="form-control form-control-sm" id="processScrap" type="number" step="0.01" min="0" max="100"></div>
                        <div class="col-6"><label class="form-label" for="processRework">Rework %</label><input class="form-control form-control-sm" id="processRework" type="number" step="0.01" min="0" max="100"></div>
                        <div class="col-6"><label class="form-label" for="processMtbf">MTBF (h)</label><input class="form-control form-control-sm" id="processMtbf" type="number" step="0.1" min="0"></div>
                        <div class="col-6"><label class="form-label" for="processMttr">MTTR (h)</label><input class="form-control form-control-sm" id="processMttr" type="number" step="0.1" min="0"></div>
                        <div class="col-6"><label class="form-label" for="processColor">Color</label><input class="form-control form-control-color form-control-sm w-100" id="processColor" type="color"></div>
                    </div>
                    <div class="row g-2 mt-2">
                        <div class="col-8">
                            <label class="form-label" for="processResourceLink">Required Resource</label>
                            <select class="form-select form-select-sm" id="processResourceLink"><option value="">Not assigned</option></select>
                        </div>
                        <div class="col-4">
                            <label class="form-label" for="processRequiredQuantity">Qty</label>
                            <input class="form-control form-control-sm" id="processRequiredQuantity" type="number" min="1" value="1">
                        </div>
                    </div>
                    <div class="mb-2">
                        <label class="form-label" for="processLayoutLink">Linked Layout Element</label>
                        <select class="form-select form-select-sm" id="processLayoutLink"><option value="">Not linked</option></select>
                    </div>
                    <div class="mb-2">
                        <label class="form-label" for="processNotes">Notes</label>
                        <textarea class="form-control form-control-sm" id="processNotes" rows="2"></textarea>
                    </div>
                    <div class="d-grid gap-2 mt-3">
                        <button type="submit" class="btn btn-sm btn-primary">Apply</button>
                        <button type="button" class="btn btn-sm btn-outline-danger" id="processDeleteBtn">Delete Node</button>
                    </div>
                </form>
            </aside>
        </div>
    </section>


    <section id="simulationView" class="app-view d-none" aria-labelledby="simulationHeading">
        <h1 class="visually-hidden" id="simulationHeading">Simulation</h1>
        <div class="row g-4">
            <div class="col-12 col-xl-3">
                <div class="card app-card h-100">
                    <div class="card-header d-flex align-items-center justify-content-between">
                        <span>Simulation Scenario</span>
                        <span class="badge text-bg-secondary" id="simulationStatus">Idle</span>
                    </div>
                    <div class="card-body">
                        <form id="simulationForm" novalidate>
                            <div class="mb-3">
                                <label class="form-label" for="simulationScenarioName">Scenario name</label>
                                <input class="form-control" id="simulationScenarioName" value="Baseline Scenario" required>
                                <div class="invalid-feedback" data-simulation-error="name"></div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label" for="simulationDuration">Duration (minutes)</label>
                                <input class="form-control" id="simulationDuration" type="number" min="1" value="480">
                                <div class="invalid-feedback" data-simulation-error="duration_minutes"></div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label" for="simulationArrivalRate">Arrival rate (jobs/hour)</label>
                                <input class="form-control" id="simulationArrivalRate" type="number" min="0.01" step="0.01" value="10">
                                <div class="invalid-feedback" data-simulation-error="arrival_rate"></div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label" for="simulationSeed">Random seed</label>
                                <input class="form-control" id="simulationSeed" type="number" value="42">
                            </div>
                            <div class="d-grid gap-2">
                                <button class="btn btn-success" type="submit" id="runSimulationBtn">Run Simulation</button>
                                <button class="btn btn-outline-primary" type="button" id="saveScenarioBtn">Save Scenario</button>
                                <button class="btn btn-outline-info" type="button" id="refreshResultsBtn">Refresh Results</button>
                            </div>
                        </form>
                    </div>
                </div>
                <div class="card app-card mt-3">
                    <div class="card-header d-flex align-items-center justify-content-between">
                        <span>Scenario Manager</span>
                        <button class="btn btn-sm btn-outline-info" type="button" id="refreshScenariosBtn">Refresh</button>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-sm table-hover align-middle mb-0">
                                <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Duration</th>
                                    <th>Arrival</th>
                                    <th class="text-end">Actions</th>
                                </tr>
                                </thead>
                                <tbody id="scenarioTableBody"><tr><td colspan="4" class="text-secondary p-3">No scenarios loaded.</td></tr></tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-12 col-xl-9">
                <div class="row g-3 mb-3">
                    <div class="col-6 col-lg-3"><div class="kpi-card"><span>Throughput</span><strong id="kpiThroughput">–</strong><small>jobs/hour</small></div></div>
                    <div class="col-6 col-lg-3"><div class="kpi-card"><span>Lead Time</span><strong id="kpiLeadTime">–</strong><small>minutes</small></div></div>
                    <div class="col-6 col-lg-3"><div class="kpi-card"><span>Average WIP</span><strong id="kpiWip">–</strong><small>jobs</small></div></div>
                    <div class="col-6 col-lg-3"><div class="kpi-card"><span>OEE</span><strong id="kpiOee">–</strong><small>%</small></div></div>
                </div>
                <div class="card app-card">
                    <div class="card-header">Simulation Event Log</div>
                    <div class="card-body simulation-log" id="simulationEventLog">Run a scenario to display simulation events.</div>
                </div>
            </div>
        </div>
    </section>

    <section id="resultsView" class="app-view d-none" aria-labelledby="resultsHeading">
        <h1 class="visually-hidden" id="resultsHeading">Results Dashboard</h1>
        <div class="card app-card">
            <div class="card-header d-flex align-items-center justify-content-between">
                <span>Results Dashboard</span>
                <div class="btn-group btn-group-sm">
                    <button class="btn btn-outline-info" type="button" id="refreshResultsDashboardBtn">Refresh</button>
                    <button class="btn btn-outline-secondary" type="button" id="exportResultsCsvBtn">Export CSV</button>
                    <button class="btn btn-outline-primary" type="button" id="openLatestReportBtn">Open Report</button>
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead>
                        <tr>
                            <th>Run</th><th>Scenario</th><th>Throughput</th><th>Lead Time</th><th>WIP</th><th>Utilization</th><th>OEE</th><th>Bottleneck</th><th>Finished</th><th class="text-end">Report</th>
                        </tr>
                        </thead>
                        <tbody id="resultsTableBody"><tr><td colspan="10" class="text-secondary">No simulation results loaded.</td></tr></tbody>
                    </table>
                </div>
            </div>
        </div>
    </section>


    <section id="settingsView" class="app-view d-none" aria-labelledby="settingsHeading">
        <h1 class="visually-hidden" id="settingsHeading">Application Settings</h1>
        <div class="row g-4">
            <div class="col-12 col-xl-5">
                <div class="card app-card h-100">
                    <div class="card-header d-flex align-items-center justify-content-between">
                        <span>Application Settings</span>
                        <span class="badge text-bg-secondary" id="settingsStatus">Local</span>
                    </div>
                    <div class="card-body">
                        <form id="settingsForm" novalidate>
                            <div class="mb-3">
                                <label class="form-label" for="settingsTheme">Theme</label>
                                <select class="form-select" id="settingsTheme">
                                    <option value="dark">Dark</option>
                                    <option value="light">Light</option>
                                    <option value="auto">Use system preference</option>
                                </select>
                                <div class="form-text">Stored in the browser and applied immediately.</div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label" for="settingsDefaultRoute">Startup screen</label>
                                <select class="form-select" id="settingsDefaultRoute">
                                    <option value="dashboard">Dashboard</option>
                                    <option value="projects">Projects</option>
                                    <option value="layout">Layout</option>
                                    <option value="resources">Resources</option>
                                    <option value="process">Process</option>
                                    <option value="simulation">Simulation</option>
                                    <option value="results">Results</option>
                                    <option value="settings">Settings</option>
                                </select>
                            </div>
                            <div class="form-check form-switch mb-3">
                                <input class="form-check-input" type="checkbox" role="switch" id="settingsReducedMotion">
                                <label class="form-check-label" for="settingsReducedMotion">Reduce motion</label>
                            </div>
                            <div class="form-check form-switch mb-3">
                                <input class="form-check-input" type="checkbox" role="switch" id="settingsCompactTables">
                                <label class="form-check-label" for="settingsCompactTables">Compact data tables</label>
                            </div>
                            <div class="d-flex gap-2">
                                <button type="submit" class="btn btn-primary">Save Settings</button>
                                <button type="button" class="btn btn-outline-secondary" id="settingsResetBtn">Reset Defaults</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            <div class="col-12 col-xl-7">
                <div class="card app-card h-100">
                    <div class="card-header">Keyboard Shortcuts</div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-sm align-middle">
                                <thead><tr><th>Shortcut</th><th>Action</th></tr></thead>
                                <tbody>
                                <tr><td><kbd>Alt</kbd> + <kbd>1</kbd></td><td>Open Dashboard</td></tr>
                                <tr><td><kbd>Alt</kbd> + <kbd>2</kbd></td><td>Open Projects</td></tr>
                                <tr><td><kbd>Alt</kbd> + <kbd>3</kbd></td><td>Open Layout</td></tr>
                                <tr><td><kbd>Alt</kbd> + <kbd>4</kbd></td><td>Open Resources</td></tr>
                                <tr><td><kbd>Alt</kbd> + <kbd>5</kbd></td><td>Open Process</td></tr>
                                <tr><td><kbd>Alt</kbd> + <kbd>6</kbd></td><td>Open Simulation</td></tr>
                                <tr><td><kbd>Alt</kbd> + <kbd>7</kbd></td><td>Open Results</td></tr>
                                <tr><td><kbd>Alt</kbd> + <kbd>8</kbd></td><td>Open Settings</td></tr>
                                </tbody>
                            </table>
                        </div>
                        <p class="text-secondary small mb-0">Preferences are intentionally local-only in the MVP and do not change engineering project data.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

</main>

<div class="toast-container position-fixed top-0 end-0 p-3" id="toastContainer" aria-live="polite" aria-atomic="true"></div>

<div class="modal fade" id="confirmDialog" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" data-confirm-title>Confirm action</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" data-confirm-message>Are you sure?</div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger" data-confirm-action>Confirm</button>
            </div>
        </div>
    </div>
</div>

<div class="visually-hidden" id="accessibilityLiveRegion" role="status" aria-live="polite" aria-atomic="true"></div>
<footer class="global-status-bar" id="globalStatusBar" role="contentinfo" aria-label="Application status"></footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/konva/9.3.6/konva.min.js"></script>
<script type="module" src="/public/js/dashboard.js"></script>
<script type="module" src="/public/js/projects.js"></script>
<script type="module" src="/public/js/layout.js"></script>
<script type="module" src="/public/js/resources.js"></script>
<script type="module" src="/public/js/process.js"></script>
<script type="module" src="/public/js/simulation.js"></script>
<script type="module" src="/public/js/settings.js"></script>
<script type="module" src="/public/js/app.js"></script>
</body>
</html>
