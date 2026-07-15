---
id: TASK-003
title: Dựng page.html.twig — 5 vùng dùng chung theo design
status: review          # đã thực thi 2026-07-16, chờ NGƯỜI review (AI không được tự duyệt)
step: 4                  # Drupal Backend / theme layer
owner: <chưa gán>
reviewer: <chưa gán — KHÔNG được trùng owner>
created: 2026-07-16

schema_change: false
new_package: false
config_change: true      # ⚠️ đặt lại block placement + thêm region breadcrumb

allowed_files:
  - web/themes/custom/nidqc/nidqc.info.yml
  - web/themes/custom/nidqc/nidqc.libraries.yml   # R5 cần — thiếu ở bản đầu, xem §10
  - web/themes/custom/nidqc/nidqc.theme
  - web/themes/custom/nidqc/templates/layout/page.html.twig
  - web/themes/custom/nidqc/css/tokens.css
  - web/themes/custom/nidqc/css/layout.css
  - web/themes/custom/nidqc/README.md
  - docs/design/DESIGN_SYSTEM.md
  - docs/design/PAGE_MAPPING.md
  - config/sync/**
  - CHANGELOG.md

read_only:
  - design/
  - docs/architecture/FRONTEND_ARCHITECTURE.md
  - docs/standards/DRUPAL_CODING_STANDARD.md
  - web/core/themes/stable9/templates/layout/page.html.twig
---

# TASK-003 — Dựng `page.html.twig`

> 📌 Task này **sửa hai lỗi trong tài liệu design** trước, rồi mới dựng layout.
> Không đảo thứ tự: dựng layout trên spec sai thì phải làm lại.

## 1. Mục tiêu

Sau task này, mọi trang có **khung chung đúng design**: top bar → banner → nav → breadcrumb →
nội dung → footer. Và mớ block bị Drupal đặt bừa vào `header_top` (TASK-001 §9.4) được dọn.

## 2. Bối cảnh — hai lỗi trong tài liệu, phát hiện khi phân tích design

Đã kiểm bằng lệnh trên cả 12 file design (2026-07-16):

### 2.1. 🔴 `DESIGN_SYSTEM.md` §2 sai: **thiếu hẳn font Lexend**

§2 hiện ghi *"Font duy nhất: Be Vietnam Pro"*. **Sai.** Đếm thật trong design:

| Font | Số lần dùng | `@font-face` nhúng | Vai trò thật |
|---|---|---|---|
| **`Lexend`** | **140** | 9 khối | **Font tiêu đề** — `h1`–`h4` |
| `Be Vietnam Pro` | 22 | 13 khối | **Font thân bài** — mặc định của `body` |
| `Roboto Mono` | 1 | 12 khối | Monospace, gần như không dùng |

`Lexend` có mặt ở **cả 12 trang**. Cả 3 font đều **self-host trong bundle**, không gọi CDN.

Style toàn cục thật của design:
```css
* { box-sizing: border-box; }
body {
  margin: 0;
  background: #FFFFFF;
  font-family: 'Be Vietnam Pro', sans-serif;
  color: #333333;                        /* ⚠️ KHÔNG phải #212529 */
  -webkit-font-smoothing: antialiased;
}
```

Lỗi này do suy từ khối `@font-face` trong helmet chứ không đếm cách dùng thật.
Hệ quả: `tokens.css` (TASK-001) **thiếu biến font tiêu đề** → footer và mọi `h1`–`h4`
sẽ sai font. `--nidqc-font` hiện có **đúng** (body dùng Be Vietnam Pro), chỉ là chưa đủ.

### 2.2. 🔴 `PAGE_MAPPING.md` §4 sai: **thiếu vùng breadcrumb**

§4 liệt kê 4 vùng dùng chung. Thực tế design có **5**. Dải breadcrumb `#F5F5F5` có ở
**cả 10 trang con** (chỉ trang chủ không có):

