# ADR-002 — Hai loại island: JS thuần để nâng cấp, Vue để render

- **Trạng thái:** Accepted
- **Ngày:** 2026-07-16
- **Người quyết:** Chủ dự án
- **Ảnh hưởng:** `frontend/src/main.js`, mọi island, `FRONTEND_ARCHITECTURE.md`
- **Quan hệ với [ADR-001](ADR-001-frontend-architecture.md):** **bổ sung, không thay thế.**
  Kiến trúc island vẫn giữ nguyên. ADR này nói *cách cài đặt* island.

---

## Bối cảnh

`ADR-001` chốt: Drupal Twig render nội dung, Vue island lo tương tác.
`FRONTEND_ARCHITECTURE.md` §1 phát biểu nguyên tắc:

> **Vue nâng cấp HTML có sẵn. Vue không sinh ra nội dung.**

Khi thực thi TASK-006 (island `mega-menu` đầu tiên) thì phát hiện: **Vue không làm được điều đó.**

`createApp().mount(el)` **xoá sạch nội dung của `el`** rồi render template vào. Đã kiểm chứng
trong trình duyệt thật:

```js
probe.innerHTML = '<ul><li><a href="/x">Nội dung Twig render sẵn</a></li></ul>';
createApp({ render: () => h('span', { hidden: true }) }).mount(probe);
probe.innerHTML;   // -> '<span hidden=""></span>'   ❌ menu đã bị xoá
```

Bootstrap trong `main.js` (TASK-005) mount thẳng vào `<div data-island>`. Nghĩa là nó sẽ **xoá
đúng cái HTML mà Twig render cho Google đọc** — làm ngược hoàn toàn mục đích của ADR-001.

Nguyên tắc §1 vì thế **tự mâu thuẫn**: Vue `mount()` không nâng cấp, nó **thay thế**.

## Nhận ra: 6 island không cùng bản chất

| Island | Bản chất | HTML từ đâu |
|---|---|---|
| `mega-menu` | Nâng cấp | Twig render sẵn cả cây menu |
| `faq-accordion` | Nâng cấp | Twig render sẵn `<details>` |
| `tabs` | Nâng cấp | Twig render sẵn mọi panel |
| `doc-filter` | Render | Kết quả lọc — động, Vue sinh ra |
| `news-filter` | Render | Kết quả lọc — động |
| `standard-search` | Render | Kết quả tìm — động |

**Ba cái đầu chỉ cần thêm hành vi** (bàn phím, aria, đóng/mở). Không sinh ra HTML nào.
Dùng Vue cho chúng là dùng sai công cụ — và `mount()` còn phá mất nội dung.

## Các phương án đã cân nhắc

### A. JS thuần cho island nâng cấp, Vue cho island render ✅ **CHỌN**
- ➕ Đúng công cụ cho đúng việc. Không có rủi ro xoá nhầm nội dung.
- ➕ Bundle nhỏ hơn: 3 island không kéo theo Vue runtime.
- ➕ Đơn giản — nâng cấp DOM vốn là việc của DOM API.
- ➖ Hai kiểu island trong `registry` → `main.js` phải phân biệt.

### B. Mối neo rỗng bên trong
Twig thêm `<span data-island-anchor>` rỗng; Vue mount vào đó, rồi thao tác DOM cha.
- ➕ Giữ Vue ở mọi island, nhất quán.
- ➖ Vòng vo: dùng Vue như cái móc chạy code, không dùng khả năng render của nó.
- ➖ Vẫn phải thao tác DOM thủ công — không được lợi gì từ Vue.

### C. `createSSRApp()` + hydrate
- ➕ Đúng bài về lý thuyết: Vue tiếp quản HTML có sẵn.
- ➖ Template Vue phải khớp **chính xác** HTML Twig sinh ra. Twig render menu từ dữ liệu Drupal;
  viết lại y hệt bằng template Vue là **nhân đôi logic** và sẽ lệch ngay lần sửa đầu tiên.
- ➖ Hydration mismatch là lỗi âm thầm, rất khó phát hiện.

## Quyết định

**Chọn A.** `registry` trong `main.js` nhận **hai loại**:

```js
// Island NÂNG CẤP: module export hàm `enhance(el)`, trả về hàm dọn dẹp.
//   Không được đụng vào innerHTML của el.
// Island RENDER: module export `default` là Vue component.
//   Chỉ dùng khi container RỖNG và nội dung do JS sinh ra.
```

`main.js` phân biệt bằng cách kiểm module export gì.

## Hệ quả

### Bắt buộc
- Island **nâng cấp** → **không import Vue**. Nhận `el`, gắn listener, trả hàm dọn.
- Island **nâng cấp** ⛔ **không được** ghi vào `el.innerHTML` — đó là nội dung của Twig.
- Island **render** chỉ mount vào container **rỗng** do Twig cố ý để trống.
- `FRONTEND_ARCHITECTURE.md` §1 phải sửa: nguyên tắc "Vue nâng cấp HTML có sẵn" là **sai kỹ thuật**.
  Phát biểu đúng: **"Twig sở hữu nội dung. Island không được xoá nội dung Twig."**

### Không đổi
- ADR-001 vẫn nguyên: Twig render nội dung, island lo tương tác, 97,9% design là tĩnh.
- Vue vẫn cần cho `doc-filter`, `news-filter`, `standard-search`.
- Mọi island vẫn phải hoạt động khi tắt JS.

### Đánh đổi chấp nhận
`main.js` phức tạp hơn một chút (phân biệt 2 loại) để đổi lấy: không có rủi ro xoá nội dung,
và bundle nhỏ hơn cho 3 trang chỉ cần nâng cấp.

## Xem lại khi nào

- Nếu số island nâng cấp tăng nhiều và JS thuần bắt đầu lặp lại → cân nhắc một helper chung
  (vẫn không cần Vue).
- Nếu có island vừa nâng cấp vừa render nhiều → xem lại phương án B.
