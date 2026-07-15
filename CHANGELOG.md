# Changelog

Theo [Keep a Changelog](https://keepachangelog.com/vi/1.1.0/) và [Semantic Versioning](https://semver.org/lang/vi/).

Mỗi PR thêm một dòng vào `[Unreleased]`. Xem `docs/DEFINITION_OF_DONE.md`.

---

## [Unreleased]

### Added
- Khung tài liệu dự án: `docs/`, `tasks/`, `prompts/`, `scripts/`
- `AGENTS.md` — luật và ranh giới cho AI agent
- `docs/design/DESIGN_SYSTEM.md` — token màu/font trích trực tiếp từ 12 file design
- `docs/design/PAGE_MAPPING.md` — ánh xạ design → route Drupal
- `docs/decisions/ADR-001` — chốt kiến trúc Drupal theme + Vue islands
- `scripts/extract-design.py` — trích markup/token từ design bundled

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
