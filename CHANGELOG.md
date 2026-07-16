# Changelog

Theo [Keep a Changelog](https://keepachangelog.com/vi/1.1.0/) và [Semantic Versioning](https://semver.org/lang/vi/).

Mỗi PR thêm một dòng vào `[Unreleased]`. Xem `docs/DEFINITION_OF_DONE.md`.

---

## [Unreleased]

### Added
- **Trang chủ theo design** (`page--front.html.twig`) — thay view frontpage mặc định của Drupal.
  5 section: hero (tin nổi bật + danh sách tin mới, **động từ node**), hoạt động chuyên môn, dịch
  vụ, CTA chất chuẩn, liên kết + liên hệ. Anchor `#dich-vu`/`#chat-chuan`/`#hoat-dong-chuyen-mon`
  khớp menu. Class + token (không hex), CSS `front.css` chỉ nạp ở trang chủ. Sửa node → trang đổi theo.
- **50 node nội dung thật từ design** — 12 tin tức · 12 văn bản · 7 FAQ · 8 đơn vị · 5 thiết bị ·
  3 chứng nhận · 3 đề tài. Trích bằng `scripts/extract-content.py` (design → JSON) rồi nhập bằng
  `scripts/import-content.php` (idempotent). Alias tiếng Việt sinh đúng, node chi tiết truy cập được.
- **Menu chính 9 mục theo design** — dựng bằng `hook_install()` nên **tái lập được** trên mọi máy,
  giải xong nợ #10. Toàn bộ 37 link có trong HTML thô (Google đọc được cả submenu sâu).
  Tắt link tĩnh `standard.front_page` trùng với "Trang chủ" của design.
- **Cấu trúc nội dung theo design đã duyệt** (TASK-007) — 8 content type, 3 taxonomy, 9 term,
  8 pathauto pattern. Field lấy **thẳng từ design**, không suy diễn. Toàn bộ tái lập được từ
  DB trống bằng `drush cim` + `hook_install()`.
- **Mega menu dùng được bằng bàn phím và cảm ứng** (TASK-006) — design chỉ có hover, làm y hệt
  là loại người dùng bàn phím và điện thoại khỏi menu chính của site nhà nước. Island thêm
  Enter/Esc/mũi tên, chạm-lần-1-mở, `aria-expanded`. Tắt JS thì `:hover`/`:focus-within` lo.
- **Hạ tầng Vue island** (TASK-005) — `frontend/` với Vite 8, bootstrap tự quét `[data-island]`
  và mount. Chưa có island nào (registry rỗng — đúng); island đầu tiên là TASK-006. Đúng 3
  package trực tiếp (`vue`, `vite`, `@vitejs/plugin-vue`), 0 lỗ hổng. Production **không chạy
  Node** — Vite chỉ build ra JS tĩnh cho Drupal library nạp.
- **Font đúng design, self-host** (TASK-004) — `Lexend` cho tiêu đề, `Be Vietnam Pro` cho thân bài,
  tiếng Việt đủ dấu. 15 file woff2 (220 KB) trích thẳng từ design bundle, **không gọi Google Fonts
  CDN** — site nhà nước không phụ thuộc hạ tầng nước ngoài và giữ được CSP chặt. Kèm giấy phép
  SIL OFL 1.1 đã xác minh từ repo `google/fonts`.
- **Khung trang theo design** (TASK-003) — `page.html.twig` dựng 5 vùng dùng chung đúng thứ tự:
  top bar → banner → nav (sticky) → breadcrumb → nội dung → footer. Thêm region `breadcrumb`
  còn thiếu, và dọn 10 block bị Drupal dồn nhầm vào `header_top` (giờ chỉ còn `account_menu`).
- **Theme custom `nidqc`** (TASK-001) — site giờ chạy theme của dự án thay vì `olivero`.
  `css/tokens.css` là **nguồn duy nhất** của màu/layout/font: 21 biến CSS trích thẳng từ design
  gốc, đã kiểm chứng 18/18 màu tồn tại thật trong `design/`. Từ nay không ai phải mở file design
  để dò mã màu, và đổi màu thương hiệu chỉ sửa một chỗ.
- Khung tài liệu dự án: `docs/`, `tasks/`, `prompts/`, `scripts/`
- `AGENTS.md` — luật và ranh giới cho AI agent
- `docs/design/DESIGN_SYSTEM.md` — token màu/font trích trực tiếp từ 12 file design
- `docs/design/PAGE_MAPPING.md` — ánh xạ design → route Drupal
- `docs/decisions/ADR-001` — chốt kiến trúc Drupal theme + Vue islands
- `scripts/extract-design.py` — trích markup/token từ design bundled
- `tasks/TASK-001.md` — dựng khung theme custom `nidqc` + `tokens.css`
- `tasks/TASK-002.md` — sửa `config_sync_directory`, đưa `settings.php` vào git (chặn TASK-001)
- `tasks/TASK-003.md` — dựng `page.html.twig` (5 vùng dùng chung) + sửa 2 lỗi tài liệu design
- `tasks/TASK-004.md` — self-host font Lexend + Be Vietnam Pro trích từ design bundle
- `tasks/TASK-005.md` — dựng `frontend/` (Vite + bootstrap island)
- `tasks/TASK-006.md` — island `mega-menu` (hover + bàn phím + cảm ứng)
- `docs/ROADMAP.md` — lộ trình, đường găng và blocker
- `docs/CAU_HOI_NIDQC.md` — 7 câu hỏi chặn dự án, soạn sẵn để gửi Viện
- `scripts/extract-fonts.py` — trích woff2 + sinh `@font-face` từ design bundle

### Fixed
- 🔴 **Pathauto cắt mất từ tiếng Việt trong URL** (TASK-007) — `ignore_words` mặc định là stopword
  **tiếng Anh**, trùng từ tiếng Việt thật: "Phòng **Tổ** chức" → `/phong-chuc-hanh-chinh`,
  "Viện **in ấn** tài liệu" → `/vien-tai-lieu`. URL mất nghĩa và **đụng alias** với trang khác.
  Đã sửa về rỗng.
- 🔴 **`drush cim` thất bại trên môi trường mới** (TASK-007) — `si` sinh UUID site mới, Drupal từ
  chối import config từ "site khác". **TASK-002 bỏ sót** vì chỉ kiểm `cim` trên cùng site.
  Đã ghi bước khớp UUID vào `README.md` và `DEPLOYMENT.md`.
- 🔴 **`ENTITY_MAPPING.md` §4 bịa field cho content type `document`** — đặc tả
  `field_document_number`, `field_issued_date`, `field_file`, `field_category` mà **không field nào
  có trong design**. Chúng được suy ra từ hiểu biết chung về "trang văn bản pháp quy", không phải
  từ nguồn chân lý. Ai cài theo bản đó là dựng sai schema cho dữ liệu thật của Viện. Đã gỡ và ghi
  rõ design thật chứa gì (`{ title, meta }`, không link tải).
- 🔴 **Bootstrap island sẽ xoá mất nội dung Twig** (TASK-006, sửa lỗi của TASK-005) —
  `createApp().mount(el)` xoá sạch nội dung container rồi mới render, nên mount vào
  `<div data-island>` sẽ phá đúng HTML mà Twig render cho SEO. Nguyên tắc *"Vue nâng cấp HTML có
  sẵn"* trong `FRONTEND_ARCHITECTURE` §1 sai về kỹ thuật. Nay tách hai loại island ([ADR-002]):
  nâng cấp → JS thuần, render → Vue. `main.js` chặn thẳng việc mount Vue vào container có nội dung.
  Phụ thêm: trang chỉ có mega-menu giờ tải **0,6 kB** thay vì **58 kB**.
- **Tài liệu design sai về font** (TASK-003) — `DESIGN_SYSTEM` §2 khẳng định "Font duy nhất:
  Be Vietnam Pro". Đếm thật: `Lexend` dùng **140 lần** (font tiêu đề, cả 12 trang), Be Vietnam Pro
  22 lần (thân bài), Roboto Mono 1 lần. Hệ quả: `tokens.css` thiếu biến font tiêu đề nên mọi
  `h1`-`h4` sai font. Đã thêm `--nidqc-font-heading`, `--nidqc-font-mono`, `--nidqc-text-body`
  (21 → 24 token).
- **Tài liệu design thiếu một vùng** (TASK-003) — `PAGE_MAPPING` §4 ghi 4 vùng dùng chung, thực tế
  có **5**: thiếu dải breadcrumb, có ở cả 10 trang con. Đây là nguyên nhân gốc khiến Drupal ném
  10 block vào `header_top`.
- **Link top bar không đọc được** (TASK-003) — "Đăng nhập" render xanh dương trên nền xanh đậm
  do link không kế thừa `color` của cha. Lỗi accessibility, chỉ phát hiện được khi chụp màn hình.
- **Config giờ tái lập được giữa các máy** (TASK-002). Trước đây `drush cex` ghi ra
  `sites/default/files/sync` — thư mục bị gitignore — nên cấu hình không bao giờ được commit;
  người mới `git clone` không nhận được theme, ngôn ngữ hay module đã bật. Nay export vào
  `config/sync/` ở gốc dự án và được commit (232 file).
- Sửa lệnh kiểm tra sai trong tài liệu: `drush watchdog:show --severity=Error` lỗi exit 1 vì
  site chạy langcode `vi` (Drush khớp nhãn severity đã dịch) — phải dùng `--severity=3`

### Security
- `settings.php` được đưa vào git để đặt `config_sync_directory`. Đã xác minh không chứa secret
  (`hash_salt = ''`, không có `$databases`). Secret vẫn nằm ngoài git: `settings.ddev.php`
  (DDEV tự sinh) và `settings.local.php` (mới bật include — chỗ đặt secret cho staging/production).

---

## Loại thay đổi

| Loại | Dùng khi |
|---|---|
| `Added` | Tính năng mới |
| `Changed` | Thay đổi hành vi có sẵn |
| `Deprecated` | Sắp bỏ |
| `Removed` | Đã bỏ |
| `Fixed` | Sửa lỗi |
| `Security` | Vá bảo mật |

## Quy tắc

- Viết cho **người đọc**, không phải máy. Không dán commit message vào đây.
- Nói **tác động**, không nói cài đặt.
- Thay đổi bảo mật **luôn** ghi vào `Security`.
- Có breaking change → ghi rõ, đặc biệt nếu **URL đổi** (link cũ nằm trong văn bản giấy).

```
❌ refactor DocumentController
✅ Lọc văn bản theo năm giờ trả đúng kết quả khi năm nằm ngoài khoảng có dữ liệu
```
