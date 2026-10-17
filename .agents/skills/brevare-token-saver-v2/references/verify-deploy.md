# Verify and deploy

## Focused checks

Run the payment/checkout suites first:

```powershell
php artisan test tests/Feature/Payments tests/Feature/Store/CheckoutTest.php tests/Feature/Admin/RefundManagementTest.php
```

Add `tests/Feature/Payments/CulqiGatewayTest.php` when touching Culqi antifraud,
charges or status mapping.

Build and refresh compiled views:

```powershell
npm run build
php artisan view:clear; php artisan view:cache
```

Do not hardcode asset hashes in documentation or tests; read
`public/build/manifest.json`.

## Production topology

- Laravel root: `~/Bravera`.
- Public document root: `~/public_html`.
- Both `~/Bravera/public/build/` and `~/public_html/build/` must contain the
  same compiled assets.
- The domain is `https://brevare.com`.
- SSH/SCP host and user are operational details; use the current credentials
  from the environment, never commit them.

## Deploy checklist

1. `git status` — only intended files.
2. Run the focused tests above.
3. `npm run build`; `php artisan view:clear; php artisan view:cache`.
4. Commit with a scoped message and push
   `recuperar-cambios-godaddy`.
5. SSH to the host:

```bash
cd ~/Bravera
git pull origin recuperar-cambios-godaddy
php artisan optimize:clear
php artisan optimize
```

6. Upload assets to both locations:

```bash
scp -r public/build/. <host>:~/Bravera/public/build/
scp -r public/build/. <host>:~/public_html/build/
```

7. Verify:

```bash
cd ~/Bravera
grep -o 'assets/app-[^"]*' public/build/manifest.json
curl -s -o /dev/null -w '%{http_code}' https://brevare.com/build/assets/<css>
curl -s -o /dev/null -w '%{http_code}' https://brevare.com/build/assets/<js>
curl -s -o /dev/null -w '%{http_code}' https://brevare.com/
```

All should be `200`.

8. If Blade views changed, confirm the compiled view contains the new marker:

```bash
grep -l 'mp-modal-card-brick' storage/framework/views/*.php | wc -l
grep -l 'data-culqi-session' storage/framework/views/*.php | wc -l
```

## Cache notes

- `php artisan optimize:clear` clears config/route/view/event caches.
- `php artisan optimize` rebuilds them.
- If assets 404, check `public/build/manifest.json` and both build directories.
- If a Blade change does not appear, run `view:clear` + `view:cache`.
- If PHP changes do not appear, check OPcache/FPM revalidation on the host.

## Safety

- Never print `.env`, access tokens, webhook secrets, card tokens or customer
  data. Verify credentials by boolean/prefix only.
- Do not create real charges or refunds without explicit authorization.
- Do not run production migrations or destructive SQL without authorization.
- Do not refund the verified test payment `1328465656`.

## Rollback

- Revert the last commit locally (`git revert <sha>`) and push; or check out the
  previous known-good commit and redeploy.
- Re-upload the build from that commit to both build directories.
- Run `php artisan optimize:clear` and `php artisan optimize`.
- Verify the site and assets again.