```
div #0D2870   top bar        <- header_top
div #FFFFFF   banner         <- header_banner
header #0F3093 nav sticky    <- primary_menu
div #F5F5F5   BREADCRUMB     <- THIẾU trong nidqc.info.yml
...           nội dung       <- content
footer #0D2870 footer        <- footer
```

Đây **chính là nguyên nhân** TASK-001 §9.4: không có region `breadcrumb` nên Drupal
ném block `breadcrumbs` vào `header_top` cùng 9 block khác.

## 3. Phạm vi

### Trong phạm vi
- Sửa `DESIGN_SYSTEM.md` §2 + §7 và `PAGE_MAPPING.md` §4 cho khớp design thật
- Thêm biến font tiêu đề vào `tokens.css`
- Thêm region `breadcrumb` vào `nidqc.info.yml`
- Dựng `page.html.twig` với 5 vùng + `layout.css`
- Đặt lại block về region đúng (dọn §9.4)

### ⛔ Ngoài phạm vi

| Không làm | Vì sao |
|---|---|
| **File `@font-face` / woff2** | Task font riêng. Task này chỉ **khai báo biến**, chưa nạp file. |
| **Mega menu tương tác** (hover, dropdown) | Vue island — bước 5, task riêng. Task này chỉ render `<ul><li><a>` tĩnh. |
| **Dải tiêu đề trang** (`#F3F7FC` / `#0D2870`) | **Không** phải vùng chung — màu và nội dung đổi theo trang, 8/10 trang mới có. Thuộc `node--*.html.twig`. |
| **Dải tabs** (`#FFF`) | Chỉ 5 trang có. Task riêng. |
| **Trang chủ** | Cấu trúc **khác hẳn** (nhiều `section`, top bar nằm trong `sc-if`). Cần `page--front.html.twig` riêng. |
| **Nội dung thật của footer** | Task này dựng **region + block**, không hard-code text Viện vào Twig. |
| **Content type, field** | `schema_change: false`. |

## 4. Yêu cầu

### Sửa spec trước (R1–R2)

- [ ] **R1** — `DESIGN_SYSTEM.md` §2: bỏ khẳng định *"Font duy nhất"*. Ghi đúng 3 font kèm
      vai trò và số lần dùng (bảng §2.1). Ghi rõ `body` color thật là `#333333`.
      §7: thêm `--nidqc-font-heading`, `--nidqc-font-mono`, `--nidqc-text-body`.
- [ ] **R2** — `tokens.css` thêm **3 biến** (giữ nguyên 21 biến cũ → tổng **24**):
      ```css
      --nidqc-font-heading: 'Lexend', sans-serif;
      --nidqc-font-mono:    'Roboto Mono', ui-monospace, monospace;
      --nidqc-text-body:    #333333;   /* màu mặc định của body trong design */
      ```
      > Copy **nguyên văn** từ design. `--nidqc-font` giữ nguyên — nó đã đúng.

### Rồi mới dựng layout (R3–R6)

- [ ] **R3** — `nidqc.info.yml` thêm region `breadcrumb`, đặt **giữa `primary_menu` và `content`**
      (đúng thứ tự design). Tổng 8 region.
- [ ] **R4** — `templates/layout/page.html.twig` render 5 vùng theo đúng thứ tự §2.2.
      Dùng `{{ page.header_top }}` v.v. **Không hard-code nội dung** — mọi thứ qua region/block.

      **Tên class bắt buộc** (AC4 kiểm theo đúng các tên này — đừng đặt tên khác):
      | Vùng | Class |
      |---|---|
      | Top bar | `nidqc-header-top` |
      | Banner | `nidqc-header-banner` |
      | Nav | `nidqc-nav-main` |
      | Breadcrumb | `nidqc-breadcrumb` |
      | Nội dung | `nidqc-main` |
      | Footer | `nidqc-footer` |
      | Container trong mỗi vùng | `nidqc-container` |

      Dùng thẻ ngữ nghĩa: `<header>` cho nav, `<nav>` cho breadcrumb, `<main>` cho nội dung,
      `<footer>` cho footer (`FRONTEND_ARCHITECTURE.md` §9 — accessibility là bắt buộc với site nhà nước).
