# Entity Mapping — Design → Drupal

> Ánh xạ dữ liệu lặp trong design sang entity Drupal.
>
> ⛔ **Đây là tài liệu dự kiến, chưa cài đặt.** Tạo content type/field = **đổi schema**.
> Chỉ làm trong task có `schema_change: true` đã được duyệt. Xem `AGENTS.md` §2.

---

## 1. Nguồn suy ra

Các vòng lặp `sc-for` trích từ design (xem `scripts/extract-design.py`):

`navMenu`, `activeChildren` (11 trang) · `depts` (2) · `objectives`, `standards`, `functions`,
`related`, `cats`, `items`, `fields`, `equipment`, `certs`, `tabs`, `docs`, `newsList`,
`announcements`, `webLinks`, `groups`, `phdInfo`, `phdSteps`, `projects`

> 🔴 **Đây là GỢI Ý, không phải đặc tả — và khoảng cách giữa hai thứ đó lớn hơn tưởng.**
>
> Đã kiểm chứng 2026-07-16: **design không chứa đủ thông tin để chốt schema.** Nó là mockup
> thị giác — cho thấy trang *trông* thế nào, không cho biết dữ liệu *là* gì. Ví dụ cụ thể ở §4:
> cả trang "Văn bản tài liệu" chỉ có `{ title, meta }`, không có file để tải, không có số hiệu,
> không có ngày ban hành — dù một hệ thống văn bản thật chắc chắn cần cả ba.
>
> **Suy field từ hiểu biết chung về loại trang đó là BỊA.** Bản trước của §4 đã mắc đúng lỗi này.
> Cấu trúc field thật **phải** xác nhận với NIDQC trước khi cài. Xem §5.

## 2. Content type dự kiến

| Machine name | Nhãn | Nguồn từ design | Ghi chú |
|---|---|---|---|
| `page` | Trang tĩnh | Giới thiệu, Chính sách, Năng lực… | Có sẵn trong Drupal core |
| `news` | Tin tức | `newsList`, `announcements`, `related` | |
| `document` | Văn bản tài liệu | `docs` | Có file đính kèm |
| `faq` | Câu hỏi thường gặp | `items` (trang FAQ) | |
| `department` | Đơn vị trực thuộc | `depts`, `groups` | Có phân cấp |
| `equipment` | Trang thiết bị | `equipment` | Trang Năng lực |
| `certificate` | Chứng nhận | `certs` | Trang Năng lực |
| `project` | Đề tài NCKH | `projects`, `phdInfo`, `phdSteps` | Trang Đào tạo |
| `contact_submission` | Liên hệ gửi từ website | Form `/lien-he` | TASK-009 — node không publish, chỉ dùng trong backend |

### Không phải content type

| Dữ liệu | Cài bằng |
|---|---|
| `navMenu`, `activeChildren` | Menu `main` của Drupal |
| `webLinks` | Menu `footer` hoặc block |
| `tabs` | Cấu trúc trang, không phải dữ liệu |
| `objectives`, `functions`, `fields` | Nội dung trong body của `page` — **không** tách entity |
| `cats` | Taxonomy |
| `standards` | ⚠️ Chưa rõ — xem §5 |

> Cám dỗ thường gặp: biến mọi vòng lặp thành content type. Đừng.
> `objectives` và `functions` là danh sách gạch đầu dòng trong một trang tĩnh — dùng body là đủ.

## 3. Taxonomy dự kiến

| Vocabulary | Dùng cho |
|---|---|
| `news_category` | Phân loại tin tức |
| `document_category` | Loại văn bản (Thông tư, Quyết định, Nghị định…) |
| `faq_category` | Nhóm câu hỏi |

## 4. Field — `news`

| Field | Kiểu | Bắt buộc | Nguồn | Ghi chú |
|---|---|---|---|---|
| `title` | base field | có | Site cũ / design | Tiêu đề tin |
| `body` | text_with_summary | không | Site cũ | TASK-010 — nội dung chi tiết bài viết import từ site cũ |
| `created` (core) | timestamp | có | Drupal core / site cũ | Ngày đăng; không tạo field riêng |
| `field_category` | ER → `news_category` | có | Site cũ / design | Mapping category cũ sang taxonomy mới |
| `field_tag` | string | không | Design | Nhãn hiển thị ngắn nếu có |
| `field_image` | image | không | Site cũ / design | Ảnh đại diện, whitelist extension theo field config |

Quy tắc import từ site cũ:
- Không tạo category mới khi chưa duyệt schema/taxonomy; category cũ được map vào 6 term đã có.
- Không lưu tài khoản, mật khẩu hoặc cookie scrape.
- Nội dung HTML cũ phải lọc thẻ nguy hiểm trước khi lưu vào `body`.

## 5. Field — `document`

> 🔴 **BẢN TRƯỚC CỦA MỤC NÀY LÀ BỊA. Đã gỡ.**
>
> Tôi từng đặc tả `document` có `field_document_number` (`15/2024/TT-BYT`), `field_issued_date`,
> `field_file`, `field_category`. **Không field nào trong số đó có trong design.** Chúng được suy
> ra từ hiểu biết chung về "trang văn bản pháp quy", không phải từ nguồn chân lý của dự án.
> Nếu ai đó cài theo bản đó thì đã dựng sai schema cho dữ liệu thật của Viện.

