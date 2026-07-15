# Database Schema — NIDQC

## 1. Trạng thái

> **Chưa cài đặt schema nào.** Drupal core đã có, chưa có content type custom.
>
> Thiết kế entity dự kiến: `docs/database/ENTITY_MAPPING.md`.

## 2. Nền tảng

| Mục | Giá trị |
|---|---|
| DBMS | MariaDB 11.8 (qua DDEV) |
| Charset | `utf8mb4` |
| Collation | `utf8mb4_general_ci` |

> `utf8mb4` bắt buộc — không phải `utf8`. MySQL/MariaDB gọi `utf8` là bí danh của `utf8mb3`,
> chỉ 3 byte, **không chứa hết** ký tự tiếng Việt tổ hợp và emoji. Sai charset thì lỗi chỉ lộ ra
> khi gặp ký tự cụ thể, thường là lúc đã có dữ liệu thật.

## 3. Nguyên tắc

1. **Không viết SQL tạo bảng thủ công.** Dùng Entity API / Field API của Drupal.
   Drupal tự sinh bảng. Tự tạo bảng = mất cache, mất access control, mất translation, mất revision.
2. **Không sửa bảng của core** (`node`, `users`, `node_field_data`…).
3. Bảng custom (nếu thật sự cần) → `hook_schema()` trong module, không chạy SQL tay.
4. Đổi schema → `drush cex` → commit `config/sync/`.

## 4. Cấu trúc Drupal sinh ra

Với content type `document` + field `field_issued_date`, Drupal tạo:

| Bảng | Nội dung |
|---|---|
| `node` | Bản ghi node |
| `node_field_data` | Dữ liệu theo ngôn ngữ: title, status, created |
| `node__field_issued_date` | Giá trị field |
| `node_revision__field_issued_date` | Lịch sử phiên bản |

Đây là lý do **không** đổi machine name sau khi có dữ liệu — tên bảng gắn với machine name.

## 5. Khi nào cần bảng custom

Hầu như **không bao giờ** trong dự án này. Entity API đủ cho toàn bộ nội dung.

Chỉ cân nhắc khi: dữ liệu không phải nội dung (log, cache, hàng đợi), khối lượng rất lớn,
hoặc hiệu năng đã đo được là vấn đề thật.

**Cần bảng custom → task riêng có `schema_change: true` và được duyệt.** Không tự quyết.

## 6. Index

Drupal tự index khoá chính và entity reference. Cân nhắc index thêm khi:
- Field hay dùng để lọc (`field_issued_date`, `field_category`)
- **Đã đo được** query chậm — không index theo phỏng đoán

## 7. Sao lưu

```bash
ddev drush sql:dump --gzip --result-file=/backup/nidqc-$(date +%F).sql.gz
```

Xem `docs/deployment/DEPLOYMENT.md` và `ROLLBACK.md`.

## 8. Cấm

- ⛔ Chạy SQL trực tiếp trên production
- ⛔ `ALTER TABLE` bảng của Drupal
- ⛔ Sửa dữ liệu bằng SQL thay vì Entity API (mất cache invalidation → nội dung cũ vẫn hiện)
- ⛔ Commit dump chứa dữ liệu thật của Viện
