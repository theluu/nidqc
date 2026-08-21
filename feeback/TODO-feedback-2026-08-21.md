# Việc cần làm từ feedback khách hàng

> **Nguồn:** `feeback/feedback.docm` — bản **16:17 ngày 21/08/2026** (sha256 `cc39047e…`)
> **Deadline: 23/08/2026**
> Đối chiếu thêm: `gop y web 9.8.26.docm`, `Feedback NIDQC.pdf`
> Ký hiệu: **[FE]** code Nuxt · **[BE]** Drupal/config · **[ND]** nhập liệu trong admin · **[SV]** sửa trên server
> ⚠️ = câu chữ chưa rõ, cần hỏi lại khách

## ⚠️ Lưu ý: file feedback đã được khách sửa lúc 16:17

So với bản 15:37, khách **xoá hẳn** yêu cầu nặng nhất:

> ~~"Khối tin tức: Phần thông báo hiện tại em cấu trúc lại như [ảnh nifc.gov.vn] · Chuyển 'tin tổng hợp' thành Thông báo · 'Kỹ thuật chuyên môn' thành 'tin hoạt động' · Mấy mục video, tạp chí, liên kết web bố cục như trang trên"~~

⇒ **KHÔNG phải** dựng lại khối tin tức 3 cột theo mẫu nifc, **KHÔNG phải** làm khối "Tạp chí" mới. Đây là phần đáng lo nhất cho deadline — nay đã bỏ. Toàn bộ phần còn lại nằm gọn trong 2 ngày.
*(Đã kiểm tra bằng hash từng ảnh: chỉ ảnh mockup nifc bị xoá, 13 ảnh còn lại giữ nguyên, chỉ đổi số thứ tự. Diff toàn văn không còn khác biệt nào khác.)*

---

## P0 — Bắt buộc xong trước 23/08

### 1. [FE] Tin nổi bật: tiêu đề xuống DƯỚI ảnh
> *"Phần tiêu đề đặt dưới hình ảnh, không chồng nên hình ảnh"*

- Hiện `components/FeaturedHero.vue` để `.hero__overlay` `position:absolute` đè lên ảnh (gradient + chữ trắng).
- Cần: ảnh tỉ lệ cố định (16:9) ở trên → khối chữ (badge "Tin nổi bật" + tiêu đề + mô tả + ngày) là block nền trắng nằm dưới.
- Lưu ý: hero nằm cùng hàng grid với cột tab (`align-items:stretch`) → phải clamp tiêu đề 2 dòng + mô tả 2 dòng để không giật chiều cao khi đổi slide.
- File: `frontend/components/FeaturedHero.vue`

### 2. [FE] Tab chuyên mục: rút gọn nhãn + cho xuống 2 hàng
> *"Mục 'mua sắm, đấu thầu' nếu dài quá"* (câu bị bỏ lửng) — kèm mockup của khách xếp tab thành **2 hàng**:
> `[Thông báo] [Tin hoạt động]` / `[Mua sắm, đấu thầu] [Đào tạo]`

- Hiện tại: 1 hàng `overflow-x:auto` + `white-space:nowrap` ⇒ nhãn dài bị cắt thành `Mua sắm, đấu thầu & cô…` (thấy rõ trong ảnh khách chụp).
- Cần **2 việc**:
  - Đổi nhãn `Mua sắm, đấu thầu & công khai minh bạch` → **`Mua sắm, đấu thầu`** (slug `mua-sam-dau-thau` giữ nguyên, không vỡ link).
  - Bỏ cuộn ngang, cho dải tab `flex-wrap: wrap` xuống nhiều hàng như mockup.
- File: `frontend/composables/useMainNav.ts`, `frontend/components/NewsTabs.vue`

### 3. [ND] Bổ sung tab "Đào tạo"
> *"Bổ sung 'Đào tạo'"*

- **Code đã có sẵn** tab `Đào tạo` (`/tin-tuc?cat=dao-tao`), nhưng `NewsTabs.vue` **tự ẩn tab rỗng**.
- ⇒ Vào Drupal kiểm tra taxonomy `news_category` đã có term **"Đào tạo"** chưa và đã có bài gắn term đó chưa. Chưa có thì tạo term + gắn ít nhất 1 bài.

### 4. [ND] Bổ sung nội dung cho khối Thông báo
> *"Bổ sung nội dung phần thông báo, như trao đổi mấy buổi trước"*

- Mockup của khách cho thấy mỗi tab có **5 tin** — code đã `limit: 5`, nên đây là **việc nhập liệu**, không phải code.
- ⇒ Rà từng tab, tab nào chưa đủ 5 bài thì đăng bổ sung (đặc biệt `Đào tạo`, `Hội nghị - Hội thảo`, `Tuyển dụng`).

