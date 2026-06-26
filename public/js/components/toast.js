import { eventBus } from '../eventBus.js';

const DEFAULT_DELAY = 4200;

export class ToastManager {
    constructor(containerId = 'toastContainer') {
        this.container = document.getElementById(containerId);
    }

    show(message, variant = 'info', delay = DEFAULT_DELAY) {
        if (!this.container || !message) {
            return;
        }

        const toast = document.createElement('div');
        toast.className = `toast align-items-center border-0 text-bg-${variant}`;
        toast.setAttribute('role', 'status');
        toast.setAttribute('aria-live', 'polite');
        toast.setAttribute('aria-atomic', 'true');
        toast.innerHTML = `
            <div class="d-flex">
                <div class="toast-body"></div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>`;
        toast.querySelector('.toast-body').textContent = message;
        this.container.appendChild(toast);

        const bootstrapToast = new bootstrap.Toast(toast, { delay });
        toast.addEventListener('hidden.bs.toast', () => toast.remove());
        bootstrapToast.show();
    }
}

export function initializeToasts() {
    const manager = new ToastManager();

    eventBus.on('ui:toast', (event) => {
        manager.show(event.detail?.message, event.detail?.variant || 'info', event.detail?.delay || DEFAULT_DELAY);
    });

    eventBus.on('api:error', (event) => {
        manager.show(event.detail?.message || 'API request failed.', 'danger');
    });

    return manager;
}
