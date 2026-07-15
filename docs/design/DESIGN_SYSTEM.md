# Design System — NIDQC

> **Nguồn:** trích trực tiếp từ 12 file `design/*.html` (bundled React mockup, inline style).
> Số lần xuất hiện là đếm thật trên toàn bộ design. Đây là nguồn chân lý — không tự chế màu mới.

---

## 1. Màu

### Brand
| Token | Hex | Dùng | Xuất hiện |
|---|---|---|---|
| `--nidqc-primary` | `#0F3093` | Header chính, nút chính, tiêu đề mục | 159 |
| `--nidqc-primary-dark` | `#0D2870` | Thanh top bar, hover nút chính | 31 |
| `--nidqc-primary-light` | `#7FA8E0` | Link phụ trên nền xanh, viền nhạt | 33 |
| `--nidqc-primary-pale` | `#E8F0F7` | Nền khối nhấn, nền tab active | 29 |
| `--nidqc-accent` | `#1D6AC5` | Link trong nội dung, nhấn phụ | 21 |
| `--nidqc-accent-2` | `#1565B3` | Hover của accent | 11 |

### Text
| Token | Hex | Dùng | Xuất hiện |
|---|---|---|---|
| `--nidqc-text` | `#212529` | Chữ chính | 84 |
| `--nidqc-text-muted` | `#495057` | Chữ phụ, mô tả | 76 |
| `--nidqc-text-light` | `#777777` | Meta, ngày tháng | 25 |

### Nền & viền
| Token | Hex | Dùng | Xuất hiện |
|---|---|---|---|
| `--nidqc-white` | `#FFFFFF` | Nền trang | 24 |
| `--nidqc-border` | `#ECECEC` | Viền mặc định | 53 |
| `--nidqc-border-strong` | `#CCCCCC` | Viền bảng, divider | 69 |
| `--nidqc-bg-subtle` | `#F5F5F5` | Nền section xen kẽ | 16 |
| `--nidqc-bg-blue-1` | `#F5F8FC` | Nền khối xanh rất nhạt | 11 |
| `--nidqc-bg-blue-2` | `#F3F7FC` | Nền khối xanh nhạt | 8 |
| `--nidqc-bg-alt` | `#F0F0F0` | Nền xám thay thế | 11 |

### Trạng thái
| Token | Hex | Dùng | Xuất hiện |
|---|---|---|---|
| `--nidqc-danger` | `#ED1B24` | Cảnh báo, thu hồi thuốc | 3 |
| `--nidqc-success` | `#1E8A4C` | Thành công, đạt chuẩn | 2 |

> ⚠️ `--nidqc-danger` và `--nidqc-success` xuất hiện rất ít trong design.
> Trước khi dùng rộng, kiểm tra tương phản WCAG AA — `#ED1B24` trên nền trắng chỉ đạt ~4.0:1,
> **không đạt AA cho chữ thường**. Chỉ dùng cho icon/viền, hoặc làm tối lại khi dùng cho chữ.

---

## 2. Typography

**Ba font**, tất cả **self-host**, KHÔNG gọi Google Fonts CDN (site nhà nước, tránh phụ thuộc ngoài).

| Font | Dùng | Vai trò | Weight trong design |
|---|---|---|---|
| **`Lexend`** | **140 lần** | **Tiêu đề** — `h1`–`h4`. Có ở **cả 12 trang**. | `500`, `600`, `700` — **variable font** |
| **`Be Vietnam Pro`** | 22 lần | **Thân bài** — mặc định của `body` | `400`, `500`, `600`, `700` |
| `Roboto Mono` | 1 lần | Monospace. Chỉ ở trang Đào tạo NCKH. | `400`, `500` — variable |

> Số liệu đếm thật trên cả 12 file design, không phải ước lượng. Kiểm lại:
> ```bash
> python3 scripts/extract-design.py --all -o /tmp/tpl
> cat /tmp/tpl/*.tpl.html | grep -oE "font-family:\s*'[^']+'" | sort | uniq -c | sort -rn
> ```

