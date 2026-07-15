---
id: TASK-001
title: Dựng khung theme custom nidqc + tokens.css từ design system
status: ready
step: 4                  # Drupal Backend (nền cho bước 5)
owner: <chưa gán>
reviewer: <chưa gán — KHÔNG được trùng owner>
created: 2026-07-16

schema_change: false     # không đụng content type / field
new_package: false       # không composer require, không npm install
config_change: true      # ⚠️ đặt nidqc làm default theme → đổi system.theme

allowed_files:
  - web/themes/custom/nidqc/nidqc.info.yml
  - web/themes/custom/nidqc/nidqc.libraries.yml
  - web/themes/custom/nidqc/css/tokens.css
  - web/themes/custom/nidqc/css/base.css
  - web/themes/custom/nidqc/README.md
  - CHANGELOG.md

read_only:
  - docs/design/DESIGN_SYSTEM.md
  - docs/design/PAGE_MAPPING.md
  - docs/architecture/BACKEND_ARCHITECTURE.md
  - docs/standards/DRUPAL_CODING_STANDARD.md
  - design/
  - web/core/themes/olivero/          # tham chiếu cấu trúc info.yml
  - web/core/themes/starterkit_theme/
---

# TASK-001 — Dựng khung theme custom `nidqc` + `tokens.css`

## 1. Mục tiêu

Sau task này, Drupal chạy bằng theme custom `nidqc` thay vì `olivero`, và **mọi màu/khoảng cách
của dự án có đúng một nguồn duy nhất** là `css/tokens.css`. Các task sau chỉ việc dùng
`var(--nidqc-*)`, không ai phải mở lại file design để dò mã màu.

## 2. Bối cảnh

Đây là task nền — mọi task frontend/theme sau đều phụ thuộc nó.

- Token màu/font: `docs/design/DESIGN_SYSTEM.md` §1, §3, §4, §7 (đã trích từ design thật)
- Region cần có: `docs/design/PAGE_MAPPING.md` §4
- Cấu trúc theme: `docs/architecture/BACKEND_ARCHITECTURE.md` §3

Trạng thái hiện tại (đã kiểm tra ngày 2026-07-16):
- Site **đã cài** (profile `standard`), DDEV đang chạy tại `https://nidqc.ddev.site`
- Theme mặc định: `olivero` · Theme admin: `claro`
- `web/themes/custom/` **chưa tồn tại**

## 3. Phạm vi

### Trong phạm vi
- Tạo thư mục `web/themes/custom/nidqc/`
- `nidqc.info.yml` — khai báo theme + region theo `PAGE_MAPPING.md` §4
- `nidqc.libraries.yml` — khai báo library `global`
- `css/tokens.css` — **toàn bộ** biến CSS từ `DESIGN_SYSTEM.md` §7
- `css/base.css` — reset tối thiểu + áp `--nidqc-font`, `--nidqc-text` lên `body`
- `README.md` của theme — giải thích tokens.css là nguồn duy nhất
- Bật theme, đặt làm default

### ⛔ Ngoài phạm vi — KHÔNG làm, dù thấy cần

| Không làm | Vì sao |
|---|---|
| **File `.twig` nào** | Task riêng. Task này chỉ dựng khung + token. |
| **`@font-face` / file woff2** | → **TASK-002**. Xem §7 bên dưới. |
| **Dựng giao diện giống design** | Task này **không** làm trang giống design. Xem §7. |
| **Vue / `frontend/`** | Bước 5, task riêng. |
| **Content type, field** | `schema_change: false`. |
| **Cài package** | `new_package: false`. Không cần gì thêm. |
| **Đụng olivero/claro/stable9** | `web/core/` là vùng cấm. |
| **Sửa `config_sync_directory`** | Vấn đề thật nhưng là task riêng — xem §9. |

## 4. Yêu cầu

- [ ] **R1** — `nidqc.info.yml`: `core_version_requirement: ^11`, `base theme: stable9`,
      `type: theme`, `package: NIDQC`.
- [ ] **R2** — Region khai báo đúng theo `PAGE_MAPPING.md` §4, machine name **chính xác**:
      `header_top` · `header_banner` · `primary_menu` · `content` · `footer`
      (kèm `page_top`, `page_bottom` — Drupal cần cho toolbar).
      `content` là region **bắt buộc** của Drupal, không được thiếu.
- [ ] **R3** — `css/tokens.css` chứa **đủ 15 biến màu + 3 biến layout + 1 biến font**
      của `DESIGN_SYSTEM.md` §7, đặt trong `:root`. Copy **nguyên văn**, không tự đổi giá trị,
      không tự thêm màu mới.