### Design thật sự chứa gì

Đã trích từ `design/NIDQC Van ban tai lieu.html` (2026-07-16):

```js
docsByTab = {
  phapquy: [
    { title: 'Thông tư quy định về kiểm nghiệm thuốc, nguyên liệu làm thuốc', meta: 'Bộ Y tế · Thông tư' },
    { title: 'Quyết định ban hành quy trình kiểm nghiệm nội bộ',              meta: 'Viện KNTTW · Quyết định' },
  ],
  chuyenmon: [
    { title: 'Quy trình thao tác chuẩn (SOP) phân tích hóa lý', meta: 'Tài liệu kỹ thuật · SOP' },
  ],
}
```

**Đúng 2 field:** `title` và `meta`. `meta` là **chuỗi hiển thị** ghép sẵn
(`"cơ quan ban hành · loại văn bản"`), không phải dữ liệu có cấu trúc.

Thẻ văn bản trong design có icon file và tiêu đề — **nhưng không có `<a href>` để tải**.

### Vì sao không thể chốt schema từ design

| Câu hỏi | Design trả lời được? |
|---|---|
| Văn bản có file đính kèm để tải không? | ❌ Không có link tải nào. Nhưng trang văn bản mà không tải được thì vô nghĩa → **design thiếu, không phải không cần**. |
| Có số hiệu văn bản không? | ❌ Không xuất hiện |
| Có ngày ban hành không? | ❌ Không xuất hiện |
| "Bộ Y tế · Thông tư" là 1 field hay 2? | ❌ Không biết — trong design nó là chuỗi ghép sẵn |
| Có bao nhiêu tab/nhóm? | ⚠️ Thấy `phapquy`, `chuyenmon` — chưa chắc đủ |

**Design là mockup thị giác, không phải đặc tả dữ liệu.** Nó cho thấy trang *trông* thế nào,
không cho biết dữ liệu *là* gì.

→ **Phải hỏi NIDQC.** Xem §5.

### Rủi ro bảo mật khi có `field_file`

Nếu NIDQC xác nhận cần đính kèm file (nhiều khả năng), đó sẽ là **điểm rủi ro cao nhất** của
schema: upload file trên site nhà nước. Bắt buộc whitelist extension, giới hạn dung lượng, kiểm
MIME thật theo nội dung (không tin extension), và **kiểm tra thật** rằng nginx không thực thi PHP
trong thư mục files (`.htaccess` **không** có tác dụng với nginx). Xem `docs/security/SECURITY_POLICY.md` §5.

## 6. Câu hỏi phải trả lời trước khi cài schema

| Câu hỏi | Vì sao chặn |
|---|---|
| **`standards` / "chất chuẩn" là gì?** Nghiệp vụ lõi của Viện, được link 20 lần trong design, nhưng `/tim-kiem-chat-chuan` **không có design**. | Có thể là content type lớn nhất dự án. Không đoán. |
| **English có nội dung thật không?** | Bật `content_translation` đổi cấu trúc mọi entity. Làm sau = migrate đau. |
| **Migrate từ site cũ?** | Quyết định field name/kiểu. Làm lại sau rất tốn. |
| **`department` phân cấp mấy tầng?** | Quyết định dùng ER tự tham chiếu hay taxonomy. |

**Không tự trả lời các câu này bằng suy đoán.** Hỏi NIDQC.

## 7. Field — `contact_submission`

> Đã chốt trong TASK-009 theo yêu cầu trực tiếp: tạo content type lưu dữ liệu từ form contact.

| Field | Kiểu | Bắt buộc | Nguồn | Ghi chú |
|---|---|---|---|---|
| `title` | base field | có | Sinh tự động | `Liên hệ: {name} - {Y-m-d H:i}` |
| `field_contact_name` | string | có | Form design | Họ và tên, 2–120 ký tự |
| `field_contact_email` | string | có | Form design | Validate bằng email validator, 5–254 ký tự |
| `field_contact_phone` | string | không | Form design | Tối đa 40 ký tự |
| `field_contact_subject` | string | có | Form design | Whitelist 4 chủ đề trong API contract |
| `field_contact_message` | text_long | có | Form design | Nội dung liên hệ, 10–4000 ký tự, lưu plain text |

Quy tắc lưu:
- Node `contact_submission` do API tạo luôn `status = 0` để không publish ra public.
- Không lưu token reCAPTCHA, IP thô, SMTP credential hoặc dữ liệu kỹ thuật không cần cho nghiệp vụ.
- Không dùng bảng custom; Drupal Field API tự quản lý bảng field và access control.

## 8. Quy tắc

1. Machine name: `snake_case`, tiền tố `field_`.
2. Không đổi machine name sau khi có dữ liệu — phải migrate.
3. Mọi thay đổi schema → `drush cex` → commit `config/sync/`.
4. Không tạo field "phòng khi cần".
5. Content type mới phải có trong file này **trước** khi cài.

## 9. Liên quan

`docs/database/DATABASE_SCHEMA.md` · `docs/design/PAGE_MAPPING.md` · `docs/architecture/BACKEND_ARCHITECTURE.md`