### 5. [FE] "Tin mua sắm" → "Mua sắm, đấu thầu, công khai minh bạch"
> *"Tin mua sắm → Mua sắm, đấu thầu, công khai minh bạch"*

- Đổi tiêu đề cột ở section "Tin thông báo | Tin mua sắm" trên trang chủ.
- ⚠️ Lưu ý mâu thuẫn nhỏ với mục 2: **nhãn tab** rút ngắn, nhưng **tiêu đề cột** thì viết dài đủ. Đúng như khách ghi — giữ khác nhau ở 2 chỗ.
- File: `frontend/pages/index.vue`

### 6. [FE] Tin trong cột đổi sang bố cục list theo mẫu
> *"Các tin tức vào trong dạng list"* + ảnh mẫu + *"Bố cục ảnh và nội dung cho vừa nhau"*

- Mẫu khách gửi: **thumbnail bên trái (~16:10) — bên phải: tiêu đề đậm, ngày, trích dẫn 2 dòng**.
- Hiện `NewsColumn.vue` chỉ có ảnh nhỏ + tiêu đề + ngày, **thiếu phần trích dẫn**, tỉ lệ ảnh/chữ chưa cân.
- Cần: thêm `summary` (API đã trả sẵn `n.summary`), chỉnh lại kích thước thumbnail cho khớp chiều cao khối chữ.
- File: `frontend/components/NewsColumn.vue`

### 7. [FE] Trang /tin-tuc: bỏ lưới 4 cột, đổi sang list dọc
> *"Chuyển thành bố cục dạng list từ trên xuống dưới"* (ảnh chụp trang /tin-tuc)

- Hiện `NewsGrid.vue` là `grid-template-columns: repeat(4, 1fr)` — khách muốn **1 tin/dòng, từ trên xuống**, cùng khuôn với mục 6.
- Ảnh hưởng cả `/tim-kiem` (dùng chung `NewsGrid`) → đổi cả hai cho đồng bộ.
- File: `frontend/components/NewsGrid.vue`, `frontend/components/NewsCard.vue`

### 8. [FE] Sơ đồ menu cuối trang: chỉ hiện tên mục cấp 1
> *"Chỉ cần tên tab thôi, không cần chi tiết như vầy"*

- Hiện liệt kê đầy đủ mục con của 7 nhóm menu → chỉ còn hàng tên nhóm, bỏ hết link con.
- File: `frontend/pages/index.vue` (section `nidqc-sitemap`)

### 9. [FE] Bỏ nút "Đăng nhập" khỏi header
> *"Có cách đăng nhập khác không. Không cần hiển trị trên trang web. Vì đã tách trang bán chuẩn sang một trang mới"*

- Xoá link `/user/login` ở top bar.
- Biên tập viên đăng nhập bằng URL trực tiếp `https://<domain>/user/login` → **soạn 1 dòng hướng dẫn gửi khách** kèm tài khoản test (`Tai-khoan-test-NIDQC-DEV.pdf` đã có sẵn ở root).
- File: `frontend/layouts/default.vue` (`.nidqc-topbar__login`)

### 10. [BE][SV] Sửa lỗi không upload được ảnh
> Ảnh lỗi *"Không tải được tệp lên"* — `2 MB limit`, `Allowed types: png gif jpg jpeg webp`
> *"Việc chèn ảnh: có những định dạng nào, để những định dạng quen thuộc"*

Hai nguyên nhân, phải xử lý cả hai:

- **Dung lượng:** `field.field.node.news.field_image.yml` có `max_filesize: ''` ⇒ ăn theo PHP; server đang để `upload_max_filesize = 2M`.
  - [SV] Nâng `upload_max_filesize` + `post_max_size` (đề xuất **16M / 20M**) trên 45.118.145.203, restart php-fpm.
  - [BE] Set `max_filesize: '10 MB'` + `max_resolution: '2560x2560'` (Drupal tự resize ảnh máy ảnh cỡ lớn thay vì báo lỗi).
- **Định dạng:** bổ sung đuôi quen thuộc — `jfif`, `avif`, `bmp`, `tif`, `tiff`.
- Áp dụng cho **cả 3 field**: `field_image`, `field_gallery_images`, `field_attachments`.
- File: `config/sync/field.field.node.news.field_*.yml` → `drush cim`

### 11. [BE] Giải thích ý nghĩa mục "Nhãn"
> *"Ý nghĩa của mục nhãn"*

- `field_tag` (label "Nhãn") **để trống description** ⇒ biên tập viên không biết dùng làm gì.
- Cần: thêm mô tả, VD *"Chữ hiển thị trên góc ảnh của thẻ tin ở trang danh sách (VD: TIN HOẠT ĐỘNG). Để trống thì tự lấy tên chuyên mục."*
- Hoặc gỡ khỏi form đăng bài nếu thực tế không dùng → ⚠️ hỏi khách.
- File: `config/sync/field.field.node.news.field_tag.yml`