- [ ] **R4** — `nidqc.libraries.yml` khai báo library `global` gồm `tokens.css` rồi `base.css`
      (đúng thứ tự — `base.css` dùng biến của `tokens.css`). `nidqc.info.yml` nạp qua
      `libraries: - nidqc/global`.
- [ ] **R5** — `css/base.css` **chỉ** đặt `font-family`, `color`, `background` cho `body`
      bằng `var(--nidqc-*)`. Không style component nào khác.
- [ ] **R6** — **Không có mã hex nào** ngoài `tokens.css`. `base.css` chỉ dùng `var()`.
- [ ] **R7** — `README.md` của theme ghi rõ: sửa màu thì sửa `tokens.css`, và nguồn gốc token
      là `docs/design/DESIGN_SYSTEM.md` (đừng sửa ngược).

## 5. Tiêu chí chấp nhận

- [ ] **AC1** — `ddev drush pm:list --type=theme --status=enabled --format=list` có `nidqc`.
- [ ] **AC2** — `ddev drush config:get system.theme default --format=string` trả về `nidqc`.
- [ ] **AC3** — Vào `https://nidqc.ddev.site/` không lỗi, không trang trắng.
- [ ] **AC4** — HTML trang chủ có nạp `tokens.css` và `base.css`.
- [ ] **AC5** — **Mọi mã hex trong `tokens.css` đều tồn tại thật trong design.**
      Kiểm bằng lệnh ở §6.4 — đây là AC quan trọng nhất: nó chứng minh token không bị bịa.
- [ ] **AC6** — `grep -E '#[0-9a-fA-F]{6}' web/themes/custom/nidqc/css/base.css` **không ra gì**.
- [ ] **AC7** — Region hiện đủ tại `/admin/structure/block` (đúng 5 region ở R2).
- [ ] **AC8** — `ddev drush watchdog:show --severity=3` không có lỗi mới.
      (`3` = Error. **Phải dùng số**, không dùng `--severity=Error` — site chạy langcode `vi`
      nên Drush khớp nhãn đã dịch và lệnh sẽ lỗi exit 1.)

## 6. Cách verify

> Chạy **thật**. Dán output vào §10. Không dán = coi như chưa làm.

### 6.1. Bật theme
```bash
ddev drush theme:install nidqc -y
ddev drush config:set system.theme default nidqc -y
ddev drush cr
```

### 6.2. Kiểm theme
```bash
ddev drush pm:list --type=theme --status=enabled --format=list        # phải có: nidqc
ddev drush config:get system.theme default --format=string            # phải là: nidqc
```

> `--format=string` là bắt buộc — thiếu nó lệnh trả về **rỗng**, dễ tưởng là lỗi.

### 6.3. Kiểm trang chạy + CSS được nạp
```bash
curl -s -o /dev/null -w "%{http_code}\n" https://nidqc.ddev.site/   # phải: 200
curl -s https://nidqc.ddev.site/ | grep -oE '[^"]*(tokens|base)\.css[^"]*'
```

### 6.4. ⭐ Kiểm token khớp design — AC5
```bash
# Mọi hex trong tokens.css phải xuất hiện trong design gốc.
# Output rỗng = đạt. Có dòng nào = token bị bịa, KHÔNG ĐẠT.
python3 scripts/extract-design.py --all --colors | awk '{print toupper($2)}' | sort -u > /tmp/design_colors.txt
grep -oE '#[0-9a-fA-F]{6}' web/themes/custom/nidqc/css/tokens.css \
  | tr 'a-f' 'A-F' | sort -u > /tmp/token_colors.txt
comm -23 /tmp/token_colors.txt /tmp/design_colors.txt
```

### 6.5. Kiểm không hex ngoài tokens.css — AC6
```bash
grep -E '#[0-9a-fA-F]{6}' web/themes/custom/nidqc/css/base.css   # phải rỗng
```

### 6.6. Kiểm lỗi
```bash
# 3 = Error. PHẢI dùng số: site chạy langcode `vi`, Drush khớp nhãn severity đã dịch
# ("Thông tin", "Lỗi"...) nên --severity=Error sẽ lỗi exit 1, KHÔNG phải vì có lỗi thật.
ddev drush watchdog:show --severity=3 --count=10
```
Đạt: `No log messages available.`

### 6.7. Bằng mắt
1. Mở `https://nidqc.ddev.site/` → trang hiển thị, **không** trắng, **không** lỗi PHP.
2. DevTools → `:root` có đủ biến `--nidqc-*`.
3. `/admin/structure/block` → thấy đúng 5 region.

