---
id: TASK-017
title: Popup tìm kiếm Tin tức và trang kết quả
status: in_progress
step: 3
owner: Codex
reviewer: <chưa gán — KHÔNG được trùng owner>
created: 2026-07-26

schema_change: false
new_package: false
config_change: false

allowed_files:
  - docs/api/API_CONTRACT.md
  - web/modules/custom/nidqc_content/**
  - frontend/layouts/default.vue
  - frontend/assets/css/main.css
  - frontend/composables/useNewsSearch.ts
  - frontend/pages/tim-kiem.vue
  - tasks/TASK-017.md
  - CHANGELOG.md

read_only:
  - design/
  - docs/design/PAGE_MAPPING.md
  - docs/design/DESIGN_SYSTEM.md
  - docs/architecture/BACKEND_ARCHITECTURE.md
  - docs/architecture/FRONTEND_ARCHITECTURE.md
  - docs/security/
  - docs/standards/
  - docs/testing/TEST_STRATEGY.md
  - frontend/pages/tin-tuc/index.vue
---

# TASK-017 — Popup tìm kiếm Tin tức và trang kết quả

## 1. Mục tiêu

- Bỏ mục menu “Tra cứu”.
- Khi bấm icon tìm kiếm, mở popup nhập từ khóa.
- Chỉ tìm trong content type `news` (Tin tức).
- Trang kết quả có bố cục và cách hiển thị giống trang `/tin-tuc`.

## 2. Phạm vi

- Chốt API contract tìm kiếm trước khi viết backend/frontend.
- Kết quả nội dung render SSR để bảo đảm SEO và progressive enhancement.
- Popup hỗ trợ bàn phím, focus rõ ràng, đóng bằng Escape và trả focus về nút mở.
- Không đổi schema, không cài package, không sửa design.

## 3. Tiêu chí chấp nhận

- [x] Menu không còn mục “Tra cứu”.
- [x] Icon search mở popup; submit điều hướng tới URL kết quả có query rõ ràng.
- [x] API validate từ khóa, chỉ trả node `news` đã publish và có access.
- [x] Trang kết quả dùng cùng card/grid/pagination với `/tin-tuc`.
- [x] Nội dung kết quả có trong HTML SSR.
- [x] Tắt JavaScript vẫn có đường dẫn/form tìm kiếm dùng được.
- [x] Responsive tại 390px, 768px và 1440px; WCAG 2.1 AA.
- [x] Build, PHP syntax, test API và watchdog đạt.
- [ ] Security review/UAT có người khác ký.

## 3b. Quyết định thiết kế (chốt 2026-07-27)

**Card kết quả** — tách `components/NewsCard.vue` + `components/NewsGrid.vue` dùng
chung cho `/tin-tuc` và `/tim-kiem`, thay vì mỗi trang một bản CSS. Bỏ được ~120
dòng CSS trùng và hai trang không thể lệch nhau khi đổi design.

> **Ngoại lệ read_only:** việc này sửa `frontend/pages/tin-tuc/index.vue` (đang liệt
> kê trong `read_only`). Người dùng chọn phương án này khi được hỏi trực tiếp
> 2026-07-27. Thay đổi chỉ là thay markup card bằng component và xoá CSS đã chuyển
> đi — không đổi dữ liệu, route hay hành vi; đã verify lọc chuyên mục + phân trang.

**Ảnh kết quả** — `NewsSearchController` dùng chung `NewsPresenter` với
`/tin-tuc`, nên ảnh qua image style `max_650x650` thay vì file gốc.

## 4. Verify

```bash
ddev drush cr
ddev exec --dir /var/www/html/frontend npm run build
ddev drush watchdog:show --severity=3
git diff --check
```

## 5. Nhật ký

| Ngày | Agent | Nội dung |
|---|---|---|
| 2026-07-26 | Codex | Tạo task theo yêu cầu trực tiếp của người dùng; giới hạn tìm kiếm ở content type `news`. |
| 2026-07-27 | Claude | Sửa 3 lỗi chặn nghiệm thu: (1) icon search là `NuxtLink` nên `preventDefault()` không chặn được Vue Router — bấm icon vừa mở popup vừa nhảy sang `/tim-kiem`; đổi sang `<a>` thường giữ `href` làm fallback không-JS. (2) Kết quả nháy về 0 sau hydrate — Nuxt tải `/_payload.json` **không kèm query string** nên payload không-từ-khoá đè lên SSR đúng; tắt `experimental.payloadExtraction` và đổi key `useAsyncData` sang động theo (từ khoá, trang). (3) Ảnh kết quả là file gốc — cho `NewsSearchController` dùng chung `NewsPresenter`. Tách `NewsCard`/`NewsGrid` dùng chung với `/tin-tuc`. |