---

## P1 — Nội dung + tinh chỉnh CSS

### 12. [ND] Danh mục năng lực: đổi tên, viết HOA, bổ sung mục thứ 6

| # | Tên khách chốt | Hiện tại |
|---|---|---|
| 1 | NĂNG LỰC KIỂM NGHIỆM | Kiểm nghiệm |
| 2 | NĂNG LỰC HIỆU CHUẨN | Hiệu chuẩn |
| 3 | DANH MỤC CÁC KHÓA ĐÀO TẠO VÀ TƯ VẤN | Danh mục các khoá đào tạo & tư vấn |
| 4 | NĂNG LỰC ĐÁNH GIÁ TƯƠNG ĐƯƠNG SINH HỌC | Đánh giá tương đương sinh học (TĐSH) |
| 5 | DANH MỤC CÁC CHƯƠNG TRÌNH THỬ NGHIỆM THÀNH THẠO | Các chương trình TNTH |
| 6 | **DANH MỤC CHỨNG NHẬN HỆ THỐNG QUẢN LÝ** | *(chưa có — tạo mới)* |

- Sửa node content type `capability` trong Drupal admin. Không cần code.

### 13. [FE] Viết HOA nhãn thẻ khối "Dịch vụ" và "Hoạt động chuyên môn"
> *"Các chữ để viết hoa"* (mục Dịch vụ) · *"Các chữ viết hoa"* (mục Hoạt động chuyên môn)

- Làm bằng CSS `text-transform: uppercase` trên `.nidqc-tile__label` + `.nidqc-capability__label` — **không sửa nội dung node**, để menu/breadcrumb giữ chữ thường đúng chính tả.
- File: `frontend/pages/index.vue`

### 14. [ND][FE] Bỏ "Tạp chí Kiểm nghiệm Dược và Mỹ phẩm" khỏi Hoạt động chuyên môn
- [ND] Unpublish/xoá node `expertise` tương ứng (khối trang chủ + submenu lấy động từ đây nên tự mất cả hai chỗ).
- [FE] Xoá dòng dự phòng ở `frontend/composables/useMainNav.ts:40` (dùng khi API lỗi).

### 15. [FE] Khối "Liên kết nổi bật" (Nhà tài trợ): co lại cho khớp cột bên trái
> *"Chỉnh sao cho chiều cao phù hợp với 'Hoạt động chuyên môn'"* · *"Co lại, bố trí lại cho phù hợp với hoạt động chuyên môn"*

- Giới hạn chiều cao bằng cột trái, item thu nhỏ (logo + 1 dòng tiêu đề), tràn thì cuộn trong khối.
- File: `frontend/pages/index.vue` (`.nidqc-highlight-list`)

### 16. [FE] Section "Thư viện video · Liên kết web · Thống kê truy cập": cân chiều cao 3 cột
> *"Bố trí chiều cao các mục cho hợp lý"*

- Ảnh khách gửi: cột "Thống kê truy cập" ngắn hơn hẳn, chừa mảng trắng lớn bên dưới.
- Cần 3 cột cùng chiều cao (khung nền cho cột thống kê) hoặc giảm chiều cao khối video.
- File: `frontend/pages/index.vue` (`nidqc-three-col`), `frontend/components/MediaLibrary.vue`

### 17. [ND] Rà lại Footer theo bảng góp ý lần trước
> *"Footer: như góp ý lần trước"*

Footer **đã đúng cấu trúc 3 cột**. Chỉ cần kiểm tra dữ liệu đã nhập đủ:
- [ ] Cột 1: Cơ sở chính + Cơ sở 2, mỗi cơ sở có link **Bản đồ**
- [ ] Cột 1: Tel (ghi chú "giờ hành chính") · Fax · Email → `/admin/config/nidqc/settings`
- [ ] Cột 2: đủ **4 đầu mối** (Kiểm nghiệm · Đào tạo · Hiệu chuẩn thiết bị · Cung ứng chất chuẩn) + email/hotline
- [ ] Cột 3: đủ **4 mạng xã hội** YouTube · Facebook · Zalo · TikTok
- [ ] Bản quyền đang ghi **© 2026** (bảng Word ghi 2019 — giữ 2026)

---

## Cần chốt với khách (không tự quyết được)

