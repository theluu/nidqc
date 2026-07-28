# Yêu cầu cập nhật Content Type Tin tức – Thư viện Video & Hình ảnh

## 1. Mục tiêu

Mở rộng **Content Type: Tin tức** để hỗ trợ thêm 2 danh mục nội dung:

- **Videos**
- **Hình ảnh**

Các nội dung thuộc 2 danh mục này được sử dụng cho khu vực **Thư viện Video / Hình ảnh trên trang chủ**.

---

## 2. Cập nhật Content Type: Tin tức

### 2.1. Thêm danh mục

Trong taxonomy/danh mục của `Tin tức`, thêm:

- `Videos`
- `Hình ảnh`

### 2.2. Quy tắc hiển thị

- Tin tức thuộc danh mục `Videos` chỉ hiển thị trong block **Thư viện Video** trên trang chủ.
- Tin tức thuộc danh mục `Hình ảnh` chỉ hiển thị trong block **Thư viện Hình ảnh / Media** tương ứng.
- Không để các nội dung này xuất hiện sai vào các block Tin tức thông thường nếu block hiện tại đang query toàn bộ Tin tức.

---

## 3. Field dành cho Videos

Khi admin chọn danh mục `Videos`, hiển thị thêm các field:

### 3.1. YouTube URL

Field:

```text
field_youtube_urls
```

Yêu cầu:

- Kiểu nhập URL.
- Hỗ trợ **multi-value**.
- Admin có thể nhập nhiều link YouTube.
- Validate URL hợp lệ.
- Hỗ trợ các dạng URL phổ biến:
  - `https://www.youtube.com/watch?v=...`
  - `https://youtu.be/...`
  - `https://www.youtube.com/shorts/...`

### 3.2. Upload Video

Field:

```text
field_videos
```

Yêu cầu:

- Cho phép upload video trực tiếp.
- Hỗ trợ **multi-value**.
- Các định dạng tối thiểu:
  - `.mp4`
  - `.webm`
- Video phải có thể phát trực tiếp trên frontend bằng HTML5 video player.

### 3.3. Video thumbnail

Frontend cần lấy thumbnail theo thứ tự ưu tiên:

1. Thumbnail/media được admin khai báo nếu hệ thống đã có field thumbnail.
2. Thumbnail YouTube tự động lấy theo YouTube ID.
3. Nếu là video upload local và không có thumbnail thì dùng placeholder phù hợp.

---

## 4. Field dành cho Hình ảnh

Khi admin chọn danh mục `Hình ảnh`, hiển thị thêm field:

```text
field_gallery_images
```

Yêu cầu:

- Cho phép upload nhiều ảnh.
- Field dạng **multi-value**.
- Có thể thay đổi thứ tự ảnh trong admin.
- Thứ tự hiển thị frontend phải đúng theo thứ tự admin đã sắp xếp.

Định dạng tối thiểu:

- `.jpg`
- `.jpeg`
- `.png`
- `.webp`

---

## 5. Block Thư viện Media trên trang chủ

Cập nhật khu vực thư viện trên trang chủ theo dạng **slider/carousel**, giao diện tương tự block **Tin nổi bật** hiện tại.

Mỗi item trong slider hiển thị:

- Thumbnail.
- Tiêu đề nếu thiết kế hiện tại có hỗ trợ.
- Icon phân biệt:
  - Hình ảnh.
  - Video.

Slider cần hỗ trợ:

- Previous.
- Next.
- Responsive desktop/tablet/mobile.
- Swipe trên mobile.
- Không reload trang khi chuyển slide.

---

## 6. Popup / Media Viewer

Khi người dùng click vào thumbnail hoặc icon media, mở viewer dạng modal/lightbox.

### 6.1. Nếu item là Hình ảnh

Hiển thị:

- Ảnh lớn.
- Nút `Previous`.
- Nút `Next`.
- Danh sách thumbnail ảnh.
- Có thể click thumbnail để chuyển ảnh.
- Nội dung lấy từ toàn bộ `field_gallery_images` của bài Tin tức tương ứng.

Ví dụ:

```text
Thumbnail trên homepage
        |
        v
Open Modal
        |
        +--> Image 1
        +--> Image 2
        +--> Image 3
        +--> ...
```

### 6.2. Nếu item là Video

Trong vùng media lớn của modal:

- Hiển thị video thay cho ảnh.
- Video phải play được trực tiếp.
- Nếu là YouTube:
  - Render bằng YouTube iframe/embed.
- Nếu là video upload:
  - Render bằng HTML5 `<video controls>`.

Viewer vẫn phải hỗ trợ:

