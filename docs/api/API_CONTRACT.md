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
| `GET /api/v1/contact/config` | contact form `/lien-he` | 🟢 Chốt trong TASK-013 |
| `GET /api/v1/online` | online counter toàn site | 🟢 Chốt trong TASK-012 |
| `GET /api/v1/online/csrf-token` | online counter toàn site | 🟢 Chốt trong TASK-012 |
| `POST /api/v1/online/heartbeat` | online counter toàn site | 🟢 Chốt trong TASK-012 |
| `GET /api/v1/contact/csrf-token` | contact form `/lien-he` | 🟢 Chốt trong TASK-009 |
| `POST /api/v1/contact` | contact form `/lien-he` | 🟢 Chốt trong TASK-009 |
| `GET /api/v1/documents` | `doc-filter` | 🔴 Chưa đặc tả |
| `GET /api/v1/news` | `news-filter` | 🔴 Chưa đặc tả |
| `GET /api/v1/news/search` | popup tìm kiếm + `/tim-kiem` | 🟢 Chốt trong TASK-017 |
| `GET /api/v1/news/list` | `/tin-tuc`, trang chủ, `sitemap.xml` | 🟢 Chốt trong TASK-018 |
| `GET /api/v1/news/detail` | trang chi tiết tin `/<slug>` | 🟢 Chốt trong TASK-018 |
| `GET /api/v1/standards/search` | `standard-search` | 🔴 Chưa đặc tả — **phạm vi chưa rõ**, xem `PROJECT_CONTEXT.md` §5 |

`mega-menu`, `faq-accordion`, `tabs` **không cần API** — dữ liệu render sẵn từ Twig.

## 4. Endpoint đã chốt

### `GET /api/v1/news/list`

**Mục đích:** danh sách tin có phân trang **kèm tổng số** và (tuỳ chọn) danh mục,
phục vụ `/tin-tuc`, khối tin trang chủ và `sitemap.xml`.

**Vì sao không dùng JSON:API:** JSON:API không trả tổng số bản ghi (chỉ có link
`next`/`prev`) và chặn cứng `page[limit]` ở 50. Frontend phải liệt kê hết rồi cộng
— 18 request chỉ để đếm 705 tin, đo được 5.5s cho một lần mở `/tin-tuc` khi cache
nguội. Ở đây tổng số là một câu `COUNT`.

**Query params**

| Tên | Kiểu | Bắt buộc | Mặc định | Ràng buộc |
|---|---|---|---|---|
| `cat` | string | không | — | UUID term hoặc **tên** chuyên mục, nhiều giá trị phân tách bằng `,`; `all` = không lọc |
| `page` | int | không | `0` | số nguyên, 0–10000 |
| `limit` | int | không | `12` | số nguyên, 1–50 |
| `categories` | string | không | — | `1` để trả kèm danh sách chuyên mục |

Param ngoài contract bị từ chối bằng `400 INVALID_PARAMETER`. `cat` không khớp
chuyên mục nào trả `200` với `data: []` (không phải lỗi).

**Response 200**

```json
{
  "data": [
    {
      "id": 123,
      "title": "Thông báo mẫu",
      "created": "2026-07-26T08:30:00+00:00",
      "changed": "2026-07-26T09:00:00+00:00",
      "tag": "Thông báo",
      "category": "Thông báo",
      "image": "/sites/default/files/styles/max_650x650/public/news/example.jpg.avif?itok=…",
      "alias": "/thong-bao-mau"
    }
  ],
  "meta": { "total": 705, "page": 0, "limit": 12 },
  "categories": [{ "id": "<uuid>", "label": "Thông báo" }]
}
```

`categories` chỉ xuất hiện khi truyền `categories=1`.

**Cache:** cache tag `node_list:news`, `taxonomy_term_list:news_category`; cache
context `url.query_args:cat|page|limit|categories`.

### `GET /api/v1/news/detail`

**Mục đích:** trọn gói dữ liệu một trang chi tiết tin — node + tin liên quan + tin
mới nhất — trong **một** request.

**Vì sao không dùng JSON:API:** JSON:API không lọc được trên computed field `path`
(trả 500 `'path' not found`), nên frontend phải dựng map `alias -> nid` bằng 16
request quét toàn bộ node: đo được **13.8s** khi cache Drupal nguội, 1.07s khi ấm.
Ở đây alias tra thẳng bảng `path_alias` (1 query có index).

**Query params**

| Tên | Kiểu | Bắt buộc | Mặc định | Ràng buộc |
|---|---|---|---|---|
| `alias` | string | có | — | đường dẫn bài, tối đa 512 ký tự; tự thêm `/` đầu |

