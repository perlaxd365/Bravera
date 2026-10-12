# Brevare — repository guidance

- Stack verified in `composer.json` / `package.json`: PHP ^8.3, Laravel ^13.8, Livewire ^3.6, Volt ^1.7, Flux ^2.20, Blade, Vite ^8, Tailwind CSS ^4; MySQL in production.
- Preserve the existing modular structure under `app/Modules/` and the current Blade/Livewire/Flux UI architecture.
- Make the smallest safe change that solves the requested task. Inspect only relevant files and directly connected code.
- Never scan `.git/`, `vendor/`, `node_modules/`, `public/build/`, logs, caches, uploads, backups, or generated archives unless needed.
- Never read or print `.env`, credentials, payment keys, customer personal data, or production dumps.
- Preserve payment protections in `app/Modules/Payments/` (Culqi, Mercado Pago, Demo), including validation, idempotency, webhook handling, and status transitions.
- For checkout/orders/inventory/auth/database changes, trace the relevant flow and run focused tests first.
- Never run production migrations, destructive SQL, real payment calls, or deployment commands without explicit authorization.
- Load only one relevant `.agents/skills/<skill>/SKILL.md` when a task genuinely needs it; do not load all UI skill references for routine coding.
- Avoid broad refactors, full test suites, repeated searches, full-file dumps, and long explanations unless requested.
- Final response: changed files, focused checks and results, blockers only.