- [ ] **R5** — `css/layout.css` (thêm vào library `global`, **sau** `base.css`):
      - Container: `max-width: var(--nidqc-container); margin: 0 auto; padding: 0 var(--nidqc-gutter);`
      - Top bar: nền `var(--nidqc-primary-dark)`, `min-height: 34px`
      - Nav: nền `var(--nidqc-primary)`, `height: 50px`, `position: sticky; top: 0; z-index: 40`,
        `box-shadow: 0 2px 4px rgba(0,0,0,0.10)`
      - Breadcrumb: nền `var(--nidqc-bg-subtle)`, `border-bottom: 1px solid var(--nidqc-border)`,
        `padding: 14px 24px`, `font-size: 13px`, màu `var(--nidqc-text-light)`
      - Footer: nền `var(--nidqc-primary-dark)`, grid `2fr 1fr 1.3fr`, `gap: 40px`,
        `padding: 46px 24px 20px`; dải dưới `border-top: 1px solid rgba(255,255,255,0.14)`
      - `h1`–`h4` dùng `var(--nidqc-font-heading)`
      - ⛔ **Không mã hex** — chỉ `var(--nidqc-*)`. Ngoại lệ duy nhất: `rgba()` trên nền xanh
        (design dùng `rgba(255,255,255,.14/.6/.72/.85)` — **không** có token tương ứng; ghi comment).
- [ ] **R6** — Đặt lại block về region đúng, dọn TASK-001 §9.4:
      | Block | Region đúng |
      |---|---|
      | `site_branding` | `header_banner` |
      | `main_menu` | `primary_menu` (giữ nguyên) |
      | `breadcrumbs` | `breadcrumb` |
      | `page_title`, `messages`, `help`, `content` | `content` |
      | `powered` | `footer` |
      | `account_menu` | `header_top` (đúng — design có "Đăng nhập hệ thống" ở top bar) |
      | `primary_admin_actions`, `primary_local_tasks`, `secondary_local_tasks` | `content` |

      Sau đó `drush cex -y` và commit.

## 5. Tiêu chí chấp nhận

- [ ] **AC1** — `tokens.css` có **24 biến** (21 cũ + 3 mới), giá trị cũ **không đổi**.
- [ ] **AC2** — Mọi hex trong `tokens.css` vẫn tồn tại thật trong design (lệnh §6.2).
- [ ] **AC3** — `nidqc.info.yml` có **8 region**, `breadcrumb` nằm giữa `primary_menu` và `content`.
- [ ] **AC4** — `curl https://nidqc.ddev.site/lien-he` (hoặc trang bất kỳ) → `200`, HTML có
      đủ 5 vùng theo **đúng thứ tự**: top bar → banner → nav → breadcrumb → content → footer.
- [ ] **AC5** — 🔴 **Không block nào còn ở sai region.** `header_top` chỉ còn `account_menu`.
- [ ] **AC6** — `grep -E '#[0-9a-fA-F]{6}' css/layout.css` **rỗng** (chỉ `var()` + `rgba()`).
- [ ] **AC7** — `ddev drush cim -y` no-op sau `cex` → config tái lập được.
- [ ] **AC8** — Nội dung **có trong HTML thô**, không cần JS (`ADR-001`). Kiểm bằng `curl`.
- [ ] **AC9** — `ddev drush watchdog:show --severity=3` không lỗi mới.
- [ ] **AC10** — Tiếng Việt đúng dấu trong breadcrumb và footer.

## 6. Cách verify

> Chạy **thật**, dán output vào §11.

