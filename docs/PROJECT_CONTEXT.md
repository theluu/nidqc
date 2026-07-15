# Project Context — NIDQC

> Đọc đầu tiên, cùng với `AGENTS.md`. Trả lời: dự án này là gì, làm gì, không làm gì.

---

## 1. Dự án

Website **Viện Kiểm nghiệm thuốc Trung ương** — National Institute of Drug Quality Control (NIDQC).
Domain: `nidqc.gov.vn`. Cơ quan trực thuộc Bộ Y tế, kiểm nghiệm chất lượng thuốc.

Đây là **làm lại website hiện có** dựa trên bộ design mới (12 file trong `design/`).

## 2. Đặc thù chi phối mọi quyết định kỹ thuật

| Đặc thù | Hệ quả |
|---|---|
| **Cơ quan nhà nước** | Accessibility WCAG 2.1 AA bắt buộc. Không phụ thuộc CDN nước ngoài. |
| **Nặng nội dung, ít tương tác** | Drupal render HTML. Vue chỉ cho island tương tác. |
| **SEO là yêu cầu chính** | Nội dung không được nằm sau JavaScript. |
| **Tiếng Việt** | Font `Be Vietnam Pro` (có subset vietnamese). UTF-8 xuyên suốt. |
| **Link cũ tồn tại trong văn bản giấy** | Đổi URL phải kèm redirect. Không được để link chết. |
| **Thông tin y tế/dược** | Sai nội dung có hậu quả thật. Không tự chế nội dung mẫu nghe như thật. |

## 3. Công nghệ

| Lớp | Công nghệ | Vị trí |
|---|---|---|
| Backend + render | Drupal 11.4.3, PHP 8.3 | `web/` |
| Frontend tương tác | Vue 3 + Vite | `frontend/` |
| DB | MariaDB 11.8 | qua DDEV |
| Web server | nginx-fpm | qua DDEV |
| Dev env | DDEV (`ddev start`) | `.ddev/` |

**Module contrib đã cài** (không thêm gì nếu chưa duyệt):
`metatag` ^2.2 · `pathauto` ^1.15 · `redirect` ^1.13 · `simple_sitemap` ^4.2 · `token` ^1.17 · `drush` ^13.7

Bộ module này cho thấy rõ định hướng: **SEO-first, Drupal render**. Đừng chống lại nó.

## 4. Kiến trúc: Vue islands

```
Drupal Twig render HTML đầy đủ (SEO, metatag, sitemap, alias)
   └── <div data-island="mega-menu">   → Vue mount
   └── <div data-island="faq">         → Vue mount
   └── <div data-island="doc-filter">  → Vue mount
```

- Nội dung → Twig, server-side.
- Tương tác → Vue island, mount vào phần tử có sẵn.
- Island phải **progressive enhancement**: tắt JS thì nội dung vẫn đọc và dùng được.

Lý do chọn: xem `docs/decisions/ADR-001-frontend-architecture.md`.

## 5. Phạm vi

### Trong phạm vi
11 trang có design thật (xem `docs/design/PAGE_MAPPING.md`), theme Drupal custom,
Vue island cho các khối tương tác, SEO, accessibility, form liên hệ.

### Chưa rõ — cần chốt với chủ đầu tư

| Vấn đề | Tại sao quan trọng |
|---|---|
| **`/tim-kiem-chat-chuan`** — được design link tới 2 lần nhưng **không có file design** | Tra cứu chất chuẩn là nghiệp vụ lõi của Viện. Nếu trong phạm vi → thiếu design. Nếu ngoài → link tới đâu? |
| **`#chat-chuan`, `#dich-vu`** — anchor trên trang chủ nhưng được link 20 và 10 lần | Là mục điều hướng chính. Giữ anchor hay tách trang? Ảnh hưởng menu + SEO. |
| **English** — có nút VI/EN ở top bar | Chỉ UI hay có nội dung thật? Bật `content_translation` là đổi schema lớn. |
| **Đăng nhập hệ thống** — có link ở top bar | Đăng nhập cho ai? Cán bộ nội bộ? Ngoài phạm vi? |
| **Nguồn dữ liệu** | Migrate từ site cũ hay nhập tay? Chưa có kế hoạch migrate. |

> Các mục này **chưa được quyết**. Agent gặp phải thì **dừng và hỏi**, không tự suy diễn.

### Ngoài phạm vi (cho tới khi có quyết định khác)
Đa ngôn ngữ nội dung · Cổng đăng nhập nội bộ · Migrate dữ liệu · App mobile · Thanh toán trực tuyến

## 6. Các bên

| Vai | Trách nhiệm |
|---|---|
| Chủ đầu tư (NIDQC) | Duyệt nội dung, UAT, chốt phạm vi |
| Dev team | Triển khai, review, deploy |
| AI agent | Thực thi task trong `allowed_files`. **Không tự quyết kiến trúc, schema, package.** |

## 7. Bắt đầu

```bash
ddev start
ddev composer install
ddev drush site:install    # lần đầu
ddev launch
```

Frontend: xem `frontend/README.md` (chưa dựng — task TASK-002).

## 8. Đọc tiếp

`AGENTS.md` (luật) → `docs/DEFINITION_OF_DONE.md` (thế nào là xong) →
`docs/design/DESIGN_SYSTEM.md` (token) → `docs/design/PAGE_MAPPING.md` (trang)
