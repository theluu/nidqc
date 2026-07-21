---
id: TASK-010
title: Import tin tức cũ từ nidqc.gov.vn vào site mới
status: review          # đã thực thi 2026-07-18, chờ NGƯỜI review/UAT
step: 7                 # Test
owner: Codex
reviewer: TBD
created: 2026-07-18

schema_change: true
new_package: false
config_change: true

allowed_files:
  - tasks/TASK-010.md
  - scripts/import-old-news.php
  - docs/database/ENTITY_MAPPING.md
  - frontend/pages/tin-tuc/index.vue
  - web/modules/custom/nidqc_content/config/install/field.field.node.news.body.yml
  - config/sync/field.field.node.news.body.yml
  - CHANGELOG.md

read_only:
  - AGENTS.md
  - docs/PROJECT_CONTEXT.md
  - docs/DEFINITION_OF_DONE.md
  - docs/database/ENTITY_MAPPING.md
  - docs/security/SECURITY_POLICY.md
  - docs/security/SECURITY_CHECKLIST.md
  - docs/standards/DRUPAL_CODING_STANDARD.md
  - scripts/import-content.php
  - web/modules/custom/nidqc_content/nidqc_content.install
  - config/sync/node.type.news.yml
  - config/sync/field.field.node.news.field_category.yml
  - config/sync/field.field.node.news.field_date.yml
  - config/sync/field.field.node.news.field_tag.yml
  - config/sync/field.field.node.news.field_image.yml
---

# TASK-010 — Import tin tức cũ từ nidqc.gov.vn

## 1. Mục tiêu

Viết script lấy dữ liệu tin tức cũ từ `https://nidqc.gov.vn/admin/tin-tuc` và import vào site mới
`https://nidqc.ddev.site/` dưới content type `news`, gán đúng taxonomy `news_category` hiện có.

## 2. Bối cảnh

- Site cũ là Drupal 7, view `/admin/tin-tuc` có table gồm title, category cũ, post date và link
  chi tiết public.
- Site mới đã có content type `news` và vocabulary `news_category` từ TASK-007.
- `news` đang thiếu field `body`; để import đúng nội dung chi tiết, task này gắn field `body`
  có sẵn vào bundle `news`.
- Không được commit thông tin đăng nhập. Script nhận tài khoản qua biến môi trường.

## 3. Phạm vi

### Trong phạm vi

- Script import idempotent, có chế độ dry-run mặc định.
- Gắn field `body` vào content type `news`.
- Scrape danh sách `/admin/tin-tuc` và các trang phân trang.
- Scrape body + ảnh đại diện nếu trang chi tiết public có dữ liệu.
- Import node `news` với `title`, `body`, `field_date`, `field_category`, `field_tag`, `field_image`.
- Map category cũ sang category mới theo từ khóa và mapping mặc định.

### Ngoài phạm vi

- Không tạo content type/field storage mới ngoài việc gắn body field có sẵn vào bundle `news`.
- Không thêm package.
- Không commit credential hoặc dữ liệu scrape thô.
- Không xoá nội dung đang có.
- Không sửa nội dung site cũ.

## 4. Yêu cầu

- [x] R1 — Script chạy bằng `ddev drush php:script scripts/import-old-news.php -- --dry-run`.
- [x] R2 — Script lấy đủ phân trang từ `/admin/tin-tuc`.
- [x] R3 — Script map category mới:
  `Thông báo`, `Tin hoạt động`, `Mua sắm - đấu thầu`, `Đào tạo`, `Hội nghị - Hội thảo`, `Tuyển dụng`.
- [x] R4 — Import idempotent bằng UUID deterministic từ URL cũ, không tạo trùng khi chạy lại.
- [x] R5 — Không lưu credential vào repo/log.
- [x] R6 — Có báo cáo số lượng fetched/imported/skipped/warnings.
- [x] R7 — `news` có field `body` để lưu nội dung chi tiết từ site cũ.

## 5. Tiêu chí chấp nhận

- [x] AC1 — Dry-run lấy được danh sách từ site cũ và in thống kê category.
- [x] AC2 — Import thật tạo node `news` published với ngày đăng, category và body.
- [x] AC3 — Chạy lại import không tạo trùng.
- [x] AC4 — Trang `/tin-tuc` site mới đọc được node import qua SSR/JSON:API.
- [x] AC5 — Không có lỗi mới trong watchdog.
- [x] AC6 — Không có secret trong diff.
- [x] AC7 — `config/sync/field.field.node.news.body.yml` được export và config status sạch.

## 6. Cách verify

```bash
ddev drush php:script scripts/import-old-news.php -- --dry-run --limit=3
ddev drush php:script scripts/import-old-news.php -- --import --limit=3 --with-images
ddev drush php:script scripts/import-old-news.php -- --import --limit=3 --with-images
ddev drush php:eval '$fields = \Drupal::service("entity_field.manager")->getFieldDefinitions("node", "news"); print isset($fields["body"]) ? "news_body=yes\n" : "news_body=no\n";'
ddev drush config:status
ddev drush sql:query "SELECT nid, title, status FROM node_field_data WHERE type='news' ORDER BY nid DESC LIMIT 5"
curl -k -s https://nidqc.ddev.site/tin-tuc | rg "Tin tức|Thông báo|NIDQC"
ddev drush watchdog:show --severity=3
git diff --check
```

