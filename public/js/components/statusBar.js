import { eventBus } from '../eventBus.js';

export class StatusBar {
    constructor(elementId = 'globalStatusBar') {
        this.element = document.getElementById(elementId);
        this.route = 'projects';
        this.state = 'Idle';
        this.project = 'No active project';
    }

    initialize() {
        if (!this.element) {
            return;
        }

        eventBus.on('openmep:route-changed', (event) => {
            this.route = event.detail?.route || this.route;
            this.render();
        });

        eventBus.on('openmep:project-selected', (event) => {
            this.project = event.detail?.name || 'No active project';
            this.render();
        });

        eventBus.on('ui:status', (event) => {
            this.state = event.detail?.message || this.state;
            this.render();
        });

        eventBus.on('api:request-started', () => {
            this.state = 'Loading';
            this.render();
        });

        eventBus.on('api:request-finished', () => {
            this.state = 'Idle';
            this.render();
        });

        this.render();
    }

    render() {
        if (!this.element) {
            return;
        }

        this.element.innerHTML = `
            <span>Module: <strong>${this.escape(this.route)}</strong></span>
            <span>Project: <strong>${this.escape(this.project)}</strong></span>
            <span>Status: <strong>${this.escape(this.state)}</strong></span>`;
    }

    escape(value) {
        return String(value)
            .replaceAll('&', '&amp;')
            .replaceAll('<', '&lt;')
            .replaceAll('>', '&gt;')
            .replaceAll('"', '&quot;')
            .replaceAll("'", '&#039;');
    }
}

export function initializeStatusBar() {
    const statusBar = new StatusBar();
    statusBar.initialize();
    return statusBar;
}