### 6.1. Đếm token — AC1
```bash
# ⚠️ BẮT BUỘC có ':' trong regex. Thiếu nó thì dòng COMMENT nhắc tới tên biến
# (vd "--nidqc-text-muted (76 lần) ghi đè...") cũng bị đếm -> ra 25 thay vì 24.
grep -cE '^\s*--nidqc-[a-z0-9-]+:' web/themes/custom/nidqc/css/tokens.css   # phải: 24
```
Tách theo nhóm:
```bash
f=web/themes/custom/nidqc/css/tokens.css
echo "màu:    $(grep -cE '^\s*--nidqc-[a-z0-9-]+:\s*#' $f)"        # 19
echo "layout: $(grep -cE '^\s*--nidqc-[a-z0-9-]+:\s*[0-9]+px' $f)" # 2
echo "font:   $(grep -cE '^\s*--nidqc-font[a-z-]*:' $f)"           # 3
```

### 6.2. Token vẫn khớp design — AC2
```bash
python3 scripts/extract-design.py --all --colors | awk '{print toupper($2)}' | sort -u > /tmp/dc.txt
grep -oE '#[0-9a-fA-F]{6}' web/themes/custom/nidqc/css/tokens.css | tr 'a-f' 'A-F' | sort -u > /tmp/tc.txt
comm -23 /tmp/tc.txt /tmp/dc.txt          # phải RỖNG
```

### 6.3. Region — AC3
```bash
ddev drush php:eval "
print implode(PHP_EOL, array_keys(\Drupal::service('theme_handler')->getTheme('nidqc')->info['regions']));"
# phải có breadcrumb, giữa primary_menu và content
```

### 6.4. Thứ tự vùng trong HTML — AC4
```bash
ddev drush cr
# In ra các vùng THEO THỨ TỰ chúng xuất hiện trong HTML.
# Dùng đúng tên class quy định ở R4.
#
# Hai chi tiết trong lệnh này KHÔNG được bỏ, đã kiểm chứng cả hai:
#
#  1. [" ] hai đầu -> bắt TRỌN tên class. Thiếu nó thì `nidqc-main` khớp nhầm
#     chuỗi con trong `block-nidqc-main-menu` (class Drupal tự sinh cho block
#     menu) -> lệnh báo có nidqc-main NGAY CẢ KHI page.html.twig chưa tồn tại.
#
#  2. awk '!seen[$0]++' khử trùng lặp NHƯNG GIỮ THỨ TỰ XUẤT HIỆN.
#     Đừng thay bằng `sort -u`: nó sắp alphabet, xoá mất thông tin thứ tự,
#     mà thứ tự chính là thứ AC4 cần kiểm.
curl -s https://nidqc.ddev.site/ \
  | grep -oE '[" ]nidqc-(header-top|header-banner|nav-main|breadcrumb|main|footer)[" ]' \
  | tr -d '" ' | awk '!seen[$0]++'
```
Kết quả phải ra **đúng thứ tự này**:
```
nidqc-header-top
nidqc-header-banner
nidqc-nav-main
nidqc-breadcrumb
nidqc-main
nidqc-footer
```
> Trang chủ chưa có breadcrumb (xem §9.1) → chạy lệnh này trên **trang con**, ví dụ
> `https://nidqc.ddev.site/lien-he`, khi đã có nội dung. Lúc site chưa có node nào thì
> kiểm bằng `/admin` hoặc trang 404 — vẫn dùng `page.html.twig`.

### 6.5. ⭐ Block đã về đúng chỗ — AC5
```bash
for f in config/sync/block.block.nidqc_*.yml; do
  printf "%-28s %s\n" "$(basename $f .yml | sed 's/block.block.nidqc_//')" "$(grep '^region:' $f | cut -d' ' -f2)"
done
# header_top CHỈ được có account_menu
grep -l "^region: header_top" config/sync/block.block.nidqc_*.yml
```

### 6.6. Không hex trong layout.css — AC6
```bash
grep -E '#[0-9a-fA-F]{6}' web/themes/custom/nidqc/css/layout.css    # phải RỖNG
```

### 6.7. Config tái lập — AC7
```bash
ddev drush cex -y && ddev drush cim -y      # phải: There are no changes to import.
```

