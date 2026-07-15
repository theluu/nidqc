# Prompt chuẩn — Review code

> Dùng để nhờ AI review. ⚠️ **AI review không thay thế người review.**
> `docs/standards/GIT_WORKFLOW.md` §3: PR phải có **người** duyệt.
> AI là lượt quét đầu, bắt lỗi máy móc — không phải người ký duyệt.

---

```
Review thay đổi này. Đọc trước:

1. AGENTS.md                             — luật, ranh giới
2. tasks/TASK-xxx.md                     — task này đáng lẽ làm gì
3. docs/security/SECURITY_POLICY.md
4. docs/standards/DRUPAL_CODING_STANDARD.md  (nếu có PHP)
5. docs/standards/VUE_CODING_STANDARD.md     (nếu có Vue)
6. docs/DEFINITION_OF_DONE.md

Kiểm tra theo thứ tự ưu tiên:

A. PHẠM VI — quan trọng nhất
   - Có file nào NGOÀI `allowed_files` của task không?
   - Có refactor ngoài phạm vi không?
   - Có đổi schema mà task không cho phép (schema_change: false) không?
   - composer.json / package.json có đổi mà task không cho phép không?

B. BẢO MẬT
   - `|raw` mới? `v-html`? Có sanitize + comment giải thích chưa?
   - Entity Query có `->accessCheck(TRUE)` không?
   - Nối chuỗi vào SQL?
   - Route mới có `_permission`/`_access` chưa?
   - Param có validate không? `limit` có trần không?
   - Thông báo lỗi có lộ stack trace / SQL / đường dẫn / phiên bản không?
   - Có secret trong diff không?
   - Upload file: whitelist extension? kiểm MIME thật?

C. KIẾN TRÚC (ADR-001 — Drupal theme + Vue islands)
   - Vue có đang render nội dung mà Google cần đọc không? → SAI KIẾN TRÚC
   - Island có hoạt động khi tắt JS không?
   - Có hard-code hex thay vì dùng var(--nidqc-*) không?
   - Có gọi font/asset từ CDN ngoài không?

D. ĐÚNG ĐẮN
   - Có đạt tiêu chí chấp nhận ở §5 của task không?
   - Cache tag/context có đúng không?
   - Có xử lý loading/error/empty không?
   - Tiếng Việt có đúng dấu không?

E. SÓT LẠI
   - dpm(), var_dump(), console.log(), TODO tạm?

CÁCH BÁO CÁO:

- Nghiêm trọng nhất trước.
- Mỗi vấn đề: file:dòng + vì sao sai + tình huống hỏng cụ thể.
- Phân biệt rõ: LỖI THẬT vs góp ý phong cách. Đừng trộn lẫn.
- KHÔNG bịa vấn đề để tỏ ra hữu ích. Không có vấn đề thì nói không có.
- KHÔNG tự sửa. Chỉ báo cáo.
```

---

## Ghi chú cho người dùng prompt này

**AI review giỏi ở:** quét cơ học (`|raw`, `accessCheck`, hex hard-code, file ngoài phạm vi,
secret sót, `console.log`).

**AI review kém ở:** nội dung có đúng nghiệp vụ không · thiết kế có khớp ý đồ không ·
đánh đổi kiến trúc có hợp lý không · thông tin y tế/dược có chính xác không.

**Đừng tin AI khi nó nói "không có vấn đề gì".** Đó là câu trả lời rẻ nhất.
Vẫn phải có người đọc diff.

**Nếu AI báo một loạt vấn đề nghe rất kêu:** kiểm chứng từng cái. AI hay bịa lỗi để tỏ ra
đang làm việc — xem `superpowers:receiving-code-review`.
