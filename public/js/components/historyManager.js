export class HistoryManager {
    constructor({ limit = 50, onChange = () => {} } = {}) {
        this.limit = limit;
        this.onChange = onChange;
        this.undoStack = [];
        this.redoStack = [];
    }

    clear() {
        this.undoStack = [];
        this.redoStack = [];
        this.notify();
    }

    push(snapshot) {
        const serialized = JSON.stringify(snapshot);
        if (this.undoStack[this.undoStack.length - 1] === serialized) {
            return;
        }
        this.undoStack.push(serialized);
        if (this.undoStack.length > this.limit) {
            this.undoStack.shift();
        }
        this.redoStack = [];
        this.notify();
    }

    undo(currentSnapshot) {
        if (!this.canUndo()) {
            return null;
        }
        this.redoStack.push(JSON.stringify(currentSnapshot));
        const snapshot = this.undoStack.pop();
        this.notify();
        return JSON.parse(snapshot);
    }

    redo(currentSnapshot) {
        if (!this.canRedo()) {
            return null;
        }
        this.undoStack.push(JSON.stringify(currentSnapshot));
        const snapshot = this.redoStack.pop();
        this.notify();
        return JSON.parse(snapshot);
    }

    canUndo() {
        return this.undoStack.length > 0;
    }

    canRedo() {
        return this.redoStack.length > 0;
    }

    notify() {
        this.onChange({ canUndo: this.canUndo(), canRedo: this.canRedo() });
    }
}
