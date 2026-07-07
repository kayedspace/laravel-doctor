---
name: laravel-doctor
description: >-
  Diagnose Laravel application health with Laravel Doctor and turn its findings
  into a read-only remediation plan. Use when the user shares Doctor findings,
  asks to run a Doctor scan, or wants help triaging Laravel config, routing,
  security, database, or runtime issues surfaced by the Doctor MCP tools.
---

# Laravel Doctor AI Workflow

Laravel Doctor is a read-only diagnostic scanner. It reports findings (rule id,
severity, confidence, `file:line`, message) — it never edits code. Use this
skill to run scans, read findings correctly, and plan focused fixes.

## When to use

- The user pastes Doctor findings and asks what they mean or how to fix them.
- The user asks you to run Doctor (scan the project, changed files, or specific paths).
- You are triaging a Laravel app and want authoritative findings before guessing.

## Doctor tools

The MCP server is **Laravel Doctor** (local handle `doctor`). Call tools by the
bare names below; if your client requires a server prefix, use `doctor:<tool>`.
All tools are read-only.

| Tool                  | Purpose                         | Key inputs (all optional unless noted)                                                                                         |
|-----------------------|---------------------------------|--------------------------------------------------------------------------------------------------------------------------------|
| `doctor_scan`         | Full/scoped project scan        | `scopePreset` (`full`\|`manual`\|`changed`\|`laravel`), `paths`, `rules`, `packs`, `exclusions`, `booted`, `auditDependencies` |
| `doctor_scan_files`   | Scan specific files             | `paths` (**required**)                                                                                                         |
| `doctor_scan_changed` | Scan git-changed files          | `rules`, `packs`, `exclusions`, `booted`                                                                                       |
| `doctor_list_rules`   | List all rules + metadata       | —                                                                                                                              |
| `doctor_explain_rule` | Explain one rule + remediation  | `ruleId` (**required**)                                                                                                        |
| `doctor_resolve_plan` | Group findings into a fix order | same scan inputs as `doctor_scan`                                                                                              |

Prefer the narrowest scan for the task: `doctor_scan_files` or
`doctor_scan_changed` over a full `doctor_scan` when you already know the target.

## Workflow

```
- [ ] Get findings (existing paste, or run the narrowest Doctor scan)
- [ ] Record each finding: rule id, severity, confidence, file:line, message
- [ ] Expand unclear remediation with doctor_explain_rule
- [ ] For 3+ findings, order them with doctor_resolve_plan
- [ ] Implement only what the user asked, scoped to the cited files
- [ ] Re-scan the touched files (or run tests) to confirm the fix
```

1. **Get findings.** Prefer findings the user already has. Otherwise scan the
   smallest relevant scope.
2. **Preserve identity.** Keep each finding's rule id, severity, confidence, and
   `file:line` verbatim in your notes — they are how you verify the fix later.
3. **Explain before fixing.** Call `doctor_explain_rule` when a remediation
   pointer is thin; act on the rule's guidance, not a guess.
4. **Order multi-fixes.** `doctor_resolve_plan` groups findings by rule and
   severity so you fix the highest-impact issues first.
5. **Fix narrowly.** Only touch the cited files, and only when the user asks for
   implementation.
6. **Verify.** Re-run the relevant scan or the project's tests after changes.

## Rules

- **Doctor tools are read-only — never expect them to change files.** They
  diagnose; edits happen through your normal file tools, only after the user
  asks. This keeps a scan safe to run at any time.
- **Never ask Doctor tools to run shell commands.** They have no exec surface;
  requesting one is always a misuse and will fail.
- **Keep fixes scoped to the cited `file:line` and the rule's remediation.**
  Findings are precise; unrelated edits add risk the scan can't vouch for.
- **Never surface `.env` contents or secrets.** Doctor flags misconfig without
  printing secret values; you must not print them either.
- **Re-scan or test after changing code.** A fix isn't done until the finding is
  gone or the tests pass — don't assume.

## Output format

Scan tools return a compact structure:

```json
{
  "status": "completed",
  "rules": ["...ids that ran..."],
  "findings": [
    { "rule": "security.app_debug_true", "severity": "error",
      "confidence": "high", "location": "config/app.php:18",
      "message": "APP_DEBUG is enabled." }
  ],
  "errors": []
}
```

- `severity`: `critical` > `error` > `warning` > `info`. Triage in that order.
- `confidence`: `high` findings are safe to act on directly; `medium`/`low`
  warrant a quick manual check before changing code.
- `location` is `file:line` — cite it when you propose a fix.
- A non-empty `errors` array means the scan was partial; mention it rather than
  treating the result as complete.