**Response 200**

```json
{
  "data": {
    "node": {
      "nid": 106,
      "title": "Tiêu đề bài",
      "created": "2026-05-28T05:00:00+00:00",
      "tag": "Tin hoạt động",
      "category": "Tin hoạt động",
      "image": "/sites/default/files/styles/max_1300x1300/public/…?itok=…",
      "body": "<p>…</p>",
      "attachments": [{ "url": "/sites/default/files/…pdf", "label": "Văn bản gốc" }]
    },
    "related": [{ "id": 1, "title": "…", "created": "…", "changed": "…", "tag": "…", "category": "…", "image": "…", "alias": "/…" }],
    "latest": []
  }
}
```

`related` tối đa 3 (cùng chuyên mục, thiếu thì lấp bằng `latest`), `latest` tối đa 5.
Ảnh trong `body` được đổi sang image style `max_1300x1300`, thêm
`loading="lazy" decoding="async"` và `width`/`height` đúng tỉ lệ derivative (đo từ
file gốc) để lazy-load không gây layout shift.

**Lỗi:** `400 MISSING_PARAMETER` · `400 INVALID_PARAMETER` · `404 NOT_FOUND`
(alias không tồn tại, không phải bài `news`, hoặc chưa xuất bản) · `500 INTERNAL_ERROR`.

**Cache:** cache tag `node_list:news` + cache tag của node; cache context
`url.query_args:alias`. Response 404 cũng cacheable theo `node_list:news` để bài
mới xuất bản tự làm mới.

### `GET /api/v1/news/search`

**Mục đích:** tìm theo tiêu đề và nội dung trong content type `news`, phục vụ
popup tìm kiếm toàn site và trang kết quả SSR `/tim-kiem`.

**Query params**

| Tên | Kiểu | Bắt buộc | Mặc định | Ràng buộc |
|---|---|---|---|---|
| `q` | string | có | — | trim, 2–200 ký tự |
| `page` | int | không | `0` | số nguyên, 0–10000 |
| `limit` | int | không | `12` | số nguyên, 1–100 |

Param ngoài contract bị từ chối bằng `400 INVALID_PARAMETER`.

**Response 200**

```json
{
  "data": [
    {
      "id": 123,
      "title": "Thông báo mẫu",
      "created": "2026-07-26T08:30:00+07:00",
      "tag": "Thông báo",
      "image": "/sites/default/files/news/example.jpg",
      "url": "/tin-tuc/thong-bao-mau"
    }
  ],
  "meta": { "total": 1, "page": 0, "limit": 12 }
}
```

**Lỗi:** `400 MISSING_PARAMETER` · `400 INVALID_PARAMETER` ·
`500 INTERNAL_ERROR`.

**Cache:** cache tag `node_list:news`; cache context `url.query_args:q`,
`url.query_args:page`, `url.query_args:limit`.

**Quyền:** route yêu cầu `access content`. Entity Query luôn
`->accessCheck(TRUE)`, giới hạn `type = news` và `status = 1`.

**Bảo mật:** không nối chuỗi SQL; từ khóa đi qua Entity Query; không trả body;
giới hạn độ dài từ khóa, trang và số kết quả.

**Progressive enhancement:** icon là link tới `/tim-kiem`; popup chứa form GET
tới `/tim-kiem?q=...`. Trang kết quả được Nuxt SSR render nên kết quả có trong
HTML thô; không JavaScript vẫn mở trang và gửi form được.

### `GET /api/v1/contact/config`

**Mục đích:** trả cấu hình công khai cần thiết để frontend khởi tạo reCAPTCHA v3
mà không cần build lại Nuxt khi quản trị viên đổi site key.

**Query params:** không có. Param lạ trả `400 INVALID_PARAMETER`.

**Response 200**

```json
{
  "data": {
    "recaptcha": {
      "enabled": true,
      "site_key": "6Lc_public_site_key"
    }
  }
}
```

`enabled = false` ở DDEV khi `NIDQC_RECAPTCHA_BYPASS=1`. Response không bao giờ
chứa reCAPTCHA secret, SMTP password, SMTP username hoặc DSN.

**Lỗi:** `400 INVALID_PARAMETER` · `500 INTERNAL_ERROR`

**Cache:** không cache; `Cache-Control: no-store`.

**Quyền:** public có chủ đích; chỉ trả reCAPTCHA site key vốn phải xuất hiện trên
trình duyệt và cờ enabled.

**Progressive enhancement:** không JavaScript thì trang vẫn hiển thị email, số
điện thoại và địa chỉ liên hệ server-side.

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
