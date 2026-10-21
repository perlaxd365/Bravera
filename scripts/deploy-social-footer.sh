#!/usr/bin/env bash

set -euo pipefail

ROOT="${HOME}/Bravera"
PUBLIC_ROOT="${HOME}/public_html"
BRANCH="recuperar-cambios-godaddy"
STAMP="$(date +%Y%m%d-%H%M%S)"
BACKUP="${HOME}/backups/social-footer-${STAMP}"

cd "${ROOT}"

mkdir -p "${BACKUP}"

for path in \
  "config/seo.php" \
  "resources/views/store/layouts/partials/footer.blade.php" \
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
printf 'Redes sociales y assets desplegados.\n'
