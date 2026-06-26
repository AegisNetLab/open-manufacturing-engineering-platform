export class EventBus {
    constructor(target = window) {
        this.target = target;
    }

    on(eventName, handler) {
        this.target.addEventListener(eventName, handler);
        return () => this.target.removeEventListener(eventName, handler);
    }

    emit(eventName, detail = {}) {
        this.target.dispatchEvent(new CustomEvent(eventName, { detail }));
    }
}

export const eventBus = new EventBus();
