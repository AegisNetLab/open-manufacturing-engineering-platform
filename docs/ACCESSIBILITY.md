# Accessibility

OpenMEP aims to keep the engineering workflow usable with keyboard navigation, assistive technologies, and reduced-motion user preferences.

## Implemented baseline

- Main content skip link.
- Semantic navigation landmarks.
- Hidden page headings for every application module.
- Route changes are announced through a polite live region.
- Active navigation links expose `aria-current="page"`.
- Layout and Process canvases expose accessible labels and keyboard focus targets.
- Global status bar is exposed as application status content.
- Focus-visible styling is enabled for controls and engineering toolbars.
- Reduced-motion preferences are respected in CSS.

## Keyboard shortcuts

| Shortcut | Action |
| --- | --- |
| `Alt + 1` | Open Projects |
| `Alt + 2` | Open Layout |
| `Alt + 3` | Open Resources |
| `Alt + 4` | Open Process |
| `Alt + 5` | Open Simulation |
| `Alt + 6` | Open Results |

Shortcuts are ignored while the user is typing in inputs, textareas, selects, or editable content.

## Engineering canvas note

Konva and graph-based engineering canvases are visual interaction surfaces. The MVP provides accessible labels, focus targets, status feedback, and property forms. Future releases should add full keyboard manipulation for layout objects and process nodes.

## Manual QA checklist

1. Use `Tab` from the browser address bar and verify that the skip link appears first.
2. Activate the skip link and verify that focus moves to the main content area.
3. Navigate modules using `Alt + 1` through `Alt + 6`.
4. Verify that a screen reader announces module changes.
5. Verify that all forms show visible focus outlines.
6. Enable reduced motion in the operating system and verify that transitions are minimized.
