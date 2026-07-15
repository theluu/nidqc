# API Error Standard — NIDQC

## 1. Cấu trúc lỗi

Mọi lỗi trả về **đúng** cấu trúc này. Không có ngoại lệ.

```json
{
  "error": {
    "code": "INVALID_PARAMETER",
    "message": "Tham số 'year' không hợp lệ.",
    "details": [
      { "field": "year", "issue": "Phải là số nguyên từ 1990 đến 2026." }
    ]
  }
}
```

| Trường | Bắt buộc | Mô tả |
|---|---|---|
| `error.code` | ✅ | Mã máy đọc, `SCREAMING_SNAKE_CASE`. FE bắt theo mã này, **không parse `message`**. |
| `error.message` | ✅ | Tiếng Việt, hiển thị được cho người dùng cuối. |
| `error.details` | ❌ | Chi tiết theo từng field. Dùng cho lỗi validate. |

## 2. Mã lỗi

| HTTP | `code` | Khi nào |
|---|---|---|
| 400 | `INVALID_PARAMETER` | Tham số sai kiểu, ngoài khoảng, không thuộc whitelist |
| 400 | `MISSING_PARAMETER` | Thiếu tham số bắt buộc |
| 403 | `ACCESS_DENIED` | Không đủ quyền |
| 403 | `CSRF_TOKEN_INVALID` | Token CSRF sai/thiếu (POST) |
| 404 | `NOT_FOUND` | Không tìm thấy tài nguyên |
| 405 | `METHOD_NOT_ALLOWED` | Sai HTTP method |
| 429 | `RATE_LIMITED` | Quá nhiều request. Kèm header `Retry-After`. |
| 500 | `INTERNAL_ERROR` | Lỗi máy chủ |

## 3. Quy tắc bắt buộc

### 3.1. Không rò rỉ thông tin nội bộ

`message` **không bao giờ** chứa: stack trace, câu SQL, đường dẫn file, tên bảng, phiên bản phần mềm.

```php
// ❌ SAI — rò rỉ nội bộ ra ngoài
return new JsonResponse(['error' => ['message' => $e->getMessage()]], 500);

// ✅ ĐÚNG — log chi tiết vào trong, trả thông báo chung ra ngoài
$this->logger->error('Lỗi truy vấn văn bản: @msg', ['@msg' => $e->getMessage()]);
return new JsonResponse([
  'error' => [
    'code' => 'INTERNAL_ERROR',
    'message' => 'Đã có lỗi xảy ra. Vui lòng thử lại sau.',
  ],
], 500);
```

Đây là yêu cầu bảo mật, không phải góp ý phong cách. Xem `docs/security/SECURITY_POLICY.md`.

### 3.2. Không tiết lộ sự tồn tại

Không phân biệt "không tồn tại" với "không có quyền" khi điều đó để lộ thông tin.
Nội dung chưa publish → trả `404`, không phải `403`.

### 3.3. Message dành cho người dùng thật

Người đọc là cán bộ, người dân — không phải lập trình viên.

| ❌ | ✅ |
|---|---|
| `EntityQuery failed on field_year` | `Năm không hợp lệ.` |
| `Undefined index: category` | `Vui lòng chọn loại văn bản.` |
| `Error 500` | `Đã có lỗi xảy ra. Vui lòng thử lại sau.` |

Tiếng Việt có dấu đầy đủ.

### 3.4. HTTP status phải đúng

Không trả `200` kèm `{"error": ...}`. Status code là phần của contract.

## 4. Phía frontend

```js
// src/lib/api.js
if (!res.ok) {
  const body = await res.json().catch(() => null);
  throw new ApiError(
    body?.error?.code ?? 'INTERNAL_ERROR',
    body?.error?.message ?? 'Không kết nối được máy chủ. Vui lòng thử lại.',
    res.status,
  );
}
```

- Bắt theo `error.code`, **không** so khớp chuỗi `message`.
- Luôn có fallback khi response không phải JSON (nginx lỗi, mất mạng).
- Hiển thị `message` cho người dùng. **Không** hiện `code` ra màn hình.
- Mọi island xử lý đủ: loading · error · empty.

## 5. Logging

| Mức | Dùng cho |
|---|---|
| `error` | 500 — cần người xem |
| `warning` | 403, 429 — bất thường nhưng biết trước |
| `info` | 400, 404 — lỗi phía client, bình thường |

**Không log** mật khẩu, token, session, thông tin cá nhân.
