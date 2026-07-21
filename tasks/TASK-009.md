---
id: TASK-009
title: Thêm contact form reCAPTCHA v3, lưu backend và gửi email
status: review          # đã thực thi 2026-07-18, chờ NGƯỜI review/UAT
step: 7                 # Test
owner: Codex
reviewer: TBD
created: 2026-07-18

schema_change: true
new_package: false
config_change: true

allowed_files:
  - tasks/TASK-009.md
  - docs/api/API_CONTRACT.md
  - docs/database/ENTITY_MAPPING.md
  - docs/database/DATABASE_SCHEMA.md
  - docs/deployment/DEPLOYMENT.md
  - CHANGELOG.md
  - .ddev/config.yaml
  - .ddev/nginx_full/nginx-site.conf
  - frontend/nuxt.config.ts
  - frontend/pages/lien-he.vue
  - frontend/composables/useContactApi.ts
  - frontend/composables/useRecaptchaV3.ts
  - web/modules/custom/nidqc_contact/nidqc_contact.info.yml
  - web/modules/custom/nidqc_contact/nidqc_contact.routing.yml
  - web/modules/custom/nidqc_contact/nidqc_contact.services.yml
  - web/modules/custom/nidqc_contact/nidqc_contact.module
  - web/modules/custom/nidqc_contact/config/install/node.type.contact_submission.yml
  - web/modules/custom/nidqc_contact/config/install/field.storage.node.field_contact_*.yml
  - web/modules/custom/nidqc_contact/config/install/field.field.node.contact_submission.field_contact_*.yml
  - web/modules/custom/nidqc_contact/src/Controller/ContactController.php
  - web/modules/custom/nidqc_contact/src/Service/ContactMailer.php
  - web/modules/custom/nidqc_contact/src/Service/RecaptchaVerifier.php
  - config/sync/core.extension.yml
  - config/sync/system.mail.yml
  - config/sync/node.type.contact_submission.yml
  - config/sync/field.storage.node.field_contact_*.yml
  - config/sync/field.field.node.contact_submission.field_contact_*.yml

read_only:
  - design/NIDQC Lien he.html
  - docs/design/PAGE_MAPPING.md
  - docs/security/SECURITY_POLICY.md
  - docs/security/SECURITY_CHECKLIST.md
  - docs/architecture/BACKEND_ARCHITECTURE.md
  - docs/architecture/FRONTEND_ARCHITECTURE.md
---

# TASK-009 — Contact form reCAPTCHA v3, backend và email

## 1. Mục tiêu

Trang `/lien-he` có form liên hệ thật: chạy reCAPTCHA v3 khi submit, gửi POST về Drupal, lưu submission thành content type `contact_submission`, gửi email thông báo cho admin và email xác nhận cho người gửi. Trang cũng hiển thị bản đồ Google Maps cho địa chỉ `48 Hai Bà Trưng, Tràng Tiền, Hoàn Kiếm, Hà Nội`.

## 2. Bối cảnh

- Design: `design/NIDQC Lien he.html`
- Trang: `docs/design/PAGE_MAPPING.md` §1 dòng 11
- User yêu cầu: reCAPTCHA v3, lưu backend, setup SMTP mail server, gửi email admin và user, thêm bản đồ 48 Hai Bà Trưng.

## 3. Phạm vi

### Trong phạm vi

- Đặc tả `POST /api/v1/contact`.
- Tạo module `nidqc_contact`.
- Tạo content type `contact_submission` và field lưu dữ liệu form.
- Thêm xử lý reCAPTCHA v3 server-side.
- Gửi mail qua Symfony Mailer bằng `system.mail` DSN, cấu hình local DDEV dùng SMTP Mailpit.
- Nâng cấp form `/lien-he` để submit thật và có map.

### Ngoài phạm vi

- Không cài package composer/npm mới.
- Không commit reCAPTCHA secret, SMTP password, API key.
- Không tạo trang quản trị custom ngoài màn hình content Drupal sẵn có.
- Không đổi kiến trúc Nuxt/Drupal.
- Không sửa nội dung Drupal hiện có ngoài submission được tạo khi test.

## 4. Yêu cầu

- [x] R1 — Form `/lien-he` lấy reCAPTCHA v3 token với action `contact_submit` khi submit.
- [x] R2 — API Drupal validate CSRF, JSON body, field lengths, email format, reCAPTCHA action/score.
- [x] R3 — API tạo node `contact_submission` không publish, gồm họ tên, email, số điện thoại, chủ đề, nội dung.
- [x] R4 — API gửi 1 email admin và 1 email xác nhận cho user.
- [x] R5 — DDEV dùng SMTP Mailpit để test email local.
- [x] R6 — Trang `/lien-he` hiển thị bản đồ cho `48 Hai Bà Trưng, Tràng Tiền, Hoàn Kiếm, Hà Nội`.

