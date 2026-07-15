---
id: TASK-001
title: Dựng khung theme custom nidqc + tokens.css từ design system
status: review          # đã thực thi 2026-07-16, chờ NGƯỜI review (AI không được tự duyệt)
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
| **`@font-face` / file woff2** | → task font riêng (chưa đánh số). Xem §7 bên dưới. |
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
- [ ] **R3** — `css/tokens.css` chứa **đủ 21 biến** của `DESIGN_SYSTEM.md` §7, đặt trong `:root`:
      **18 biến màu + 2 biến layout (`--nidqc-container`, `--nidqc-gutter`) + 1 biến font**.
      Copy **nguyên văn**, không tự đổi giá trị, không tự thêm màu mới, không bịa thêm biến cho "đủ bộ".
      > Số đếm này đã kiểm bằng lệnh, không phải ước lượng:
      > `sed -n '/^:root {/,/^}/p' docs/design/DESIGN_SYSTEM.md | grep -cE '^\s*--nidqc-[a-z0-9-]+:'` → `21`
      >
      > ⚠️ Regex **phải có `:`**. Thiếu nó thì dòng comment nhắc tới tên biến cũng bị đếm.
      > (TASK-003 nâng lên 24 khi thêm `--nidqc-font-heading`, `--nidqc-font-mono`, `--nidqc-text-body`.)
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
- [ ] **AC4** — CSS được nạp: **21 biến `--nidqc-*`** có mặt trong CSS site phục vụ,
      và `tokens.css` đứng **trước** `base.css` trong library. Kiểm bằng §6.3.
      (Không grep tên file trong HTML — CSS aggregation đổi tên thành `css_<hash>.css`.)
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
```

> ⚠️ **Không grep `tokens.css` trong HTML.** Site bật CSS aggregation
> (`system.performance css.preprocess = 1`), Drupal gộp file lại thành
> `css_<hash>.css` — tên `tokens.css` **không bao giờ** xuất hiện trong HTML.
> Grep tên file sẽ luôn fail dù CSS nạp hoàn toàn đúng.

Kiểm đúng cách — mở file CSS **đã gộp** và tìm token bên trong:
```bash
# Hai cái bẫy, cả hai đều làm lệnh báo sai:
#  1. Phải GIỮ query string (?delta=...&theme=nidqc...). Cắt mất -> server trả
#     "The theme must be passed as a query argument", không phải CSS.
#  2. CSS gộp đã MINIFY về một dòng (:root{--nidqc-primary:#0F3093;...) nên
#     KHÔNG được neo '^' trong regex — neo vào là luôn ra 0.
curl -s https://nidqc.ddev.site/ \
  | grep -oE 'href="/sites/default/files/css/[^"]+"' \
  | sed 's/href="//;s/"$//;s/&amp;/\&/g' \
  | while read -r u; do curl -s "https://nidqc.ddev.site$u"; done \
  | grep -oE '\-\-nidqc-[a-z0-9-]+\s*:' | sed 's/\s*:$//' | sort -u | wc -l
# phải ra 21
```

Cách khác, chắc chắn hơn — hỏi thẳng Drupal xem library có đăng ký đúng không:
```bash
ddev drush php:eval "
\$d = \Drupal::service('library.discovery')->getLibraryByName('nidqc', 'global');
print \$d ? print_r(array_column(\$d['css'], 'data'), TRUE) : 'KHÔNG TÌM THẤY';"
# phải liệt kê tokens.css TRƯỚC base.css
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

- Font **chưa** phải Be Vietnam Pro → `@font-face` + file woff2 là **task riêng, chưa đánh số**.
  `--nidqc-font` đã trỏ đúng tên họ font, nhưng chưa có file woff2 nên trình duyệt fallback về
  `-apple-system`. (Font self-host trích được từ design: `extract-design.py --resources` cho thấy
  21 file woff2 nhúng sẵn trong mỗi bundle.)
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

**Xử lý:** ⛔ **KHÔNG tự sửa trong task này** (ngoài `allowed_files`).
→ Đã có **`tasks/TASK-002.md`** xử lý riêng, kèm nguyên nhân gốc đã truy được.

**Thứ tự khuyến nghị: làm TASK-002 TRƯỚC, rồi mới merge TASK-001.** Nếu làm TASK-001 trước,
việc bật theme chỉ là thao tác thủ công trên máy người làm — người khác `git pull` vẫn thấy
`olivero`. Người review phải biết điều này để không tưởng đã tái lập được.

### 9.2. 🟡 Site không có content type nào

Profile là `standard` (lẽ ra có `article` + `page`) nhưng `node_type` **đếm được 0**.
Ai đó đã xoá, hoặc site được cài khác quy trình.

