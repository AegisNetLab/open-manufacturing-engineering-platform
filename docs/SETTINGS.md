# Settings and Local Preferences

OpenMEP v1 stores MVP user interface preferences locally in the browser. These settings are intentionally separated from engineering project data, so changing them never modifies projects, layouts, resources, processes, scenarios or simulation results.

## Included Preferences

- Theme: dark, light or system preference.
- Startup screen: default route opened when no hash route is provided.
- Reduced motion: disables non-essential transitions and animations.
- Compact tables: reduces table spacing for dense engineering data views.

## Storage

Preferences are stored in `localStorage` under `openmep.preferences`. The startup route is additionally mirrored to `openmep.defaultRoute` so the application shell can apply it before modules initialize.

## Accessibility Notes

The Settings screen documents the global navigation shortcuts. `Alt + 1` through `Alt + 7` open Projects, Layout, Resources, Process, Simulation, Results and Settings respectively.

## MVP Boundary

Settings are local-only in the MVP. Server-side user accounts and shared team preferences are intentionally out of scope and can be added later without changing project data models.
