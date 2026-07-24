---
id: TASK-012
title: Hiển thị số người đang trực tuyến
status: review
step: 7
owner: Codex
reviewer: <chưa gán — KHÔNG được trùng owner>
created: 2026-07-25

schema_change: false
new_package: false
config_change: true

allowed_files:
  - docs/api/API_CONTRACT.md
  - config/sync/core.extension.yml
  - web/modules/custom/nidqc_online/**
  - web/themes/custom/nidqc/templates/layout/page.html.twig
  - web/themes/custom/nidqc/templates/layout/page--front.html.twig
  - web/themes/custom/nidqc/css/layout.css
  - frontend/layouts/default.vue
  - frontend/assets/css/main.css
  - frontend/composables/useOnlineCounter.ts
  - tasks/TASK-012.md
  - CHANGELOG.md
---

# TASK-012 — Hiển thị số người đang trực tuyến

## 1. Mục tiêu

Hiển thị số phiên truy cập hoạt động trong 5 phút gần nhất tại thanh cuối footer,
trên cả giao diện Drupal Twig và Nuxt SSR. Giá trị ban đầu có trong HTML, sau đó
được cập nhật định kỳ khi JavaScript hoạt động.

## 2. Quyết định thiết kế

- Đặt cạnh bản quyền trong thanh cuối footer: hiện diện toàn site, dễ tìm nhưng
  không cạnh tranh với nội dung nghiệp vụ.
- Dùng chấm trạng thái xanh kèm chữ “Đang trực tuyến” để không truyền đạt trạng
  thái chỉ bằng màu sắc.
- Desktop trình bày hai đầu thanh; mobile xếp dọc, căn giữa.

## 3. API contract

Phải chốt `docs/api/API_CONTRACT.md` trước khi viết backend/frontend. Endpoint chỉ
đếm session Drupal có hoạt động trong 300 giây; không lưu hoặc trả IP, user-agent,
fingerprint hay danh tính.

## 4. Yêu cầu

- SSR hiển thị số ban đầu; lỗi API vẫn hiển thị nhãn an toàn, không làm hỏng footer.
- Client gửi heartbeat tối đa mỗi 60 giây, dừng khi tab ẩn và cập nhật khi tab hiện lại.
- POST heartbeat kiểm CSRF; mọi response `no-store`.
- Không đổi schema, không cài package, không dùng dịch vụ ngoài.
- Có `aria-live="polite"` nhưng không thông báo lặp khi số không đổi.

## 5. Tiêu chí chấp nhận

- [x] Contract được cập nhật trước code.
- [x] Anonymous gọi GET count thành công; POST thiếu/sai CSRF trả 403.
- [x] Hai request cùng session chỉ được tính là một người.
- [x] HTML SSR chứa nhãn và giá trị đếm.
- [x] Responsive đạt tại 390px, 768px và 1440px; không tràn ngang.
- [x] Build và PHP syntax đạt; không có lỗi watchdog mới.
- [ ] Security review có người khác ký.

## 6. Cách verify

```bash
ddev drush en nidqc_online -y
ddev drush cr
curl -ks https://nidqc.ddev.site/api/v1/online
ddev exec --dir /var/www/html/frontend npm run build
ddev exec php -l web/modules/custom/nidqc_online/src/Controller/OnlineController.php
ddev drush watchdog:show --severity=3
git diff --check
```

Kiểm bằng trình duyệt tại 390px, 768px và 1440px; xem HTML SSR bằng `curl`, tắt
JavaScript để xác nhận giá trị ban đầu vẫn hiển thị.

## 7. Bảo mật và riêng tư

- Không ghi IP, user-agent, URL đang xem hoặc định danh tự tạo.
- Dùng session Drupal hiện có và token CSRF core.
- GET chỉ đọc; POST chỉ cập nhật timestamp session hiện tại.
- Không log session ID hoặc token.

## 8. Nhật ký

| Ngày | Agent | Nội dung |
|---|---|---|
| 2026-07-25 | Codex | Người duyệt chấp thuận tạo TASK-012 và phạm vi triển khai. |
| 2026-07-25 | Codex | Chốt API contract trước code; triển khai Drupal module, Twig fallback, Nuxt SSR/client heartbeat và giao diện responsive. |
| 2026-07-25 | Codex | API, build, PHP syntax, config, SSR, responsive và diff check đạt. Chuyển review; chờ người khác ký security review/UAT. |

## 9. Output verify

```text
PHP syntax:
  No syntax errors detected (nidqc_online.module, OnlineCounter.php,
  OnlineController.php).

API:
  GET /api/v1/online
  -> 200 {"data":{"count":1,"window_seconds":300}}
  POST /api/v1/online/heartbeat thiếu CSRF
  -> 403 CSRF_TOKEN_INVALID
  POST hợp lệ, gọi hai lần cùng cookie
  -> cả hai lần count = 1
  GET /api/v1/online?unexpected=1
  -> 400 INVALID_PARAMETER

Frontend:
  ddev exec --dir /var/www/html/frontend npm run build
  -> Build complete
  npm run lint
  -> không chạy được: package.json không khai báo script "lint"

Drupal/config:
  ddev drush config:status
  -> No differences between DB and sync directory.
  ddev drush watchdog:show --severity=3
  -> không có lỗi mới từ TASK-012; các lỗi hiển thị đều cũ, mới nhất ID 466
     ngày 19/07.

Visual:
  Playwright Chromium, full-page tại 390×844, 768×1024, 1440×1000.
  Footer không tràn ngang; desktop chia bản quyền/counter hai đầu, mobile xếp
  dọc và căn giữa.

Clean checks:
  git diff --check -> pass
  rg dpm/var_dump/console.log/TODO trong file mới -> không có output

PHPCS:
  ddev composer phpcs
  -> fail do dự án không khai báo Composer command "phpcs".
```
