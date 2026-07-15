# Page Mapping — Design → Drupal

> Ánh xạ 12 file `design/*.html` sang route, entity và template Drupal.
> Đường dẫn URL lấy theo cấu trúc `nidqc.gov.vn` hiện tại, chốt qua Pathauto.

---

## 1. Bảng ánh xạ

| # | File design | URL | Loại | Entity | Template |
|---|---|---|---|---|---|
| 1 | `NIDQC Trang chu.html` | `/` | Trang chủ | Không (view tổng hợp) | `page--front.html.twig` |
| 2 | `NIDQC Gioi thieu chung.html` | `/gioi-thieu-chung` | Trang tĩnh | `node:page` | `node--page--full.html.twig` |
| 3 | `NIDQC Co cau to chuc.html` | `/co-cau-to-chuc` | Trang tĩnh | `node:page` + `department` | `node--page--co-cau.html.twig` |
| 4 | `NIDQC Chinh sach chat luong.html` | `/chinh-sach-chat-luong` | Trang tĩnh | `node:page` | `node--page--full.html.twig` |
| 5 | `NIDQC Nang luc.html` | `/nang-luc` | Trang tĩnh + tabs | `node:page` + `equipment`, `certificate` | `node--page--nang-luc.html.twig` |
| 6 | `NIDQC Dao tao NCKH.html` | `/dao-tao-nckh` | Trang tĩnh + tabs | `node:page` + `project` | `node--page--dao-tao.html.twig` |
| 7 | `NIDQC Tin tuc danh sach.html` | `/tin-tuc` | Danh sách | View `news_list` | `views-view--news-list.html.twig` |
| 8 | `NIDQC Tin tuc chi tiet.html` | `/tin-tuc/{alias}` | Chi tiết | `node:news` | `node--news--full.html.twig` |
| 9 | `NIDQC Van ban tai lieu.html` | `/van-ban-tai-lieu` | Danh sách + lọc | View `documents` | `views-view--documents.html.twig` |
| 10 | `NIDQC FAQ.html` | `/faq` | Accordion | `node:faq` hoặc View | `views-view--faq.html.twig` |
| 11 | `NIDQC Lien he.html` | `/lien-he` | Trang + form | `node:page` + Contact form | `node--page--lien-he.html.twig` |
| 12 | `NIDQC Mobile Preview.html` | — | 🔍 **Không phải trang** | — | — |

> **Lưu ý #12:** `NIDQC Mobile Preview.html` chỉ có template 790 bytes (các trang khác ~18–52KB).
> Đây là **file preview/khung xem mobile**, không phải một trang thật. **Không dựng route cho nó.**
> Dùng nó để tham chiếu responsive nếu cần, không hơn.

---

## 2. Liên kết trong design

Đếm thật từ `href` trong toàn bộ design:

| Đích | Số lần | Ý nghĩa |
|---|---|---|
| `NIDQC-Homepage.dc.html` | 20 | Logo + về trang chủ |
| `NIDQC-Homepage.dc.html#chat-chuan` | 20 | **Anchor "Tra cứu chất chuẩn" trên trang chủ** |
| `Gioi-thieu-chung.dc.html` | 14 | |
| `Lien-he.dc.html` | 13 | |
| `Tin-tuc-danh-sach.dc.html` | 12 | |
| `NIDQC-Homepage.dc.html#dich-vu` | 10 | **Anchor "Dịch vụ" trên trang chủ** |
| `Chinh-sach-chat-luong.dc.html` | 4 | |
| `Nang-luc.dc.html` | 4 | |
| `Co-cau-to-chuc.dc.html` | 4 | |
| `Tin-tuc-chi-tiet.dc.html` | 2 | |
| `FAQ.dc.html` | 2 | |
| `https://nidqc.gov.vn/tim-kiem-chat-chuan` | 2 | 🔍 **Trang ngoài phạm vi design** |
| `https://nidqc.gov.vn/` | 1 | |

### Hai điểm cần chốt với chủ đầu tư

