# Thiết kế: Mục "Liên kết web" quản trị được trong backend

**Ngày:** 2026-07-21
**Trạng thái:** Đã duyệt thiết kế

## Bối cảnh & mục tiêu

Hiện mục "Liên kết web" ở trang chủ (`frontend/pages/index.vue`) dùng mảng
`webLinks` hardcode (chỉ có `label` + `href`). Yêu cầu: mỗi liên kết có thêm
**ảnh (logo)** và **mô tả**, và toàn bộ (title, link, image, description) phải
**quản trị được trong Drupal backend**.

## Quyết định thiết kế

- **Bố cục:** giữ "Liên kết web" ở cột phải cạnh video như hiện tại; mỗi liên
  kết là một hàng gọn: logo ~44px bên trái + tên (đậm) + mô tả 1–2 dòng
  (clamp). Mở tab mới. (Người dùng đã chọn phương án này thay vì tách khối lưới
  riêng.)
- **Mô hình dữ liệu:** node type mới `web_link`, theo đúng pattern các node type
  sẵn có (news, department...) — tự expose qua JSON:API (module `jsonapi` đã bật).

## Backend (Drupal)

Node type `web_link` (nhãn "Liên kết web"), config ship vào `config/sync`
(`enforced module: nidqc_content`), gồm:

| Trường | Kiểu | Ghi chú |
|--------|------|---------|
| `title` (built-in) | string | Tên liên kết, VD "Bộ Y Tế" |
| `field_link` (mới) | link | URL đích; cho phép external. Cần bật module core `link`. |
| `field_image` (tái dùng) | image | Logo/ảnh — dùng lại storage `field_image` |
| `field_description` (tái dùng) | text_long | Mô tả ngắn — dùng lại storage `field_description` |
| `field_weight` (mới) | integer | Thứ tự sắp xếp; sort tăng dần |

Config cần tạo:
- `core.extension.yml`: bật module `link`.
- `field.storage.node.field_link.yml`, `field.storage.node.field_weight.yml` (mới).
- `field.field.node.web_link.field_link.yml`, `.field_image.yml`,
  `.field_description.yml`, `.field_weight.yml` (instance).
- `node.type.web_link.yml`.
- `core.entity_form_display.node.web_link.default.yml` (admin nhập liệu).
- `core.entity_view_display.node.web_link.default.yml`.
- Không cần pathauto (không có trang chi tiết).

**Seed:** script `scripts/seed-web-links.php` tạo 4 node từ danh sách hiện tại
(Bộ Y Tế → moh.gov.vn, Cục Quản lý Dược → dav.gov.vn, VKN TP.HCM → #,
WHO → who.int) với title + `field_link` + `field_weight`. Logo và mô tả để admin
bổ sung sau. Idempotent (chạy lại không tạo trùng).

## Frontend (Nuxt) — `frontend/pages/index.vue`

- Bỏ mảng `webLinks` hardcode.
- Fetch SSR trong `useAsyncData` hiện có (hoặc thêm khối fetch song song):
  `fetchJsonApi('/node/web_link', { 'filter[status]': 1, sort: 'field_weight',
  'page[limit]': 12, include: 'field_image' })`.
- Map mỗi node → `{ label: title, href: field_link.uri, image: imageUrl(...),
  description: field_description.processed|value }`.
- Render trong cột phải cạnh video: hàng gồm logo (ảnh, hoặc icon fallback nếu
  thiếu) + tên đậm + mô tả clamp 2 dòng. `target="_blank" rel="noopener"`.
- Nếu danh sách rỗng → ẩn khối (không hiển thị khung trống).

## Triển khai / vận hành

- `ddev drush cim -y` để import config mới (tạo node type + fields + bật link).
- Chạy `scripts/seed-web-links.php` qua `ddev drush php:script`.
- `nuxt build` lại (Nuxt SSR chạy bản build) + `ddev restart`.

## Ngoài phạm vi (YAGNI)

- Không làm trang chi tiết cho web_link.
- Không làm UI kéo-thả sắp xếp (dùng `field_weight` nhập tay là đủ).
- Không migrate mô tả/logo cũ (nguồn cũ không có).
