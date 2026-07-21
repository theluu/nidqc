---
id: TASK-008
title: Google Translate mặc định tiếng Việt và hiển thị đủ ngôn ngữ
status: review
step: 5
owner: Codex
reviewer: Người review
created: 2026-07-18

schema_change: false
new_package: false
config_change: false

allowed_files:
  - frontend/layouts/default.vue
  - CHANGELOG.md
  - tasks/TASK-008.md

read_only:
  - AGENTS.md
  - docs/PROJECT_CONTEXT.md
  - docs/DEFINITION_OF_DONE.md
  - docs/architecture/FRONTEND_ARCHITECTURE.md
  - docs/architecture/BACKEND_ARCHITECTURE.md
  - docs/standards/VUE_CODING_STANDARD.md
  - docs/standards/DRUPAL_CODING_STANDARD.md
  - docs/security/SECURITY_CHECKLIST.md
  - docs/security/SECURITY_POLICY.md
  - docs/decisions/ADR-004-nuxt-ssr.md
---

# TASK-008 — Google Translate mặc định tiếng Việt và hiển thị đủ ngôn ngữ

## 1. Mục tiêu

Top bar Google Translate mặc định về tiếng Việt trên mỗi lần tải trang, không giữ bản dịch cũ sau F5, và dropdown ngôn ngữ lấy đầy đủ danh sách do Google Translate cung cấp thay vì danh sách rút gọn hard-code.

## 2. Bối cảnh

Trang `https://nidqc.ddev.site/` đang chạy Nuxt SSR theo ADR-004. Phần chọn ngôn ngữ nằm trong `frontend/layouts/default.vue`, không phải trong Twig theme Drupal.

## 3. Phạm vi

### Trong phạm vi
- Sửa logic client-side của Google Translate trong layout Nuxt.
- Giữ tiếng Việt là mặc định sau refresh.
- Lấy danh sách ngôn ngữ từ select thật của Google Translate.

### Ngoài phạm vi
- Bật content translation Drupal hoặc đổi schema.
- Cài package.
- Refactor layout/nav/footer ngoài phần translate.

## 4. Yêu cầu

- [x] R1 — Mỗi lần tải trang mới phải reset trạng thái Google Translate về tiếng Việt.
- [x] R2 — Dropdown phải hiển thị tất cả ngôn ngữ Google Translate trả về.
- [x] R3 — Chọn tiếng Việt từ trạng thái đã dịch phải xoá cookie dịch và reload về nội dung gốc.
- [x] R4 — Không thêm package, không đổi schema/config.
- [x] R5 — Top bar không còn hai nút nhanh `Tiếng Việt`/`English`; chỉ giữ dropdown `Ngôn ngữ`.

## 5. Tiêu chí chấp nhận

- [x] AC1 — `curl -k -L https://nidqc.ddev.site/` trả HTML SSR tiếng Việt.
- [x] AC2 — `ddev npm run build` trong `frontend/` pass.
- [x] AC3 — Không có `console.log()` mới, không có secret trong diff.
- [x] AC4 — HTML top bar không còn hai button quick switch.

## 6. Cách verify

```bash
ddev npm run build
curl -k -L https://nidqc.ddev.site/ | rg "Tiếng Việt|google_translate_element"
git diff --check
```

Kiểm bằng mắt:
1. Mở trang, dropdown ngôn ngữ có danh sách đầy đủ.
2. Chọn một ngôn ngữ khác, sau đó F5: trang trở lại tiếng Việt.

## 7. Bảo mật

- [x] Đã tự rà theo `docs/security/SECURITY_CHECKLIST.md`; cần người review ký trước merge.
- Không nhận input server-side, không thêm endpoint, không thêm `v-html`, không thêm `|raw`.
- Có tải script Google Translate đã tồn tại trước task; task này không mở rộng endpoint nội bộ.

## 8. Định nghĩa hoàn thành

Xem `docs/DEFINITION_OF_DONE.md`.

## 9. Câu hỏi mở

- Cần người review ký checklist bảo mật trước merge theo quy trình repo.

## 10. Nhật ký