Subset bắt buộc: `vietnamese`, `latin-ext`, `latin`. Định dạng `woff2`, `font-display: swap`.
**Subset `vietnamese` không được thiếu** — thiếu là chữ có dấu vỡ hoặc rơi về font khác.

### Style toàn cục thật của design

```css
* { box-sizing: border-box; }
body {
  margin: 0;
  background: #FFFFFF;
  font-family: 'Be Vietnam Pro', sans-serif;
  color: #333333;                        /* ⚠️ mặc định của body — KHÔNG phải #212529 */
  -webkit-font-smoothing: antialiased;
}
```

> ⚠️ `body` mặc định là `#333333` (`--nidqc-text-body`), nhưng hầu như mọi thành phần **ghi đè**
> bằng `#212529` (`--nidqc-text`, 84 lần) hoặc `#495057` (`--nidqc-text-muted`, 76 lần).
> Nên `#333333` gần như không bao giờ hiện ra thật.

### Ghi chú

- **`Lexend` không có weight 400** trong design — nó chỉ dùng cho tiêu đề (luôn đậm).
  Cần `h*` weight 400 thì sẽ bị trình duyệt giả lập (synthetic), nhìn xấu.
- **`Roboto Mono` không được self-host** (`TASK-004` §3): 134 KB cho **1 chỗ dùng** là không đáng.
  `--nidqc-font-mono` sẽ fallback về `ui-monospace` của hệ thống.

| Vai trò | Font | Size | Weight |
|---|---|---|---|
| Top bar / meta | Be Vietnam Pro | 12.5px | 400 |
| Nav chính | Be Vietnam Pro | 14px | 600 |
| Body | Be Vietnam Pro | 15–16px | 400 |
| Tiêu đề mục | **Lexend** | 18–22px | 700 |
| Tiêu đề trang | **Lexend** | 28–32px | 700 |

---

## 3. Layout

| Token | Giá trị |
|---|---|
| `--nidqc-container` | `1280px` (max-width, `margin: 0 auto`) |
| `--nidqc-gutter` | `24px` (padding ngang container) |
| Chiều cao top bar | `34px` (min-height) |
| Chiều cao header nav | `50px` |
| Header | `position: sticky; top: 0; z-index: 40` |
| Shadow header | `0 2px 4px rgba(0,0,0,0.10)` |

---

## 4. Cấu trúc chung mọi trang

```
┌──────────────────────────────────────────┐
│ Top bar  #0D2870  · ngày · VI/EN · login │  34px
├──────────────────────────────────────────┤
│ Banner ảnh full-width (banner-header.jpg)│  auto
├──────────────────────────────────────────┤
│ Nav  #0F3093  sticky  · mega menu        │  50px
├──────────────────────────────────────────┤
│ Nội dung trang                           │
├──────────────────────────────────────────┤
│ Footer                                   │
└──────────────────────────────────────────┘
```

Top bar / banner / nav / footer **giống nhau ở cả 12 trang** → tách thành Twig template dùng chung
(`page.html.twig` + region), không lặp lại.

---

## 5. Component → nơi cài đặt

