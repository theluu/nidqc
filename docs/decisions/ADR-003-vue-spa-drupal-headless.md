# ADR-003 — Vue SPA + Drupal headless (thay thế ADR-001)

- **Trạng thái:** Accepted
- **Ngày:** 2026-07-16
- **Người quyết:** Chủ dự án (làm rõ lại plan gốc)
- **Thay thế:** [ADR-001](ADR-001-frontend-architecture.md) — **Superseded**

---

## Bối cảnh — sửa một quyết định sai

Plan gốc của chủ đầu tư ghi các bước:

> Design → Phân tích component → **API contract → Drupal Backend → Vue Frontend** → Security → Test → UAT → Deploy

Ba bước "API contract → Drupal Backend → Vue Frontend" mô tả **kiến trúc tách rời (decoupled)**:
Drupal làm backend cung cấp API, Vue làm frontend convert từ design.

**ADR-001 đã hiểu sai điều này.** Ở câu hỏi kiến trúc đầu tiên, agent đánh dấu "Drupal theme +
Vue islands" là "Khuyến nghị" và lái người dùng chọn nó, viện lý do SEO. Đó là **ghi đè plan của
chủ đầu tư bằng ý agent** — sai. Yêu cầu thật là: **convert HTML design sang Vue.js, tích hợp
Drupal backend qua API.**

## Quyết định

**Kiến trúc tách rời:**

```
Drupal (headless)  ──JSON:API──▶  Vue SPA (convert từ design)  ──▶  người dùng
   nội dung, admin                 toàn bộ giao diện
```

- **Drupal**: backend thuần. Quản trị nội dung, cung cấp dữ liệu qua **JSON:API** (`/jsonapi`).
  Không còn render giao diện người dùng.
- **Vue SPA**: toàn bộ frontend. Convert **trực tiếp** từ design HTML (giữ nguyên markup + style +
  ảnh → **giống hệt design đã duyệt**). `vue-router` cho điều hướng. Fetch dữ liệu từ JSON:API.

## Vì sao đổi so với ADR-001

ADR-001 chọn islands để giữ SEO. Nhưng:
1. **Đó không phải yêu cầu của chủ đầu tư** — plan ghi rõ "Vue Frontend" tách khỏi "Drupal Backend".
2. Islands (Twig render) khiến trang **không giống design** — agent phải viết lại markup, mất độ
   trung thực. Đã kiểm chứng: trang chủ Twig "quá khác xa" design.
3. Convert thẳng design HTML sang Vue **giống hệt** vì dùng chính markup đã duyệt.

## Hệ quả

### Được
- **Giống hệt design** — dùng chính markup pixel-perfect đã duyệt.
- Đúng plan của chủ đầu tư.
- Tách bạch FE/BE rõ ràng.

### Mất (chấp nhận)
- **Phần lớn Twig của theme `nidqc` không còn dùng cho frontend** — `page.html.twig`,
  `page--front.html.twig`, `menu--main.html.twig`, `layout.css`, `front.css`. Theme chỉ còn phục
  vụ trang admin. Đây là công sức bỏ đi từ các task Twig trước (TASK-003, 006, và trang chủ).
- **SEO cần xử lý riêng** — Vue SPA render phía client, Google khó index. Rủi ro thật với site
  `.gov.vn`. Cần **prerender hoặc SSR** trước khi go-live (task riêng, xem §Rủi ro).
- `metatag`/`simple_sitemap` của Drupal không tự áp cho trang Vue.

### Rủi ro cần xử lý trước go-live
- **SEO**: thêm prerender (build-time) hoặc SSR (Nuxt) — chưa làm, phải làm trước deploy thật.
- **Bảo mật JSON:API**: chỉ expose nội dung publish; không mở ghi; cân nhắc read-only.

## Giữ lại từ công việc trước

- **Toàn bộ backend**: 8 content type, 9 term, 50 node, menu, pathauto — **không đổi**, giờ phục
  vụ qua JSON:API.
- **Font self-host, design token** — Vue dùng lại.
- **`scripts/extract-*.py`** — công cụ trích design/nội dung vẫn dùng.
- **Config tái lập được** (ADR/TASK-002) — vẫn giá trị.

## Xem lại khi nào
- Nếu SEO không giải được bằng prerender → cân nhắc Nuxt SSR.
