# Security Checklist — bước 6 của quy trình

> Chạy checklist này **trước khi merge**, sau khi code xong (bước 4–5).
> Người review ký tên. **Không tự ký cho code của chính mình.**
>
> Chỉ tick khi **đã kiểm tra thật**. Tick vì "chắc là ổn" là vô nghĩa và tệ hơn không tick.

**Task:** `TASK-___`  ·  **Người review:** ___  ·  **Ngày:** ___

---

## A. Input (mọi task có nhận dữ liệu ngoài)

- [ ] Mọi param có validate kiểu + khoảng + whitelist
- [ ] Dùng whitelist, không blacklist
- [ ] `limit` có giới hạn trên (≤ 100)
- [ ] Không tin `Content-Type` hay extension do client gửi
- [ ] Param không có trong `API_CONTRACT.md` thì bị từ chối, không âm thầm bỏ qua

## B. Output / XSS

- [ ] Không có `|raw` mới; nếu có → đã `json_encode`/sanitize **và** có comment giải thích
- [ ] Twig autoescape không bị tắt ở bất kỳ đâu
- [ ] Không `v-html` với dữ liệu từ API hoặc người dùng
- [ ] Không `echo`/`print` dữ liệu người dùng trong PHP
- [ ] Dữ liệu Twig → Vue đi qua `|json_encode` trước `|raw`

## C. SQL / Query

- [ ] Không nối chuỗi vào SQL
- [ ] Mọi query dùng placeholder hoặc Entity Query
- [ ] **`->accessCheck(TRUE)`** ở mọi Entity Query (nếu `FALSE` → có comment giải thích lý do)
- [ ] Endpoint công khai chỉ trả `status = 1`

## D. Quyền

- [ ] Mọi route mới có `_permission` hoặc `_access` có chủ đích
- [ ] Không có `_access: 'TRUE'` mới (nếu có → có comment giải thích)
- [ ] Đã thử truy cập bằng **người dùng ẩn danh** — không lộ gì thừa
- [ ] Nội dung chưa publish trả `404`, không phải `403`

## E. CSRF / Form

- [ ] Form dùng Form API (không tự xử lý `$_POST`)
- [ ] Endpoint POST kiểm tra CSRF token
- [ ] `GET` không gây thay đổi trạng thái

## F. File upload (nếu task có đụng)

- [ ] Whitelist extension (không có `php`, `phtml`, `html`, `svg`, `js`)
- [ ] Kiểm MIME thật theo nội dung file
- [ ] Giới hạn dung lượng ở Drupal **và** PHP **và** nginx
- [ ] **Đã kiểm tra thật** rằng nginx không thực thi PHP trong thư mục files
      (⚠️ `.htaccess` **không** có tác dụng với nginx — phải cấu hình riêng)

## G. Rò rỉ thông tin

- [ ] Thông báo lỗi ra ngoài không chứa stack trace / SQL / đường dẫn / phiên bản
- [ ] Chi tiết lỗi được log lại phía server
- [ ] Không log mật khẩu, token, session, thông tin cá nhân
- [ ] Không có `dpm()`, `var_dump()`, `console.log()` sót lại

## H. Secrets

- [ ] Không có key, mật khẩu, token trong diff
- [ ] Không commit `settings.local.php` hay `.env`
- [ ] `git diff` đã được đọc **toàn bộ**, không chỉ lướt

## I. Dependency

- [ ] Không có package mới ngoài task cho phép
- [ ] `composer.lock` / `package-lock.json` không đổi ngoài ý muốn

## J. Frontend

- [ ] Không hard-code URL/endpoint trong component (đi qua `src/lib/api.js`)
- [ ] Island vẫn hoạt động khi tắt JS
- [ ] Không lộ dữ liệu nhạy cảm trong `data-props` (nhìn được bằng View Source)

---

## Kết luận

- [ ] ✅ **Đạt** — có thể merge
- [ ] ⚠️ **Đạt kèm ghi chú** — ghi bên dưới, xử lý ở task sau
- [ ] ❌ **Không đạt** — phải sửa rồi review lại

**Ghi chú:**

```
```

**Chữ ký người review:** ___

---

> Nếu bất kỳ mục nào không áp dụng cho task này, ghi `N/A` **kèm lý do**.
> Bỏ trống là chưa review.
