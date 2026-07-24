---
id: TASK-011
title: Sửa banner đầu trang full-width và responsive mobile
status: review
step: 5
owner: Codex
reviewer: <chưa gán — KHÔNG được trùng owner>
created: 2026-07-24

schema_change: false
new_package: false
config_change: false

allowed_files:
  - frontend/assets/css/main.css
  - tasks/TASK-011.md
  - CHANGELOG.md
---

# TASK-011 — Sửa banner đầu trang full-width và responsive mobile

## 1. Mục tiêu

Banner đầu trang phải chạm hai mép viewport ở desktop, tablet và mobile; ảnh giữ
đúng tỷ lệ, không bị ép chiều cao hoặc cắt nội dung trên màn hình nhỏ.

## 2. Nguyên nhân đã xác minh

- `body` giữ margin mặc định 8px của trình duyệt, làm toàn trang và banner hụt 8px mỗi bên.
- Media query dưới 640px đặt `min-height: 90px` và `object-fit: cover`, làm ảnh banner
  tỷ lệ 1170 × 140 bị ép cao và cắt ngang.

## 3. Yêu cầu

- Đặt margin của `body` về 0.
- Banner rộng 100% viewport và ảnh giữ tỷ lệ gốc ở mọi breakpoint.
- Không tạo thanh cuộn ngang.
- Không cài package và không thay đổi schema/API.

## 4. Tiêu chí chấp nhận

- [x] Banner có `x = 0` và cạnh phải bằng chiều rộng viewport ở desktop, tablet, mobile.
- [x] Ảnh banner giữ đúng tỷ lệ 1170:140, không dùng `object-fit: cover` trên mobile.
- [x] `documentElement.scrollWidth` không lớn hơn `innerWidth`.
- [x] `npm run build` thành công trong DDEV.
- [x] Kiểm tra trực quan tại 1440px, 768px và 390px.

## 5. Bảo mật và accessibility

Thay đổi chỉ liên quan CSS, không nhận input, không render HTML và không thay đổi
focus/semantic. Alt text hiện có của banner được giữ nguyên.

## 6. Nhật ký

| Ngày | Agent | Nội dung |
|---|---|---|
| 2026-07-24 | Codex | Tạo task sau khi người duyệt chấp thuận phạm vi file. |
| 2026-07-24 | Codex | Sửa CSS, build Nuxt thành công trong DDEV và kiểm tra browser ở 1440/768/390px. Banner đều `x=0`, không tràn ngang; mobile giữ đúng tỷ lệ ảnh. |

## 7. Output verify

```text
ddev exec --dir /var/www/html/frontend npm run build
└─ ✨ Build complete!

Viewport  Banner (x → right)  Image height  Horizontal overflow
1440px    0 → 1440            172.30px      false
768px     0 → 768              91.89px      false
390px     0 → 390              46.66px      false
```

Build trực tiếp trên host fail trước khi compile vì `node_modules` hiện thiếu optional
native binding macOS `@oxc-parser/binding-darwin-x64`. Không cài lại dependency vì task
không cho phép; cùng source build thành công trong môi trường DDEV chuẩn của dự án.
