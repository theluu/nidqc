# Definition of Done — NIDQC

> "Xong" không phải là "code chạy trên máy tôi". Task chỉ xong khi **tất cả** mục dưới đây đạt.
> Chưa đủ → trạng thái là `in_progress`, không phải `done`.

---

## 1. Áp dụng cho mọi task

### Phạm vi
- [ ] Chỉ sửa file trong `allowed_files`
- [ ] Không refactor ngoài phạm vi
- [ ] Không đổi schema (trừ khi `schema_change: true`)
- [ ] Không thêm package (trừ khi `new_package: true`)
- [ ] `git diff` đã đọc **toàn bộ** — không có thay đổi ngoài ý muốn

### Yêu cầu
- [ ] Mọi mục §4 (Yêu cầu) của task đạt
- [ ] Mọi mục §5 (Tiêu chí chấp nhận) đạt
- [ ] Câu hỏi mở §9 đã được trả lời hoặc ghi nhận rõ

### Verify — **đã chạy thật, đã nhìn thấy output**
- [ ] Lệnh ở §6 của task đã chạy, output đã dán vào task
- [ ] Không có lỗi mới trong log (`ddev drush watchdog:show --severity=3`)
      — `3` = Error. Dùng **số**, không dùng `--severity=Error`: site chạy langcode `vi`,
      Drush khớp nhãn đã dịch nên dạng chữ sẽ lỗi exit 1 kể cả khi không có lỗi nào.
- [ ] Không còn `dpm()`, `var_dump()`, `console.log()`, `TODO` tạm

> ⚠️ Đây là mục hay bị nói dối nhất. Không chạy thì không được tick.

### Chuẩn code
- [ ] `ddev composer phpcs` sạch (nếu đụng PHP)
- [ ] `npm run lint` sạch (nếu đụng Vue)
- [ ] Đặt tên, comment theo `docs/standards/`

### Bảo mật
- [ ] `docs/security/SECURITY_CHECKLIST.md` đã chạy và **có người khác ký**
- [ ] Không secret trong diff

### Tài liệu
- [ ] Tài liệu liên quan đã cập nhật (contract, mapping, ADR)
- [ ] `CHANGELOG.md` đã thêm dòng
- [ ] Task đã điền §10 Nhật ký

### Review
- [ ] Có người khác review (**không phải owner**, không phải AI)

---

## 2. Riêng cho task frontend/theme

- [ ] Khớp design — đối chiếu `design/*.html` bằng `scripts/extract-design.py`
- [ ] **Không hard-code hex** — chỉ dùng biến từ `DESIGN_SYSTEM.md` §7
- [ ] Font `Be Vietnam Pro`, self-host, **không gọi Google Fonts CDN**
- [ ] Tiếng Việt hiển thị đúng dấu ở mọi trạng thái
- [ ] Responsive: đã kiểm ở 375px, 768px, 1280px
- [ ] **Tắt JS → nội dung và chức năng cốt lõi vẫn dùng được**
- [ ] Accessibility: điều hướng bàn phím được, focus nhìn rõ, tương phản ≥ 4.5:1
- [ ] Không lỗi trong console trình duyệt

## 3. Riêng cho task backend

- [ ] Cache tag/context đúng — sửa nội dung thì trang cập nhật
- [ ] `->accessCheck(TRUE)` ở mọi Entity Query
- [ ] Người dùng ẩn danh không thấy nội dung chưa publish
- [ ] Config đã `ddev drush cex` và commit (nếu `config_change: true`)

## 4. Riêng cho task API

- [ ] Endpoint có trong `docs/api/API_CONTRACT.md` **trước khi** code
- [ ] Lỗi theo `docs/api/API_ERROR_STANDARD.md`
- [ ] Mọi param được validate
- [ ] Đã test: tham số sai, thiếu tham số, `limit` quá lớn, người dùng ẩn danh
- [ ] Có fallback không-JS

## 5. Riêng cho task đổi schema

> `schema_change: true` — mức rủi ro cao nhất.

- [ ] Đã có trong `docs/database/ENTITY_MAPPING.md` **trước khi** cài
- [ ] Đã được người duyệt (không phải AI tự quyết)
- [ ] Machine name theo chuẩn, **sẽ không phải đổi sau**
- [ ] Đã `drush cex`, commit `config/sync/`
- [ ] Đã kiểm import sạch trên môi trường mới: `drush cim`
- [ ] Có kế hoạch nếu đã có dữ liệu thật

---

## 6. Trước khi deploy

- [ ] UAT ký — `docs/testing/UAT_CHECKLIST.md`
- [ ] Đã test trên staging với dữ liệu giống production
- [ ] Có phương án rollback — `docs/deployment/ROLLBACK.md`
- [ ] URL đổi thì đã có redirect (**link cũ nằm trong văn bản giấy, không được chết**)
- [ ] Đã kiểm SEO: metatag, sitemap, alias

---

## 7. Quy tắc trung thực

> Test fail thì **nói fail, kèm output**. Bước bị bỏ thì **nói rõ**.
> Không tick mục chưa kiểm tra thật.
>
> Một task báo "done" sai làm hỏng niềm tin vào **toàn bộ** các task khác.
> Chưa xong thì nói chưa xong — điều đó luôn ổn.
