# Security Policy — NIDQC

> Site cơ quan nhà nước (`gov.vn`), thông tin y tế/dược. Bị tấn công thành công không chỉ là
> sự cố kỹ thuật — nó là sự cố uy tín của một cơ quan nhà nước và có thể ảnh hưởng thông tin
> về thuốc mà người dân tra cứu. Các quy định dưới đây là **bắt buộc**.

---

## 1. Nguyên tắc

1. **Không tin bất kỳ input nào.** Query string, form, header, file upload, JSON — tất cả.
2. **Escape ở nơi xuất.** Sanitize khi lưu là chưa đủ.
3. **Đặc quyền tối thiểu.** Mặc định là từ chối.
4. **Không tự viết bảo mật.** Dùng Form API, Entity Query, Password API của Drupal.
5. **Không rò rỉ nội bộ.** Lỗi ra ngoài phải chung chung. Chi tiết vào log.

## 2. XSS

**Twig autoescape bật mặc định. Không được tắt.**

```twig
{# ❌ NGUY HIỂM — XSS #}
{{ user_input|raw }}

{# ✅ Mặc định, an toàn #}
{{ user_input }}

{# ✅ |raw chỉ khi đã json_encode trước #}
{{ data|json_encode|raw }}
```

`|raw` chỉ được dùng khi: (a) dữ liệu đã qua `json_encode`, hoặc (b) đã qua
`Xss::filterAdmin()` / text format có filter. **Mọi `|raw` phải có comment giải thích vì sao an toàn.**

Trong PHP: không `print`/`echo` dữ liệu người dùng. Trả render array, để Drupal escape.

Trong Vue: **không `v-html`** với dữ liệu từ API hoặc người dùng. Dùng `{{ }}`.

## 3. SQL injection

```php
// ❌ NGUY HIỂM
$db->query("SELECT * FROM {node} WHERE title = '$title'");

// ✅ Entity Query
$ids = \Drupal::entityQuery('node')
  ->condition('type', 'document')
  ->condition('status', 1)
  ->accessCheck(TRUE)          // ⚠️ bắt buộc — bỏ là lộ nội dung chưa publish
  ->condition('title', $title, 'CONTAINS')
  ->execute();

// ✅ Database API có placeholder
$db->query('SELECT nid FROM {node_field_data} WHERE title = :title', [':title' => $title]);
```

**`->accessCheck(TRUE)` là bắt buộc** trừ khi có lý do ghi rõ bằng comment.
Đây là lỗi hay gặp nhất và làm lộ nội dung chưa publish ra công khai.

## 4. Validate input

Whitelist, không blacklist.

```php
// ✅ Whitelist
$allowed = ['thong-tu', 'quyet-dinh', 'nghi-dinh'];
if (!in_array($category, $allowed, TRUE)) {
  throw new BadRequestHttpException('Loại văn bản không hợp lệ.');
}

// ✅ Ép kiểu + kiểm khoảng
$year = (int) $request->query->get('year');
if ($year < 1990 || $year > (int) date('Y')) { /* 400 */ }

// ✅ Giới hạn limit — chống DoS
$limit = min(max((int) $request->query->get('limit', 20), 1), 100);
```

Mọi param trong `docs/api/API_CONTRACT.md` phải có ràng buộc và được kiểm tra thật.

## 5. File upload — rủi ro cao nhất

Content type `document` cho upload file. Đây là bề mặt tấn công nghiêm trọng nhất.

**Bắt buộc:**
- **Whitelist extension:** chỉ `pdf, doc, docx, xls, xlsx`. Không bao giờ cho `php, phtml, html, svg, js`.
- **Kiểm MIME thật** bằng nội dung file, không tin extension và không tin `Content-Type` client gửi.
- **Giới hạn dung lượng** ở cả Drupal, PHP (`upload_max_filesize`) và nginx (`client_max_body_size`).
- **Chặn thực thi** thư mục upload — nginx không được chạy PHP trong `/sites/default/files/`.
- File private → dùng private file system + kiểm quyền, không dựa vào URL khó đoán.

> `.htaccess` của Drupal chặn thực thi ở `files/` — **nhưng dự án dùng nginx, nginx không đọc
> `.htaccess`**. Phải cấu hình chặn thủ công trong nginx config. Kiểm tra thật, đừng giả định.

## 6. CSRF

- Form → **luôn** dùng Form API. Nó tự có CSRF token.
- Không tự viết xử lý `$_POST`.
- Endpoint POST → yêu cầu `X-CSRF-Token`, lấy từ `/session/token`.
- `GET` **không được** gây thay đổi trạng thái.

## 7. Quyền truy cập

```yaml
# ❌ NGUY HIỂM
nidqc.endpoint:
  requirements:
    _access: 'TRUE'

# ✅ Có chủ đích
nidqc.documents:
  requirements:
    _permission: 'access content'
```

- Mặc định từ chối.
- `_access: 'TRUE'` chỉ khi thật sự công khai **và** có comment giải thích.
- Chỉ trả nội dung `status = 1` cho người dùng ẩn danh.
- Tài khoản `admin` không dùng hằng ngày. Mỗi người một tài khoản riêng.

## 8. Secrets

**Không bao giờ commit:** `web/sites/default/settings.local.php` · `.env` · API key · mật khẩu DB
· private key · `hash_salt`

- Có `.env.example` với giá trị giả để tham chiếu.
- Lỡ commit secret → **coi như đã lộ**: đổi ngay, không chỉ xoá commit.

## 9. Dependency

- **Không `composer require` / `npm install` nếu chưa duyệt.** Xem `AGENTS.md` §2.
- Theo dõi Drupal security advisory. Bản vá bảo mật core/contrib ưu tiên cao nhất.
- Không cài module chỉ để tiện một việc nhỏ.

## 10. Header & HTTPS

Production bắt buộc:

| Header | Giá trị |
|---|---|
| `Strict-Transport-Security` | `max-age=31536000; includeSubDomains` |
| `X-Content-Type-Options` | `nosniff` |
| `X-Frame-Options` | `SAMEORIGIN` |
| `Referrer-Policy` | `strict-origin-when-cross-origin` |
| `Content-Security-Policy` | Xem bên dưới |

HTTPS bắt buộc, HTTP redirect sang HTTPS.

**CSP:** self-host toàn bộ asset (font `Be Vietnam Pro` self-host — **không gọi Google Fonts CDN**),
nên CSP có thể chặt: `default-src 'self'`. Site nhà nước không nên phụ thuộc CDN nước ngoài.

## 11. Rò rỉ thông tin

- **Tắt** hiển thị lỗi PHP trên production (`error_level: hide`).
- Không lộ phiên bản Drupal/PHP trong header hoặc trang lỗi.
- Không log mật khẩu, token, session, thông tin cá nhân.
- Trang lỗi 403/404/500 dùng template riêng, không lộ stack trace.

Xem `docs/api/API_ERROR_STANDARD.md` §3.1.

## 12. Báo lỗi bảo mật

Phát hiện lỗ hổng → **không** mở issue công khai, **không** commit PoC.
Báo trực tiếp cho quản trị dự án.

## 13. Kiểm tra

Trước mỗi lần merge: `docs/security/SECURITY_CHECKLIST.md`.