- Previous.
- Next.
- Danh sách thumbnail.
- Chuyển qua lại giữa ảnh và video nếu cùng một gallery có nhiều media.

Khi chuyển khỏi video đang phát:

- Dừng video hiện tại.
- Không để audio tiếp tục chạy ngầm.

Khi đóng modal:

- Dừng toàn bộ video/audio đang phát.

---

## 7. Quy tắc dữ liệu frontend

Frontend cần normalize dữ liệu về dạng thống nhất, ví dụ:

```json
[
  {
    "type": "image",
    "thumbnail": "/path/image-1.webp",
    "src": "/path/image-1.webp"
  },
  {
    "type": "youtube",
    "thumbnail": "https://img.youtube.com/vi/VIDEO_ID/hqdefault.jpg",
    "video_id": "VIDEO_ID"
  },
  {
    "type": "video",
    "thumbnail": "/path/video-thumb.webp",
    "src": "/path/video.mp4"
  }
]
```

Không hard-code dữ liệu media trong frontend.

---

## 8. Logic hiển thị field trong Admin

Admin form của `Tin tức`:

```text
Danh mục = Videos
    -> Show field_youtube_urls
    -> Show field_videos

Danh mục = Hình ảnh
    -> Show field_gallery_images

Danh mục khác
    -> Không cần show các field media trên
```

Nếu hệ thống hiện tại khó condition field theo taxonomy ở backend, có thể sử dụng JavaScript admin form nhưng phải đảm bảo dữ liệu vẫn được validate ở backend.

---

## 9. Yêu cầu API / Backend

API trả về dữ liệu Tin tức cần có đầy đủ:

```json
{
  "id": 123,
  "title": "Tên nội dung",
  "category": "Videos",
  "youtube_urls": [],
  "videos": [],
  "gallery_images": []
}
```

Yêu cầu:

- Multi-value phải giữ đúng thứ tự.
- Trả đủ URL media để frontend render.
- YouTube URL cần parse được `video_id`.
- Không để frontend phải xử lý dữ liệu Drupal/raw field phức tạp nếu API hiện tại đã có layer normalize.

---

## 10. Yêu cầu responsive

### Desktop

- Slider hiển thị nhiều thumbnail.
- Modal media kích thước lớn.
- Previous/Next rõ ràng.

### Tablet

- Giảm số item trên slider phù hợp màn hình.

### Mobile

- Slider swipe được.
- Modal gần full-screen.
- Media không tràn màn hình.
- Video giữ đúng aspect ratio `16:9`.
- Ảnh dùng `object-fit: contain` trong viewer lớn.

---

## 11. Acceptance Criteria

Hoàn thành khi đạt đủ:

- [ ] Content Type Tin tức có danh mục `Videos`.
- [ ] Content Type Tin tức có danh mục `Hình ảnh`.
- [ ] Chọn `Videos` có thể nhập nhiều YouTube URL.
- [ ] Chọn `Videos` có thể upload nhiều video.
- [ ] Chọn `Hình ảnh` có thể upload nhiều ảnh.
- [ ] Media giữ đúng thứ tự admin nhập.
- [ ] Homepage có slider thư viện tương tự block Tin nổi bật.
- [ ] Click thumbnail mở modal/lightbox.
- [ ] Gallery ảnh có Previous/Next.
- [ ] Gallery ảnh có danh sách thumbnail.
- [ ] YouTube phát được trong viewer lớn.
- [ ] Video upload phát được trong viewer lớn.
- [ ] Chuyển slide phải dừng video cũ.
- [ ] Đóng modal phải dừng video/audio.
- [ ] Responsive tốt trên desktop/tablet/mobile.
- [ ] Không ảnh hưởng các block Tin tức hiện có.
- [ ] Không hard-code media.
- [ ] Không tạo duplicate content hoặc duplicate query không cần thiết.

---

## 12. Yêu cầu khi thực thi

Claude cần:

1. Kiểm tra cấu trúc Content Type `Tin tức` hiện tại.
2. Kiểm tra taxonomy/danh mục hiện tại trước khi tạo mới.
3. Tái sử dụng component slider/modal hiện có nếu phù hợp.
4. Tái sử dụng style của block `Tin nổi bật` để giao diện đồng nhất.
5. Không thay đổi cấu trúc hoặc chức năng cũ ngoài phạm vi yêu cầu.
6. Không hard-code node ID, taxonomy ID hoặc URL domain.
7. Sau khi code xong cần test:
   - 1 bài có nhiều ảnh.
   - 1 bài có nhiều YouTube URL.
   - 1 bài có nhiều video upload.
   - Mobile.
   - Tablet.
   - Desktop.