| Component | Có ở trang | Cài ở đâu | Lý do |
|---|---|---|---|
| Top bar (ngày, VI/EN, đăng nhập) | tất cả | Twig | Tĩnh, cần SEO |
| Banner header | tất cả | Twig | Ảnh tĩnh |
| **Mega menu** (hover mở submenu) | tất cả | 🟢 **Vue island** | Cần tương tác hover/keyboard |
| Footer | tất cả | Twig | Tĩnh |
| Danh sách tin | Tin tức danh sách, Trang chủ | Twig (Views) | SEO bắt buộc |
| **Bộ lọc + phân trang tin** | Tin tức danh sách | 🟢 **Vue island** | Lọc động, nhưng có fallback SSR |
| **FAQ accordion** | FAQ | 🟢 **Vue island** | Đóng/mở |
| **Bộ lọc văn bản** | Văn bản tài liệu | 🟢 **Vue island** | Lọc theo loại/năm |
| **Tìm kiếm chất chuẩn** | Trang chủ (`#chat-chuan`) | 🟢 **Vue island** | Tìm kiếm động |
| **Tabs** (Năng lực, Đào tạo) | Năng lực, Đào tạo NCKH | 🟢 **Vue island** | Chuyển tab |
| Sơ đồ tổ chức | Cơ cấu tổ chức | Twig | Tĩnh |
| Form liên hệ | Liên hệ | Twig (Drupal Form API) | CSRF + validate phía server |

> **Nguyên tắc:** nội dung Google cần đọc → Twig. Chỉ hành vi tương tác → Vue.
> Island phải progressive enhancement: không có JS thì nội dung vẫn đọc được.

---

## 6. Danh sách dữ liệu lặp trong design

Trích từ `sc-for` trong template — mỗi cái là một tập dữ liệu lặp, gợi ý content type/field:

`navMenu`, `activeChildren` (11 trang) · `depts` (2) · `objectives`, `standards`, `functions`,
`related`, `cats`, `items`, `fields`, `equipment`, `certs`, `tabs`, `docs`, `newsList`,
`announcements`, `webLinks`, `groups`, `phdInfo`, `phdSteps`, `projects`

Ánh xạ sang entity Drupal: xem `docs/database/ENTITY_MAPPING.md`.

---

## 7. CSS variables (nguồn dùng chung)

```css
:root {
  --nidqc-primary:        #0F3093;
  --nidqc-primary-dark:   #0D2870;
  --nidqc-primary-light:  #7FA8E0;
  --nidqc-primary-pale:   #E8F0F7;
  --nidqc-accent:         #1D6AC5;
  --nidqc-accent-2:       #1565B3;

  --nidqc-text:           #212529;
  --nidqc-text-muted:     #495057;
  --nidqc-text-light:     #777777;
  --nidqc-text-body:      #333333;   /* mặc định của body; hầu như luôn bị ghi đè — xem §2 */

  --nidqc-white:          #FFFFFF;
  --nidqc-border:         #ECECEC;
  --nidqc-border-strong:  #CCCCCC;
  --nidqc-bg-subtle:      #F5F5F5;
  --nidqc-bg-blue-1:      #F5F8FC;
  --nidqc-bg-blue-2:      #F3F7FC;
  --nidqc-bg-alt:         #F0F0F0;

  --nidqc-danger:         #ED1B24;
  --nidqc-success:        #1E8A4C;

  --nidqc-container:      1280px;
  --nidqc-gutter:         24px;

  /* Font — xem §2. Lexend cho tiêu đề, Be Vietnam Pro cho thân bài. */
  --nidqc-font:           'Be Vietnam Pro', -apple-system, BlinkMacSystemFont, sans-serif;
  --nidqc-font-heading:   'Lexend', -apple-system, BlinkMacSystemFont, sans-serif;
  --nidqc-font-mono:      'Roboto Mono', ui-monospace, monospace;
}
```

**24 biến**: 19 màu · 2 layout · 3 font.

File này là nguồn duy nhất. Twig và Vue **cùng** dùng biến này — không hard-code hex ở bất kỳ đâu khác.

> ⚠️ `--nidqc-font-mono` trỏ tới `Roboto Mono` nhưng font đó **không được self-host**
> (`TASK-004` §3) → thực tế fallback về `ui-monospace`. Có chủ đích: dùng đúng 1 lần trong design.

---

## 8. Cách đọc lại file design gốc

Design là HTML tự giải nén, không đọc tĩnh được. Trích template bằng:

```bash
python3 scripts/extract-design.py "design/NIDQC FAQ.html"
```

Xem `scripts/extract-design.py`.
