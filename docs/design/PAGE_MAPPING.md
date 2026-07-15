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

## 4. Vùng dùng chung (mọi trang)

> 🔴 **MỤC NÀY THIẾU — đang chờ sửa ở `tasks/TASK-003.md` (R1).**
> Design có **5** vùng dùng chung, không phải 4: thiếu dải **breadcrumb** (`#F5F5F5`,
> `border-bottom: 1px solid #ECECEC`, `padding: 14px 24px`) nằm **ngay dưới nav**, có ở
> **cả 10 trang con** (trang chủ không có). Đây chính là nguyên nhân gốc của `TASK-001.md` §9.4:
> theme không có region `breadcrumb` nên Drupal ném block `breadcrumbs` vào `header_top`.

| Vùng | Region Drupal | Nguồn nội dung |
|---|---|---|
| Top bar | `header_top` | Block: ngày (JS), chuyển ngữ, link đăng nhập |
| Banner ảnh | `header_banner` | Block ảnh tĩnh |
| Nav chính | `primary_menu` | Menu `main` (mega menu — Vue island) |
| Footer | `footer` | Menu `footer` + block thông tin Viện |

Bốn vùng này giống hệt nhau ở cả 11 trang thật → dựng một lần trong `page.html.twig`.

---

## 5. Đa ngôn ngữ

Design có nút **Tiếng Việt / English** ở top bar (VI đang active, EN mờ).

**Chưa chốt:** EN chỉ là giao diện hay có nội dung thật? Nếu có nội dung thật thì cần bật
`content_translation` + `locale` — đây là **thay đổi schema lớn**, phải là task riêng có duyệt.
Hiện tại: dựng UI nút chuyển ngữ, chưa bật đa ngôn ngữ. Ghi nhận tại `docs/decisions/`.
