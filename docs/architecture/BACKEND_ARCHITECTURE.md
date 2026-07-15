# Backend Architecture — Drupal 11

## 1. Phạm vi

Drupal 11.4.3 / PHP 8.3. Chịu trách nhiệm: nội dung, render HTML, SEO, form, endpoint cho island.

**Được sửa:** `web/modules/custom/`, `web/themes/custom/`
**Cấm sửa:** `web/core/`, `vendor/`, `web/modules/contrib/`

## 2. Module custom

Đặt tên tiền tố `nidqc_`. Mỗi module một trách nhiệm.

```
web/modules/custom/
├── nidqc_core/         # service dùng chung, hook chung
├── nidqc_content/      # content type, field (⚠️ đụng schema — cần duyệt)
└── nidqc_island/       # endpoint cho Vue island
```

Không tạo module mới nếu task không yêu cầu. Không gộp mọi thứ vào một module "utils".

## 3. Theme

```
web/themes/custom/nidqc/
├── nidqc.info.yml
├── nidqc.libraries.yml       # nhúng asset Vite build
├── templates/
│   ├── layout/page.html.twig         # top bar, banner, nav, footer
│   ├── node/node--news--full.html.twig
│   └── views/views-view--documents.html.twig
├── css/tokens.css            # ⚠️ nguồn duy nhất của token màu/font
└── src/                      # SCSS nếu dùng
```

`css/tokens.css` sinh từ `docs/design/DESIGN_SYSTEM.md` §7. Không hard-code hex ở nơi khác.

## 4. Nhúng Vue island

Vite build ra `frontend/dist/`. Theme khai báo qua library:

```yaml
# nidqc.libraries.yml
islands:
  version: 1.x
  js:
    /themes/custom/nidqc/dist/islands.js: { attributes: { type: module, defer: true } }
  css:
    theme:
      /themes/custom/nidqc/dist/islands.css: {}
```

Chỉ nhúng ở trang cần: dùng `#attached` trong preprocess, không nhúng toàn site.

## 5. Content type

> ⚠️ **Tạo/sửa content type = đổi schema.** Chỉ làm trong task có `schema_change: true` đã duyệt.
> Xem `AGENTS.md` §2. Chi tiết field: `docs/database/ENTITY_MAPPING.md`.

Dự kiến: `page` · `news` · `document` · `faq` · `department` · `equipment` · `certificate` · `project`

## 6. Config

- Config export vào `config/sync/`.
- Đổi config trên UI → **phải** `drush cex` và commit. Không để lệch giữa DB và code.
- Không commit config chứa secret hoặc thông tin môi trường.

```bash
ddev drush cex     # export sau khi đổi trên UI
ddev drush cim     # import về (KHÔNG chạy trên production ngoài quy trình deploy)
```

## 7. Endpoint cho island

Island cần dữ liệu động (lọc, tìm kiếm) → endpoint riêng, không dùng JSON:API mở toàn bộ.

- Định nghĩa **trước** trong `docs/api/API_CONTRACT.md`, chốt rồi mới code.
- Route trong `nidqc_island.routing.yml`, `_format: json`.
- **Luôn** có `_permission` hoặc `_access`. Không có route nào `_access: 'TRUE'` nếu chưa cân nhắc.
- Validate mọi tham số. Giới hạn `limit` (mặc định 20, tối đa 100).
- Trả lỗi theo `docs/api/API_ERROR_STANDARD.md`.

> Không bật JSON:API rộng rãi chỉ để tiện. Đó là mở rộng bề mặt tấn công không cần thiết
> cho một kiến trúc island.

## 8. Bảo mật

- Không `|raw` trong Twig nếu chưa sanitize. Twig autoescape mặc định — **đừng tắt**.
- Query dùng Entity Query / Database API có placeholder. **Không nối chuỗi SQL.**
- Form dùng Form API (có CSRF sẵn). Không tự viết xử lý POST.
- Không `\Drupal::request()->get()` thẳng vào query.

Chi tiết: `docs/security/SECURITY_POLICY.md`.

## 9. Chuẩn code

Xem `docs/standards/DRUPAL_CODING_STANDARD.md`.

```bash
ddev composer phpcs      # kiểm tra
ddev drush cr            # xoá cache
```

## 10. Hiệu năng

- Cache tag/context đúng cho mọi render array. Sai cache tag → nội dung cũ hiện ra sau khi sửa.
- Views có pager. Không load toàn bộ node.
- Ảnh qua image style, không dùng ảnh gốc.