Không chặn TASK-001 (task này không đụng content). Nhưng **chặn** mọi task về nội dung sau này —
cần chốt `docs/database/ENTITY_MAPPING.md` trước. Đã ghi nhận, cần hỏi lại người cài site.

### 9.3. 🟡 `base theme: stable9` — xác nhận

R1 chọn `stable9` (có sẵn trong `web/core/themes/`). Đây là base theme ổn định, không áp đặt
markup như `olivero`. Nếu người review muốn `starterkit_theme` thay thế → nói **trước khi** code,
đừng đổi giữa chừng.

### 9.4. 🔴 `theme:install` tự đặt 10 block sai chỗ — phát sinh khi thực thi

**Chưa lường trước khi soạn task.** `drush theme:install nidqc` khiến Drupal **tự chép** block
placement từ `olivero` sang, rồi map region theo tên. Chỉ 2 region trùng tên nên map đúng:

| Block | Region ở olivero | Region ở nidqc | |
|---|---|---|---|
| `content` | `content` | `content` | ✅ |
| `main_menu` | `primary_menu` | `primary_menu` | ✅ |
| `site_branding` | `header` | **`header_top`** | ❌ |
| `page_title` | `content_above` | **`header_top`** | ❌ |
| `breadcrumbs` | `breadcrumb` | **`header_top`** | ❌ |
| `messages` | `highlighted` | **`header_top`** | ❌ |
| `help`, `powered`, `account_menu`, `primary_admin_actions`, `primary_local_tasks`, `secondary_local_tasks` | (nhiều) | **`header_top`** | ❌ |

Region của olivero (`header`, `highlighted`, `breadcrumb`, `content_above`, `footer_bottom`,
`secondary_menu`) **không tồn tại** trong `nidqc`, nên Drupal dồn hết vào `header_top` —
region **đầu tiên** trong `nidqc.info.yml`.

Theo design, `header_top` chỉ là **thanh mỏng 34px** chứa ngày / VI-EN / đăng nhập
(`PAGE_MAPPING.md` §4). Nhét 10 block vào đó là sai hoàn toàn.

**Vì sao KHÔNG sửa trong task này:** đặt block vào region nào = **dựng layout**, mà §3 ghi rõ
"Dựng giao diện giống design" là **ngoài phạm vi**. Sửa ở đây là tự ý mở rộng task — đúng thứ
`AGENTS.md` §2 cấm.

**Vì sao vẫn commit:** đây là config Drupal tự sinh, không phải quyết định của ai. Nhờ TASK-002,
config giờ nằm trong `config/sync/` nên sửa lại chỉ là `config:set` + `cex`. Không commit thì máy
khác vẫn sinh ra đúng mớ này khi cài theme — chẳng khá hơn, lại còn lệch giữa các máy.

→ **Task tiếp theo (dựng Twig + layout) PHẢI xử lý mớ block này.** Đừng tưởng `header_top` đang đúng.

### 9.5. 🟢 `README.md` của theme có chứa mã hex — cố ý

`grep '#[0-9a-fA-F]{6}'` trên thư mục theme sẽ khớp `README.md` (dòng 11, 15). Đó là **ví dụ dạy
anti-pattern** (`/* ❌ SAI */ .nav { background: #0F3093; }`), không phải CSS được nạp.

R6/AC6 chỉ soi `base.css` — và `base.css` sạch. Ghi ra đây để người review không tưởng là vi phạm,
và để nếu sau này viết lint rule "cấm hex ngoài tokens.css" thì **giới hạn phạm vi vào `css/`**,
đừng quét `.md`.

## 11. Output verify (chạy thật 2026-07-16)

```
$ ddev drush theme:install nidqc -y
[success] Successfully installed theme: nidqc

$ ddev drush pm:list --type=theme --status=enabled --format=list
claro / olivero / stable9 / nidqc                        # AC1 ✅

$ ddev drush config:get system.theme default --format=string
nidqc                                                    # AC2 ✅

$ curl -s -o /dev/null -w "%{http_code}" https://nidqc.ddev.site/
200                                                      # AC3 ✅

$ # đếm token trong CSS site phục vụ (xem §6.3)
21                                                       # AC4 ✅

$ ddev drush php:eval "... getLibraryByName('nidqc','global') ..."
[0] => themes/custom/nidqc/css/tokens.css                # tokens TRƯỚC base ✅
[1] => themes/custom/nidqc/css/base.css

$ grep -E '#[0-9a-fA-F]{6}' css/base.css
(rỗng)                                                   # AC6 ✅

$ # region
header_top / header_banner / primary_menu / content / footer / page_top / page_bottom
TỔNG: 7                                                  # AC7 ✅

$ ddev drush watchdog:show --severity=3 --count=10
[notice] No log messages available.                      # AC8 ✅
```

