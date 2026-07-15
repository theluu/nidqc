---
id: TASK-006
title: Island mega-menu — nâng cấp menu chính, có bàn phím và cảm ứng
status: ready
step: 5                  # Vue Frontend
owner: <chưa gán>
reviewer: <chưa gán — KHÔNG được trùng owner>
created: 2026-07-16

schema_change: false
new_package: false       # dùng lại vue/vite của TASK-005. KHÔNG cài thêm gì.
config_change: false     # ⚠️ menu link là CONTENT, không phải config — xem §2.2

# PHỤ THUỘC: sau TASK-005 (hạ tầng Vite) và TASK-003 (page.html.twig).

allowed_files:
  - frontend/src/islands/MegaMenu.vue
  - frontend/src/main.js
  - web/themes/custom/nidqc/nidqc.theme
  - web/themes/custom/nidqc/css/layout.css
  - web/themes/custom/nidqc/templates/navigation/menu--main.html.twig
  - CHANGELOG.md

read_only:
  - design/
  - docs/architecture/FRONTEND_ARCHITECTURE.md
  - docs/standards/VUE_CODING_STANDARD.md
  - docs/design/DESIGN_SYSTEM.md
---

# TASK-006 — Island `mega-menu`

## 1. Mục tiêu

Menu chính mở submenu khi hover — **và dùng được bằng bàn phím lẫn cảm ứng**, thứ design không có.
Tắt JS thì menu vẫn là `<ul><li><a>` bấm được.

## 2. Bối cảnh — đã đo trên design thật

### 2.1. Hành vi trong design

```html
<header onmouseleave="{{ closeMenu }}">              <!-- rời header -> đóng -->
  <nav>
    <sc-for list="{{ navMenu }}" hint-placeholder-count="9">
      <div onmouseenter="{{ item.onEnter }}">        <!-- hover -> mở -->
        <a ref="{{ item.setRef }}" href="{{ item.href }}">{{ item.label }}</a>
      </div>
    </sc-for>
  </nav>
  <a href="#chat-chuan" title="Tra cứu">…</a>        <!-- nút tròn tìm kiếm -->
  <div style="{{ dropdownStyle }}">                  <!-- panel, vị trí theo menuRect -->
    <sc-for list="{{ activeChildren }}">…</sc-for>
  </div>
</header>
```

State: `{ openMenu: null, menuRect: null }`. `setRef` + `menuRect` → dropdown **canh theo vị trí
mục đang hover**, không phải canh trái cố định.

### 2.2. 🔴 Menu chính đang RỖNG, và menu link không tái lập được

Đã kiểm 2026-07-16:

| Việc | Kết quả |
|---|---|
| Số `menu_link_content` trong menu `main` | **0** |
| "Nhà" hiện đang thấy | Plugin tĩnh `standard.front_page`, **không phải** menu link |
| `menu_link_content` là entity loại gì | **`content`** — không phải `configuration` |
| `drush cex` có export menu link không | **KHÔNG.** `config/sync/system.menu.main.yml` chỉ là *vỏ* menu, không có link. |

**Hệ quả nghiêm trọng:** tạo 9 mục menu qua UI thì chúng chỉ nằm trong DB máy người tạo.
Người khác `git clone` + `drush cim` → **menu vẫn rỗng**. Đây đúng là thứ TASK-002 vừa sửa được
cho config, nhưng **content thì cex không lo**.

**Đã chứng minh bằng thực nghiệm** (2026-07-16), không phải suy luận:
```
$ # tạo 1 mục cha + 4 mục con trong menu main
$ ddev drush php:eval "...MenuLinkContent::create(...)..."
đã tạo menu tạm
$ ddev drush php:eval "...menuTree()->load('main')..."
  Nhà
  Giới thiệu (4 con)          <- menu ĐÃ có 5 link

$ ddev drush cex -y && git status --porcelain config/sync/
(rỗng)                        <- cex KHÔNG ghi ra gì cả
```
5 menu link vừa tạo **không sinh một dòng config nào**. Chúng chỉ sống trong DB.

→ Xem §9.1. Task này **không** giải bài toán đó.

### 2.3. Menu thật trong design (tham chiếu, chưa dựng được)

