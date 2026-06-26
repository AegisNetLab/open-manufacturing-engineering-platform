export function setToolbarBusy(toolbar, busy) {
    if (!toolbar) {
        return;
    }

    toolbar.querySelectorAll('button, input, select').forEach((control) => {
        control.disabled = busy;
    });
}

export function setBadgeState(badge, label, variant = 'secondary') {
    if (!badge) {
        return;
    }

    badge.className = `badge text-bg-${variant}`;
    badge.textContent = label;
}