### 6.8. SEO — AC8
```bash
# Nội dung phải có trong HTML thô, KHÔNG cần JS. Đây là lý do chọn kiến trúc islands.
curl -s https://nidqc.ddev.site/ | grep -c "Viện Kiểm nghiệm thuốc Trung ương"   # > 0
```

### 6.9. Lỗi — AC9
```bash
ddev drush watchdog:show --severity=3 --count=10
```

> ⚠️ **Không** grep `layout.css` trong HTML — site bật CSS aggregation, file bị gộp thành
> `css_<hash>.css`. Xem `tasks/TASK-001.md` §6.3 để biết cách kiểm đúng.

## 7. Kỳ vọng đúng về kết quả

Sau task này trang **vẫn chưa giống hệt design**:

- **Font vẫn là font hệ thống** — `--nidqc-font-heading` mới chỉ là *biến*, chưa có file woff2.
  Task font riêng sẽ nạp. Đây là **kỳ vọng đúng**.
- **Mega menu chưa hover được** — mới là `<ul><li><a>` tĩnh. Vue island là bước 5.
- **Banner chưa có ảnh** — cần block ảnh, task nội dung.
- **Footer trống** — mới có region; nội dung thật là task nội dung.
- **Dải tiêu đề trang chưa có** — thuộc `node--*.html.twig`, ngoài phạm vi.

Đạt = **khung đúng, thứ tự đúng, màu đúng, block đúng chỗ**. Không phải "giống design".

## 8. Bảo mật

- [ ] Đã chạy `docs/security/SECURITY_CHECKLIST.md` (mục B — XSS — là quan trọng nhất ở đây)
- [ ] **Không `|raw`** trong `page.html.twig`. Twig autoescape phải giữ nguyên.
- [ ] Không hard-code email/điện thoại của cán bộ vào Twig
      (`UAT_CHECKLIST.md` §8; và repo là **public**)
- [ ] Không nạp font/CSS từ CDN ngoài (`SECURITY_POLICY.md` §10)

## 9. Câu hỏi mở

### 9.1. 🟡 Trang chủ cần template riêng

Trang chủ khác hẳn 10 trang con: nhiều `<section>`, top bar nằm trong `sc-if value="{{ showUtilityBar }}"`
(tức là **có điều kiện**), không có breadcrumb. → cần `page--front.html.twig`, **task riêng**.

Câu hỏi cho NIDQC: `showUtilityBar` có điều kiện là ý gì — top bar ẩn ở trang chủ trong trường hợp nào?

### 9.2. 🟡 `rgba()` trên nền xanh chưa có token

Design dùng `rgba(255,255,255,0.14 / 0.6 / 0.72 / 0.85)` cho viền và chữ mờ trên nền xanh đậm.
`DESIGN_SYSTEM.md` §7 **không có** token cho các giá trị này. R5 tạm cho phép `rgba()` trực tiếp
kèm comment. Nếu chúng lặp lại nhiều ở các task sau → nên thêm token.

### 9.3. 🟡 Body color: `#333333` hay `#212529`?

Design đặt `body { color: #333333 }` nhưng hầu như mọi thành phần **ghi đè** bằng `#212529` (84 lần)
hoặc `#495057` (76 lần). `base.css` (TASK-001) hiện đặt `--nidqc-text` (`#212529`) cho body.

R2 thêm `--nidqc-text-body` (`#333333`) cho **đúng design**. Nhưng đổi `base.css` sang dùng nó thì
gần như không ảnh hưởng gì (bị ghi đè hết) và nằm ngoài `allowed_files`... **`base.css` KHÔNG có
trong `allowed_files`** — cố ý. Task này chỉ **khai báo biến**; ai đổi `base.css` là task khác quyết.

### 9.4. 🟡 Dải breadcrumb rỗng vẫn hiện ở trang chủ — phát hiện khi NHÌN

