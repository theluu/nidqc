# AGENTS.md — Quy tắc bắt buộc cho AI agent

> File này là **luật**. Mọi agent (Claude Code, Copilot, Cursor…) phải đọc file này
> trước khi chạm vào bất kỳ dòng code nào. Khi tài liệu khác mâu thuẫn với file này,
> **file này thắng**.

---

## 1. Dự án là gì

Website **Viện Kiểm nghiệm thuốc Trung ương (NIDQC)** — `nidqc.gov.vn`.
Site cơ quan nhà nước, nặng nội dung, **SEO là yêu cầu bắt buộc**, tiếng Việt là ngôn ngữ chính.

| Thành phần | Công nghệ | Vị trí |
|---|---|---|
| Backend + render | Drupal 11.4.3, PHP 8.3 | `web/` (giữ nguyên tại root) |
| Frontend tương tác | Vue 3 + Vite (islands) | `frontend/` |
| Môi trường dev | DDEV (nginx-fpm, MariaDB 11.8) | `.ddev/` |
| Design gốc | 12 file HTML bundled | `design/` |

**Kiến trúc: Drupal theme render HTML (Twig) + Vue mount vào các "island" tương tác.**
KHÔNG phải headless. KHÔNG phải SPA. Xem `docs/decisions/ADR-001-frontend-architecture.md`.

---

## 2. Luật tuyệt đối — vi phạm là hỏng việc

AI **KHÔNG ĐƯỢC**, trong mọi trường hợp, nếu không có task cho phép rõ ràng bằng văn bản:

1. **Refactor tự phát.** Không đổi tên, không "dọn dẹp", không tối ưu code ngoài phạm vi task.
2. **Đổi schema.** Không thêm/sửa/xoá content type, field, taxonomy, view mode.
   Schema chỉ đổi qua task có `schema_change: true` và đã được người duyệt.
3. **Cài package.** Không `composer require`, không `npm install`, không thêm module/library.
   Đề xuất package → ghi vào task, chờ duyệt.
4. **Sửa ngoài `allowed_files`.** Task định nghĩa `allowed_files`. Ra ngoài danh sách = dừng lại và hỏi.
5. **Đụng vào `web/core/`, `vendor/`, `web/modules/contrib/`, `node_modules/`.** Đây là code của người khác.
6. **Sửa `design/`.** Design là nguồn chân lý chỉ-đọc.
7. **Chạy migration/update trên production.** Không `drush updb`, `drush cim` trên prod.
8. **Commit secrets.** Không `settings.local.php`, không `.env`, không key, không password.
9. **Xoá hoặc ghi đè file mình chưa đọc.**
10. **Tự ý đổi kiến trúc** đã chốt (islands → SPA, thêm Nuxt, đổi state management…).

### Khi bị chặn
Dừng. Ghi rõ vào task: *cần gì, tại sao, đề xuất phương án*. **Hỏi người.** Không tự quyết.

---

## 3. Quy trình 8 bước — không nhảy cóc

```
Design → Phân tích component → API contract → Drupal Backend
      → Vue Frontend → Security Review → Test → UAT → Deploy
```

| # | Bước | Đầu ra bắt buộc | Tài liệu chuẩn |
|---|---|---|---|
| 1 | Design | Đối chiếu `design/*.html` | `docs/design/PAGE_MAPPING.md` |
| 2 | Phân tích component | Danh sách component + island | `docs/design/DESIGN_SYSTEM.md` |
| 3 | API contract | Endpoint + schema **chốt trước khi code** | `docs/api/API_CONTRACT.md` |
| 4 | Drupal Backend | Module/theme + test | `docs/architecture/BACKEND_ARCHITECTURE.md` |
| 5 | Vue Frontend | Island + test | `docs/architecture/FRONTEND_ARCHITECTURE.md` |
| 6 | Security Review | Checklist ký tên | `docs/security/SECURITY_CHECKLIST.md` |
| 7 | Test | Unit + functional pass | `docs/testing/TEST_STRATEGY.md` |
| 8 | UAT → Deploy | UAT ký + deploy | `docs/testing/UAT_CHECKLIST.md` |

**API contract phải chốt trước khi viết code backend hoặc frontend.** Đây là điểm gãy hay gặp nhất.

---

## 4. Agent được đọc gì

Mỗi task chỉ cho agent đọc và sửa trong phạm vi đã định nghĩa:

**Luôn đọc (bắt buộc):**
- `AGENTS.md` (file này)
- `docs/PROJECT_CONTEXT.md`
- `docs/DEFINITION_OF_DONE.md`
- `tasks/TASK-xxx.md` — task đang làm

**Đọc theo bước đang ở:**
- `docs/architecture/` — khi code
- `docs/standards/` — khi code
- `docs/security/` — luôn luôn khi có input người dùng
- `docs/api/API_CONTRACT.md` — khi đụng API

**Chỉ đọc, không sửa:** `design/`, `web/core/`, `vendor/`, `web/modules/contrib/`

---

## 5. Ranh giới thư mục

| Đường dẫn | Quyền | Ghi chú |
|---|---|---|
| `web/modules/custom/` | ✅ sửa | Code module của dự án |
| `web/themes/custom/` | ✅ sửa | Theme của dự án |
| `frontend/src/` | ✅ sửa | Vue islands |
| `config/sync/` | ⚠️ chỉ khi task cho phép | Config export — đụng vào là đổi schema |
| `docs/`, `tasks/` | ✅ sửa | Tài liệu |
| `design/` | 🔒 chỉ đọc | Nguồn chân lý |
| `web/core/`, `vendor/`, `web/modules/contrib/`, `node_modules/` | ⛔ cấm | Code bên thứ ba |
| `web/sites/default/settings.local.php`, `.env` | ⛔ cấm | Secrets |

---

## 6. Bốn quy tắc kỹ thuật không thương lượng

1. **SEO.** Nội dung phải render server-side qua Twig. Vue **chỉ** cho phần tương tác.
   Nội dung mà Google cần đọc thì không được nằm sau JavaScript.
2. **Security.** Mọi output escape. Mọi input validate. Không `|raw` trong Twig nếu chưa sanitize.
   Xem `docs/security/SECURITY_POLICY.md`.
3. **Accessibility.** Site nhà nước. Tối thiểu WCAG 2.1 AA.
4. **Tiếng Việt.** Font `Be Vietnam Pro`. UTF-8. Dấu tiếng Việt phải đúng ở mọi nơi.

---

## 7. Giao tiếp

- Báo cáo trung thực. Test fail thì nói fail **kèm output**. Bước bị bỏ qua thì nói rõ.
- Không nói "xong" khi chưa chạy lệnh verify và chưa nhìn thấy kết quả.
- Không phỏng đoán. Không biết thì đọc code hoặc hỏi.

---

## 8. Tài liệu liên quan

| File | Nội dung |
|---|---|
| `docs/PROJECT_CONTEXT.md` | Bối cảnh, phạm vi, các bên liên quan |
| `docs/DEFINITION_OF_DONE.md` | Thế nào là xong |
| `docs/design/DESIGN_SYSTEM.md` | Token màu, font, spacing lấy từ design thật |
| `docs/design/PAGE_MAPPING.md` | 12 trang design → route Drupal |
| `docs/api/API_CONTRACT.md` | Hợp đồng API |
| `docs/database/ENTITY_MAPPING.md` | Design → content type Drupal |
| `docs/security/SECURITY_CHECKLIST.md` | Checklist review bảo mật |
| `tasks/TASK_TEMPLATE.md` | Mẫu task |
| `prompts/IMPLEMENT_TASK.md` | Prompt chuẩn giao task cho AI |