9 mục, 2 tầng. Ví dụ:
```
Trang chủ            (không con)
Giới thiệu           -> Giới thiệu chung / Chính sách chất lượng / Năng lực / Cơ cấu tổ chức
Hoạt động chuyên môn -> Chỉ đạo tuyến / Kiểm nghiệm và giám sát… / Hợp tác quốc tế / NRA / Tạp chí…
```
Phần lớn trỏ tới trang **chưa tồn tại** (0 content type). Dựng menu thật = **chặn bởi TASK-007**.

## 3. Phạm vi

### Trong phạm vi
- `MegaMenu.vue` — island nâng cấp menu có sẵn
- `menu--main.html.twig` — render `<ul><li><a>` + `data-island`, **dùng được khi tắt JS**
- CSS dropdown + nút tìm kiếm theo design
- Đăng ký island vào `registry`, `#attached` library `islands`

### ⛔ Ngoài phạm vi

| Không làm | Vì sao |
|---|---|
| **Tạo 9 mục menu thật** | Trỏ tới trang chưa tồn tại. Chặn bởi TASK-007. Xem §9.1. |
| **Giải bài "menu link không tái lập được"** | Vấn đề thật nhưng là quyết định kiến trúc riêng — §9.1. |
| **Nút tra cứu chất chuẩn hoạt động** | `/tim-kiem-chat-chuan` **không có design**, phạm vi chưa rõ. Task này chỉ dựng **nút**, trỏ `#chat-chuan` như design. |
| **Island khác** (FAQ, tabs, filter) | Task riêng. |
| **Sửa `tokens.css`** | Không cần token mới. |

## 4. Yêu cầu

- [ ] **R1** — `menu--main.html.twig`: render `<ul><li><a>` **lồng nhau đúng cấp**, bọc
      `<div data-island="mega-menu">`. **Không** ẩn submenu bằng `display:none` mặc định trong CSS
      — xem R4.

- [ ] **R2** — `MegaMenu.vue`:
      - Hover mục cha → mở dropdown; rời `<header>` → đóng (như design)
      - Dropdown canh theo **vị trí mục đang hover** (design dùng `menuRect`)
      - 🔴 **Bàn phím**: `Tab` đi qua từng mục; `Enter`/`Space` mở-đóng; `Esc` đóng và trả focus
        về mục cha; mũi tên xuống vào submenu. **Không bẫy focus.**
      - 🔴 **Cảm ứng**: chạm mục cha lần 1 → mở, không điều hướng; lần 2 → đi.
        (`pointerdown` + kiểm `pointerType`, đừng đoán bằng độ rộng màn hình.)
      - `aria-expanded`, `aria-controls` trên mục cha; `aria-hidden` đúng trạng thái

- [ ] **R3** — CSS theo design (`layout.css`, chỉ `var(--nidqc-*)`, **không hex**):
      | Thành phần | Giá trị |
      |---|---|
      | Dropdown item | `padding: 11px 18px`, `font-size: 13.5px`, `line-height: 18px`, màu `--nidqc-text` |
      | Dropdown item viền | `border-bottom: 1px solid var(--nidqc-bg-alt)` (`#F0F0F0`) |
      | Dropdown item hover | nền `--nidqc-primary-pale` (`#E8F0F7`), chữ `--nidqc-primary` |
      | Nút tra cứu | `42×34`, `border-radius: 18px`, nền `--nidqc-accent` (`#1D6AC5`), hover `--nidqc-accent-2` (`#1565B3`) |

- [ ] **R4** — 🔴 **Progressive enhancement.** Tắt JS → submenu **vẫn tới được**.
      Cách làm: submenu hiện qua `:hover`/`:focus-within` bằng **CSS thuần**; Vue chỉ thêm
      bàn phím, cảm ứng, aria và định vị. **Không** để trạng thái mở chỉ tồn tại trong Vue.

- [ ] **R5** — Đăng ký `'mega-menu'` vào `registry` trong `main.js`.

- [ ] **R6** — `nidqc.theme`: `#attached` library `nidqc/islands` **chỉ khi** trang có menu.
      **Không** nạp toàn site (`BACKEND_ARCHITECTURE.md` §4).

## 5. Tiêu chí chấp nhận