| Ngày | Người/Agent | Việc |
|---|---|---|
| 2026-07-18 | Codex | Tạo task theo yêu cầu sửa Google Translate trên Nuxt layout. |
| 2026-07-18 | Codex | Sửa `frontend/layouts/default.vue`: reset cookie `googtrans` khi mount, lấy danh sách ngôn ngữ từ `.goog-te-combo`, giới hạn chiều cao dropdown. |
| 2026-07-18 | Codex | Verify bằng DDEV build, SSR curl, browser interaction; chuyển task sang review. |
| 2026-07-18 | Codex | Follow-up: bỏ hai nút nhanh `Tiếng Việt`/`English`, chỉ giữ dropdown `Ngôn ngữ`. |

## 11. Output verify

### Build

Local host:

```text
$ npm run build
ERROR Cannot find native binding ... Cannot find module '@oxc-parser/binding-darwin-x64'
```

Ghi nhận: lỗi do `node_modules` local thiếu optional native binding cho macOS, xảy ra trước khi compile code task.

DDEV:

```text
$ ddev npm run build
Nuxt 3.21.8
Client built
Server built
Build complete
```

### SSR markers

```text
$ ddev exec sh -lc 'curl -ksL https://nidqc.ddev.site/ | grep -q "google_translate_element" && echo google_element=yes || echo google_element=no'
google_element=yes

$ ddev exec sh -lc 'curl -ksL https://nidqc.ddev.site/ | grep -q "Tiếng Việt" && echo vietnamese_label=yes || echo vietnamese_label=no'
vietnamese_label=yes

$ ddev exec sh -lc 'curl -ksL https://nidqc.ddev.site/ | grep -q "<html  lang" && echo html_lang_marker=yes || echo html_lang_marker=no'
html_lang_marker=yes

$ ddev exec sh -lc 'curl -ksL https://nidqc.ddev.site/ | grep -q "language-menu-toggle" && echo toggle_marker=yes || echo toggle_marker=no'
toggle_marker=yes
```

### Browser interaction

```json
{
  "comboOptionCount": 249,
  "hasToggle": true,
  "htmlLang": "vi",
  "title": "Trang chủ — Viện Kiểm nghiệm thuốc Trung ương"
}
```

Mở dropdown custom:

```json
{
  "customOptionCount": 249,
  "firstLabels": ["Tiếng Việt", "Ả Rập", "Abkhaz", "Aceh", "Acholi"],
  "hasVietnamese": true
}
```

Chọn English rồi tải lại trang:

```json
{
  "translatedState": {
    "htmlLang": "en",
    "title": "Homepage — Central Institute for Drug Testing"
  },
  "resetState": {
    "comboOptionCount": 249,
    "hasVietnameseHome": true,
    "hasVietnameseLabel": true,
    "htmlLang": "vi",
    "title": "Trang chủ — Viện Kiểm nghiệm thuốc Trung ương"
  }
}
```

Sau follow-up bỏ hai nút nhanh:

```json
{
  "buttons": ["Ngôn ngữ"],
  "comboOptionCount": 249,
  "hasDropdown": true,
  "htmlLang": "vi"
}
```

Mở dropdown sau follow-up:

```json
{
  "customOptionCount": 249,
  "firstOptions": ["Tiếng Việt", "Ả Rập", "Abkhaz", "Aceh", "Acholi"]
}
```

SSR sau follow-up:

```text
$ ddev exec sh -lc "curl -ksL https://nidqc.ddev.site/ | grep -q 'language-menu-toggle' && echo dropdown_marker=yes || echo dropdown_marker=no"
dropdown_marker=yes

$ ddev exec sh -lc "curl -ksL https://nidqc.ddev.site/ | grep -o 'English</button>' | wc -l"
0

$ ddev exec sh -lc "curl -ksL https://nidqc.ddev.site/ | grep -o 'Tiếng Việt</button>' | wc -l"
0
```

### Clean checks

```text
$ git diff --check
# no output

$ rg -n "console\\.log|var_dump\\(|dpm\\(|TODO" frontend/layouts/default.vue
# no output
```

### Watchdog

```text
$ ddev drush watchdog:show --severity=3
ID 403,400,397,394,391,388,375,372,369,366 — Date 17/Jul — QueryException: 'path' not found
```

Ghi nhận: watchdog có lỗi cũ ngày 17/Jul, không phải lỗi mới của task 2026-07-18.
