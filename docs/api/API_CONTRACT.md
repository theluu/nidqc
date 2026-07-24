# API Contract — NIDQC

> **Bước 3 của quy trình.** Contract phải **chốt trước khi viết code** backend hoặc frontend.
> Đây là điểm gãy hay gặp nhất: BE và FE code song song theo hai giả định khác nhau.

---

## 1. Nguyên tắc

1. **Contract trước, code sau.** Endpoint chưa có trong file này thì chưa được gọi, chưa được viết.
2. **Chỉ tạo endpoint cho island cần dữ liệu động.** Nội dung tĩnh → Twig render, không qua API.
3. **Không bật JSON:API rộng rãi.** Mỗi endpoint là một quyết định có chủ đích.
4. Đổi contract = đổi file này trước + báo cả hai phía. Không đổi lén trong code.

## 2. Quy ước chung

| Mục | Quy định |
|---|---|
| Base path | `/api/v1/` |
| Format | JSON, UTF-8 |
| Method | `GET` cho đọc. `POST` cho ghi (kèm CSRF token). |
| Ngôn ngữ | Tiếng Việt có dấu, không escape unicode |
| Phân trang | `?page=0&limit=20` — `limit` mặc định 20, **tối đa 100** |
| Lỗi | Theo `docs/api/API_ERROR_STANDARD.md` |
| Auth | Mặc định công khai (nội dung công khai). Ghi rõ nếu khác. |

### Cấu trúc response thành công

```json
{
  "data": [],
  "meta": { "total": 0, "page": 0, "limit": 20 }
}
```

## 3. Endpoint

Danh sách dưới đây gồm endpoint đã chốt và endpoint dự kiến. Endpoint chưa có đặc tả đầy đủ
thì chưa được gọi, chưa được viết.

| Endpoint | Island | Trạng thái |
|---|---|---|
| `GET /api/v1/online` | online counter toàn site | 🟢 Chốt trong TASK-012 |
| `GET /api/v1/online/csrf-token` | online counter toàn site | 🟢 Chốt trong TASK-012 |
| `POST /api/v1/online/heartbeat` | online counter toàn site | 🟢 Chốt trong TASK-012 |
| `GET /api/v1/contact/csrf-token` | contact form `/lien-he` | 🟢 Chốt trong TASK-009 |
| `POST /api/v1/contact` | contact form `/lien-he` | 🟢 Chốt trong TASK-009 |
| `GET /api/v1/documents` | `doc-filter` | 🔴 Chưa đặc tả |
| `GET /api/v1/news` | `news-filter` | 🔴 Chưa đặc tả |
| `GET /api/v1/standards/search` | `standard-search` | 🔴 Chưa đặc tả — **phạm vi chưa rõ**, xem `PROJECT_CONTEXT.md` §5 |

`mega-menu`, `faq-accordion`, `tabs` **không cần API** — dữ liệu render sẵn từ Twig.

## 4. Endpoint đã chốt

### Online counter

**Định nghĩa “đang trực tuyến”:** số session Drupal khác nhau có hoạt động trong
300 giây gần nhất. Không lưu hoặc trả IP, user-agent, fingerprint, URL đang xem
hay danh tính người dùng.

#### `GET /api/v1/online`

**Mục đích:** trả số người đang trực tuyến để SSR và client đọc. GET chỉ đọc,
không tạo session và không thay đổi trạng thái.

**Query params:** không có. Param lạ trả `400 INVALID_PARAMETER`.

**Response 200**

```json
{
  "data": {
    "count": 128,
    "window_seconds": 300
  }
}
```

**Lỗi:** `400 INVALID_PARAMETER` · `500 INTERNAL_ERROR`

**Cache:** không cache; `Cache-Control: no-store`.

**Quyền:** public có chủ đích; chỉ trả số tổng hợp, không trả dữ liệu session.

**Progressive enhancement:** Twig/Nuxt SSR gọi endpoint để render giá trị ban
đầu. Không JavaScript thì số tại thời điểm render vẫn đọc được.

#### `GET /api/v1/online/csrf-token`

**Mục đích:** tạo anonymous session và trả CSRF token core, dùng riêng cho
heartbeat.

**Response 200:** plain text UTF-8, token gắn với session cookie.

**Lỗi:** `500 INTERNAL_ERROR`

**Cache:** không cache; `Cache-Control: no-store`.

**Quyền:** public có chủ đích; token không dùng được nếu thiếu cookie session đi kèm.

#### `POST /api/v1/online/heartbeat`

**Mục đích:** đánh dấu session hiện tại vừa hoạt động và trả số tổng hợp mới nhất.

**Headers**

| Tên | Bắt buộc | Ràng buộc |
|---|---|---|
| `X-CSRF-Token` | có | token từ `GET /api/v1/online/csrf-token`, cùng session cookie |

**Body và query params:** không có. Body/query khác rỗng trả
`400 INVALID_PARAMETER`.

**Response 200:** cùng schema với `GET /api/v1/online`.

**Lỗi:** `400 INVALID_PARAMETER` · `403 CSRF_TOKEN_INVALID` ·
`500 INTERNAL_ERROR`

**Cache:** không cache; `Cache-Control: no-store`.

**Bảo mật và tải:** client gửi tối đa một heartbeat mỗi 60 giây khi tab đang
hiển thị. Server chỉ cập nhật session hiện tại; nhiều heartbeat cùng session
không làm tăng số đếm.

### `POST /api/v1/contact`

**Mục đích:** nhận form liên hệ trên `/lien-he`, xác thực CSRF + reCAPTCHA v3, lưu submission
thành node `contact_submission` không publish, gửi email cho admin và email xác nhận cho người gửi.