### ⭐ AC5 — token có bịa không

```
$ # đếm R3
màu: 18 | layout: 2 | font: 1 | TỔNG: 21                 # khớp R3 ✅

$ diff <(DESIGN_SYSTEM §7) <(tokens.css)
(rỗng) -> giống hệt nguyên văn, không sai lệch giá trị nào

$ comm -23 /tmp/token_colors.txt /tmp/design_colors.txt
(rỗng)                                                   # AC5 ✅
# 18/18 màu trong tokens.css đều TỒN TẠI THẬT trong design (design có 37 màu).
# Không màu nào bịa.
```

### Tái lập được không — điểm mà TASK-002 vừa mở khoá

```
$ ddev drush cex -y  &&  grep "^default:" config/sync/system.theme.yml
default: nidqc                                # đã vào config/sync, ĐƯỢC COMMIT

$ ddev drush config:set system.theme default olivero -y   # cố tình làm lệch
olivero
$ ddev drush cim -y  &&  ddev drush config:get system.theme default --format=string
nidqc                                         # ✅ khôi phục từ config/sync
```
Trước TASK-002, thay đổi này sẽ ghi vào `sites/default/files/sync` và **biến mất khi commit**.

### Kiểm trang thật

```
$ curl -s https://nidqc.ddev.site/ | wc -c
2614 bytes — không trắng

$ # CSS phục vụ, body:
body{font-family:var(--nidqc-font);color:var(--nidqc-text);background:var(--nidqc-white);}

$ # text trong body:
"Nhảy đến nội dung  Main navigation  Nhà  No front page content has been created yet..."

$ curl -s https://nidqc.ddev.site/ | grep -icE "fatal error|warning:|exception"
0                                             # không lỗi PHP
$ curl -s https://nidqc.ddev.site/ | grep -c "olivero"
0                                             # không còn markup olivero
```

> "No front page content" là **đúng** — site có 0 content type (xem §9.2), chưa có nội dung.
> Trang trông thô là **kỳ vọng đúng** của task này — xem §7.

## 10. Nhật ký

| Ngày | Người/Agent | Việc |
|---|---|---|
| 2026-07-16 | Claude | Soạn task. Đã kiểm tra trạng thái site thật: đã install, profile `standard`, langcode `vi`, theme `olivero`, `web/themes/custom/` chưa có, 0 content type, `config_sync_directory` bị gitignore. |
| 2026-07-16 | Claude | **Đã chạy thử toàn bộ lệnh §6 trước khi giao task.** Phát hiện và sửa 2 lệnh sai: `config:get system.theme default` trả rỗng nếu thiếu `--format=string`; `watchdog:show --severity=Error` lỗi exit 1 vì site langcode `vi` (Drush khớp nhãn đã dịch) — phải dùng `--severity=3`. Đã kiểm pipeline §6.4 bắt đúng màu bịa (`#ABCDEF` bị chặn, `#0F3093` qua). |
| 2026-07-16 | Claude | **Sửa R3 trước khi code.** R3 ghi "15 biến màu + 3 layout + 1 font" — đếm thật là **18 màu + 2 layout + 1 font = 21**. Số cũ viết theo trí nhớ, không phải đếm. Nếu để nguyên, người làm sẽ hoặc bịa thêm biến cho đủ 15, hoặc bối rối. |
| 2026-07-16 | Claude | **Sửa tham chiếu lạc.** §3 và §7 nói font `@font-face` là "TASK-002", nhưng TASK-002 hoá ra là task config. Đã đổi thành "task riêng, chưa đánh số". |
| 2026-07-16 | Claude | **Thực thi task.** R1–R7 xong, AC1–AC8 đạt, output ở §11. Đã export config: `system.theme.yml` = `nidqc`, và kiểm chứng khôi phục được (`olivero` → `cim` → `nidqc`). |
| 2026-07-16 | Claude | **Sửa lệnh verify AC4 — nó sai và suýt làm task bị đánh trượt oan.** Lệnh cũ grep `tokens.css` trong HTML, nhưng site bật CSS aggregation nên Drupal gộp file thành `css_<hash>.css` — tên `tokens.css` **không bao giờ** xuất hiện. Bản sửa lần 1 vẫn sai: neo `^\s*` không khớp vì CSS gộp đã **minify về một dòng** → luôn ra `0`. Bản cuối bỏ neo, ra đúng `21`. Đã ghi cả hai cái bẫy vào §6.3. |