- [ ] **AC1** — `npm run build` sạch; `dist/island-*.js` có chunk riêng cho mega-menu (lazy load)
- [ ] **AC2** — HTML thô có `<ul><li><a>` **đủ mọi mục kể cả submenu** (kiểm bằng `curl`, không JS) — SEO
- [ ] **AC3** — 🔴 **Tắt JS: submenu vẫn tới được** (hover bằng CSS thuần)
- [ ] **AC4** — 🔴 **Chỉ dùng bàn phím**: Tab tới, Enter mở, Esc đóng, focus **luôn nhìn thấy**
- [ ] **AC5** — Chạm (cảm ứng): lần 1 mở, lần 2 đi
- [ ] **AC6** — `aria-expanded` đổi đúng `true`/`false` khi mở/đóng
- [ ] **AC7** — Không hex trong `layout.css`
- [ ] **AC8** — Không lỗi console
- [ ] **AC9** — `islands.js` **chỉ** nạp ở trang có menu, không nạp ở `/admin`
- [ ] **AC10** — Tương phản chữ dropdown ≥ 4.5:1

## 6. Cách verify

### 6.1. Build — AC1
```bash
ddev exec "cd frontend && npm run build"
ls web/themes/custom/nidqc/dist/
# phải có islands.js + chunk riêng cho mega-menu
```
> ⚠️ DDEV dùng **mutagen sync bất đồng bộ** — `ls` ngay sau build có thể báo chưa có file.
> Không phải lỗi. Chờ vài giây hoặc `ddev exec "ls web/themes/custom/nidqc/dist/"`.

### 6.2. ⭐ SEO — AC2: submenu có trong HTML thô không?
```bash
# KHÔNG dùng DevTools (nó hiện DOM sau JS). Phải dùng curl.
curl -s https://nidqc.ddev.site/ | grep -oE '<li[^>]*>.*?</li>' | head
curl -s https://nidqc.ddev.site/ | grep -c "Giới thiệu chung"   # > 0
```

### 6.3. 🔴 Tắt JS — AC3
DevTools → Settings → Debugger → **Disable JavaScript** → tải lại:
1. Menu vẫn hiện đủ mục
2. **Hover mục cha → submenu vẫn mở** (CSS thuần)
3. Bấm link submenu → đi được

> Không đạt = **task chưa xong**. Đây là ràng buộc cứng của `ADR-001`.

### 6.4. 🔴 Bàn phím — AC4, AC6
**Cất chuột đi.** Chỉ dùng bàn phím:
1. `Tab` → focus vào mục đầu, **viền focus nhìn rõ**
2. `Enter` → mở submenu, `aria-expanded="true"`
3. `↓` → vào mục con đầu
4. `Esc` → đóng, focus **quay lại mục cha**, `aria-expanded="false"`
5. `Tab` liên tục → thoát được menu, **không bị kẹt**

```js
// Kiểm aria trong Console
[...document.querySelectorAll('[aria-expanded]')].map(e => [e.textContent.trim(), e.ariaExpanded])
```

### 6.5. Cảm ứng — AC5
DevTools → Device toolbar (bật cảm ứng) → chạm mục cha:
- Lần 1: mở submenu, **không** nhảy trang
- Lần 2: đi

### 6.6. AC7, AC9
```bash
grep -E '#[0-9a-fA-F]{6}' web/themes/custom/nidqc/css/layout.css     # rỗng
curl -s https://nidqc.ddev.site/ | grep -c islands.js                 # > 0
curl -s https://nidqc.ddev.site/admin | grep -c islands.js            # 0
```

### 6.7. Chuẩn bị dữ liệu để test
Menu `main` đang **rỗng** (§2.2). Tạo menu tạm để test — **sẽ không được commit** vì menu link
là content:
```bash
ddev drush php:eval "
\\\$p = \Drupal\menu_link_content\Entity\MenuLinkContent::create([
  'title' => 'Giới thiệu', 'link' => ['uri' => 'internal:/'], 'menu_name' => 'main', 'weight' => 1,
]); \\\$p->save();
foreach (['Giới thiệu chung','Chính sách chất lượng','Năng lực','Cơ cấu tổ chức'] as \\\$i => \\\$t) {
  \Drupal\menu_link_content\Entity\MenuLinkContent::create([
    'title' => \\\$t, 'link' => ['uri' => 'internal:/'], 'menu_name' => 'main',
    'parent' => 'menu_link_content:' . \\\$p->uuid(), 'weight' => \\\$i,
  ])->save();
}
print 'đã tạo menu tạm';"
```
> ⚠️ Đây là **dữ liệu tạm để test**, không phải menu thật. Menu thật cần TASK-007. Xem §9.1.

## 7. Kỳ vọng đúng về kết quả

