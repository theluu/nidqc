#!/usr/bin/env bash
#
# Deploy NIDQC lên production từ git.
# Nguyên tắc (cách A): git mang CODE + CONFIG; NỘI DUNG (DB) sống ở prod,
# KHÔNG đi qua git. Deploy chỉ đồng bộ code + config, không ghi đè dữ liệu.
#
# Dùng:
#   ./scripts/deploy.sh            # deploy thường (code + config)
#   ./scripts/deploy.sh --seed     # + seed nội dung baseline (CHỈ chạy lần đầu / prod trống)
#
set -euo pipefail

ROOT="$(cd "$(dirname "$0")/.." && pwd)"
cd "$ROOT"

DRUSH="${DRUSH:-vendor/bin/drush}"
SEED=0
[ "${1:-}" = "--seed" ] && SEED=1

echo "==> 1. Cập nhật code từ git"
git pull --ff-only

echo "==> 2. Cài PHP deps"
composer install --no-dev --optimize-autoloader --no-interaction

echo "==> 3. Drupal: updatedb + import config + cache rebuild"
# drush deploy = updatedb -> config:import -> cache:rebuild -> deploy:hook
"$DRUSH" deploy -y

echo "==> 4. Build frontend Nuxt (SSR)"
cd frontend
npm ci
npm run build
cd "$ROOT"

# Seed nội dung baseline — CHỈ lần đầu (prod chưa có dữ liệu).
# Các lần deploy sau KHÔNG chạy để tránh đụng nội dung admin đã quản lý.
if [ "$SEED" = "1" ]; then
  echo "==> 5. Seed nội dung baseline (lần đầu)"
  "$DRUSH" php:script scripts/import-old-news.php -- --import --with-images
  "$DRUSH" php:script scripts/setup-web-links.php
  "$DRUSH" php:script scripts/setup-home-blocks.php
  "$DRUSH" cr
fi

echo "==> 6. Khởi động lại tiến trình Nuxt SSR"
# Mặc định dùng systemd unit `nidqc-nuxt` (xem docs/deployment/DEPLOYMENT.md).
# Hạ tầng khác (pm2…) thì đặt SSR_RESTART_CMD, ví dụ:
#   SSR_RESTART_CMD="pm2 restart nidqc-nuxt" ./scripts/deploy.sh
SSR_RESTART_CMD="${SSR_RESTART_CMD:-systemctl restart nidqc-nuxt}"
echo "   \$ $SSR_RESTART_CMD"
eval "$SSR_RESTART_CMD"

echo "==> Xong. Nội dung do prod quản lý (admin /admin/content), không đến từ git."
