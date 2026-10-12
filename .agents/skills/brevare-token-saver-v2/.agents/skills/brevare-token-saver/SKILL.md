---
name: brevare-token-saver
description: Minimize DeepSeek API token usage while safely coding in the Brevare ecommerce repository. Use for Laravel, Livewire, Blade, payment, catalog, checkout, SEO, tests, debugging, and refactoring tasks.
---

# Brevare Token Saver

## Objective
Solve the requested task with the least necessary context, tool calls, repeated work, and output tokens. Correctness, security, and data integrity always take priority over token savings.

## Repository facts
- Product: Brevare ecommerce website.
- Runtime: PHP ^8.3, Laravel ^13.8, Livewire ^3.6, Livewire Volt ^1.7, Flux ^2.20.
- Front end: Blade, Vite ^8, Tailwind CSS ^4, Axios, SweetAlert2.
- Database configured for MySQL in production; tests may use SQLite.
- Payment code is modular: `app/Modules/Payments/`; gateways include Culqi, Mercado Pago, and Demo. Shared service: `PaymentService`.
- Business modules include Coupon, Discounts, Dropshipping, Location, Ordering, Payments, Product, and Shipping.
- Existing UI skills live under `.agents/skills/`. Invoke/read a specific UI skill only when the task actually needs it; never load all skill references for an ordinary task.
- `docs/LANZAMIENTO.md` describes GoDaddy production deployment. Read only the relevant subsection when deployment is in scope.

## Default execution policy
1. Convert the request into one specific outcome. Ask a question only if a missing fact blocks safe implementation.
2. Start with `git status --short` and a narrow search for the relevant symbol or route. Do not print the full directory tree or full git status history.
3. Read the nearest `AGENTS.md`. Use existing repository conventions; do not re-read it if already present in the current context and unchanged.
4. Trace only the smallest relevant vertical slice: route → Livewire/controller → service/model → view/test as applicable. Do not inspect unrelated modules.
5. Prefer one focused search and small excerpts. Limit results (for example `Select-Object -First 30`, `head -n 60`) and inspect additional results only when needed.
6. Make the smallest coherent patch. Avoid unrelated refactors, formatting churn, broad renames, speculative improvements, and new dependencies unless requested.
7. Run the narrowest relevant test first. Do not run the full suite or build unless scope/risk requires it or the user asks.
8. Reuse results already collected. Do not repeat successful searches, commands, tests, or explanations unless code changed or the previous result is stale.
9. Do not browse the web for routine coding. Browse only for current provider/API behavior, version-specific issues, or external facts that cannot be resolved from the repository.
10. Finish with a short report: changed files, checks run/result, and any unresolved blocker. Do not paste full diffs unless asked.

## Context and token controls
- Never dump entire source files, route lists, schemas, test output, lockfiles, or logs into context.
- Prefer targeted commands:
  - `rg -n "Symbol|Route|ErrorText" app routes resources tests`
  - `git diff -- path/to/file`
  - `php artisan test --compact --filter=RelevantTest`
  - `vendor/bin/pint --test path/to/changed.php` when applicable.
- For logs, inspect only the first root-cause exception and a small tail around it.
- For code, read only the relevant method plus necessary imports and directly called methods.
- Exclude generated/dependency/data folders from exploration unless required: `.git/`, `vendor/`, `node_modules/`, `public/build/`, `storage/logs/`, caches, uploads, backups, exported databases, and generated archives.
- Never open or print `.env`. If configuration is relevant, inspect variable names in `.env.example` or config files, or report only whether a key is configured without showing its value.
- Do not run broad `find`, `dir /s`, recursive `Get-Content`, or full repository scans when a targeted `rg`/file list suffices.
- Do not create a project-wide summary unless repeated tasks demonstrate it is needed. If needed, keep `PROJECT_CONTEXT.md` concise (target ~1 page) and update only relevant facts.
- Use a new conversation for a materially different task. Start the new conversation with a short handoff: goal, files, decisions, last check.
- Keep stable instructions and prompt prefixes stable to improve DeepSeek cache reuse where supported. Do not claim caching or savings unless usage data confirms it.
- Ask the user to share only sanitized, minimal excerpts. Never ask for secrets, production customer records, or full dumps.

## Task-specific rules

### UI and storefront
- Preserve the existing Blade + Livewire + Flux + Tailwind architecture.
- Reuse existing `resources/views/components/brevare/` components and current patterns before creating components.
- Do not introduce React, Vue, Bootstrap, or AdminLTE unless explicitly requested and consistent with the repository.
- For UI-only work, inspect the target view/component and directly related styles/scripts; do not read backend modules unless behavior requires it.
- Load one relevant `.agents/skills/<skill>/SKILL.md` only when the task maps clearly to it (for example accessibility, forms, polish, or animation). Do not load all 33 skills or their references.

### Checkout, payments, orders, and inventory
- Treat these as high-risk paths. Trace the relevant route, component/controller, `PaymentService`, gateway, webhook, model, and focused tests only as needed.
- Preserve signature validation, amount/currency checks, idempotency, transaction boundaries, status transitions, error handling, and duplicate-webhook protection.
- Do not call real payment APIs or make real charges during tests. Use fakes/stubs and existing payment tests.
- Do not change gateway selection, credentials, or production payment configuration unless explicitly requested.
- For payment changes, inspect focused tests such as `tests/Feature/Payments/` and checkout tests only as relevant.

### Database and production
- Never run production migrations, seeders, destructive SQL, bulk updates/deletes, cache-clearing, or deployment commands without explicit authorization and a scope check.
- Never use production data for local tests. Prefer isolated test DBs and existing factories.
- Before schema changes, inspect only the relevant migration, model relationships, and tests.
- Deployment guidance is in `docs/LANZAMIENTO.md`; do not read the entire guide for a single deployment question.

### Credentials and privacy
- Never print secrets, `.env` values, API keys, payment tokens, SMTP passwords, database credentials, session cookies, or customer personal data.
- If a provided archive contains `.env`, credentials, `.git`, production dumps, or private uploads, do not inspect secret values. Warn the user and recommend rotating exposed credentials if the archive was shared outside their trusted environment.
- Never commit or include `.env`, local database files, logs, customer exports, or credentials in generated patches/archives.

## Completion checklist
- Scope limited to the requested change.
- No unrelated files changed.
- Relevant focused check run, or a brief reason why it was not run.
- No secrets or personal data exposed.
- Final response concise: `Changed`, `Verified`, `Not verified/blockers`.
