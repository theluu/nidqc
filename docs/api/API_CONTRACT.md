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

> **Trạng thái hiện tại: chưa có endpoint nào được chốt.**
>
> Danh sách dưới đây là **dự kiến**, suy ra từ các island trong `docs/design/DESIGN_SYSTEM.md` §5.
> Mỗi endpoint phải được đặc tả đầy đủ và duyệt trước khi code. Agent **không được** tự
> đặc tả rồi tự code — đó là bỏ qua bước 3.

| Endpoint | Island | Trạng thái |
|---|---|---|
| `GET /api/v1/documents` | `doc-filter` | 🔴 Chưa đặc tả |
| `GET /api/v1/news` | `news-filter` | 🔴 Chưa đặc tả |
| `GET /api/v1/standards/search` | `standard-search` | 🔴 Chưa đặc tả — **phạm vi chưa rõ**, xem `PROJECT_CONTEXT.md` §5 |

`mega-menu`, `faq-accordion`, `tabs` **không cần API** — dữ liệu render sẵn từ Twig.

## 4. Mẫu đặc tả endpoint

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

## 5. Checklist trước khi chốt một endpoint

- [ ] Đã điền đủ mẫu §4
- [ ] Mọi param có kiểu + ràng buộc + giá trị mặc định
- [ ] Response có ví dụ thật (tiếng Việt có dấu)
- [ ] Đã liệt kê mã lỗi
- [ ] Đã xác định cache tag/context
- [ ] Đã ghi rõ quyền truy cập
- [ ] Đã ghi fallback không-JS
- [ ] **Cả BE và FE đã đọc và đồng ý**
- [ ] Được người duyệt
