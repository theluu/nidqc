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
├── nidqc.info.yml         # khai báo theme + region
├── nidqc.libraries.yml    # library `global`: tokens.css -> base.css
├── css/
│   ├── tokens.css         # ⭐ NGUỒN DUY NHẤT của màu/layout/font
│   └── base.css           # chỉ áp token lên body, không style component
└── README.md
```

`tokens.css` **phải** nạp trước `base.css` — `base.css` dùng biến của nó.

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
