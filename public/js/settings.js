import { eventBus } from './eventBus.js';
import { showToast } from './components/toast.js';

const STORAGE_KEY = 'openmep.preferences';
const DEFAULTS = Object.freeze({
    theme: 'dark',
    defaultRoute: 'dashboard',
    reducedMotion: false,
    compactTables: false,
});

function readPreferences() {
    try {
        const raw = window.localStorage.getItem(STORAGE_KEY);
        return { ...DEFAULTS, ...(raw ? JSON.parse(raw) : {}) };
    } catch (_error) {
        return { ...DEFAULTS };
    }
}

function writePreferences(preferences) {
    const normalized = { ...DEFAULTS, ...preferences };
    window.localStorage.setItem(STORAGE_KEY, JSON.stringify(normalized));
    window.localStorage.setItem('openmep.defaultRoute', normalized.defaultRoute);
    return normalized;
}

function resolveTheme(theme) {
    if (theme !== 'auto') {
        return theme;
    }

    return window.matchMedia('(prefers-color-scheme: light)').matches ? 'light' : 'dark';
}

function applyPreferences(preferences) {
    const resolvedTheme = resolveTheme(preferences.theme);
    document.documentElement.setAttribute('data-bs-theme', resolvedTheme);
    document.documentElement.dataset.openmepThemeMode = preferences.theme;
    document.body.classList.toggle('openmep-reduced-motion', Boolean(preferences.reducedMotion));
    document.body.classList.toggle('openmep-compact-tables', Boolean(preferences.compactTables));
    eventBus.emit('settings:changed', { preferences, resolvedTheme });
}

function syncForm(preferences) {
    const theme = document.getElementById('settingsTheme');
    const defaultRoute = document.getElementById('settingsDefaultRoute');
    const reducedMotion = document.getElementById('settingsReducedMotion');
    const compactTables = document.getElementById('settingsCompactTables');

    if (theme) theme.value = preferences.theme;
    if (defaultRoute) defaultRoute.value = preferences.defaultRoute;
    if (reducedMotion) reducedMotion.checked = Boolean(preferences.reducedMotion);
    if (compactTables) compactTables.checked = Boolean(preferences.compactTables);
}

function readForm() {
    return {
        theme: document.getElementById('settingsTheme')?.value || DEFAULTS.theme,
        defaultRoute: document.getElementById('settingsDefaultRoute')?.value || DEFAULTS.defaultRoute,
        reducedMotion: Boolean(document.getElementById('settingsReducedMotion')?.checked),
        compactTables: Boolean(document.getElementById('settingsCompactTables')?.checked),
    };
}

function initializeSettings() {
    let preferences = readPreferences();
    applyPreferences(preferences);
    syncForm(preferences);

    document.getElementById('settingsForm')?.addEventListener('submit', (event) => {
        event.preventDefault();
        preferences = writePreferences(readForm());
        applyPreferences(preferences);
        syncForm(preferences);
        showToast('Settings saved.', 'success');
    });

    document.getElementById('settingsResetBtn')?.addEventListener('click', () => {
        preferences = writePreferences(DEFAULTS);
        applyPreferences(preferences);
        syncForm(preferences);
        showToast('Settings reset to defaults.', 'info');
    });

    window.matchMedia('(prefers-color-scheme: light)').addEventListener('change', () => {
        preferences = readPreferences();
        if (preferences.theme === 'auto') {
            applyPreferences(preferences);
        }
    });
}

initializeSettings();
