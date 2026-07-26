---
id: TASK-014
title: Phân quyền và quy trình duyệt Tin tức
status: review
step: 7
owner: Codex
reviewer: <chưa gán — KHÔNG được trùng owner>
created: 2026-07-26

schema_change: true
new_package: false
config_change: true

allowed_files:
  - config/sync/workflows.workflow.editorial.yml
  - config/sync/language/en/workflows.workflow.editorial.yml
  - config/sync/user.role.news_author.yml
  - config/sync/user.role.news_reviewer.yml
  - config/sync/user.role.news_publisher.yml
  - tasks/TASK-014.md
  - CHANGELOG.md
---

# TASK-014 — Phân quyền và quy trình duyệt Tin tức

## 1. Mục tiêu

Triển khai ba cấp phân quyền và luồng Bản nháp → Chờ kiểm duyệt → Đã xuất bản
cho riêng content type `news`, theo `docs/drupal11-tin-tuc-roles-workflow.md`.

## 2. Phê duyệt

Người dùng yêu cầu trực tiếp ngày 2026-07-26: “Update theo
`.md docs/drupal11-tin-tuc-roles-workflow.md`”. Yêu cầu này là phê duyệt bằng văn
bản cho thay đổi schema/config đúng phạm vi tài liệu.

## 3. Phạm vi

- Tạo roles `news_author`, `news_reviewer`, `news_publisher`.
- Cấu hình workflow `editorial` chỉ áp dụng cho bundle `node:news`.
- Không cài package; dùng core `workflows` và `content_moderation` đã bật.
- Không thay đổi field hoặc content type.

## 4. Tiêu chí chấp nhận

- [x] Người viết tạo/sửa bài mình, gửi duyệt nhưng không publish.
- [x] Kiểm duyệt sửa mọi Tin tức, trả bài về nháp nhưng không publish.
- [x] Lãnh đạo là role duy nhất ngoài super-admin có transition publish.
- [x] Anonymous không xem được bản nháp hoặc chờ duyệt.
- [x] Config import sạch và kiểm tra quyền theo từng role đạt.
- [x] Ba role nhìn thấy navigation quản trị và contextual edit links tương ứng.
- [ ] Security review có người khác ký.

## 5. Verify

```bash
ddev drush cim -y
ddev drush cr
ddev drush config:status
ddev drush watchdog:show --severity=3
git diff --check
```

Kiểm tra functional bằng ba user tạm, mỗi user mang đúng một role; xác nhận các
transition hiển thị/không hiển thị và anonymous không truy cập revision chưa
publish.

## 6. Nhật ký

| Ngày | Agent | Nội dung |
|---|---|---|
| 2026-07-26 | Codex | Tạo task theo phê duyệt trực tiếp của người dùng; giới hạn phạm vi ở workflow và ba role Tin tức. |
| 2026-07-26 | Codex | Import config, kiểm tra permissions, quyền sửa bài mình/người khác và anonymous access bằng entity tạm; đã xoá toàn bộ dữ liệu test. Chuyển review, chờ người khác ký security/UAT. |
| 2026-07-26 | Codex | Bổ sung `access navigation` và contextual links; trước đó role có quyền node nhưng không thấy chức năng trên giao diện quản trị. |

## 7. Output verify

```text
Roles/transition:
  news_author:    publish=no, return_to_draft=no
  news_reviewer:  publish=no, return_to_draft=yes
  news_publisher: publish=yes, return_to_draft=yes

Workflow:
  states=draft,needs_review,published
  transitions=create_new_draft,submit_for_review,keep_in_review,
              return_to_draft,publish
  bundles=news

Entity access:
  author_own_edit=yes
  author_other_edit=no
  reviewer_other_edit=yes
  publisher_other_edit=yes
  anonymous_draft_view=no
  anonymous_published_view=yes

Account/UI access:
  content_writer: password=valid, navigation=yes, add_news=yes,
                  content_overview=yes, publish=no
  content_reviewer: password=valid, navigation=yes, add_news=yes,
                    content_overview=yes, publish=no

Config:
  ddev drush cim -y -> success
  ddev drush config:status -> No differences

Watchdog:
  Không có lỗi workflow/content moderation mới.
  ID 697 là ParseError do lệnh php:eval kiểm thử đầu tiên escape namespace sai;
  lệnh kiểm thử sửa lại sau đó chạy đạt. Các lỗi SMTP còn lại có trước TASK-014.
```
