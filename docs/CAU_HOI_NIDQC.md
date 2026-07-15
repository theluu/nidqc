# Câu hỏi cần NIDQC trả lời

> Soạn 2026-07-16. **Đây là việc chặn nhiều nhất trong dự án** — 9 task không thể bắt đầu
> cho tới khi có câu trả lời. Xem `ROADMAP.md` §3.
>
> Dùng file này để gửi NIDQC (email/họp). Điền câu trả lời vào đây rồi cập nhật
> `PROJECT_CONTEXT.md` §5 và `database/ENTITY_MAPPING.md`.

---

## Vì sao phải hỏi thay vì tự quyết

Bộ design 12 file cho biết website **trông** thế nào. Nó **không** cho biết dữ liệu **là** gì.

Ví dụ cụ thể — toàn bộ trang "Văn bản tài liệu" trong design chỉ chứa:

```js
{ title: 'Thông tư quy định về kiểm nghiệm thuốc, nguyên liệu làm thuốc',
  meta:  'Bộ Y tế · Thông tư' }
```

Hai trường. `meta` là chuỗi hiển thị ghép sẵn. **Không có** số hiệu văn bản, **không có** ngày
ban hành, **không có** link tải file — dù một hệ thống văn bản thật chắc chắn cần cả ba.

Nếu chúng tôi tự suy ra cấu trúc dữ liệu, rồi sau này Viện nhập dữ liệu thật vào và phát hiện
sai, thì phải **chuyển đổi lại toàn bộ dữ liệu** — tốn kém gấp nhiều lần so với hỏi bây giờ.

---

## 🔴 Câu 1 — Tra cứu chất chuẩn (quan trọng nhất)

**Bối cảnh:** Trong design, mục "Tra cứu chất chuẩn" được liên kết tới **20 lần** — nhiều nhất
trong toàn bộ website, ngang với liên kết về Trang chủ. Có một nút tròn riêng trên thanh menu
chính chỉ để vào mục này.

**Nhưng bộ design không có file nào cho trang này.** Có 2 liên kết trỏ tới
`nidqc.gov.vn/tim-kiem-chat-chuan` — một địa chỉ nằm ngoài bộ design.

**Câu hỏi:**

1. Chức năng tra cứu chất chuẩn có nằm trong phạm vi dự án này không?
2. Nếu **có**: chúng tôi đang thiếu design cho trang đó. Viện có bản thiết kế không, hay cần
   thiết kế mới?
3. Dữ liệu chất chuẩn gồm những thông tin gì? (ví dụ: tên chất, mã số, lô, hạn dùng, nồng độ,
   nhà sản xuất, tình trạng còn/hết…)
4. Dữ liệu đó hiện đang được quản lý ở đâu? (Excel? Phần mềm riêng? Website cũ?)
5. Người dùng tra cứu là ai — công chúng, hay chỉ đơn vị đã đăng ký?
6. Số lượng bản ghi ước tính?

**Vì sao quan trọng:** đây có thể là phần dữ liệu lớn nhất và là nghiệp vụ lõi của Viện.
Chúng tôi không thể đoán cấu trúc dữ liệu chất chuẩn thuốc.

---

## 🔴 Câu 2 — Cấu trúc dữ liệu của từng loại nội dung

Với **mỗi** loại dưới đây, xin cho biết: cần lưu **những thông tin gì**, và thông tin nào **bắt buộc**.

### 2.1. Văn bản tài liệu
- Có file đính kèm để người dùng tải về không? Định dạng gì (PDF/Word/Excel)?
- Có số hiệu văn bản không? (ví dụ `15/2024/TT-BYT`)
- Có ngày ban hành / ngày hiệu lực không?
- Design hiển thị `"Bộ Y tế · Thông tư"` — đây là **hai** thông tin riêng (cơ quan ban hành +
  loại văn bản) hay một?
- Danh sách **đầy đủ** các loại văn bản? (design mới thấy: Thông tư, Quyết định, Nghị định,
  Quy chế, SOP, Hướng dẫn, Sổ tay, Quy trình)
- Danh sách **đầy đủ** các nhóm/tab? (design mới thấy: "Pháp quy", "Chuyên môn")

### 2.2. Tin tức
- Có ảnh đại diện không? Có bắt buộc không?
- Có phân loại tin không? Nếu có, danh sách đầy đủ các mục?
- Có tác giả / nguồn tin không?
- Có tin nổi bật / ghim lên đầu không?

### 2.3. Câu hỏi thường gặp (FAQ)
- Có nhóm câu hỏi theo chủ đề không, hay một danh sách phẳng?

### 2.4. Cơ cấu tổ chức
- Có bao nhiêu cấp? (Viện → Phòng/Khoa → Tổ?)
- Mỗi đơn vị cần lưu gì? (tên, chức năng, trưởng đơn vị, điện thoại, email…)
- Có hiển thị thông tin cá nhân cán bộ không? Nếu có, **những ai đồng ý công khai?**

### 2.5. Năng lực (trang thiết bị, chứng nhận)
- Thiết bị cần lưu gì? (tên, hãng, model, năm, ảnh…)
- Chứng nhận cần lưu gì? (tên, số hiệu, ngày cấp, ngày hết hạn, file scan…)

