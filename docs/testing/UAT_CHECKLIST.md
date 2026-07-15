# UAT Checklist — bước 8

> Nghiệm thu bởi **người dùng thật của NIDQC**, không phải dev, không phải AI.
> Chạy trên **staging** với dữ liệu giống production.

**Phiên bản:** ___ · **Ngày:** ___ · **Người nghiệm thu:** ___

---

## 1. Nội dung — quan trọng nhất

> Đây là site về **thuốc**. Nội dung sai không chỉ là lỗi giao diện.

- [ ] Tên Viện, địa chỉ, điện thoại, email **chính xác**
- [ ] Cơ cấu tổ chức, tên đơn vị đúng thực tế
- [ ] Số hiệu văn bản, ngày ban hành đúng
- [ ] File văn bản tải về đúng file, mở được
- [ ] **Không còn nội dung mẫu / Lorem ipsum / dữ liệu giả** ở bất kỳ đâu
- [ ] Tiếng Việt đúng chính tả, đủ dấu, không lỗi font

## 2. Từng trang

Đối chiếu với `design/` — xem `docs/design/PAGE_MAPPING.md`.

| Trang | URL | Đạt |
|---|---|---|
| Trang chủ | `/` | ☐ |
| Giới thiệu chung | `/gioi-thieu-chung` | ☐ |
| Cơ cấu tổ chức | `/co-cau-to-chuc` | ☐ |
| Chính sách chất lượng | `/chinh-sach-chat-luong` | ☐ |
| Năng lực | `/nang-luc` | ☐ |
| Đào tạo NCKH | `/dao-tao-nckh` | ☐ |
| Tin tức | `/tin-tuc` | ☐ |
| Tin chi tiết | `/tin-tuc/{alias}` | ☐ |
| Văn bản tài liệu | `/van-ban-tai-lieu` | ☐ |
| FAQ | `/faq` | ☐ |
| Liên hệ | `/lien-he` | ☐ |

## 3. Chức năng

- [ ] Mega menu mở đúng, link đúng trang
- [ ] Tìm kiếm trả kết quả hợp lý (tiếng Việt có dấu **và** không dấu)
- [ ] Lọc văn bản theo loại/năm đúng
- [ ] FAQ đóng mở được
- [ ] Tabs (Năng lực, Đào tạo) chuyển đúng
- [ ] Form liên hệ gửi được, **có người nhận được thật**
- [ ] Phân trang tin tức đúng
- [ ] Tải file văn bản được

## 4. Thiết bị

- [ ] Điện thoại (375px)
- [ ] Máy tính bảng (768px)
- [ ] Máy tính (1280px+)
- [ ] Chrome · Firefox · Safari · Edge

## 5. Accessibility — bắt buộc với site nhà nước

- [ ] Duyệt toàn site **chỉ bằng bàn phím** (Tab, Enter, Esc)
- [ ] Vị trí focus luôn nhìn thấy rõ
- [ ] Ảnh có alt
- [ ] Phóng to 200% vẫn đọc và dùng được
- [ ] Tương phản chữ đạt

## 6. Hiệu năng

- [ ] Trang chủ tải < 3s trên mạng thường
- [ ] Ảnh không vỡ, không kéo giãn

## 7. SEO

- [ ] Tiêu đề, mô tả từng trang đúng
- [ ] `/sitemap.xml` truy cập được
- [ ] **URL cũ của site hiện tại vẫn vào được** (có redirect)

> Mục này quan trọng đặc biệt: link cũ nằm trong văn bản giấy đã phát hành. Link chết là sự cố thật.

## 8. Không được có

- [ ] Không lỗi trong console trình duyệt
- [ ] Không trang lỗi trắng
- [ ] Không link 404
- [ ] Không lộ thông tin kỹ thuật (stack trace, phiên bản, đường dẫn máy chủ)
- [ ] Không hiện email/điện thoại cá nhân của cán bộ nếu chưa được phép

---

## Kết luận

- [ ] ✅ **Đạt** — cho phép deploy
- [ ] ⚠️ **Đạt kèm điều kiện** — ghi bên dưới
- [ ] ❌ **Không đạt** — sửa rồi nghiệm thu lại

**Vấn đề còn tồn:**

| # | Mô tả | Mức độ | Xử lý trước deploy? |
|---|---|---|---|
| 1 | | | |

**Chữ ký:**
- Đại diện NIDQC: ___
- Đại diện dev: ___

---

> UAT chưa ký thì **không deploy**. Không có ngoại lệ, không có "deploy trước sửa sau".
