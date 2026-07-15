# Theme NIDQC

Theme chính của website Viện Kiểm nghiệm thuốc Trung ương.

## Quy tắc số một: `css/tokens.css` là nguồn duy nhất

Mọi màu, khoảng cách, font của dự án nằm ở **một chỗ**: `css/tokens.css`.

- ✅ Đổi màu thương hiệu → sửa `css/tokens.css`, **một dòng, một chỗ**.
- ⛔ **Không hard-code mã hex** ở bất kỳ file nào khác — Twig, CSS component, hay `.vue`.
- ✅ Dùng `var(--nidqc-primary)`, không dùng `#0F3093`.

```css
/* ❌ SAI */
.nav { background: #0F3093; }

/* ✅ ĐÚNG */
.nav { background: var(--nidqc-primary); }
```

## Token đến từ đâu

Giá trị trong `tokens.css` **trích từ design gốc**, không phải ước lượng hay chọn cho đẹp:

```bash
python3 scripts/extract-design.py --all --colors
```

Nguồn tài liệu: `docs/design/DESIGN_SYSTEM.md` §7.

> ⚠️ **Đừng sửa ngược.** `DESIGN_SYSTEM.md` mô tả design thật. Nếu `tokens.css` lệch với nó,
> thứ sai là `tokens.css` — trừ khi design thật sự đổi, và khi đó `DESIGN_SYSTEM.md` phải đổi trước.

## Cấu trúc

```
nidqc/
├── nidqc.info.yml         # khai báo theme + 8 region
├── nidqc.libraries.yml    # library `global`
├── css/
│   ├── fonts.css          # @font-face — SINH TỰ ĐỘNG, đừng sửa tay
│   ├── tokens.css         # ⭐ NGUỒN DUY NHẤT của màu/layout/font
│   ├── base.css           # chỉ áp token lên body
│   └── layout.css         # 5 vùng dùng chung
├── fonts/                 # 15 file woff2 + giấy phép
├── templates/
│   └── layout/page.html.twig
└── README.md
```

**Thứ tự nạp bắt buộc:** `fonts.css` → `tokens.css` → `base.css` → `layout.css`.
`@font-face` phải khai báo trước khi có gì dùng tới; `base.css`/`layout.css` dùng biến của `tokens.css`.

## Font

**Self-host. KHÔNG gọi Google Fonts CDN** — site cơ quan nhà nước không phụ thuộc hạ tầng nước ngoài,
và giữ được CSP chặt (`default-src 'self'`). Xem `docs/security/SECURITY_POLICY.md` §10.

| Font | File | Vai trò |
|---|---|---|
| **Lexend** | 3 (variable, weight 500–700) | Tiêu đề `h1`–`h4` — dùng 140 lần trong design |
| **Be Vietnam Pro** | 12 (weight 400/500/600/700) | Thân bài — mặc định của `body` |

Mỗi font có 3 subset: `vietnamese`, `latin-ext`, `latin`. **Subset `vietnamese` không được thiếu** —
thiếu là chữ có dấu vỡ hoặc rơi về font khác.

`Roboto Mono` **cố ý không self-host**: dùng đúng 1 lần trong toàn design, không đáng 134 KB.
`--nidqc-font-mono` fallback về `ui-monospace`.

### Trích lại font

Font lấy từ chính design bundle (`design/*.html` nhúng sẵn woff2 base64), **không tải từ CDN**:

```bash
python3 scripts/extract-fonts.py --list     # xem trước, không ghi
python3 scripts/extract-fonts.py \
  --out web/themes/custom/nidqc/fonts \
  --css web/themes/custom/nidqc/css/fonts.css
```

> ⚠️ `css/fonts.css` **sinh tự động** — sửa tay sẽ bị ghi đè. Cần đổi thì sửa
> `scripts/extract-fonts.py`. Lý do sinh bằng script: mỗi `@font-face` có một
> `unicode-range` dài ~200 ký tự; gõ sai một ký tự là chữ Việt rơi về font khác,
> lỗi rất khó nhìn ra.

### Giấy phép

Cả hai font là **SIL Open Font License 1.1**, đã xác minh từ nguồn chính thức
(`license: "OFL"` trong `METADATA.pb` của repo `google/fonts`). Toàn văn giấy phép kèm tại:

- `fonts/LICENSE-Lexend.txt`
- `fonts/LICENSE-Be-Vietnam-Pro.txt`

OFL cho phép self-host và nhúng. **Không xoá hai file này** — giữ giấy phép là điều kiện của OFL.

## Region

Theo `docs/design/PAGE_MAPPING.md` §4. Bốn vùng dùng chung (`header_top`, `header_banner`,
`primary_menu`, `footer`) giống hệt nhau ở cả 11 trang design → dựng một lần ở `page.html.twig`.

## Trạng thái hiện tại

Theme mới có **khung + token**. Chưa có Twig template, chưa có font, chưa có component.
Trang sẽ trông thô — đó là **đúng**, xem `tasks/TASK-001.md` §7.

Còn thiếu (task riêng):
- `@font-face` + file woff2 của `Be Vietnam Pro` (self-host, **không** gọi Google Fonts CDN)
- Twig template: top bar, banner, mega menu, footer
- Vue island — xem `docs/architecture/FRONTEND_ARCHITECTURE.md`

## Lệnh

```bash
ddev drush cr                                          # xoá cache sau khi sửa CSS/yml
ddev drush theme:install nidqc -y                      # cài theme
ddev drush config:set system.theme default nidqc -y    # đặt làm mặc định
ddev drush cex -y                                      # export config -> config/sync/
```
