# Working style (token-efficient, safe)

This preserves the original Brevare token-saver execution policy.

## Default execution policy

1. Convert the request into one concrete outcome. Ask only if a missing fact
   blocks safe implementation.
2. Start with `git status --short` and one narrow search for the relevant symbol
   or route. Do not print the directory tree or long git history.
3. Use the nearest `AGENTS.md`; do not re-read unchanged instructions.
4. Trace the smallest vertical slice: route → Livewire/controller →
   service/model → view/test.
5. Prefer one focused search and small excerpts (`Select-Object -First 30`,
   `head -n 60`); expand only when needed.
6. Make the smallest coherent patch. No unrelated refactors, renames,
   formatting churn or new dependencies.
7. Run the narrowest relevant test first; the full suite only when risk or the
   user requires it.
8. Reuse prior results; do not repeat successful searches/tests unless code
   changed or the result is stale.
9. Browse only for current provider/API behavior or version-specific facts that
   the repository cannot answer.
10. Finish with changed files, checks and blockers. No full diffs unless asked.

## Context controls

- Never dump entire files, route lists, schemas, lockfiles, logs or test output.
- Targeted commands:
  - `rg -n "Symbol|Route|ErrorText" app routes resources tests`
  - `git diff -- path/to/file`
  - `php artisan test --compact --filter=RelevantTest`
  - `vendor/bin/pint --test path/to/changed.php`
- Exclude `.git/`, `vendor/`, `node_modules/`, `public/build/`, `storage/logs/`,
  caches, uploads, backups and generated archives unless required.
- Never open or print `.env`. Inspect `.env.example`/config keys or report only
  whether a value is configured.
- Do not run broad recursive scans when `rg`/`rg --files` suffices.

## Payment/checkout risk rules

- Trace route → component → `PaymentService` → gateway → webhook → model →
  focused tests.
- Always pass the gateway explicitly; never rely on `default_gateway` in a
  gateway-specific flow.
- Preserve `payment_intent` isolation, stock release, cart restore, webhook
  verification and status transitions.
- Do not create real charges, refunds, production migrations or destructive SQL
  without explicit authorization.

## UI rules

- Preserve Blade + Livewire + Flux + Tailwind.
- Reuse `resources/views/components/brevare/` before creating components.
- Do not introduce React/Vue/Bootstrap/AdminLTE unless requested.
- Load one focused UI skill only when the task clearly needs it; do not load all
  UI skill references for routine work.

## Reporting

- Short final response: changed files, focused checks/results, blockers.
- No secrets, card data, customer data or full production logs.
