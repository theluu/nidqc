# Deployment — NIDQC

> ⚠️ **Hạ tầng production chưa được xác định.** Tài liệu này mô tả quy trình chuẩn và những
> gì **phải chốt** trước lần deploy đầu. Không deploy khi §1 còn câu hỏi chưa trả lời.

---

## 1. Phải chốt trước lần deploy đầu

| Câu hỏi | Vì sao chặn |
|---|---|
| Máy chủ production ở đâu? Ai quản trị? | Site nhà nước — thường có yêu cầu về hạ tầng đặt trong nước |
| Có staging không? | UAT bắt buộc chạy trên staging |
| Quy trình sao lưu? Ai giữ? | Không có backup = không có rollback |
| Chứng chỉ SSL do ai cấp? | HTTPS bắt buộc |
| CI/CD hay deploy tay? | Quyết định §4 |
| Ai được quyền deploy? | Không phải ai cũng được |
| Cửa sổ bảo trì? | Site cơ quan — không tự ý downtime giờ hành chính |

## 2. Môi trường

| Môi trường | Mục đích | Ghi chú |
|---|---|---|
| Local | Dev | DDEV — `ddev start` |
| Staging | UAT | ❓ chưa có — **cần** |
| Production | Thật | ❓ chưa xác định |

### Về DDEV và docker-compose

Dự án dùng **DDEV** cho local (`.ddev/config.yaml`: nginx-fpm, PHP 8.3, MariaDB 11.8).

> **Không thêm `docker-compose.yml` ở root.** DDEV đã sinh và quản lý compose file riêng.
> Thêm file thứ hai tạo hai nguồn sự thật xung đột — chỉnh một chỗ, chỗ kia im lặng khác đi.
> Cần dịch vụ thêm → dùng `.ddev/docker-compose.*.yaml` theo đúng cơ chế của DDEV.

Production **không** dùng DDEV (DDEV là công cụ dev). Hạ tầng production chốt riêng.

## 3. Build frontend

**Production không chạy Node.** Vite build ra tĩnh:

```bash
cd frontend
npm ci
npm run build      # → web/themes/custom/nidqc/dist/
```

Chốt một trong hai (**chưa quyết**):
- **(a)** Commit `dist/` vào git — deploy đơn giản, nhưng diff bẩn và dễ xung đột.
- **(b)** Build trong CI — sạch hơn, nhưng cần CI.

## 4. Quy trình deploy

```bash
# 1. Bật bảo trì
drush state:set system.maintenance_mode 1

# 2. SAO LƯU TRƯỚC — không có bước này thì không có rollback
drush sql:dump --gzip --result-file=/backup/nidqc-$(date +%F-%H%M).sql.gz
tar czf /backup/files-$(date +%F-%H%M).tar.gz web/sites/default/files/

# 3. Lấy code
git pull origin main

# 4. Dependency (KHÔNG chạy composer update trên production)
composer install --no-dev --optimize-autoloader

# 5. Cập nhật DB + config
drush updb -y

# ⚠️ Lần deploy ĐẦU TIÊN trên môi trường mới: phải khớp UUID site trước, nếu không
# cim thất bại với "Site UUID in source storage does not match the target storage".
# drush config:set system.site uuid $(grep '^uuid:' ../config/sync/system.site.yml | cut -d' ' -f2) -y

drush cim -y

# 6. Xoá cache
drush cr

# 7. Tắt bảo trì
drush state:set system.maintenance_mode 0
```

> `composer install`, **không bao giờ** `composer update` trên production.
> `update` đọc `composer.json` và có thể nâng phiên bản ngoài ý muốn. `install` bám `composer.lock`.

> **Script gói sẵn:** `scripts/deploy.sh` (deploy thường) và `scripts/deploy.sh --seed`
> (seed nội dung baseline lần đầu). Nhớ chỉnh dòng restart Nuxt SSR cho hạ tầng prod.

### Chiến lược nội dung & DB (cách A — DB KHÔNG nằm trong git)

