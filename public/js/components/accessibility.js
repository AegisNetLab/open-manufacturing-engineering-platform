import { eventBus } from '../eventBus.js';

const ROUTE_LABELS = {
    dashboard: 'Dashboard',
    projects: 'Project Manager',
    layout: 'Layout Designer',
    resources: 'Resource Manager',
    process: 'Process Designer',
    simulation: 'Simulation',
    results: 'Results Dashboard',
    settings: 'Application Settings',
};

export class AccessibilityManager {
    constructor({ liveRegionId = 'accessibilityLiveRegion' } = {}) {
        this.liveRegion = document.getElementById(liveRegionId);
    }

    initialize() {
        this.ensureLiveRegion();
        this.enhanceNavigation();
        this.enhanceEngineeringCanvases();
        this.bindRouteAnnouncements();
        this.bindKeyboardShortcuts();
    }

    ensureLiveRegion() {
        if (this.liveRegion) {
            return;
        }

        this.liveRegion = document.createElement('div');
        this.liveRegion.id = 'accessibilityLiveRegion';
        this.liveRegion.className = 'visually-hidden';
        this.liveRegion.setAttribute('role', 'status');
        this.liveRegion.setAttribute('aria-live', 'polite');
        this.liveRegion.setAttribute('aria-atomic', 'true');
        document.body.appendChild(this.liveRegion);
    }

    enhanceNavigation() {
        const nav = document.querySelector('.app-navbar');
        nav?.setAttribute('aria-label', 'Main navigation');

        document.querySelectorAll('[data-route]').forEach((link) => {
            const route = link.dataset.route;
            link.setAttribute('aria-controls', `${route}View`);
            link.setAttribute('aria-current', link.classList.contains('active') ? 'page' : 'false');
        });
    }

    enhanceEngineeringCanvases() {
        this.enhanceCanvas('layoutCanvasWrap', 'Factory layout canvas. Drag components from the library, then edit selected object properties in the right panel.');
        this.enhanceCanvas('processCanvasWrap', 'Process graph canvas. Drag process nodes from the library and connect output ports to input ports.');
    }

    enhanceCanvas(id, label) {
        const canvasWrap = document.getElementById(id);
        if (!canvasWrap) {
            return;
        }

        canvasWrap.setAttribute('role', 'application');
        canvasWrap.setAttribute('aria-label', label);
        canvasWrap.setAttribute('tabindex', '0');
    }

    bindRouteAnnouncements() {
        eventBus.on('openmep:route-changed', (event) => {
            const route = event.detail?.route || 'dashboard';
            document.querySelectorAll('[data-route]').forEach((link) => {
                link.setAttribute('aria-current', link.dataset.route === route ? 'page' : 'false');
            });

            const view = document.getElementById(`${route}View`);
            if (view) {
                view.setAttribute('tabindex', '-1');
                view.focus({ preventScroll: true });
            }

            this.announce(`${ROUTE_LABELS[route] || route} opened.`);
        });

        eventBus.on('ui:status', (event) => {
            if (event.detail?.message) {
                this.announce(event.detail.message);
            }
        });
    }

    bindKeyboardShortcuts() {
        document.addEventListener('keydown', (event) => {
            if (this.isTyping(event.target)) {
                return;
            }

            if (event.altKey && !event.shiftKey && !event.ctrlKey && !event.metaKey) {
                const route = this.routeFromShortcut(event.key);
                if (route) {
                    event.preventDefault();
                    window.location.hash = route;
                }
            }
        });
    }

    routeFromShortcut(key) {
        return {
            '1': 'dashboard',
            '2': 'projects',
            '3': 'layout',
            '4': 'resources',
            '5': 'process',
            '6': 'simulation',
            '7': 'results',
            '8': 'settings',
        }[key] || null;
    }

    isTyping(target) {
        if (!target) {
            return false;
        }

        const tagName = target.tagName?.toLowerCase();
        return tagName === 'input' || tagName === 'textarea' || tagName === 'select' || target.isContentEditable;
    }

    announce(message) {
        if (!this.liveRegion || !message) {
            return;
        }

        this.liveRegion.textContent = '';
        window.setTimeout(() => {
            this.liveRegion.textContent = message;
        }, 10);
    }
}

export function initializeAccessibility() {
    const manager = new AccessibilityManager();
    manager.initialize();
    return manager;
}
