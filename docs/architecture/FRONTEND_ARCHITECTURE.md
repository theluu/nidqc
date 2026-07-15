# Frontend Architecture — Vue 3 islands

## 1. Nguyên tắc số một

> **Vue nâng cấp HTML có sẵn. Vue không sinh ra nội dung.**

Nếu bạn đang viết Vue để render nội dung mà Google cần đọc → **sai kiến trúc, dừng lại**.
Nội dung là việc của Twig. Xem `docs/decisions/ADR-001-frontend-architecture.md`.

## 2. Cấu trúc

```
frontend/
├── package.json
├── vite.config.js          # build ra web/themes/custom/nidqc/dist/
├── src/
│   ├── main.js             # bootstrap: quét [data-island], mount
│   ├── islands/
│   │   ├── MegaMenu.vue
│   │   ├── FaqAccordion.vue
│   │   ├── DocFilter.vue
│   │   ├── NewsFilter.vue
│   │   ├── Tabs.vue
│   │   └── StandardSearch.vue
│   ├── composables/
│   └── lib/
│       └── api.js          # gọi endpoint, xử lý lỗi theo chuẩn
└── README.md
```

**Không có** `router/` — điều hướng là việc của Drupal.
**Không có** `store/` toàn cục — island độc lập nhau. Cần Pinia thì phải có lý do và được duyệt.

## 3. Bootstrap island

```js
// src/main.js
const registry = {
  'mega-menu':      () => import('./islands/MegaMenu.vue'),
  'faq-accordion':  () => import('./islands/FaqAccordion.vue'),
  // ...
};

document.querySelectorAll('[data-island]').forEach(async (el) => {
  const loader = registry[el.dataset.island];
  if (!loader) return;
  const { default: Component } = await loader();
  createApp(Component, readProps(el)).mount(el);
});
```

Mỗi island **lazy load riêng**. Trang chỉ có FAQ thì không tải code mega menu.

## 4. Truyền dữ liệu Twig → Vue

Một chiều. Twig là nguồn.

```twig
<div data-island="doc-filter">
  <script type="application/json" data-props>
    {{ { categories: categories, year: year }|json_encode|raw }}
  </script>
  {# nội dung render sẵn #}
</div>
```

> `|raw` ở đây an toàn **chỉ vì** `|json_encode` đã escape trước. Thứ tự này bắt buộc.
> Không bao giờ `|raw` trên dữ liệu chưa qua `json_encode` hoặc chưa sanitize.

Vue đọc props từ `<script data-props>`, không parse DOM để lấy dữ liệu.

## 5. Progressive enhancement — bắt buộc

Mỗi island **phải** hoạt động khi không có JS:

| Island | Không JS thì sao |
|---|---|
| `mega-menu` | Menu vẫn là `<ul><li><a>` — click vào được, có CSS `:hover` fallback |
| `faq-accordion` | `<details>/<summary>` gốc — vẫn đóng mở được |
| `doc-filter` | Form GET thật, submit reload trang — Drupal Views lọc phía server |
| `news-filter` | Như trên |
| `tabs` | Tất cả panel hiện ra, có anchor link |
| `standard-search` | Form GET tới trang kết quả server-side |

Nguyên tắc: **HTML làm được thì để HTML làm.** Vue chỉ thêm mượt.

Cách kiểm: tắt JS trong DevTools → mọi nội dung và chức năng cốt lõi vẫn dùng được.

## 6. Style

- Dùng CSS variable từ `web/themes/custom/nidqc/css/tokens.css`. **Không hard-code hex trong `.vue`.**
- `<style scoped>` cho style riêng island.
- Không import CSS framework. Không Tailwind — design là inline style thuần, không có utility class.

```vue
<style scoped>
.tab--active { border-bottom: 2px solid var(--nidqc-primary); }
</style>
```

## 7. Gọi API

```js
// src/lib/api.js — mọi request đi qua đây
export async function get(path, params) { /* xử lý lỗi theo API_ERROR_STANDARD */ }
```

- Không `fetch` rải rác trong component.
- Xử lý cả 3 trạng thái: loading, error, empty. Không giả định thành công.
- Endpoint phải có trong `docs/api/API_CONTRACT.md` trước khi gọi.

## 8. Accessibility — site nhà nước, không phải tuỳ chọn

| Island | Yêu cầu |
|---|---|
| `mega-menu` | Điều hướng bằng bàn phím (Tab, Esc đóng), `aria-expanded`, không bẫy focus |
| `faq-accordion` | `aria-expanded`, `aria-controls`, Enter/Space mở |
| `tabs` | `role="tablist"`, mũi tên trái/phải chuyển tab |
| Tất cả | Focus nhìn thấy rõ. Tương phản ≥ 4.5:1. Không dùng màu làm tín hiệu duy nhất. |

Hover **không được** là cách duy nhất mở mega menu — bàn phím và cảm ứng phải dùng được.

## 9. Build

```bash
cd frontend
npm run dev      # dev, HMR
npm run build    # xuất ra web/themes/custom/nidqc/dist/
```

**Production không chạy Node.** Build ra tĩnh, commit `dist/` hoặc build trong CI. Xem `docs/deployment/DEPLOYMENT.md`.

## 10. Chuẩn code

Xem `docs/standards/VUE_CODING_STANDARD.md`.
