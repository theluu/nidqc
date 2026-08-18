# Hướng dẫn nhập liệu trang chủ

Dành cho cán bộ quản trị nội dung. Không cần biết kỹ thuật.

**Nguyên tắc chung: bạn không bao giờ phải gõ đường dẫn (URL).** Hệ thống tự sinh
đường dẫn từ tiêu đề bạn nhập. Ô "Link ngoài (không bắt buộc)" chỉ dùng khi muốn
dẫn sang một website khác — bình thường **để trống**.

Mọi thứ đều nằm trong menu **Nội dung** của trang quản trị.

---

## 1. Khối "Dịch vụ" (6 ô vuông)

Mỗi ô vuông là một mục trong `Nội dung → lọc theo "Dịch vụ & tra cứu"`.

| Ô nhập | Ghi chú |
|---|---|
| Tiêu đề | Tên hiện dưới ảnh |
| **Danh mục dịch vụ** | Chọn từ danh sách. Ô vuông sẽ tự dẫn tới danh sách bài viết của danh mục này |
| Ảnh | Nên dùng ảnh ngang, rộng từ 650px trở lên |
| Thứ tự (nhỏ hiện trước) | Số. Ví dụ 1, 2, 3… |
| Link ngoài | Để trống |

**Thêm dịch vụ mới** cần 2 việc, theo đúng thứ tự:

1. Tạo danh mục: `Cấu trúc → Phân loại → Danh mục dịch vụ → Thêm mục`.
2. Tạo ô vuông: `Nội dung → Thêm nội dung → Dịch vụ & tra cứu`, chọn danh mục vừa tạo.

### Bài viết của một dịch vụ

Bấm vào ô vuông sẽ ra **danh sách bài viết**, không phải một trang duy nhất.

Viết bài: `Nội dung → Thêm nội dung → Bài viết dịch vụ`. Nhập Tiêu đề, Nội dung,
Ảnh đại diện, Tài liệu đính kèm, và **chọn Danh mục dịch vụ**. Lưu là xong — bài
tự vào đúng danh sách, đường dẫn tự sinh.

---

## 2. Khối "Hoạt động chuyên môn" (5 ô vuông)

`Nội dung → lọc theo "Hoạt động chuyên môn"`.

| Ô nhập | Ghi chú |
|---|---|
| Tiêu đề | Tên hiện dưới ảnh, **và** là tên trang khi bấm vào |
| Ảnh | Ảnh ngang, rộng từ 650px trở lên |
| Mô tả ngắn | Không bắt buộc |
| **Nội dung bài viết** | Bài viết đầy đủ của hoạt động này |
| Thứ tự (nhỏ hiện trước) | Số |
| Link ngoài | Để trống |

Ảnh, tiêu đề và bài viết **nằm chung một chỗ**. Không phải tạo trang riêng, không
phải gõ đường dẫn, không phải dán link.

> **Lưu ý:** để trống ô "Nội dung bài viết" thì ô vuông trên trang chủ chỉ là ảnh,
> bấm vào không đi đâu cả. Muốn bấm được thì phải có nội dung.

Mục này cũng chính là **menu con "Hoạt động chuyên môn"** trên thanh menu. Thêm,
sửa, xoá ở đây là menu đổi theo ngay, không phải báo kỹ thuật.

---

## 3. Khối "Danh mục năng lực" (cột phải, có đánh số)

`Nội dung → lọc theo "Danh mục năng lực"`. Nhập giống mục 2, chỉ khác là không có ảnh:

Tiêu đề · Mô tả ngắn (hiện ngoài trang chủ) · **Nội dung bài viết** · Thứ tự.

---

## 4. Banner và Liên kết nổi bật

`Nội dung → lọc theo "Banner & liên kết ảnh"`. Ô **Vị trí hiển thị** quyết định
banner nằm đâu:

| Vị trí | Nơi hiển thị trên trang chủ |
|---|---|
| `ads_1` | Dải banner chạy ngang, phía trên khối Hoạt động chuyên môn |
| `ads_2` | Dải banner chạy ngang, phía dưới |
| `sidebar` | Cột "Liên kết nổi bật" bên phải |

Banner **bắt buộc phải có ảnh**, nếu không sẽ bị bỏ qua (một ô trắng làm hỏng cả dải).

Nút **"Tra cứu chất chuẩn"** nằm ở `Nội dung → "Khối trang chủ (CTA + Video)"`.

---

## 5. Sửa xong bao lâu thì trang chủ đổi?

Thường là ngay lập tức. Hệ thống tự xoá bộ nhớ đệm khi bạn lưu nội dung. Nếu 10
phút sau vẫn thấy bản cũ, báo bộ phận kỹ thuật.

---

## 6. Những việc **không** cần làm nữa

Nếu bạn từng được hướng dẫn làm các việc sau, nay đã bỏ:

- ❌ Tạo một "Trang tĩnh" riêng cho mỗi hoạt động chuyên môn / mục năng lực.
- ❌ Bỏ tick "Generate automatic URL alias" rồi tự gõ `/hoat-dong-chuyen-mon/…`.
- ❌ Copy đường dẫn đó dán ngược vào ô "Đường dẫn" của ô vuông trang chủ.
- ❌ Gõ tay `/dich-vu/…` cho ô vuông dịch vụ.

Nội dung của các trang tĩnh cũ đã được chuyển sang đúng chỗ mới, đường dẫn giữ
nguyên nên các link đã gửi ra ngoài vẫn hoạt động.
