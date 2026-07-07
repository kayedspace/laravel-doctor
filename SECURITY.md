# Security Policy

## Supported versions

Security fixes are applied to the latest supported branch under active
development.

## Reporting a vulnerability

Please do not open public issues for suspected security vulnerabilities.

Report security issues privately to:

- Email: `3likayed@gmail.com`

Include:

- affected Laravel Doctor version or commit
- Laravel/PHP version
- reproduction steps
- expected impact
- whether the issue affects CLI, dashboard, HTTP API, MCP, or the PHP API

You should receive an acknowledgment within 5 business days.

## Scope notes

Laravel Doctor is designed to be read-only. Reports should prioritize issues
that could break that guarantee, expose secrets, allow path traversal, weaken
authorization around dashboard/API routes, or bypass MCP argument safety.
