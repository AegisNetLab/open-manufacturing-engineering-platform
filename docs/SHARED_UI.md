# Shared UI Framework

OpenMEP uses a small shared UI framework to keep engineering modules consistent without introducing unnecessary frontend complexity.

## Goals

- Keep the application as one integrated engineering environment.
- Reuse the same shell, navigation, status handling, toast messages and confirmation dialog across modules.
- Keep persistence behind REST APIs and avoid database-specific assumptions in frontend components.
- Support future refactoring of Layout, Process, Resources, Simulation and Results into smaller ES2022 modules.

## Core modules

| File | Responsibility |
| --- | --- |
| `public/js/eventBus.js` | Shared browser event bus based on `CustomEvent`. |
| `public/js/api.js` | Centralized JSON API wrapper with lifecycle and error events. |
| `public/js/app.js` | Application routing, dirty-state navigation guard and shell initialization. |
| `public/js/components/toast.js` | Bootstrap toast notifications. |
| `public/js/components/statusBar.js` | Global bottom status bar showing module, project and current state. |
| `public/js/components/modal.js` | Reusable Bootstrap confirmation dialog. |
| `public/js/components/validation.js` | Shared field-validation helpers. |
| `public/js/components/toolbar.js` | Small toolbar and badge helpers. |
| `public/js/utils/dom.js` | Minimal DOM utility functions. |

## Shared events

| Event | Purpose |
| --- | --- |
| `openmep:route-changed` | Emitted when the active module changes. |
| `openmep:project-selected` | Emitted when a project becomes active. |
| `ui:dirty` | Marks a module as having unsaved changes or clears the dirty state. |
| `ui:status` | Updates the global status bar. |
| `ui:toast` | Displays a toast notification. |
| `api:request-started` | Emitted before a REST request. |
| `api:request-succeeded` | Emitted after a successful REST request. |
| `api:request-finished` | Emitted after every REST request. |
| `api:error` | Emitted after a failed REST request. |

## Architectural decision

The framework intentionally avoids a heavy frontend library. The approved MVP stack is Bootstrap 5, ES2022 JavaScript, Konva.js and Chart.js. This shared layer gives the project consistent behavior while preserving the lightweight PHP/AJAX architecture.