> ⚠️ Trang sẽ **trông thô, chưa giống design**. Đó là **đúng** — xem §7.

## 7. Kỳ vọng đúng về kết quả

Task này **không** làm trang giống design. Sau khi xong, `https://nidqc.ddev.site/` sẽ là một
trang gần như không có style — chữ đen trên nền trắng, font hệ thống.

**Như vậy là đạt.** Cụ thể, những điều sau là **bình thường**, không phải lỗi:

- Font **chưa** phải Be Vietnam Pro → font `@font-face` là **TASK-002**. `--nidqc-font` đã trỏ
  đúng tên họ font, nhưng chưa có file woff2 nên trình duyệt fallback về `-apple-system`.
- Chưa có top bar, banner, mega menu, footer → cần `.twig`, là task sau.
- Layout chưa có container 1280px → cần `.twig`.

Task này chỉ giao **một** thứ: cái móng. Móng đúng thì mọi thứ xây lên mới đúng.
Người review đừng đánh trượt vì "trông không giống design".

## 8. Bảo mật

Rủi ro thấp — chỉ có CSS và file khai báo, không có input người dùng, không có query.

- [ ] Đã chạy `docs/security/SECURITY_CHECKLIST.md` (phần lớn mục sẽ là `N/A`, **ghi rõ lý do**)
- [ ] Không secret trong diff
- [ ] Không nạp font/CSS từ CDN ngoài (`SECURITY_POLICY.md` §10 — site nhà nước
      không phụ thuộc CDN nước ngoài)

## 9. Câu hỏi mở / Blocker đã biết

> ⚠️ **Đọc trước khi bắt đầu.**

### 9.1. 🔴 `config_sync_directory` đang trỏ vào thư mục bị gitignore

**Đã kiểm tra:** `config_sync_directory` **chưa được đặt** trong `settings.php`, nên Drupal dùng
mặc định `sites/default/files/sync`. Thư mục đó nằm trong `web/sites/*/files/` — **đang bị
`.gitignore` chặn** (dòng 35).

Hệ quả: `ddev drush cex` sẽ ghi config ra một chỗ **không bao giờ được commit**.
Tức là việc `nidqc` làm default theme **không tái lập được** trên máy khác hay trên staging —
họ `git pull` xong vẫn thấy `olivero`.

Mâu thuẫn với `docs/architecture/BACKEND_ARCHITECTURE.md` §6 (nói config export vào `config/sync/`)
và `.env.example` (`CONFIG_SYNC_DIRECTORY=../config/sync`). **Tài liệu mô tả ý định, thực tế chưa khớp.**

**Xử lý:** ⛔ **KHÔNG tự sửa trong task này** (ngoài `allowed_files`). Đây là việc của **TASK-002**.

Trong lúc chờ, TASK-001 chấp nhận: bật theme là **thao tác thủ công**, ghi lại trong `§6.1`.
Người review phải biết điều này để không tưởng đã tái lập được.

### 9.2. 🟡 Site không có content type nào

Profile là `standard` (lẽ ra có `article` + `page`) nhưng `node_type` **đếm được 0**.
Ai đó đã xoá, hoặc site được cài khác quy trình.

Không chặn TASK-001 (task này không đụng content). Nhưng **chặn** mọi task về nội dung sau này —
cần chốt `docs/database/ENTITY_MAPPING.md` trước. Đã ghi nhận, cần hỏi lại người cài site.

### 9.3. 🟡 `base theme: stable9` — xác nhận

R1 chọn `stable9` (có sẵn trong `web/core/themes/`). Đây là base theme ổn định, không áp đặt
markup như `olivero`. Nếu người review muốn `starterkit_theme` thay thế → nói **trước khi** code,
đừng đổi giữa chừng.

## 10. Nhật ký

| Ngày | Người/Agent | Việc |
|---|---|---|
| 2026-07-16 | Claude | Soạn task. Đã kiểm tra trạng thái site thật: đã install, profile `standard`, langcode `vi`, theme `olivero`, `web/themes/custom/` chưa có, 0 content type, `config_sync_directory` bị gitignore. |
| 2026-07-16 | Claude | **Đã chạy thử toàn bộ lệnh §6 trước khi giao task.** Phát hiện và sửa 2 lệnh sai: `config:get system.theme default` trả rỗng nếu thiếu `--format=string`; `watchdog:show --severity=Error` lỗi exit 1 vì site langcode `vi` (Drush khớp nhãn đã dịch) — phải dùng `--severity=3`. Đã kiểm pipeline §6.4 bắt đúng màu bịa (`#ABCDEF` bị chặn, `#0F3093` qua). |
