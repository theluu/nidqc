---
id: TASK-013
title: Trang cấu hình SMTP, reCAPTCHA v3 và thông tin website
status: review
step: 7
owner: Codex
reviewer: <chưa gán — KHÔNG được trùng owner>
created: 2026-07-25

schema_change: false
new_package: false
config_change: true

allowed_files:
  - .ddev/config.yaml
  - docs/api/API_CONTRACT.md
  - web/modules/custom/nidqc_contact/**
  - frontend/composables/useRecaptchaV3.ts
  - frontend/pages/lien-he.vue
  - config/sync/nidqc_contact.settings.yml
  - config/sync/system.mail.yml
  - tasks/TASK-013.md
  - CHANGELOG.md
---

# TASK-013 — Trang cấu hình SMTP, reCAPTCHA v3 và thông tin website

## 1. Mục tiêu

Tạo trang quản trị tập trung tại `/admin/config/nidqc/settings` cho SMTP, reCAPTCHA
v3 và thông tin website. Sửa lỗi form liên hệ local và cung cấp phép thử SMTP.

## 2. Nguyên nhân đã xác minh

- Local bật `NIDQC_RECAPTCHA_BYPASS=1`, nên reCAPTCHA không chặn submit.
- DDEV này chạy Mailpit trong web container tại `127.0.0.1:1025`; thời điểm lỗi,
  Mailpit không nhận kết nối. Sau khi tiến trình hoạt động, cùng cấu hình gửi đạt.
- `ContactMailer` trả lỗi API khi gửi email thất bại, dù submission đã được lưu.

## 3. API contract

Chốt `GET /api/v1/contact/config` trước code. Endpoint chỉ trả site key công khai
và trạng thái reCAPTCHA; tuyệt đối không trả secret hoặc SMTP credential.

## 4. Yêu cầu

- Form quản trị cần quyền `administer site configuration`.
- SMTP: scheme, host, port, username, password, email nhận và nút gửi thử.
- reCAPTCHA v3: site key, secret key, minimum score; hiển thị rõ khi environment
  đang override giá trị UI.
- Site Information: tên website, slogan và email gửi đi.
- Password SMTP và reCAPTCHA secret lưu bằng State API, không nằm trong config
  export hoặc HTML.
- Environment variable có ưu tiên cao nhất để production dùng secret manager.
- Frontend lấy site key công khai từ API thay vì bắt buộc build lại Nuxt.
- Validate host, port, email, score và độ dài key.

## 5. Tiêu chí chấp nhận

- [x] Trang cấu hình chỉ admin truy cập được.
- [x] Không có secret trong `git diff`.
- [x] Local gửi form thành công và mail xuất hiện trong Mailpit.
- [x] Config API không trả secret.
- [x] Build, PHP syntax, config sync và security checks đạt.
- [ ] Có người khác ký security review/UAT.

## 6. Verify

```bash
ddev drush cr
ddev exec php -l web/modules/custom/nidqc_contact/src/Form/NidqcSettingsForm.php
ddev exec --dir /var/www/html/frontend npm run build
curl -ks https://nidqc.ddev.site/api/v1/contact/config
ddev drush config:status
ddev drush watchdog:show --severity=3
git diff --check
```

## 7. Nhật ký

| Ngày | Agent | Nội dung |
|---|---|---|
| 2026-07-25 | Codex | Xác minh SMTP local dùng sai host; tạo task theo yêu cầu người dùng. |
| 2026-07-25 | Codex | Kiểm tra mạng cho thấy DDEV này dùng Mailpit tại 127.0.0.1:1025; SMTP NIDQC mở TLS 465 và STARTTLS 587, cổng 25 không phản hồi. |
| 2026-07-25 | Codex | Dựng trang quản trị, API public site key, State API cho secret; build và test form/Mailpit đạt. Chuyển review. |
| 2026-07-25 | Codex | Lưu cặp reCAPTCHA do người dùng cung cấp, tắt bypass DDEV và khởi tạo v3 khi trang liên hệ mount; Chromium xác nhận badge hiển thị. |
| 2026-07-25 | Codex | Submission tới Gmail được tìm thấy trong Mailpit vì SMTP còn local. Chuyển sang STARTTLS 587; alias smtp.nidqc.gov.vn sai CN nên dùng smtp.hostvn.email. Server vẫn từ chối credential với SMTP 535, cần nhà cung cấp xác nhận tài khoản/mật khẩu hoặc quyền SMTP AUTH. |
| 2026-07-25 | Codex | Theo xác nhận cuối của người dùng, bỏ cấu hình SMTP thử nghiệm và chỉ giữ smtp.nidqc.gov.vn:25. `IMAP` không phải mã hoá SMTP nên map về scheme smtp; không đụng dữ liệu/submission khác. |

## 8. Output verify

```text
Root cause:
  DDEV Mailpit có lúc chưa chạy; kết nối hiện tại:
  127.0.0.1:1025 open, hostname mailpit không resolve.

SMTP NIDQC (không gửi credential):
  port 25 timeout
  port 465 TLSv1.3, certificate verified
  port 587 STARTTLS TLSv1.3, certificate verified

Runtime:
  FORM_OK
  /admin/config/nidqc/settings|administer site configuration
  Anonymous GET trang cấu hình -> 403
  SMTP_TEST_OK

Contact API:
  POST /api/v1/contact -> 200, submission id 781
  Mailpit nhận đủ "Liên hệ mới" và "Đã nhận liên hệ của bạn"

Public config:
  {"data":{"recaptcha":{"enabled":false,"site_key":""}}}
  Không có SMTP credential hoặc reCAPTCHA secret.

Verify:
  PHP syntax -> pass
  Nuxt production build -> Build complete
  drush config:status -> No differences
  git diff --check -> pass

reCAPTCHA browser:
  API enabled=true; Chromium chờ 5 giây tại /lien-he.
  Badge reCAPTCHA v3 hiển thị cố định ở góc dưới bên phải.
```
