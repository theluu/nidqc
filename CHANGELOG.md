# Changelog

Theo [Keep a Changelog](https://keepachangelog.com/vi/1.1.0/) và [Semantic Versioning](https://semver.org/lang/vi/).

Mỗi PR thêm một dòng vào `[Unreleased]`. Xem `docs/DEFINITION_OF_DONE.md`.

---

## [Unreleased]

### Added
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

### Fixed
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
