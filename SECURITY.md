# Security Policy

OpenMEP is currently in MVP development.

## Reporting a Vulnerability

Please report security issues privately to the project maintainers instead of opening a public issue.

Include:

- affected module or endpoint,
- reproduction steps,
- expected and actual behavior,
- possible impact,
- suggested mitigation if known.

## Baseline Security Expectations

- All database access must use prepared statements.
- Controllers must validate request data before calling services.
- API errors must not expose database credentials or stack traces.
- Destructive actions must require explicit confirmation in the UI.
