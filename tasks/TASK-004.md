---
id: TASK-004
title: Self-host font Lexend + Be Vietnam Pro trích từ design bundle
status: ready
step: 4                  # theme layer
owner: <chưa gán>
reviewer: <chưa gán — KHÔNG được trùng owner>
created: 2026-07-16

schema_change: false
new_package: false       # KHÔNG npm/composer — font trích từ design có sẵn
config_change: false

# ⚠️ PHỤ THUỘC: làm SAU TASK-003.
# TASK-003 (R1-R2) sửa DESIGN_SYSTEM §2 và thêm --nidqc-font-heading vào tokens.css.
# Task này nạp file font cho biến đó trỏ tới. Làm ngược thì @font-face không có ai dùng.
# Cả hai task cùng sửa nidqc.libraries.yml và DESIGN_SYSTEM.md -> đừng chạy song song.

allowed_files:
  - scripts/extract-fonts.py
  - web/themes/custom/nidqc/fonts/**
  - web/themes/custom/nidqc/css/fonts.css
  - web/themes/custom/nidqc/nidqc.libraries.yml
  - web/themes/custom/nidqc/README.md
  - CHANGELOG.md

read_only:
  - design/
  - scripts/extract-design.py
  - docs/design/DESIGN_SYSTEM.md
  - docs/security/SECURITY_POLICY.md
---

# TASK-004 — Self-host font từ design bundle

## 1. Mục tiêu

Trang hiển thị **đúng font của design**: `Lexend` cho tiêu đề, `Be Vietnam Pro` cho thân bài,
tiếng Việt đủ dấu. Font **self-host**, **không gọi Google Fonts CDN** — site cơ quan nhà nước
không phụ thuộc hạ tầng nước ngoài (`SECURITY_POLICY.md` §10).

## 2. Bối cảnh — dữ liệu đã đo, không phải phỏng đoán

Design bundle **đã nhúng sẵn toàn bộ font** dạng woff2 base64. Không cần tải từ đâu.
Đã kiểm chứng 2026-07-16 (xem §11 để biết cách):

| Font | Weight | File cần | Dung lượng | Vai trò |
|---|---|---|---|---|
| **Lexend** | 500, 600, 700 | **3** (variable) | ~88 KB | Tiêu đề `h1`–`h4` — **dùng 140 lần** |
| **Be Vietnam Pro** | 400, 500, 600, 700 | **12** | ~101 KB | Thân bài (`body`) — dùng 22 lần |
| ~~Roboto Mono~~ | 400, 500 | ~~6~~ | ~~134 KB~~ | ⛔ **BỎ** — xem §3 |

**Tổng: 15 file, ~189 KB.**

Mỗi font có 3 subset: `vietnamese`, `latin-ext`, `latin`. **Subset `vietnamese` là bắt buộc** —
thiếu nó thì chữ có dấu bị vỡ hoặc rơi về font fallback.

### Lexend là variable font — chỉ cần 3 file, không phải 9

Đã kiểm bằng UUID trong manifest: cả 3 weight (500/600/700) của mỗi subset **trỏ vào cùng một file**.

```
f7d27b15...  ->  Lexend w500 vietnamese, Lexend w600 vietnamese, Lexend w700 vietnamese
a091d708...  ->  Lexend w500 latin-ext,  Lexend w600 latin-ext,  Lexend w700 latin-ext
d05800f4...  ->  Lexend w500 latin,      Lexend w600 latin,      Lexend w700 latin
```

→ Trích **3 file**, khai báo `@font-face` với `font-weight: 500 700` (dải weight của variable font).
Trích 9 file là **chép trùng cùng một nội dung 3 lần**.

Be Vietnam Pro thì **ngược lại** — mỗi weight một file riêng, phải đủ 12.

## 3. Phạm vi

### Trong phạm vi
- `scripts/extract-fonts.py` — trích font từ bundle, **lặp lại được**, không làm tay
- 15 file woff2 vào `web/themes/custom/nidqc/fonts/`
- `css/fonts.css` — khai báo `@font-face`
- Thêm `fonts.css` vào library `global`

### ⛔ Ngoài phạm vi

| Không làm | Vì sao |
|---|---|
| **Roboto Mono** | Dùng **đúng 1 lần** trong toàn bộ design (trang Đào tạo NCKH). 134 KB cho 1 chỗ dùng là không đáng. Cần thì mở task riêng. |
| **Tải font từ Google Fonts** | Đã có sẵn trong bundle. Tải ngoài = phụ thuộc CDN + có thể lệch phiên bản với design. |
| **`npm install` font package** | `new_package: false`. Không cần. |
| **Sửa `tokens.css`** | Biến font là việc của **TASK-003**. Task này chỉ nạp file. |
| **Sửa `base.css` / `layout.css`** | Không thuộc task này. |
| **Subset thêm / nén lại font** | Font trong bundle đã subset sẵn theo Google Fonts. Đừng tự tối ưu. |

## 4. Yêu cầu

- [ ] **R1** — `scripts/extract-fonts.py`:
      - Đọc `@font-face` trong helmet của một file design, map `uuid → (family, weight, subset)`
      - **Khử trùng lặp theo uuid** — Lexend 3 weight dùng chung 1 file (§2)
      - Giải nén từ manifest (`base64` → `gzip` nếu `compressed`) và ghi ra woff2
      - **Kiểm magic bytes `wOF2`** trước khi ghi; sai thì báo lỗi, không ghi file hỏng
      - Chỉ trích `Lexend` và `Be Vietnam Pro`. **Không** `Roboto Mono`.
      - Đặt tên file: `<family-slug>-<weight>-<subset>.woff2`,
        vd `lexend-500-700-vietnamese.woff2`, `be-vietnam-pro-400-latin.woff2`
      - Chỉ đọc `design/` — **không bao giờ ghi vào đó**

- [ ] **R2** — Chạy script, ghi **15 file** vào `web/themes/custom/nidqc/fonts/`.

- [ ] **R3** — `css/fonts.css` khai báo `@font-face`, copy **nguyên văn** `unicode-range`
      từ design (đừng tự chế — sai một ký tự là chữ Việt vỡ):
      ```css
      /* Lexend — variable, một file phục vụ weight 500–700 */
      @font-face {
        font-family: 'Lexend';
        font-style: normal;
        font-weight: 500 700;          /* dải weight, KHÔNG phải 3 khai báo riêng */
        font-display: swap;
        src: url('../fonts/lexend-500-700-vietnamese.woff2') format('woff2');
        unicode-range: U+0102-0103, U+0110-0111, ... ;   /* copy y nguyên từ design */
      }
      ```
      - `font-display: swap` cho **mọi** khai báo (design dùng vậy)
      - Thứ tự subset: `vietnamese` → `latin-ext` → `latin` (như design)

- [ ] **R4** — `nidqc.libraries.yml`: thêm `fonts.css` vào library `global`,
      **trước `tokens.css`**. `@font-face` phải được khai báo trước khi có gì dùng tới.

- [ ] **R5** — 🔴 **Kiểm giấy phép font.** `Lexend` và `Be Vietnam Pro` là font Google Fonts.
      **Phải xác minh** giấy phép (nhiều khả năng SIL OFL 1.1) và **kèm file license** vào
      `web/themes/custom/nidqc/fonts/LICENSE-<font>.txt`.
      > ⛔ **Không tự khẳng định giấy phép từ trí nhớ.** Tra từ nguồn chính thức
      > (Google Fonts / repo gốc của font). Đây là site cơ quan nhà nước — dùng font sai
      > giấy phép là rủi ro pháp lý thật. Không xác minh được → **dừng và hỏi**, đừng đoán.

- [ ] **R6** — `README.md` của theme: ghi font đến từ đâu, cách trích lại, và **không** tải từ CDN.

## 5. Tiêu chí chấp nhận

- [ ] **AC1** — `ls web/themes/custom/nidqc/fonts/*.woff2 | wc -l` → **15**
- [ ] **AC2** — **Mọi** file là woff2 hợp lệ (magic `wOF2`) — lệnh §6.2
- [ ] **AC3** — Không file `Roboto Mono` nào
- [ ] **AC4** — Có đúng **5 file subset `vietnamese`**: 1 Lexend (variable, gộp 500–700)
      + 4 Be Vietnam Pro (400/500/600/700). Xem §6.3.
- [ ] **AC5** — `fonts.css` **không** chứa `fonts.googleapis.com` / `fonts.gstatic.com` / `http`
- [ ] **AC6** — Font được phục vụ: `curl -I <font-url>` → `200`, `content-type: font/woff2`
- [ ] **AC7** — HTML/CSS site **không** có request nào ra domain ngoài
- [ ] **AC8** — File license có mặt (R5)
- [ ] **AC9** — 🔴 **Chữ Việt có dấu hiển thị đúng font** — kiểm bằng mắt, §6.6
- [ ] **AC10** — `ddev drush watchdog:show --severity=3` không lỗi mới

## 6. Cách verify

> Chạy **thật**, dán output vào §11.

### 6.1. Đếm file — AC1, AC3
```bash
ls web/themes/custom/nidqc/fonts/*.woff2 | wc -l          # 15
ls web/themes/custom/nidqc/fonts/ | grep -ci roboto       # 0
```

### 6.2. ⭐ Mọi file là woff2 hợp lệ — AC2
```bash
# File font hỏng vẫn "tồn tại" và vẫn đếm được -> phải kiểm NỘI DUNG, không chỉ đếm.
for f in web/themes/custom/nidqc/fonts/*.woff2; do
  head -c4 "$f" | grep -q 'wOF2' || echo "HỎNG: $f"
done
echo "(không in gì = tất cả hợp lệ)"

# Cách khác, độc lập:
file web/themes/custom/nidqc/fonts/*.woff2 | grep -cv "Web Open Font Format (Version 2)"
# phải ra 0
```

### 6.3. Subset vietnamese — AC4
```bash
ls web/themes/custom/nidqc/fonts/ | grep -c vietnamese
```
Con số đúng là **5**: Lexend 1 file (variable, gộp 500–700) + Be Vietnam Pro 4 file (400/500/600/700).
> ⚠️ Đừng kỳ vọng 6. Lexend variable **không** tách theo weight — xem §2.

### 6.4. Không phụ thuộc CDN — AC5, AC7
```bash
grep -iE "googleapis|gstatic|https?://" web/themes/custom/nidqc/css/fonts.css
echo "(rỗng = đạt)"

ddev drush cr
curl -s https://nidqc.ddev.site/ | grep -oE 'https?://[^"'"'"' ]+' | grep -v nidqc.ddev.site | sort -u
echo "(rỗng = không gọi ra ngoài)"
```

### 6.5. Font được phục vụ — AC6
```bash
f=$(ls web/themes/custom/nidqc/fonts/*.woff2 | head -1 | sed 's|web/||')
curl -s -o /dev/null -w "%{http_code} %{content_type}\n" "https://nidqc.ddev.site/$f"
# phải: 200 font/woff2
```

### 6.6. 🔴 Kiểm bằng mắt — AC9, không lệnh nào thay được

Font **nạp được** không có nghĩa là **hiển thị đúng**. Phải nhìn:

1. Mở `https://nidqc.ddev.site/`
2. DevTools → Network → lọc `Font` → phải thấy woff2 tải về, status `200`, **từ domain của mình**
3. DevTools → Elements → chọn một `h1` → Computed → `font-family` phải là **`Lexend`**
4. Chọn một đoạn text thường → phải là **`Be Vietnam Pro`**
5. 🔴 **Kiểm chữ Việt có dấu**: `Viện Kiểm nghiệm thuốc Trung ương — Đảm bảo chất lượng`
   - Mọi dấu phải hiện đủ: `ệ ể ữ ợ ấ ượ đ Đ ả`
   - **Không** ký tự nào rơi về font khác (nhìn lệch nét, lệch chiều cao)
   - Đây là lý do subset `vietnamese` bắt buộc

> Nếu chữ Việt rơi về font hệ thống → `unicode-range` sai. Xem R3: **copy nguyên văn**, đừng tự gõ.

### 6.7. Lỗi — AC10
```bash
ddev drush watchdog:show --severity=3 --count=10
```

## 7. Kỳ vọng đúng về kết quả

Sau task này **font sẽ đúng** — đây là task đầu tiên làm trang **thật sự** tiến gần design.

Nhưng vẫn **chưa giống design**, và đó là đúng:
- Layout: cần TASK-003 (`page.html.twig`)
- Nội dung: cần content type — **đang bị chặn** (`PROJECT_CONTEXT.md` §5)
- Mega menu, FAQ, tabs: cần Vue island — bước 5

Đạt = **font đúng, chữ Việt đủ dấu, không gọi CDN nào**.

## 8. Bảo mật

- [ ] Đã chạy `docs/security/SECURITY_CHECKLIST.md`
- [ ] 🔴 **Không request ra domain ngoài** (AC5, AC7) — `SECURITY_POLICY.md` §10.
      Self-host font là **có chủ đích**: giữ CSP chặt (`default-src 'self'`), không rò rỉ
      IP người truy cập sang máy chủ nước ngoài. Site nhà nước.
- [ ] Font chỉ trích từ `design/` — không tải file lạ từ internet
- [ ] `design/` **không bị sửa** (`git status design/` phải sạch)
- [ ] Giấy phép đã xác minh (R5)

## 9. Câu hỏi mở

### 9.1. 🟡 Lexend thiếu weight 400?

Design chỉ nhúng Lexend **500, 600, 700** — **không có 400**. Vì Lexend chỉ dùng cho tiêu đề
(luôn in đậm) nên hợp lý. Nhưng nếu task sau cần `h*` weight 400 thì **không có** — sẽ bị
trình duyệt giả lập (synthetic), nhìn xấu. Ghi nhận, chưa xử lý.

### 9.2. 🟡 `--nidqc-font-mono` mà không nạp Roboto Mono

TASK-003 (R2) thêm `--nidqc-font-mono: 'Roboto Mono', ui-monospace, monospace`. Task này **không**
nạp file. Nên biến đó sẽ fallback về `ui-monospace` của hệ thống.

**Chấp nhận được**: dùng đúng 1 lần trong toàn design. Fallback monospace hệ thống là ổn.
Nếu người review muốn nạp → mở task riêng, đừng nhét vào đây.

### 9.3. 🟢 Font đồng nhất giữa các bundle — đã kiểm, KHÔNG còn là rủi ro

Câu hỏi ban đầu: script đọc từ **một** bundle (`design/NIDQC FAQ.html`) — bundle khác có nhúng
font khác không? Nếu có, trích từ FAQ sẽ thiếu.

**Đã kiểm cả 12 file (2026-07-16): không.** 11/11 trang thật đều **giống hệt nhau** —
21 uuid, cùng 3 font, cùng weight:

```
Be Vietnam Pro:[400,500,600,700] | Lexend:[500,600,700] | Roboto Mono:[400,500]
```

(`Mobile Preview` có 0 font — thêm một bằng chứng nó không phải trang thật, xem `PAGE_MAPPING.md` §1.)

→ Trích từ bất kỳ bundle nào cũng cho kết quả như nhau. Dùng `NIDQC FAQ.html` (nhẹ nhất, 563 KB).
Không cần chế độ `--all`.

## 10. Nhật ký

| Ngày | Người/Agent | Việc |
|---|---|---|
| 2026-07-16 | Claude | Soạn task. Đo thật trước khi viết: bundle nhúng sẵn 21 file font (316 KB). **Lexend là variable font** — kiểm UUID trong manifest thấy 3 weight dùng chung 1 file/subset, nên chỉ cần 3 file chứ không phải 9. Roboto Mono dùng **đúng 1 lần** trong toàn design → bỏ, tiết kiệm 134 KB. Đã trích thử 1 file và xác minh là woff2 hợp lệ (magic `wOF2`, `file` đọc ra "Web Open Font Format (Version 2), TrueType"). |
