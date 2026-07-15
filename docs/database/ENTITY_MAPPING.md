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

> Đây là **gợi ý**, không phải đặc tả. Design là mockup — dữ liệu trong đó là giả.
> Cấu trúc field thật phải xác nhận với NIDQC trước khi cài.

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

## 4. Field dự kiến — `document`

Ví dụ mức chi tiết cần đạt trước khi cài:

| Field | Kiểu | Bắt buộc | Ghi chú |
|---|---|---|---|
| `title` | string | ✅ | Core |
| `field_document_number` | string | ❌ | VD: `15/2024/TT-BYT` |
| `field_issued_date` | datetime (date only) | ❌ | Ngày ban hành |
| `field_category` | ER → `document_category` | ✅ | |
| `field_file` | file | ✅ | **Chỉ cho phép** pdf, doc, docx, xls, xlsx. Giới hạn dung lượng. |
| `body` | text_long | ❌ | Trích yếu |

> ⚠️ `field_file` là điểm rủi ro bảo mật cao nhất trong schema này — upload file trên site
> nhà nước. Bắt buộc whitelist extension, giới hạn size, quét kiểu MIME thật (không tin
> extension), lưu ngoài webroot hoặc chặn thực thi. Xem `docs/security/SECURITY_POLICY.md`.

## 5. Câu hỏi phải trả lời trước khi cài schema

| Câu hỏi | Vì sao chặn |
|---|---|
| **`standards` / "chất chuẩn" là gì?** Nghiệp vụ lõi của Viện, được link 20 lần trong design, nhưng `/tim-kiem-chat-chuan` **không có design**. | Có thể là content type lớn nhất dự án. Không đoán. |
| **English có nội dung thật không?** | Bật `content_translation` đổi cấu trúc mọi entity. Làm sau = migrate đau. |
| **Migrate từ site cũ?** | Quyết định field name/kiểu. Làm lại sau rất tốn. |
| **`department` phân cấp mấy tầng?** | Quyết định dùng ER tự tham chiếu hay taxonomy. |

**Không tự trả lời các câu này bằng suy đoán.** Hỏi NIDQC.

## 6. Quy tắc

1. Machine name: `snake_case`, tiền tố `field_`.
2. Không đổi machine name sau khi có dữ liệu — phải migrate.
3. Mọi thay đổi schema → `drush cex` → commit `config/sync/`.
4. Không tạo field "phòng khi cần".
5. Content type mới phải có trong file này **trước** khi cài.

## 7. Liên quan

`docs/database/DATABASE_SCHEMA.md` · `docs/design/PAGE_MAPPING.md` · `docs/architecture/BACKEND_ARCHITECTURE.md`