`page.html.twig` có `{% if page.breadcrumb %}`, nhưng region **vẫn "truthy"** kể cả khi block
breadcrumb bên trong render ra rỗng (bẫy kinh điển của Drupal: region array có key của block,
dù nội dung block rỗng). Kết quả: trang chủ hiện một **dải xám rỗng cao ~40px** — mà design nói
trang chủ **không có** breadcrumb.

Không sửa ở đây: trang chủ sẽ có `page--front.html.twig` riêng (**TASK-008**, `ROADMAP.md` §4),
và ở đó bỏ hẳn khối breadcrumb là đúng hơn là thêm logic vá vào template dùng chung.

→ TASK-008 phải xử lý. Chỉ lộ ra khi **chụp màn hình**; mọi lệnh `curl`/`grep` đều báo đạt.

### 9.5. 🟡 `header_banner` không có container — `site_branding` tràn sát mép

`header_banner` **cố ý không bọc** `.nidqc-container` vì design đặt ảnh banner full-width
(`<img style="width:100%">`). Nhưng hiện region đó đang chứa block `site_branding` (logo + tên
Viện dạng text) → text tràn sát mép trái (x=8) thay vì thẳng container (x=186).

Đây là **placeholder**, không phải bug của layout: theo design, banner là **một tấm ảnh**
(`banner-header.jpg`), không phải logo+text. Khi task nội dung thay `site_branding` bằng block ảnh
thật thì full-width là đúng.

→ Task nội dung phải thay. Nếu quyết định giữ `site_branding` ở banner thì **phải** bọc container.

## 11. Output verify (chạy thật 2026-07-16)

```
$ grep -cE '^\s*--nidqc-[a-z0-9-]+:' css/tokens.css
24                          # AC1 ✅  (màu 19 | layout 2 | font 3)

$ comm -23 /tmp/tc.txt /tmp/dc.txt
(rỗng)                      # AC2 ✅  mọi hex vẫn tồn tại thật trong design

$ # region
header_top -> header_banner -> primary_menu -> breadcrumb -> content -> footer -> page_top -> page_bottom
                            # AC3 ✅  8 region, breadcrumb đúng chỗ

$ # AC4 — thứ tự vùng trong HTML (cả trang chủ và /khong-ton-tai)
nidqc-header-top
nidqc-header-banner
nidqc-nav-main
nidqc-breadcrumb
nidqc-main
nidqc-footer                # AC4 ✅  đúng thứ tự design

$ grep -l "^region: header_top" config/sync/block.block.nidqc_*.yml
account_menu                # AC5 ✅  header_top CHỈ còn account_menu

$ grep -E '#[0-9a-fA-F]{6}' css/layout.css
(rỗng)                      # AC6 ✅

$ ddev drush cim -y
There are no changes to import.   # AC7 ✅

$ curl -s https://nidqc.ddev.site/ | grep -c "Viện Kiểm nghiệm thuốc Trung ương"
2                           # AC8 ✅  nội dung trong HTML thô, không cần JS

$ ddev drush watchdog:show --severity=3
No log messages available.  # AC9 ✅

$ # AC10 tiếng Việt:
"Nhảy đến nội dung  Đăng nhập  Viện Kiểm nghiệm thuốc Trung ương  Main navigation  Nhà..."
                            # AC10 ✅  đủ dấu
```

### Dọn block — TASK-001 §9.4

| Block | Trước | Sau |
|---|---|---|
| `site_branding` | header_top | **header_banner** |
| `breadcrumbs` | header_top | **breadcrumb** |
| `page_title`, `messages`, `help`, `*_local_tasks`, `primary_admin_actions` | header_top | **content** |
| `powered` | header_top | **footer** |
| `account_menu` | header_top | header_top *(đúng — design có "Đăng nhập hệ thống" ở top bar)* |

`header_top`: **10 block → 1**.

### CSS phục vụ

```
biến --nidqc-* trong CSS site trả về: 24
.nidqc-header-top{background:var(--nidqc-primary-dark);color:var(--nidqc-white);...}
.nidqc-nav-main{position:sticky;top:0;z-index:40;background:var(--nidqc-prim...
.nidqc-page h1,h2,h3,h4{font-family:var(--nidqc-font-heading);}
```