Kết quả đã chạy:
- `--dry-run --limit=3 --update-existing --delay-ms=0`: `listed: 3 | details: 3 | created: 0 | updated: 0 | skipped: 3`.
- Full import ban đầu: 704 dòng cũ, 15 trang; 3 node tạo ở smoke test và 701 node tạo ở full run; rerun limit 3: `created: 0 | skipped: 3`.
- Full update sau khi sửa sanitize/body image: `listed: 704 | details: 704 | created: 0 | updated: 704 | skipped: 0`; category mới: `Mua sắm - đấu thầu: 539`, `Tin hoạt động: 52`, `Thông báo: 50`, `Đào tạo: 35`, `Hội nghị - Hội thảo: 20`, `Tuyển dụng: 8`.
- DB sau import: `imported_news=704`, `imported_with_body=704`, `imported_with_image=701`.
- `curl -k https://nidqc.ddev.site/tin-tuc`: `200`, HTML SSR có bài import và ảnh `/sites/default/files/old-news/...`.
- `curl -k https://nidqc.ddev.site/tin-tuc/106`: `200`, HTML SSR có body dài của bài `Lễ công bố...` và ảnh inline đã rewrite về local files.
- `ddev drush php:eval ... body`: `news_body=yes`.
- `ddev drush config:status`: `No differences between DB and sync directory.`
- `ddev drush sql:query "... watchdog WHERE wid > 423 AND severity <= 3 ..."`: không có dòng lỗi mới.
- `ddev exec php -l scripts/import-old-news.php`: `No syntax errors detected`.
- `ddev exec npm --prefix frontend run build`: pass, `Build complete`.
- `git diff --check`: pass.
- `ddev composer phpcs`: fail do project chưa khai báo Composer script `phpcs` (`Command "phpcs" is not defined.`).

## 7. Bảo mật

- [x] Tự kiểm theo `docs/security/SECURITY_CHECKLIST.md`; chờ người review ký.
- Không in credential ra stdout.
- Không lưu credential hoặc cookie vào repo.
- Không import script/style/iframe/form/event handler từ HTML cũ.
- Chỉ import ảnh `https://nidqc.gov.vn` có extension/MIME whitelist và kích thước tối đa 8 MB.

## 8. Định nghĩa hoàn thành

Xem `docs/DEFINITION_OF_DONE.md`. Bổ sung riêng task này:
- [x] `news.body` có trong `docs/database/ENTITY_MAPPING.md` trước khi cài field.
- [x] `field.field.node.news.body.yml` đã export vào `config/sync/` và thêm vào config install của `nidqc_content`.
- [x] Không lưu credential/cookie site cũ vào repository.
- [ ] Chưa có người khác review/UAT ký; task để trạng thái `review`.
- [ ] Chưa chạy `drush cim` trên DB mới trong lượt này.
- [ ] `ddev composer phpcs` chưa chạy được vì project chưa có script `phpcs`.

## 9. Câu hỏi mở

- Không còn câu hỏi mở để code.
- Cần người nội dung UAT mapping category vì site cũ chỉ có 4 category, trong khi site mới có 6 term.
- 12 node `news` có sẵn từ design trước TASK-010 được giữ nguyên, không xoá trong task import này.

## 10. Nhật ký

| Ngày | Người/Agent | Việc |
|---|---|---|
| 2026-07-18 | Codex | Tạo task import tin tức cũ, không lưu credential. |
| 2026-07-18 | Codex | Phát hiện `news` thiếu body; mở rộng task thành schema change nhỏ để gắn `body` vào bundle `news` trước khi import bài viết thật. |
| 2026-07-18 | Codex | Viết `scripts/import-old-news.php`: scrape listing/detail Drupal 7, map category, sanitize HTML, import ảnh, UUID deterministic và dry-run mặc định. |
| 2026-07-18 | Codex | Import 704 bài cũ vào content type `news`; gắn ngày đăng/category/body/ảnh, không tạo trùng khi chạy lại. |
| 2026-07-18 | Codex | Sửa lỗi sanitize wrapper làm mất body gốc, cập nhật lại toàn bộ 704 bài và rewrite ảnh inline trong body về local files. |
| 2026-07-18 | Codex | Cập nhật `/tin-tuc` sort theo `field_date` trước `created` để tin cũ mới nhất đứng đúng thứ tự theo ngày đăng gốc. |
| 2026-07-18 | Codex | Verify: `php -l` pass; Nuxt build pass; `drush cr` pass; config status sạch; watchdog không có lỗi mới; SSR listing/detail trả 200; `git diff --check` pass; `composer phpcs` không tồn tại. |