**Headers**

| Tên | Bắt buộc | Ràng buộc |
|---|---|---|
| `Content-Type` | có | `application/json` |
| `X-CSRF-Token` | có | Lấy từ `GET /session/token`; token sai/thiếu trả `403 CSRF_TOKEN_INVALID` |

**JSON body**

| Tên | Kiểu | Bắt buộc | Mặc định | Ràng buộc |
|---|---|---|---|---|
| `name` | string | có | — | trim, 2–120 ký tự |
| `email` | string | có | — | email hợp lệ, 5–254 ký tự |
| `phone` | string | không | `''` | trim, tối đa 40 ký tự, chỉ chữ số, khoảng trắng và `+ . ( ) -` |
| `subject` | string | không | `Khác` | một trong: `Dịch vụ kiểm nghiệm`, `Chất chuẩn - chất đối chiếu`, `Văn bản - tài liệu`, `Khác` |
| `message` | string | có | — | trim, 10–4000 ký tự |
| `recaptchaToken` | string | có | — | token reCAPTCHA v3, tối đa 4096 ký tự |

Param ngoài contract bị từ chối bằng `400 INVALID_PARAMETER`.

**Response 200**

```json
{
  "data": {
    "id": 123,
    "message": "Cảm ơn bạn đã gửi liên hệ. Viện sẽ phản hồi sớm nhất có thể."
  }
}
```

**Lỗi:** `400 MISSING_PARAMETER` · `400 INVALID_PARAMETER` · `403 CSRF_TOKEN_INVALID` ·
`403 ACCESS_DENIED` khi reCAPTCHA không đạt · `429 RATE_LIMITED` · `500 INTERNAL_ERROR`

**Cache:** không cache. Response có `Cache-Control: no-store`.

**Bảo mật:**
- Route public có chủ đích vì đây là form cho người dùng ẩn danh.
- POST luôn kiểm `X-CSRF-Token`.
- reCAPTCHA v3 verify server-side, action phải là `contact_submit`, score tối thiểu cấu hình được
  và mặc định `0.5`.
- Flood control: tối đa 5 submit / 1 giờ / IP.
- Submission lưu thành node `contact_submission` với `status = 0`, không publish ra public.
- Không log token reCAPTCHA, nội dung message hoặc dữ liệu cá nhân.
- Không commit reCAPTCHA secret hoặc SMTP password; production đặt trong `settings.local.php` hoặc
  biến môi trường ngoài git.

**Progressive enhancement:** reCAPTCHA v3 cần JavaScript. Không JS → trang vẫn hiển thị đầy đủ
thông tin liên hệ và link mở bản đồ; người dùng liên hệ qua email/điện thoại hiển thị server-side.

---

### `GET /api/v1/contact/csrf-token`

**Mục đích:** tạo anonymous session cho form liên hệ và trả token CSRF core của Drupal để gửi
`POST /api/v1/contact`.

**Response 200**

Plain text UTF-8, một token 43 ký tự.

**Lỗi:** `500 INTERNAL_ERROR`

**Cache:** không cache. Response có `Cache-Control: no-store`.

**Bảo mật:**
- Route public có chủ đích vì token tự nó không submit được nếu không có session cookie tương ứng.
- Frontend phải gọi với `credentials: include` và gửi lại cookie khi POST.

---

## 5. Mẫu đặc tả endpoint

Mỗi endpoint phải điền đủ mẫu này trước khi chuyển sang bước 4.

---

### `GET /api/v1/documents`

**Mục đích:** lọc danh sách văn bản cho island `doc-filter`.

**Query params**

| Tên | Kiểu | Bắt buộc | Mặc định | Ràng buộc |
|---|---|---|---|---|
| `category` | string | không | — | phải thuộc taxonomy `document_category` |
| `year` | int | không | — | 1990 ≤ year ≤ năm hiện tại |
| `q` | string | không | — | ≤ 200 ký tự |
| `page` | int | không | `0` | ≥ 0 |
| `limit` | int | không | `20` | 1–100 |

**Response 200**

```json
{
  "data": [
    {
      "id": 123,
      "title": "Thông tư quy định về kiểm nghiệm thuốc",
      "number": "15/2024/TT-BYT",
      "issued_date": "2024-03-15",
      "category": { "id": 4, "name": "Thông tư" },
      "file": { "url": "/sites/default/files/...", "size": 245678, "mime": "application/pdf" },
      "url": "/van-ban-tai-lieu/thong-tu-quy-dinh-ve-kiem-nghiem-thuoc"
    }
  ],
  "meta": { "total": 87, "page": 0, "limit": 20 }
}
```

**Lỗi:** `400` tham số sai · `429` quá nhiều request

**Cache:** cache tag `node_list:document`. Cache context `url.query_args`.

**Bảo mật:**
- Chỉ trả node đã publish (`status = 1`).
- `q` đưa vào Entity Query có placeholder — **không nối chuỗi**.
- `category`, `year` validate whitelist trước khi query.

**Progressive enhancement:** không JS → form GET tới `/van-ban-tai-lieu?category=...`,
Drupal Views lọc phía server, trả HTML đầy đủ.

---

## 6. Checklist trước khi chốt một endpoint

- [ ] Đã điền đủ mẫu §4
- [ ] Mọi param có kiểu + ràng buộc + giá trị mặc định
- [ ] Response có ví dụ thật (tiếng Việt có dấu)
- [ ] Đã liệt kê mã lỗi
- [ ] Đã xác định cache tag/context
- [ ] Đã ghi rõ quyền truy cập
- [ ] Đã ghi fallback không-JS
- [ ] **Cả BE và FE đã đọc và đồng ý**
- [ ] Được người duyệt
