#!/usr/bin/env bash

set -euo pipefail

ROOT="${HOME}/Bravera"
PUBLIC_ROOT="${HOME}/public_html"
BRANCH="recuperar-cambios-godaddy"
STAMP="$(date +%Y%m%d-%H%M%S)"
BACKUP="${HOME}/backups/latest-ui-${STAMP}"

cd "${ROOT}"

mkdir -p "${BACKUP}"

for path in \
  "config/seo.php" \
  "resources/views/store/layouts/partials/footer.blade.php" \
  "app/Livewire/Admin/Dashboard/Index.php" \
  "resources/views/livewire/admin/dashboard/index.blade.php" \
  "app/Livewire/Admin/Catalog/Products/Form.php" \
  "app/Modules/Product/Forms/SupplierVariantForm.php" \
  "app/Http/Middleware/ForceCanonicalHost.php" \
  "app/Http/Middleware/TrackStorefrontView.php" \
  "app/Models/PageView.php" \
  "app/Services/GoogleAnalyticsService.php" \
  "bootstrap/app.php" \
  "config/services.php" \
  "database/migrations/2026_10_21_000001_create_page_views_table.php" \
  "resources/views/components/brevare/cookie-consent.blade.php" \
  "resources/views/partials/legal/privacy.blade.php" \
  "public/images/social" \
  "public/build"
do
  if [ -e "${path}" ]; then
    mkdir -p "${BACKUP}/$(dirname "${path}")"
    cp -a "${path}" "${BACKUP}/${path}"
  fi
done

git fetch origin "${BRANCH}"

git restore --source="origin/${BRANCH}" --worktree -- \
  config/seo.php \
  resources/views/store/layouts/partials/footer.blade.php \
  app/Livewire/Admin/Dashboard/Index.php \
  resources/views/livewire/admin/dashboard/index.blade.php \
  app/Livewire/Admin/Catalog/Products/Form.php \
  app/Modules/Product/Forms/SupplierVariantForm.php \
  app/Http/Middleware/ForceCanonicalHost.php \
  app/Http/Middleware/TrackStorefrontView.php \
  app/Models/PageView.php \
  app/Services/GoogleAnalyticsService.php \
  bootstrap/app.php \
  config/services.php \
  database/migrations/2026_10_21_000001_create_page_views_table.php \
  resources/views/components/brevare/cookie-consent.blade.php \
  resources/views/partials/legal/privacy.blade.php \
  public/images/social \
  public/build

mkdir -p "${PUBLIC_ROOT}/images/social" "${PUBLIC_ROOT}/build"
cp -a public/images/social/. "${PUBLIC_ROOT}/images/social/"
cp -a public/build/. "${PUBLIC_ROOT}/build/"

php artisan optimize:clear
php artisan optimize
php artisan view:clear
php artisan view:cache

printf 'Respaldo: %s\n' "${BACKUP}"
printf 'Dashboard, medición local y conexión GA4 desplegados.\n'
printf 'Migración pendiente si page_views no existe: php artisan migrate --force\n'