### 2.6. Đào tạo & Nghiên cứu khoa học
- Đề tài NCKH cần lưu gì? (tên, chủ nhiệm, cấp, năm, tình trạng…)
- Thông tin đào tạo tiến sĩ cần lưu gì?

---

## 🟡 Câu 3 — Menu và cấu trúc trang

Trong design, hai mục **"Tra cứu chất chuẩn"** và **"Dịch vụ"** là *neo* (anchor) trên trang chủ,
tức là bấm vào sẽ cuộn xuống một phần của trang chủ chứ không sang trang mới. Nhưng chúng lại
được liên kết tới **20 lần** và **10 lần** — nhiều như một mục chính.

**Câu hỏi:** hai mục này nên là **phần của trang chủ**, hay **trang riêng**?

**Vì sao hỏi:** ảnh hưởng tới cấu trúc menu và khả năng tìm thấy trên Google. Trang riêng thì
Google index được và chia sẻ link được; neo trên trang chủ thì không.

---

## 🟡 Câu 4 — Tiếng Anh

Design có nút chuyển **Tiếng Việt / English** ở thanh trên cùng.

**Câu hỏi:**
1. Website có phiên bản tiếng Anh với **nội dung thật** không?
2. Nếu có: **toàn bộ** nội dung hay chỉ một số trang? Ai dịch?
3. Nếu chưa làm ngay: **sau này có làm không?**

**Vì sao quan trọng:** hỗ trợ đa ngôn ngữ phải bật **ngay từ đầu**. Bật sau khi đã có dữ liệu
thì phải chuyển đổi lại toàn bộ nội dung — tốn kém và rủi ro. Nếu "sau này có", chúng tôi sẽ
chuẩn bị cấu trúc ngay bây giờ.

---

## 🟡 Câu 5 — Đăng nhập hệ thống

Design có liên kết **"Đăng nhập hệ thống"** ở thanh trên cùng.

**Câu hỏi:** đăng nhập dành cho ai và để làm gì?
- Chỉ cán bộ Viện vào quản trị nội dung?
- Hay khách hàng/đơn vị gửi mẫu tra cứu kết quả kiểm nghiệm?

**Vì sao hỏi:** nếu là khách hàng tra cứu kết quả thì đó là một hệ thống riêng, phạm vi lớn hơn
nhiều so với một website giới thiệu — cần bàn lại phạm vi và bảo mật.

---

## 🟡 Câu 6 — Dữ liệu từ website hiện tại

**Câu hỏi:**
1. Website mới có cần chuyển toàn bộ nội dung cũ từ `nidqc.gov.vn` sang không?
2. Nếu có: khoảng bao nhiêu tin/văn bản? Có cần giữ nguyên đường dẫn cũ không?
3. Nếu không: nội dung mới do ai nhập, khi nào?

**Vì sao quan trọng:** đường dẫn cũ có thể đã nằm trong văn bản giấy đã phát hành. Nếu đổi
đường dẫn mà không chuyển hướng, các liên kết đó sẽ chết.

---

## 🟡 Câu 7 — Nội dung trong design có chính xác không?

Bộ design chứa nội dung **nghe rất thật**, ví dụ:

> *"Kết quả kiểm nghiệm của Viện có giá trị pháp lý không? — Có. Viện là cơ quan kiểm nghiệm
> thuốc đầu ngành…"*

và danh sách phòng ban cụ thể (Phòng Tổ chức - Hành chính, Khoa Hóa lý - Đo lường…).

**Câu hỏi:** nội dung này do Viện cung cấp, hay do đơn vị thiết kế viết mẫu cho đẹp?

**Vì sao hỏi:** đây là thông tin về giá trị pháp lý của kết quả kiểm nghiệm thuốc và về cơ cấu
tổ chức của một cơ quan nhà nước. Chúng tôi **sẽ không** đưa nội dung này lên website nếu chưa
được Viện xác nhận là chính xác.

---

## Mức ưu tiên

| Câu | Chặn gì | Cần trước khi |
|---|---|---|
| **1** — Chất chuẩn | Phạm vi dự án, có thể là phần lớn nhất | Chốt kế hoạch |
| **2** — Cấu trúc dữ liệu | **9 task** không bắt đầu được | Bắt đầu code nội dung |
| **4** — Tiếng Anh | Cấu trúc toàn bộ dữ liệu | Tạo dữ liệu đầu tiên |
| 3 — Menu/anchor | Cấu trúc menu, SEO | Dựng menu |
| 5 — Đăng nhập | Phạm vi, bảo mật | Chốt kế hoạch |
| 6 — Dữ liệu cũ | Kế hoạch chuyển đổi | Bàn giao |
| 7 — Nội dung | Nội dung hiển thị | Nhập nội dung |

**Câu 1, 2, 4 chặn gần như toàn bộ phần còn lại của dự án.**

---

## Câu trả lời

> Điền vào đây khi có phản hồi, ghi rõ ngày và người trả lời.

| Câu | Trả lời | Ngày | Người |
|---|---|---|---|
| 1 | | | |
| 2 | | | |
| 3 | | | |
| 4 | | | |
| 5 | | | |
| 6 | | | |
| 7 | | | |