- **Menu chỉ có vài mục tạm**, không phải 9 mục như design — menu thật trỏ tới trang chưa tồn tại.
- **Nút tra cứu chưa hoạt động** — chỉ là nút, trỏ `#chat-chuan`. `/tim-kiem-chat-chuan` chưa có design.
- Đạt = **hành vi đúng**: hover mở, bàn phím dùng được, cảm ứng dùng được, tắt JS vẫn tới được submenu.

## 8. Bảo mật & Accessibility

- [ ] `docs/security/SECURITY_CHECKLIST.md` (mục B — XSS — quan trọng nhất)
- [ ] **Không `v-html`** với nhãn menu. Nhãn do biên tập viên nhập → dùng `{{ }}`.
- [ ] Không `|raw` trong `menu--main.html.twig`
- [ ] 🔴 **WCAG 2.1 AA** — site cơ quan nhà nước, không phải tuỳ chọn:
      bàn phím dùng được · focus nhìn rõ · `aria-expanded` đúng · tương phản ≥ 4.5:1 ·
      **hover không được là cách duy nhất**

> Design **chỉ có hover**. Làm y hệt design là loại người dùng bàn phím và điện thoại.
> `FRONTEND_ARCHITECTURE.md` §8 yêu cầu bàn phím + cảm ứng — **task này cố ý làm khác design**,
> theo hướng thêm vào, không bớt đi.

## 9. Câu hỏi mở

### 9.1. 🔴 Menu link là content → không tái lập được. Cần quyết định.

`menu_link_content` là entity **content**, `drush cex` **không** export (§2.2). Tạo menu qua UI thì
chỉ máy đó có. Đây là lỗ hổng thật trong quy trình mà TASK-002 chưa chạm tới.

Phương án (**chưa chọn** — cần người quyết, có thể cần ADR-002):

| Cách | Ưu | Nhược |
|---|---|---|
| **`hook_install()`** trong module custom | Không cần package mới; là code, vào git | Chỉ chạy 1 lần lúc cài module; sửa menu sau phải viết `hook_update_N` |
| **Drupal recipe** (`recipes/` đã có sẵn) | Cách chính thức của D11; chạy lại được | Đội chưa dùng recipe bao giờ |
| **`default_content`** module | Sinh ra để làm việc này | `composer require` → **cần duyệt** |
| **Nhập tay + tài liệu hoá** | Không tốn gì | Không tái lập; mỗi môi trường một kiểu — đúng thứ TASK-002 vừa sửa |

→ **Đừng chọn bừa khi làm task này.** Hỏi trước. Task này chỉ cần menu tạm để test (§6.7).

### 9.2. 🟡 Menu thật chặn bởi TASK-007

9 mục trong design trỏ tới `/gioi-thieu-chung`, `/nang-luc`… — **chưa có node nào**.
Dựng menu thật trước khi có trang = tạo một đống link chết.

→ Menu thật là **task riêng, sau TASK-007**.

### 9.3. 🟡 Nút tra cứu chất chuẩn

Design có nút tròn trỏ `#chat-chuan` (anchor trang chủ), nhưng cũng có link tới
`/tim-kiem-chat-chuan` — **trang không có design**. Xem `PROJECT_CONTEXT.md` §5.
Task này dựng nút theo đúng design (`#chat-chuan`), không hơn.

## 10. Nhật ký

| Ngày | Người/Agent | Việc |
|---|---|---|
| 2026-07-16 | Claude | Soạn task. Trích markup + JS mega menu từ design: hover mở, `onmouseleave` trên `<header>` đóng, dropdown canh theo `menuRect` của mục đang hover. Menu thật có 9 mục, 2 tầng. |
| 2026-07-16 | Claude | 🔴 **Sửa lại đánh giá sai của chính mình trong `ROADMAP.md`.** Tôi ghi TASK-006 "✅ làm được, không bị chặn" — **quá lạc quan**. Kiểm thật: menu `main` có **0 link** ("Nhà" là plugin tĩnh `standard.front_page`, không phải menu link); `menu_link_content` là entity **content** nên `drush cex` **không export** → menu tạo qua UI **không tái lập được** trên máy khác; và 9 mục trong design trỏ tới trang chưa tồn tại. Island **code được**, nhưng menu **thật** thì chặn bởi TASK-007. Đã ghi §9.1 và sửa ROADMAP. |
