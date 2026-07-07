# Product

This repository builds a command-first, live Laravel doctor for the current
project state.

The current project state is authoritative. Findings are not stored by default.
JSON is allowed only as an explicit export or baseline.

The first product entry point is:

```bash
php artisan doctor:scan
```

The package exposes one shared analysis engine through:

- Artisan
- browser UI
- MCP
- public PHP API

# Architecture

- All interfaces build a DoctorRequest and call the shared scan engine.
- Browser and MCP code must never shell out to the Artisan command.
- Rules return findings and never persist them.
- Static rules cannot boot Laravel.
- Rules use only declared capabilities.
- Native rules perform no external network calls.
- Browser and MCP interfaces are read-only.
- No automatic source-code modifications.
- No database migrations.
- No host Node build requirement.
- Prefer native, lightweight first-party PHP 8.2 readonly DTO classes instead of external DTO packages (e.g. spatie/laravel-data).
- Avoid anonymous classes for static analysis traversals (e.g. AST visitors). Extract them into dedicated named visitor classes.
- Static rules must use the shared abstract rule/visitor bases. `RuleId` owns stable rule metadata (default severity, title, remediation, confidence, tags, beta status); the abstract rule owns finding construction and fingerprints; concrete static rules should declare only `RULE_ID` plus matching mechanics such as visitors, path filters, and text patterns.

# Product direction

- Start with the command, request model, report model, and a small stable rule
  set.
- Add browser, MCP, and PHP API surfaces only as thin adapters over the same
  engine.
- Prefer fewer high-signal Laravel rules over broad generic static analysis.
- Keep baselines explicit, portable, and file-based.
- Defer dashboards, history, background scans, and remote services unless the
  package has a proven need for them.

# Rule quality

- Prefer high-signal Laravel-specific rules.
- Heuristic rules are beta and advisory by default.
- Every rule requires positive and negative fixtures.
- Heuristic rules also require ambiguous fixtures.
- Findings require evidence, source location, remediation, and confidence.
- Do not duplicate PHPStan, Larastan, Composer Audit, Pint, Pest, Telescope,
  Pulse, Horizon, Boost, or architecture graph tools.

# Security

- Resolve project-relative paths only.
- Reject path traversal and symlink escape.
- Redact secrets.
- Never render .env.
- Never expose arbitrary command execution through MCP or browser routes.
- Booted analysis must not execute business actions.

# Completion

Before completing any implementation task run:

1. composer format:test
2. composer analyse
3. composer test

Do not change unrelated public contracts.
Do not weaken tests.