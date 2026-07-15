# Test Strategy — NIDQC

> **Trạng thái: chưa có test nào.** Chưa cài PHPUnit config, chưa có Vitest.
> Đây là chiến lược, không phải mô tả hiện trạng. Dựng hạ tầng test là task riêng.

## 1. Nguyên tắc

1. **Test hành vi, không test cài đặt.** Test "lọc theo năm trả đúng văn bản", không test "hàm gọi service X".
2. **Chưa chạy thì chưa xong.** Test viết ra mà không chạy là vô nghĩa.
3. **Test fail thì nói fail** — kèm output. Không sửa test cho nó xanh.
4. Ưu tiên test thứ **hỏng thì đau**: bảo mật, SEO, tiếng Việt, fallback không-JS.

## 2. Các tầng test

| Tầng | Công cụ | Test gì |
|---|---|---|
| Unit (PHP) | PHPUnit | Logic thuần: validate param, format dữ liệu |
| Kernel (PHP) | PHPUnit | Entity query, service — có Drupal, không có HTTP |
| Functional (PHP) | PHPUnit BrowserTestBase | Route, quyền truy cập, HTML trả về |
| Unit (JS) | Vitest | Composable, `lib/api.js` |
| Component (JS) | Vitest + Testing Library | Island |
| Thủ công | Người | Design, tiếng Việt, accessibility |

## 3. Bắt buộc test — không thương lượng

### Bảo mật
- [ ] Người dùng **ẩn danh** không thấy nội dung chưa publish
- [ ] Route có `_permission` → từ chối đúng khi không đủ quyền
- [ ] Param sai kiểu / ngoài khoảng → `400`, không phải `500`
- [ ] `limit` vượt trần → bị chặn về 100
- [ ] Upload sai extension → bị từ chối

> Test bảo mật quan trọng hơn test "happy path". Happy path hỏng thì người dùng báo.
> Bảo mật hỏng thì không ai báo.

### SEO — lý do chọn kiến trúc islands
- [ ] **Nội dung có trong HTML thô** (không cần JS). Kiểm bằng `curl`, không phải DevTools:
      ```bash
      curl -s https://nidqc.ddev.site/tin-tuc | grep "tiêu đề tin"
      ```
- [ ] Metatag render đúng
- [ ] Alias hoạt động, URL cũ có redirect

### Tiếng Việt
- [ ] Dấu hiển thị đúng ở: HTML, JSON response, alias, file name, kết quả tìm kiếm
- [ ] Dữ liệu test dùng tiếng Việt thật (`Thông tư số 15/2024/TT-BYT`), không dùng `foo`/`bar`

### Progressive enhancement
- [ ] Tắt JS → mọi island vẫn dùng được (xem `FRONTEND_ARCHITECTURE.md` §5)

## 4. Chạy test

```bash
# PHP — sau khi dựng hạ tầng test
ddev exec phpunit web/modules/custom/

# JS
cd frontend && npm run test

# Chuẩn code
ddev composer phpcs
cd frontend && npm run lint
```

## 5. Dữ liệu test

- Dùng tiếng Việt có dấu thật.
- **Không** dùng dữ liệu thật của Viện trong test.
- **Không tự chế nội dung y tế/dược nghe như thật.** Đây là thông tin về thuốc — nội dung
  giả trông giống thật có thể bị nhầm là thật. Dùng nội dung rõ ràng là mẫu.

## 6. Không test cái gì

Không test Drupal core, contrib, hay Vue. Chúng đã có test riêng. Chỉ test code của dự án.

## 7. Trước khi merge

- [ ] Test liên quan đã chạy và **pass** (có output)
- [ ] Không giảm coverage của phần đang sửa
- [ ] Kiểm thủ công theo §3 (đặc biệt: `curl` cho SEO, tắt JS cho fallback)

## 8. UAT

Xem `docs/testing/UAT_CHECKLIST.md`.
