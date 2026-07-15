# frontend — Vue island cho NIDQC

## Nguyên tắc số một

> **Vue nâng cấp HTML có sẵn. Vue không sinh ra nội dung.**

Đã đo trên design thật: **97,9% là nội dung tĩnh** (32/1.510 phần tử cần JS). Toàn bộ tương tác
của 11 trang chỉ gồm 7 khối. Nội dung → **Twig**. Chỉ hành vi tương tác → **Vue island**.

Nếu bạn đang viết Vue để render nội dung mà Google cần đọc → **sai kiến trúc, dừng lại**.
Xem [`ADR-001`](../docs/decisions/ADR-001-frontend-architecture.md) và [`ROADMAP.md`](../docs/ROADMAP.md) §5.

**Đây không phải SPA.** Không có router (điều hướng là việc của Drupal), không có store toàn cục
(island độc lập nhau).

## Lệnh

```bash
ddev exec "cd frontend && npm ci"        # cài — dùng ci, KHÔNG dùng install
ddev exec "cd frontend && npm run dev"   # dev server
ddev exec "cd frontend && npm run build" # build -> web/themes/custom/nidqc/dist/
```

> ⚠️ **`npm ci`, không phải `npm install`.** `ci` bám `package-lock.json`;
> `install` có thể tự nâng phiên bản ngoài phạm vi đã duyệt.

> ⚠️ **Build xong file chưa hiện ngay trên host.** DDEV dùng mutagen sync bất đồng bộ —
> file có trong container trước, host trễ vài giây. `ls` ngay sau build có thể báo "không tồn tại".
> Đó **không** phải lỗi build. Chờ vài giây hoặc kiểm trong container:
> `ddev exec "ls web/themes/custom/nidqc/dist/"`.

## Cấu trúc

```
frontend/
├── package.json
├── vite.config.js       # build ra web/themes/custom/nidqc/dist/, tên file CỐ ĐỊNH
├── .nvmrc               # Node >= 20.19
└── src/
    ├── main.js          # bootstrap: quét [data-island], mount
    ├── islands/         # (chưa có island nào — TASK-006 là cái đầu tiên)
    └── lib/
        └── api.js       # mọi request đi qua đây
```

**Không có** `router/` · **Không có** `store/`.

## ⛔ Không cài thêm package

Ba package hiện tại (`vue`, `vite`, `@vitejs/plugin-vue`) đã được **người duyệt**
(2026-07-16). Chúng kéo theo **66 package** trong lock — đó mới là bề mặt supply chain thật.

`node_modules` là bề mặt tấn công supply chain lớn nhất của dự án. Mỗi package thêm vào là thêm
rủi ro cho một site cơ quan nhà nước.

**Muốn thêm gì → dừng, hỏi người.** Xem [`AGENTS.md`](../AGENTS.md) §2.3.

Cấm sẵn: router · Pinia/Vuex · Tailwind/CSS framework · UI library · axios (dùng `fetch`).

## Thêm island mới

1. Tạo `src/islands/TenIsland.vue`
2. Đăng ký trong `src/main.js`:
   ```js
   const registry = {
     'ten-island': () => import('./islands/TenIsland.vue'),
   };
   ```
3. Twig render `<div data-island="ten-island">` **kèm nội dung thật bên trong**
4. Nạp library `nidqc/islands` qua `#attached` — **chỉ ở trang cần**, không nạp toàn site

**Island phải hoạt động khi tắt JS.** Cách kiểm: tắt JS trong DevTools → nội dung và chức năng
cốt lõi vẫn dùng được. Không đạt = chưa xong.

Xem [`FRONTEND_ARCHITECTURE.md`](../docs/architecture/FRONTEND_ARCHITECTURE.md) và
[`VUE_CODING_STANDARD.md`](../docs/standards/VUE_CODING_STANDARD.md).