## 5. Tiêu chí chấp nhận

- [x] AC1 — Submit hợp lệ trả `200` và tạo node `contact_submission` unpublished.
- [x] AC2 — Submit thiếu/sai field trả lỗi JSON theo `docs/api/API_ERROR_STANDARD.md`.
- [x] AC3 — Submit thiếu/sai CSRF token trả `403 CSRF_TOKEN_INVALID`.
- [x] AC4 — reCAPTCHA không đạt trả `403 ACCESS_DENIED`.
- [x] AC5 — DDEV Mailpit nhận 2 email sau submit hợp lệ.
- [x] AC6 — Bản đồ 48 Hai Bà Trưng hiện trên `/lien-he`, lazy-load và có title/link fallback.
- [x] AC7 — Không có package mới, không có secret trong diff.

## 6. Cách verify

```bash
ddev drush en nidqc_contact -y
ddev drush cr
ddev composer phpcs
ddev exec npm --prefix frontend run build
ddev restart
curl -k -c /tmp/nidqc-contact.cookies -b /tmp/nidqc-contact.cookies https://nidqc.ddev.site/api/v1/contact/csrf-token
curl -k -c /tmp/nidqc-contact.cookies -b /tmp/nidqc-contact.cookies -H "X-CSRF-Token: <token>" -H "Content-Type: application/json" -X POST https://nidqc.ddev.site/api/v1/contact --data '{"name":"Nguyễn Văn A","email":"user@example.com","phone":"0901234567","subject":"Dịch vụ kiểm nghiệm","message":"Tôi cần liên hệ về dịch vụ kiểm nghiệm thuốc.","recaptchaToken":"ddev-bypass"}'
ddev drush sql:query "SELECT title, status FROM node_field_data WHERE type = 'contact_submission' ORDER BY nid DESC LIMIT 1"
ddev exec curl -s http://localhost:8025/api/v1/messages
ddev drush watchdog:show --severity=3
git diff --check
```

Kiểm bằng mắt:
1. Mở `https://nidqc.ddev.site/lien-he`.
2. Submit form, thấy trạng thái loading/success/error rõ ràng.
3. Map 48 Hai Bà Trưng hiển thị đúng vùng trang liên hệ.

## 7. Bảo mật

- [x] Tự kiểm theo `docs/security/SECURITY_CHECKLIST.md`; chờ người review ký.
- Rủi ro cụ thể:
  - Endpoint public POST phải có CSRF, reCAPTCHA, flood control.
  - Không log token reCAPTCHA hoặc nội dung message.
  - Node submission để unpublished.
  - reCAPTCHA/Google Maps là ngoại lệ external script/frame theo yêu cầu user; production CSP phải cho phép đúng domain.
  - SMTP credential production phải đặt ở config override/env, không commit.

## 8. Định nghĩa hoàn thành

Xem `docs/DEFINITION_OF_DONE.md`. Bổ sung riêng task này:
- [x] Content type `contact_submission` có trong `docs/database/ENTITY_MAPPING.md` trước khi install.
- [x] `config/sync/` export sạch sau khi enable module/config mail.

## 9. Câu hỏi mở

- Admin email production chưa được cung cấp; dùng `system.site:mail` (`noreply@nidqc.gov.vn`) làm fallback, production cần override đúng hộp thư nhận.
- reCAPTCHA site key/secret production chưa được cung cấp; DDEV dùng bypass rõ ràng qua `NIDQC_RECAPTCHA_BYPASS=1`, production phải tắt bypass và đặt key thật.

## 10. Nhật ký

| Ngày | Người/Agent | Việc |
|---|---|---|
| 2026-07-18 | Codex | Tạo task, chốt phạm vi theo yêu cầu reCAPTCHA v3, content type contact submission, SMTP và bản đồ. |
| 2026-07-18 | Codex | Thêm API `GET /api/v1/contact/csrf-token` và `POST /api/v1/contact`, module `nidqc_contact`, content type `contact_submission`, validate/flood/reCAPTCHA, lưu node unpublished và gửi SMTP admin/user. |
| 2026-07-18 | Codex | Cập nhật `/lien-he` với form submit thật, composable contact API/reCAPTCHA v3, bản đồ Google Maps địa chỉ 48 Hai Bà Trưng và cấu hình DDEV Mailpit. |
| 2026-07-18 | Codex | Verify: PHP lint pass; `ddev drush cr` pass; `ddev exec npm --prefix frontend run build` pass; submit hợp lệ tạo node `57` unpublished; Mailpit nhận 2 email; watchdog không có lỗi mới sau `wid > 423`; `ddev drush config:status` không còn diff; `git diff --check` pass. |
| 2026-07-18 | Codex | Không chạy được `ddev composer phpcs` vì project chưa khai báo Composer script `phpcs`. |