1. **⚠️ Số lượng tab** — mockup của khách chỉ vẽ **4 tab** (Thông báo · Tin hoạt động · Mua sắm đấu thầu · Đào tạo), site hiện có **6** (thêm *Hội nghị - Hội thảo*, *Tuyển dụng*). Giữ 6 hay rút còn 4?
2. **⚠️ Chức năng "Tin mới"** — *"Chỉ có chức năng tin nổi bật, có chức năng tin mới không"*. Hiện chỉ có checkbox **"Tin nổi bật"**; thanh chạy "Tin mới" dưới menu **tự lấy 5 bài mới nhất**, biên tập viên không chọn được. Giữ tự động, hay thêm checkbox "Tin mới" để tự chọn? *(nếu thêm: `field_ticker` boolean + sửa query ticker, ~2h)*
3. **⚠️ "Tra cứu chất chuẩn" / "cung ứng chất chuẩn"** — khách ghi đúng 1 dòng, không nói làm gì. Đoán là **đổi tên nút** thành "Cung ứng chất chuẩn" cho khớp menu Dịch vụ và footer. Nút này là lối vào trang bán chuẩn nên không đổi khi chưa xác nhận.
4. **Mục "Nhãn"** (xem việc 11) — viết mô tả hướng dẫn, hay gỡ hẳn khỏi form đăng bài?

---

## Đã đúng — không cần sửa (đã kiểm tra code)

- ✅ **"Xem tất cả" theo tab đang chọn** — `NewsTabs.vue` render link riêng cho từng panel, trỏ đúng `tab.to`. Đúng yêu cầu *"đang ở mục Thông báo thì xem tất cả là xem tất cả nội dung của mục Thông báo"*.
- ✅ Chỉ 2 cờ Vi/En trên header (đã bỏ dropdown ~100 ngôn ngữ) — PDF mục 1.
- ✅ Icon tìm kiếm nằm trên main menu — PDF mục 3.
- ✅ Footer 3 cột đúng bố cục bảng Word (chỉ rà dữ liệu, xem việc 17).

---

## ✅ TRẠNG THÁI 21/08 — đã làm và đã kiểm chứng trên https://nidqc.ddev.site

| # | Việc | Trạng thái |
|---|---|---|
| 1 | Tin nổi bật: tiêu đề xuống dưới ảnh | ✅ xong |
| 2 | Tab rút gọn nhãn + xuống nhiều hàng | ✅ xong |
| 3 | Bổ sung tab "Đào tạo" | ✅ tab đã hiện (code sẵn có) — còn phải kiểm tra trên PROD |
| 4 | Bổ sung nội dung khối Thông báo | ⏳ [ND] nhập liệu trong admin |
| 5 | Đổi tên cột "Mua sắm, đấu thầu, công khai minh bạch" | ✅ xong |
| 6 | Cột tin dạng list có trích dẫn | ✅ xong |
| 7 | /tin-tuc + /tim-kiem đổi sang list dọc | ✅ xong |
| 8 | Sơ đồ menu chỉ tên mục cấp 1 | ✅ xong |
| 9 | Bỏ nút Đăng nhập khỏi header | ✅ xong |
| 10 | Upload ảnh: 10 MB + tự thu nhỏ 2560px | ✅ config xong · ⚠️ **PROD phải nâng php-fpm** |
| 11 | Mô tả mục "Nhãn" | ✅ xong |
| 12 | Danh mục năng lực đổi tên + mục thứ 6 | ⏳ [ND] nhập liệu trong admin |
| 13 | Viết HOA nhãn thẻ Dịch vụ / HĐCM / Năng lực | ✅ xong |
| 14 | Bỏ "Tạp chí Kiểm nghiệm Dược và Mỹ phẩm" | ✅ xong (dev) — cần gỡ lại trên PROD |
| 15 | "Liên kết nổi bật" co lại bằng cột trái | ✅ xong — hai cột đo được đúng 518px |
| 16 | Cân chiều cao 3 cột Video/Liên kết/Thống kê | ✅ xong |
| 17 | Rà dữ liệu footer | ⏳ [ND] kiểm tra trong admin |

**Ghi nhận thêm (chưa sửa, ngoài phạm vi feedback):** ảnh các slide tin nổi bật từ
slide thứ 2 trở đi đặt `loading="lazy"`, khi carousel tự chuyển có thể loé một
khoảng xanh trước khi ảnh kịp tải. Muốn hết hẳn thì bỏ lazy cho slide 2 (đánh đổi
một chút tốc độ tải trang đầu).

---

## Thứ tự làm (2 ngày)

**22/08 sáng:** việc 9, 2, 5, 8 (sửa nhanh ~1h) → việc 1 (FeaturedHero) → việc 6 + 7 (dùng chung 1 khuôn thẻ list).
**22/08 chiều:** việc 10 + 11 (upload + nhãn — cần deploy config) → việc 13, 15, 16 (CSS).
**23/08 sáng:** việc 3, 4, 12, 14, 17 (nhập liệu admin) → build `.output` + restart daemon `nuxt-ssr` → deploy.
**23/08 chiều:** rà lại trên bản deploy, gửi khách kèm 4 câu hỏi ở trên.

> Deploy: build `.output` rồi restart daemon `nuxt-ssr` — **không** `ddev restart` cả project.