1. **`#chat-chuan` và `#dich-vu` là anchor trên trang chủ**, không phải trang riêng — nhưng được
   link tới 20 và 10 lần, tức là mục điều hướng chính. Cần xác nhận: giữ dạng anchor trên trang chủ,
   hay tách thành trang riêng? Ảnh hưởng trực tiếp tới cấu trúc menu và SEO.
2. **`/tim-kiem-chat-chuan`** được link tới nhưng **không có file design**. Đây là chức năng tra cứu
   chất chuẩn — nghiệp vụ lõi của Viện. Cần làm rõ phạm vi: có nằm trong dự án này không?
   Nếu có thì thiếu design. Xem `docs/PROJECT_CONTEXT.md` §Phạm vi.

---

## 3. Quy tắc đặt alias (Pathauto)

| Content type | Pattern |
|---|---|
| `page` | `/[node:title]` |
| `news` | `/tin-tuc/[node:title]` |
| `document` | `/van-ban-tai-lieu/[node:title]` |
| `faq` | `/faq` (gộp 1 trang) |

Alias tiếng Việt: bỏ dấu, chữ thường, nối bằng `-`. Cấu hình sẵn trong Pathauto.
**Mọi thay đổi alias phải kèm redirect** (module `redirect` đã cài) — link cũ của cơ quan nhà nước
thường nằm trong văn bản giấy, không được chết.

---

## 4. Vùng dùng chung

Design có **5** dải dùng chung. Thứ tự **bắt buộc**, đã kiểm trên cả 12 file:

```
div    #0D2870   top bar,   min-height 34px          -> header_top
div    #FFFFFF   banner ảnh, border-bottom #ECECEC   -> header_banner
header #0F3093   nav, sticky, 50px                   -> primary_menu
div    #F5F5F5   breadcrumb, padding 14px 24px       -> breadcrumb
       —         nội dung                            -> content
footer #0D2870   footer, grid 2fr 1fr 1.3fr          -> footer
```

| Vùng | Region Drupal | Có ở | Nguồn nội dung |
|---|---|---|---|
| Top bar | `header_top` | 11/11 | Block: ngày (JS), chuyển ngữ, link đăng nhập |
| Banner ảnh | `header_banner` | 11/11 | Block ảnh tĩnh |
| Nav chính | `primary_menu` | 11/11 | Menu `main` (mega menu — Vue island) |
| **Breadcrumb** | `breadcrumb` | **10/11** — ❌ trang chủ | Block breadcrumb của Drupal |
| Footer | `footer` | 11/11 | Menu `footer` + block thông tin Viện |

> ⚠️ **Breadcrumb không có ở trang chủ.** Trang chủ cần `page--front.html.twig` riêng —
> nó cũng khác ở chỗ top bar nằm trong `sc-if value="{{ showUtilityBar }}"` (có điều kiện)
> và thân trang là nhiều `<section>` thay vì một khối nội dung. Xem `ROADMAP.md` §4 (TASK-008).

Năm vùng này dựng một lần trong `page.html.twig` (`TASK-003`).

### Không phải vùng chung

| Dải | Vì sao không |
|---|---|
| Dải tiêu đề trang (`#F3F7FC` hoặc `#0D2870`) | Chỉ 8/10 trang, **và màu đổi theo trang** → thuộc `node--*.html.twig` |
| Dải tabs (`#FFF`) | Chỉ 5 trang (Chính sách, Cơ cấu, Đào tạo, FAQ, Năng lực) |

---

## 5. Đa ngôn ngữ

Design có nút **Tiếng Việt / English** ở top bar (VI đang active, EN mờ).

**Chưa chốt:** EN chỉ là giao diện hay có nội dung thật? Nếu có nội dung thật thì cần bật
`content_translation` + `locale` — đây là **thay đổi schema lớn**, phải là task riêng có duyệt.
Hiện tại: dựng UI nút chuyển ngữ, chưa bật đa ngôn ngữ. Ghi nhận tại `docs/decisions/`.
