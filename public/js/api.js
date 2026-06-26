import { eventBus } from './eventBus.js';

export class ApiClient {
    constructor() {
        this.csrfToken = null;
    }

    async get(url) {
        return this.request(url, { method: 'GET' });
    }

    async post(url, data) {
        return this.request(url, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data),
        });
    }

    async ensureCsrfToken() {
        if (this.csrfToken !== null) {
            return this.csrfToken;
        }

        const response = await fetch('/api/system/csrf-token.php', { method: 'GET' });
        const payload = await response.json();

        if (!response.ok || !payload.success || !payload.data?.token) {
            throw new Error('Unable to initialize CSRF protection.');
        }

        this.csrfToken = payload.data.token;
        return this.csrfToken;
    }

    async request(url, options = {}) {
        const method = (options.method || 'GET').toUpperCase();
        eventBus.emit('api:request-started', { url, method });

        try {
            if (!['GET', 'HEAD', 'OPTIONS'].includes(method)) {
                const token = await this.ensureCsrfToken();
                options.headers = {
                    ...(options.headers || {}),
                    'X-CSRF-Token': token,
                };
            }

            const response = await fetch(url, options);
            const payload = await response.json().catch(() => ({ success: false, message: 'Invalid JSON response.' }));

            if (!response.ok || !payload.success) {
                const error = new Error(payload.message || 'API request failed.');
                error.payload = payload;
                error.status = response.status;
                eventBus.emit('api:error', {
                    url,
                    status: response.status,
                    message: error.message,
                    payload,
                });
                throw error;
            }

            eventBus.emit('api:request-succeeded', { url, payload });
            return payload;
        } finally {
            eventBus.emit('api:request-finished', { url, method });
        }
    }
}
