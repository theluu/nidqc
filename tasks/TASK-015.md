---
id: TASK-015
title: Chuẩn hoá ngày Tin tức, submenu và trang tài khoản
status: review
step: 7
owner: Codex
reviewer: <chưa gán — KHÔNG được trùng owner>
created: 2026-07-26

schema_change: true
new_package: false
config_change: true

allowed_files:
  - docs/database/ENTITY_MAPPING.md
  - config/sync/field.field.node.news.field_date.yml
  - config/sync/field.storage.node.field_date.yml
  - config/sync/core.entity_form_display.node.news.default.yml
  - config/sync/core.entity_view_display.node.news.default.yml
  - web/modules/custom/nidqc_content/**
  - scripts/import-content.php
  - scripts/import-old-news.php
  - web/themes/custom/nidqc/nidqc.theme
  - web/themes/custom/nidqc/css/user.css
  - frontend/layouts/default.vue
  - frontend/pages/index.vue
  - frontend/pages/tin-tuc/index.vue
  - frontend/pages/[slug].vue
  - tasks/TASK-015.md
  - CHANGELOG.md
---

# TASK-015 — Chuẩn hoá ngày Tin tức, submenu và trang tài khoản

## Mục tiêu

1. Bỏ `field_date` khỏi Tin tức, chuyển dữ liệu/ngõ đọc sang `created`.
2. Xác minh và sửa luồng edit tài khoản không còn 404.
3. Submenu Tin tức mở đúng tab/chuyên mục tương ứng.
4. Trang `/user/login` không hiện breadcrumb “Home” và dùng navigation đồng nhất trang chủ.

Người dùng phê duyệt trực tiếp thay đổi schema ngày 2026-07-26.

## Tiêu chí

- [x] Không còn `field_date` trong schema/form/display hoặc code runtime.
- [x] Ngày cũ được bảo toàn trong `created`.
- [x] User đăng nhập sửa được chính tài khoản.
- [x] Sáu submenu lọc đúng sáu chuyên mục.
- [x] Login page không có breadcrumb và navigation khớp trang chủ.
- [ ] Security review/UAT có người khác ký.

## Kết quả kiểm tra

```text
Schema/data:
  nidqc_content_update_11001 -> success
  field_date=removed
  sample_created=29/05/2026
  drush config:status -> No differences

User:
  content_writer self-edit access=yes
  authenticated GET /user/5/edit -> HTTP 200

Menu:
  Thông báo=/tin-tuc?cat=thong-bao
  Tin hoạt động=/tin-tuc?cat=tin-hoat-dong
  Mua sắm...=/tin-tuc?cat=mua-sam-dau-thau
  Đào tạo=/tin-tuc?cat=dao-tao
  Hội nghị...=/tin-tuc?cat=hoi-nghi-hoi-thao
  Tuyển dụng=/tin-tuc?cat=tuyen-dung
  SSR ?cat=thong-bao -> button Thông báo is-active

Login:
  LOGIN_BREADCRUMB=no
  LOGIN_NAV=yes

Build/lint:
  Nuxt production build -> exit 0, Build complete
  PHP syntax -> pass
  git diff --check -> pass

Visual QA:
  Không chạy được: phiên Browser tích hợp không khả dụng trong lượt này.
```
