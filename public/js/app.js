import { eventBus } from './eventBus.js';
import { initializeStatusBar } from './components/statusBar.js';
import { initializeToasts } from './components/toast.js';
import { initializeAccessibility } from './components/accessibility.js';

const ROUTES = ['dashboard', 'projects', 'layout', 'resources', 'process', 'simulation', 'results', 'settings'];
const state = {
    currentRoute: 'dashboard',
    dirtyModules: new Set(),
};

function setActiveView(route) {
    const normalizedRoute = ROUTES.includes(route) ? route : 'dashboard';

    if (state.currentRoute !== normalizedRoute && state.dirtyModules.has(state.currentRoute)) {
        const confirmed = window.confirm('There are unsaved changes in this module. Continue navigation?');
        if (!confirmed) {
            history.replaceState(null, '', `#${state.currentRoute}`);
            return;
        }
    }

    state.currentRoute = normalizedRoute;
    document.querySelectorAll('.app-view').forEach((view) => view.classList.add('d-none'));
    document.getElementById(`${normalizedRoute}View`)?.classList.remove('d-none');

    document.querySelectorAll('[data-route]').forEach((link) => {
        link.classList.toggle('active', link.dataset.route === normalizedRoute);
    });

    eventBus.emit('openmep:route-changed', { route: normalizedRoute });
}

function initializeNavigation() {
    window.addEventListener('hashchange', () => setActiveView(location.hash.replace('#', '')));

    eventBus.on('openmep:project-selected', (event) => {
        const label = document.getElementById('activeProjectLabel');
        if (label) {
            label.textContent = event.detail?.name ? `Active project: ${event.detail.name}` : 'No active project';
        }
    });

    eventBus.on('ui:dirty', (event) => {
        const moduleName = event.detail?.module || state.currentRoute;
        if (event.detail?.dirty === false) {
            state.dirtyModules.delete(moduleName);
        } else {
            state.dirtyModules.add(moduleName);
        }
    });

    window.addEventListener('beforeunload', (event) => {
        if (state.dirtyModules.size === 0) {
            return;
        }

        event.preventDefault();
        event.returnValue = '';
    });

    const requestedRoute = location.hash.replace('#', '') || window.localStorage.getItem('openmep.defaultRoute') || 'dashboard';
    if (!location.hash && requestedRoute) {
        history.replaceState(null, '', `#${requestedRoute}`);
    }
    setActiveView(requestedRoute);
}

initializeToasts();
initializeStatusBar();
initializeAccessibility();
initializeNavigation();
