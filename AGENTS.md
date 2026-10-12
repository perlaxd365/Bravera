# Brevare — concise repository guidance

- Stack: Laravel 13, Livewire 3, Blade, Vite, MySQL.
- Make the smallest safe change that solves the requested task.
- Inspect only relevant files; do not scan `vendor/`, `node_modules/`, `public/build/`, `.git/`, caches, logs, uploads, or backups unless necessary.
- Never print `.env` values, API keys, credentials, or customer personal data.
- Preserve current behavior, architecture, responsive UI, and the existing Culqi payment flow unless explicitly asked to change them.
- For checkout, payments, orders, stock, auth, or customer data, trace the full relevant flow before editing.
- Never run destructive database commands, production migrations, or real payment tests without explicit authorization.
- Run focused tests/checks first. Avoid repeating searches, commands, or tests whose results remain valid.
- Keep final responses brief: changed files, checks run, and blockers.
