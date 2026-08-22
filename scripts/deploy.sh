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
# Chạy composer bằng root trong phiên non-interactive thì Composer TỰ TẮT MỌI PLUGIN
# ("Composer plugins have been disabled for safety"). Mất composer/installers nghĩa là
# drupal/core rơi vào vendor/drupal/core thay vì web/core: site vẫn chạy bằng web/core
# cũ, nhưng vendor/composer/installed.php ghi sai đường dẫn nên Drush 13 (tìm root qua
# InstalledVersions::getInstallPath('drupal/core')) không bootstrap được và cả `drush
# --version` cũng chết. Đặt biến này để plugin chạy bình thường.
export COMPOSER_ALLOW_SUPERUSER=1
composer install --no-dev --optimize-autoloader --no-interaction

# Chốt lại: core phải nằm ở web/core. Nếu lệch thì dừng ngay thay vì để deploy chạy
# tiếp rồi chết ở bước drush với thông báo khó hiểu.
CORE_PATH="$(php -r '$d = require "vendor/composer/installed.php"; echo $d["versions"]["drupal/core"]["install_path"] ?? "";')"
case "$CORE_PATH" in
  */web/core) : ;;
  *)
    echo "LỖI: drupal/core đang ở '$CORE_PATH', đáng lẽ phải ở web/core." >&2
    echo "     Chạy: COMPOSER_ALLOW_SUPERUSER=1 composer reinstall drupal/core" >&2
    exit 1
    ;;
esac

echo "==> 3. Drupal: updatedb + import config + cache rebuild"
# drush deploy = updatedb -> config:import -> cache:rebuild -> deploy:hook
"$DRUSH" deploy -y

# Kiểm tra giới hạn tải file của PHP-FPM (KHÔNG phải php CLI).
#
# Field "Ảnh đại diện"/"Ảnh thư viện" đặt max_filesize 10 MB và "Tài liệu đính kèm"
# 20 MB, nhưng Drupal luôn lấy giá trị NHỎ HƠN giữa cấu hình field và php.ini. Khách
# báo lỗi "Không tải được tệp lên" kèm dòng "2 MB limit" (21/08/2026) — đó là
# upload_max_filesize mặc định của PHP, không phải lỗi của Drupal.
#
# Hỏi chính BINARY FPM chứ không grep php.ini: giá trị thật là php.ini gộp với mọi
# file trong conf.d, mà bản vá của site nằm ở conf.d/99-nidqc-upload.ini — grep mỗi
# php.ini sẽ báo động giả 2M ở mọi lần deploy. Cũng không dùng php CLI: CLI là SAPI
# riêng, cấu hình thường rộng hơn hẳn nên xem CLI là bỏ sót đúng cái đang gây lỗi.
#
# Hứng trọn output vào biến RỒI mới lọc, không nối pipe vào awk/grep có `exit`/`-m1`:
# lệnh lọc đóng pipe sớm làm php-fpm dính SIGPIPE, gặp `set -o pipefail` là cả script
# chết ngay giữa deploy — và chết KHÔNG ổn định, tuỳ output có lọt vừa pipe buffer
# hay không. Đã dính đúng lỗi này một lần.
#
# Cả khối gọi qua `|| true`: đây là bước CHẨN ĐOÁN, hỏng nó không được phép chặn
# deploy. php.ini nằm ngoài git nên chỉ cảnh báo chứ không tự sửa.
check_fpm_upload_limit() {
  local php_mm fpm_bin fpm_info value mb
  php_mm="$(php -r 'echo PHP_MAJOR_VERSION.".".PHP_MINOR_VERSION;')"
  fpm_bin="$(command -v "php-fpm$php_mm" || command -v php-fpm || true)"

  if [ -z "$fpm_bin" ]; then
    echo "   ?  Không tìm thấy binary php-fpm — kiểm tra tay bằng phpinfo()."
    return 0
  fi

  fpm_info="$("$fpm_bin" -i 2>/dev/null || true)"
  value="$(printf '%s\n' "$fpm_info" | sed -n 's/^upload_max_filesize *=> *\([^ ]*\).*/\1/p' | tail -1)"

  case "$value" in
    '')    echo "   ?  Không đọc được upload_max_filesize từ $fpm_bin." ;;
    *G|*g) echo "   ✓  upload_max_filesize = $value" ;;
    *)
      mb="$(printf '%s' "$value" | tr -cd '0-9')"
      if [ "${mb:-0}" -lt 24 ]; then
        echo "   ⚠  upload_max_filesize = $value (cần >= 24M)."
        echo "      Biên tập viên sẽ KHÔNG tải được ảnh lớn hơn mức này."
        echo "      Sửa /etc/php/$php_mm/fpm/conf.d/99-nidqc-upload.ini rồi"
        echo "      'systemctl restart php$php_mm-fpm':"
        echo "        upload_max_filesize = 24M"
        echo "        post_max_size = 32M"
        echo "        memory_limit = 512M"
      else
        echo "   ✓  upload_max_filesize = $value"
      fi
      ;;
  esac
}

echo "==> 3b. Kiểm tra giới hạn upload của PHP-FPM"
check_fpm_upload_limit || true

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

# Xoá cache HTML của Nitro TRƯỚC khi restart.
#
# routeRules '/**': { swr: 600 } lưu HTML đã render xuống ĐĨA (nuxt.config.ts đặt
# storage.cache driver 'fs'), nên cache SỐNG QUA restart daemon: build xong, restart
# xong, mà trang vẫn trả bản cũ tới hết TTL. Đã dính đúng bẫy này khi deploy đợt
# feedback 21/08 — cả 8 thay đổi giao diện đều "không lên".
#
# Đường dẫn phải khớp NUXT_CACHE_DIR lúc build (mặc định /tmp/nidqc-nitro-cache).
echo "==> 5b. Xoá cache HTML đã render (SWR)"
NITRO_CACHE_DIR="${NUXT_CACHE_DIR:-/tmp/nidqc-nitro-cache}"
rm -rf "$NITRO_CACHE_DIR"
echo "   đã xoá $NITRO_CACHE_DIR"

echo "==> 6. Khởi động lại tiến trình Nuxt SSR"
# Mặc định dùng systemd unit `nidqc-nuxt` (xem docs/deployment/DEPLOYMENT.md).
# Hạ tầng khác (pm2…) thì đặt SSR_RESTART_CMD, ví dụ:
#   SSR_RESTART_CMD="pm2 restart nidqc-nuxt" ./scripts/deploy.sh
SSR_RESTART_CMD="${SSR_RESTART_CMD:-systemctl restart nidqc-nuxt}"
echo "   \$ $SSR_RESTART_CMD"
eval "$SSR_RESTART_CMD"

echo "==> Xong. Nội dung do prod quản lý (admin /admin/content), không đến từ git."
