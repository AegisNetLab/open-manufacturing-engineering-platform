export class ConfirmDialog {
    constructor(elementId = 'confirmDialog') {
        this.element = document.getElementById(elementId);
        this.instance = this.element ? new bootstrap.Modal(this.element) : null;
        this.title = this.element?.querySelector('[data-confirm-title]');
        this.message = this.element?.querySelector('[data-confirm-message]');
        this.confirmButton = this.element?.querySelector('[data-confirm-action]');
    }

    ask({ title = 'Confirm action', message = 'Are you sure?', confirmLabel = 'Confirm', variant = 'danger' } = {}) {
        if (!this.element || !this.instance || !this.confirmButton) {
            return Promise.resolve(window.confirm(message));
        }

        this.title.textContent = title;
        this.message.textContent = message;
        this.confirmButton.textContent = confirmLabel;
        this.confirmButton.className = `btn btn-${variant}`;

        return new Promise((resolve) => {
            const onConfirm = () => {
                cleanup();
                this.instance.hide();
                resolve(true);
            };
            const onHidden = () => {
                cleanup();
                resolve(false);
            };
            const cleanup = () => {
                this.confirmButton.removeEventListener('click', onConfirm);
                this.element.removeEventListener('hidden.bs.modal', onHidden);
            };

            this.confirmButton.addEventListener('click', onConfirm, { once: true });
            this.element.addEventListener('hidden.bs.modal', onHidden, { once: true });
            this.instance.show();
        });
    }
}

export const confirmDialog = new ConfirmDialog();
