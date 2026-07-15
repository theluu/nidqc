# Prompt chuẩn — Giao task cho AI

> Copy khối dưới, thay `TASK-xxx`, dán vào agent. **Không giao task bằng lời nói suông.**

---

```
Đọc theo đúng thứ tự này TRƯỚC KHI làm bất cứ việc gì:

1. AGENTS.md                      — luật. Đọc kỹ §2 (luật tuyệt đối) và §5 (ranh giới thư mục).
2. docs/PROJECT_CONTEXT.md        — bối cảnh và phạm vi
3. docs/DEFINITION_OF_DONE.md     — thế nào là xong
4. tasks/TASK-xxx.md              — task của bạn

Sau đó đọc tài liệu tương ứng với `step` ghi trong task:
- step 2 (component): docs/design/DESIGN_SYSTEM.md
- step 3 (API):       docs/api/API_CONTRACT.md + docs/api/API_ERROR_STANDARD.md
- step 4 (backend):   docs/architecture/BACKEND_ARCHITECTURE.md + docs/standards/DRUPAL_CODING_STANDARD.md
- step 5 (frontend):  docs/architecture/FRONTEND_ARCHITECTURE.md + docs/standards/VUE_CODING_STANDARD.md
- luôn luôn:          docs/security/SECURITY_POLICY.md

RÀNG BUỘC — vi phạm là hỏng việc:

- CHỈ sửa file trong `allowed_files` của task. Cần sửa chỗ khác → DỪNG, ghi vào §9, hỏi tôi.
- KHÔNG refactor code ngoài phạm vi. Kể cả khi thấy code xấu.
- KHÔNG đổi schema (content type, field, taxonomy) trừ khi task có `schema_change: true`.
- KHÔNG cài package (composer/npm) trừ khi task có `new_package: true`.
- KHÔNG sửa: web/core/, vendor/, web/modules/contrib/, node_modules/, design/
- KHÔNG commit secrets.
- KHÔNG đổi kiến trúc đã chốt (Drupal theme + Vue islands — xem ADR-001).

CÁCH LÀM:

1. Đọc tài liệu ở trên.
2. Đọc design gốc nếu task cần:
       python3 scripts/extract-design.py "design/TÊN FILE.html" -o /tmp/design-out
   (design/*.html là HTML tự giải nén — mở trực tiếp sẽ không đọc được markup)
3. Nói cho tôi kế hoạch NGẮN GỌN trước khi code. Chờ tôi ok.
4. Code — chỉ trong allowed_files.
5. CHẠY THẬT lệnh verify ở §6 của task. Dán output vào.
6. Chạy docs/security/SECURITY_CHECKLIST.md.
7. Đối chiếu docs/DEFINITION_OF_DONE.md.

BÁO CÁO:

- Nói SỰ THẬT. Test fail thì nói fail, dán output. Bước nào bỏ qua thì nói rõ.
- KHÔNG nói "xong" khi chưa chạy lệnh verify và chưa NHÌN THẤY kết quả.
- Không chắc → hỏi. Không đoán.

Task: tasks/TASK-xxx.md
```

---

## Ghi chú cho người giao task

**Trước khi giao, kiểm tra task đã có:**
- [ ] `allowed_files` cụ thể (không phải `web/**`)
- [ ] Tiêu chí chấp nhận kiểm chứng được
- [ ] Lệnh verify chạy được thật
- [ ] Ba cờ `schema_change` / `new_package` / `config_change` đúng
- [ ] `reviewer` khác `owner`

**Dấu hiệu task chưa sẵn sàng:**
- `allowed_files` quá rộng → agent sẽ đi lạc
- Tiêu chí kiểu "hoạt động tốt", "đẹp như design" → không verify được
- Task gộp nhiều bước (vừa API vừa backend vừa frontend) → tách ra

**Nếu agent nói "xong" mà không có output verify:** không tin. Yêu cầu chạy lại và dán output.
