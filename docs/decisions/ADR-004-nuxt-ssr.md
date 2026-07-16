# ADR-004 — Nuxt SSR cho SEO/GEO

- **Trạng thái:** Accepted
- **Ngày:** 2026-07-16
- **Người quyết:** Chủ dự án
- **Bổ sung:** [ADR-003](ADR-003-vue-spa-drupal-headless.md) — giữ Drupal headless, đổi Vue SPA → **Nuxt SSR**

---

## Bối cảnh

ADR-003 chuyển sang Vue SPA (đúng "convert design → Vue"). Nhưng ADR-003 §Rủi ro đã ghi:
**SPA render client-side → SEO kém.** Chủ đầu tư xác nhận: site `.gov.vn` **bắt buộc SEO/GEO**
(Generative Engine Optimization — nội dung phải đọc được bởi Google **và** crawler AI/LLM).

SPA gửi HTML rỗng + JS; crawler không chạy JS thì thấy trang trắng. Không chấp nhận được.

## Quyết định

**Nuxt SSR** — Vue render **phía server**. Mỗi request, Node server render HTML đầy đủ (có nội
dung) rồi mới hydrate. Crawler thấy nội dung thật.

```
Drupal headless (JSON:API)  ──▶  Nuxt SSR (Node)  ──HTML đầy đủ──▶  người dùng + crawler
```

- **Mô hình:** SSR thuần (Node runtime luôn chạy) — chủ đầu tư xác nhận hosting chạy được Node.
  Nội dung luôn tươi (tin mới hiện ngay, không cần rebuild).
- **Frontend:** các Vue component từ ADR-003 **tái dùng** — Nuxt dùng Vue. Đổi cách fetch dữ liệu
  từ `onMounted` (client) sang `useAsyncData` (server) để nội dung render server-side.

## Hệ quả

### Được
- **SEO/GEO tốt** — HTML có nội dung, crawler đọc được. Kiểm bằng `curl` (view-source), không cần JS.
- Vẫn convert từ design (Vue component giữ nguyên markup design).
- Nội dung luôn tươi (SSR mỗi request).

### Mất / chi phí (chấp nhận)
- **Production cần Node runtime** chạy song song Drupal PHP. Hạ tầng phức tạp hơn — đã xác nhận
  hosting đáp ứng.
- Nuxt server fetch Drupal JSON:API mỗi request → cần cache (Nuxt `routeRules` / Drupal cache).
- Deploy 2 tầng: Drupal (PHP) + Nuxt (Node). Reverse proxy gộp domain.

### Chuyển từ ADR-003
- `vite.config.js` SPA → `nuxt.config.ts`.
- `router.js` thủ công → Nuxt file-based routing (`pages/`).
- `AppLayout.vue` → `layouts/default.vue`.
- `api.js` → composable, fetch server-side.
- `onMounted(fetch)` → `useAsyncData(fetch)` ở mọi trang (**điểm mấu chốt cho SSR**).

## Kiểm chứng bắt buộc

SSR đạt ⟺ **`curl <trang>` trả HTML có nội dung thật** (tiêu đề tin, không phải `<div id="app">` rỗng).
Đây là bài kiểm quyết định — nếu curl thấy trang rỗng thì SSR chưa hoạt động.

## Xem lại khi nào
- Nếu tải Nuxt server cao → thêm cache tầng (routeRules `swr`/`isr`) hoặc CDN.
- Nếu chi phí Node runtime thành vấn đề → cân nhắc prerender (SSG) cho trang ít đổi.