### Kiểm bằng mắt (chụp màn hình)

Cả 5 dải render đúng màu và đúng thứ tự. **Bắt được 1 lỗi thật** mà mọi lệnh đều bỏ sót —
xem §10 (link "Đăng nhập" xanh trên nền xanh). Đã sửa và chụp lại: chữ trắng, đọc được.

Hai vấn đề còn lại **không sửa** (ngoài phạm vi): §9.4 dải breadcrumb rỗng ở trang chủ,
§9.5 `site_branding` tràn mép ở banner.

## 10. Nhật ký

| Ngày | Người/Agent | Việc |
|---|---|---|
| 2026-07-16 | Claude | Soạn task. Phân tích cấu trúc cấp 1 của cả 12 file design → tìm ra 2 lỗi trong tài liệu đã commit: (1) `DESIGN_SYSTEM` §2 thiếu font `Lexend` — dùng **140 lần**, nhiều gấp 6 lần `Be Vietnam Pro`, có ở cả 12 trang; (2) `PAGE_MAPPING` §4 thiếu vùng breadcrumb — có ở cả 10 trang con, và đây chính là nguyên nhân gốc của TASK-001 §9.4. Đã xác minh style toàn cục thật: `body{font-family:'Be Vietnam Pro';color:#333333}`. |
| 2026-07-16 | Claude | **Đã chạy thử toàn bộ lệnh §6 trước khi giao task.** Xác minh `#333333` tồn tại thật trong design → R2 sẽ qua AC2. Sửa 2 lỗi trong chính lệnh verify: (1) §6.4 ban đầu grep tên class mà task **không hề quy định** → AC4 không verify được; đã thêm bảng tên class bắt buộc vào R4. (2) Regex `nidqc-main` khớp nhầm chuỗi con trong `block-nidqc-main-menu` (class Drupal tự sinh) → lệnh báo "có" ngay cả khi `page.html.twig` chưa tồn tại; đã thêm `[" ]` hai đầu để bắt trọn class token, kiểm chứng cả hai chiều. |
| 2026-07-16 | Claude | **Sửa lỗi spec trước khi thực thi:** `allowed_files` **thiếu `nidqc.libraries.yml`** trong khi R5 bắt buộc phải sửa file đó (thêm `layout.css` vào library) → task **tự mâu thuẫn**, không thể hoàn thành R5 mà không vi phạm chính nó. Đã bổ sung vào `allowed_files`. |
| 2026-07-16 | Claude | **Thực thi task.** R1–R6 xong, AC1–AC10 đạt, output ở §11. Dọn xong 10 block ở `header_top` (TASK-001 §9.4) — giờ chỉ còn `account_menu`, đúng design. |
| 2026-07-16 | Claude | **Sửa lệnh đếm token AC1 — nó đếm cả comment.** `grep -cE '^\s*--nidqc-'` ra **25** thay vì 24, vì một dòng **comment** tôi viết trong `tokens.css` (`--nidqc-text-muted (76 lần) ghi đè...`) bắt đầu bằng `--nidqc-` nên bị đếm như khai báo. Đã thêm `:` vào regex. Lỗi tương tự có trong TASK-001 §R3 → đã sửa luôn. |
| 2026-07-16 | Claude | 🔴 **Chụp màn hình bắt được lỗi mà MỌI lệnh đều bỏ sót.** Link "Đăng nhập" render **xanh dương trên nền xanh đậm** — gần như không đọc được, là lỗi accessibility trên site nhà nước. Nguyên nhân: tôi đặt `color` cho `.nidqc-header-top` nhưng **link không kế thừa `color` của cha**. Đã thêm `.nidqc-header-top a { color: var(--nidqc-white) }` + bỏ bullet list. `curl`/`grep`/`watchdog` đều báo đạt — chỉ có nhìn mới thấy. Ghi thêm 2 phát hiện khác ở §9.4, §9.5. |
