# Phân quyền và quy trình duyệt bài — Content Type Tin tức

## 1. Phạm vi

Chỉ áp dụng cho Content Type:

```text
Tin tức
```

Mục tiêu:

- Người viết bài chỉ được tạo và gửi bài chờ duyệt.
- Kiểm duyệt viên được biên tập và trả lại bài.
- Chỉ Admin / Lãnh đạo được phê duyệt xuất bản.
- Bài chưa được phê duyệt không được hiển thị public.

---

## 2. Roles

Tạo 3 roles:

| Cấp | Role |
|---|---|
| Cấp 1 | Người viết bài |
| Cấp 2 | Kiểm duyệt viên |
| Cấp 3 | Admin / Lãnh đạo |

---

## 3. Luồng duyệt bài

```text
Người viết bài
      ↓
   BẢN NHÁP
      ↓
  Gửi kiểm duyệt
      ↓
 CHỜ KIỂM DUYỆT
      ↓
 ┌────┴───────────────┐
 ↓                    ↓
Trả lại           Phê duyệt
 ↓                    ↓
BẢN NHÁP          ĐÃ XUẤT BẢN
                        ↓
                  Hiển thị public
```

Quy tắc:

```text
Người viết bài
→ Bản nháp
→ Gửi kiểm duyệt

Kiểm duyệt viên
→ Biên tập
→ Giữ trạng thái chờ duyệt
→ Hoặc trả lại người viết

Admin / Lãnh đạo
→ Phê duyệt cuối
→ Xuất bản
```

---

## 4. Trạng thái bài viết

Content Type `Tin tức` có 3 trạng thái:

```text
1. Bản nháp
2. Chờ kiểm duyệt
3. Đã xuất bản
```

Chỉ trạng thái:

```text
Đã xuất bản
```

được hiển thị trên website public.

---

## 5. Phân quyền Content Type Tin tức

### Cấp 1 — Người viết bài

Được phép:

- Tạo Tin tức.
- Sửa Tin tức do chính mình tạo.
- Lưu bài ở trạng thái Bản nháp.
- Gửi bài sang Chờ kiểm duyệt.
- Xem các bài Tin tức do mình tạo.

Không được phép:

- Sửa Tin tức của người khác.
- Phê duyệt bài.
- Xuất bản bài.
- Tự chuyển bài từ Chờ kiểm duyệt sang Đã xuất bản.

---

### Cấp 2 — Kiểm duyệt viên

Được phép:

- Xem các bài Tin tức.
- Sửa Tin tức của Người viết bài.
- Biên tập nội dung đang Chờ kiểm duyệt.
- Trả bài về Bản nháp để Người viết bài sửa lại.
- Giữ bài ở trạng thái Chờ kiểm duyệt.

Không được phép:

- Phê duyệt xuất bản.
- Chuyển bài sang Đã xuất bản.

---

### Cấp 3 — Admin / Lãnh đạo

Được phép:

- Xem tất cả Tin tức.
- Sửa tất cả Tin tức.
- Xem bài đang Chờ kiểm duyệt.
- Trả bài về Bản nháp.
- Phê duyệt bài.
- Chuyển bài từ Chờ kiểm duyệt sang Đã xuất bản.

Đây là cấp duy nhất được phép:

```text
CHỜ KIỂM DUYỆT → ĐÃ XUẤT BẢN
```

---

## 6. Permission Matrix

| Quyền | Người viết bài | Kiểm duyệt viên | Admin / Lãnh đạo |
|---|:---:|:---:|:---:|
| Tạo Tin tức | ✅ | ✅ | ✅ |
| Sửa Tin tức của mình | ✅ | ✅ | ✅ |
| Sửa Tin tức người khác | ❌ | ✅ | ✅ |
| Lưu Bản nháp | ✅ | ✅ | ✅ |
| Gửi Chờ kiểm duyệt | ✅ | ✅ | ✅ |
| Trả về Bản nháp | ❌ | ✅ | ✅ |
| Phê duyệt / Xuất bản | ❌ | ❌ | ✅ |
| Xem bài chưa xuất bản | Bài của mình | ✅ | ✅ |

---

## 7. Yêu cầu bắt buộc cho Codex

Codex thực hiện:

- [ ] Tạo đủ 3 roles.
- [ ] Áp dụng phân quyền cho Content Type `Tin tức`.
- [ ] Tạo 3 trạng thái: Bản nháp, Chờ kiểm duyệt, Đã xuất bản.
- [ ] Người viết bài không được publish.
- [ ] Kiểm duyệt viên không được publish.
- [ ] Chỉ Admin / Lãnh đạo được publish.
- [ ] Kiểm duyệt viên được sửa bài của Người viết bài.
- [ ] Kiểm duyệt viên được trả bài về Bản nháp.
- [ ] Bài Bản nháp không hiển thị public.
- [ ] Bài Chờ kiểm duyệt không hiển thị public.
- [ ] Chỉ bài Đã xuất bản hiển thị public.

---

## 8. Kết quả Codex phải báo lại

Sau khi hoàn thành, trả về:

```text
1. Danh sách roles đã tạo.
2. Permission của từng role đối với Tin tức.
3. Luồng trạng thái đã cấu hình.
4. File/config đã thay đổi.
5. Kết quả kiểm tra từng role.
```
