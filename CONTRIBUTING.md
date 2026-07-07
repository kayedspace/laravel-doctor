# Contributing

Laravel Doctor is intentionally narrow: command-first, read-only, Laravel-aware
diagnostics over the current project state.

## Product guardrails

- Keep `php artisan doctor:scan` as the primary entry point.
- All surfaces must build a `DoctorRequest` and call the shared scan engine.
- Browser, API, and MCP adapters must stay read-only and must not shell out to
  Artisan.
- Rules return findings; they do not mutate projects or persist scans by
  default.
- Prefer fewer high-signal Laravel rules over broad generic static analysis.

## Before opening a PR

- Discuss significant feature direction in an issue or draft PR first.
- Include positive and negative fixtures for every rule.
- Add ambiguous fixtures for heuristic rules.
- Keep public contract changes intentional and documented.

## Local workflow

```bash
composer install
composer format:test
composer analyse
composer test
```

## Documentation workflow

- `README.md` is the package landing page.
- `docs/` is the canonical long-form documentation, integrated as a Git Submodule linked to the docs repository.
- Markdown files for the docs site are located in `docs/src/`.
- VitePress configuration is in `docs/.vitepress/`.
- `docs/public/` is for docs-site assets such as the logo and icons.

### Docs local workflow

Since `docs/` is a submodule, make sure you clone this repository with `--recursive` or run `git submodule update --init` after cloning.

Run the docs site locally:

```bash
cd docs
npm install
npm run dev
```

### Docs sync expectations

When modifying documentation:

1. Update `README.md` if the change affects the package landing page.
2. Navigate into `docs/` and make your changes in `docs/src/`.
3. Commit and push your changes directly inside the `docs/` submodule to the docs repository.
4. After pushing the submodule, go back to the project root and commit the updated submodule reference.

## Pull request expectations

- Explain user-visible behavior changes clearly.
- Mention any config-default changes explicitly.
- Update README/docs examples when command behavior changes.
- Do not weaken tests to make unrelated failures disappear.