Git chỉ mang **code + cấu trúc** (`config/sync`: node type, field, pathauto, display…).
**Nội dung** (node tin tức, web_link, expertise, office, home_block…, tài khoản, ảnh)
**chỉ sống trong DB production** và tồn tại qua mọi lần deploy — deploy không ghi đè.

| Thứ | Nguồn | Lên prod bằng |
|-----|-------|---------------|
| Code, frontend | git | `git pull` + `npm run build` |
| Cấu trúc backend | git (`config/sync`) | `drush deploy` / `drush cim` |
| Nội dung (DB), ảnh | DB prod (không qua git) | migrate/seed **1 lần**, sau đó quản trị qua `/admin` |

**Nạp nội dung lần đầu** — chọn 1:
- Seed từ script trong git: `scripts/deploy.sh --seed` (chạy `import-old-news`, `setup-web-links`, `setup-home-blocks`).
- Hoặc migrate 1 lần từ dev: `ddev export-db` → chuyển qua SSH → `drush sql:cli` trên prod.

**Backup DB prod** (thay cho "DB trên git"): cron `drush sql:dump --gzip` phía server, đẩy lên object storage.
Không commit dump SQL vào git — `.gitignore` đã chặn `*.sql` / `*.sql.gz`.

## 5. Trước khi deploy

- [ ] UAT đã ký — `docs/testing/UAT_CHECKLIST.md`
- [ ] Deploy từ `main`, đã qua review
- [ ] `docs/DEFINITION_OF_DONE.md` đạt
- [ ] **Đã sao lưu DB và files** — và **đã thử phục hồi ít nhất một lần**
- [ ] Đã đọc `docs/deployment/ROLLBACK.md`
- [ ] Có người trực sau deploy
- [ ] Ngoài giờ cao điểm

> "Đã sao lưu" mà chưa từng thử phục hồi thì chưa biết là có backup hay không.

## 6. Sau khi deploy

- [ ] Trang chủ vào được
- [ ] Đăng nhập admin được
- [ ] Vài trang bất kỳ hiển thị đúng, tiếng Việt đúng dấu
- [ ] Island hoạt động (menu, FAQ, lọc)
- [ ] `curl -s https://nidqc.gov.vn/tin-tuc | grep "<tiêu đề>"` → **nội dung có trong HTML thô**
- [ ] `/sitemap.xml` được
- [ ] **URL cũ vẫn redirect đúng**
- [ ] `drush watchdog:show --severity=3` không có lỗi mới (`3` = Error; dùng số, xem `DEFINITION_OF_DONE.md`)
- [ ] HTTPS + header bảo mật đúng (`SECURITY_POLICY.md` §10)

## 7. Cấu hình production bắt buộc

- [ ] `error_level: hide` — không hiện lỗi PHP ra ngoài
- [ ] Cache bật, CSS/JS aggregate bật
- [ ] `settings.local.php` **không** nằm trong git
- [ ] `hash_salt` riêng cho production
- [ ] reCAPTCHA v3 cho form liên hệ:
      `NIDQC_RECAPTCHA_SITE_KEY`, `NIDQC_RECAPTCHA_SECRET`, `NIDQC_RECAPTCHA_MIN_SCORE`
      đặt ngoài git; `NIDQC_RECAPTCHA_BYPASS` không được bật trên production.
- [ ] SMTP cho form liên hệ dùng Drupal `symfony_mailer`; credential thật đặt bằng config override
      trong `settings.local.php` hoặc cơ chế secret của hạ tầng, không commit vào `config/sync`.
- [ ] `NIDQC_CONTACT_ADMIN_EMAIL` trỏ tới hộp thư admin thật của Viện.
- [ ] CSP production cho phép ngoại lệ tối thiểu cho reCAPTCHA v3 và Google Maps nếu các chức năng
      này được bật: script/frame/connect tới domain Google cần thiết.
- [ ] nginx **không thực thi PHP** trong `sites/default/files/`
      (⚠️ `.htaccess` không có tác dụng với nginx — phải cấu hình thủ công)
- [ ] Tài khoản `admin` mật khẩu mạnh, không dùng hằng ngày

## 8. Có sự cố

→ `docs/deployment/ROLLBACK.md`. Đừng sửa vá trực tiếp trên production.
