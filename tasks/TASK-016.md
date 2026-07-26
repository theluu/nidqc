---
id: TASK-016
title: Trang 404 NIDQC
status: review
step: 7
owner: Codex
reviewer: <chưa gán — KHÔNG được trùng owner>
created: 2026-07-26

schema_change: false
new_package: false
config_change: false

allowed_files:
  - frontend/error.vue
  - frontend/pages/[slug].vue
  - tasks/TASK-016.md
  - CHANGELOG.md
---

# TASK-016 — Trang 404 NIDQC

## Mục tiêu

Trang không tồn tại trả HTTP 404 thật, có giao diện trang trọng, responsive,
WCAG 2.1 AA và các lối thoát hữu ích về Trang chủ, Tin tức, Văn bản, Liên hệ.

## Tiêu chí

- [x] URL không tồn tại trả HTTP 404 thay vì 200.
- [x] Có heading rõ ràng, CTA dùng được bằng bàn phím.
- [x] Header/footer chung vẫn hiển thị.
- [x] Responsive CSS và production build đạt.
- [ ] Security review/UAT có người khác ký.

## Kết quả kiểm tra

```text
GET /duong-dan-khong-ton-tai-task016
  HTTP=404
  <title>Không tìm thấy trang — NIDQC
  SSR có: Không tìm thấy trang, Về trang chủ,
          Văn bản – Tài liệu, Liên hệ hỗ trợ

Nuxt production build:
  Client built
  Server built
  Nitro server built
  Build complete

Visual browser:
  Chưa chạy được vì phiên Browser tích hợp không khả dụng.
```
