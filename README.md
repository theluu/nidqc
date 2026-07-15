# NIDQC — Website Viện Kiểm nghiệm thuốc Trung ương

Website `nidqc.gov.vn` — Drupal 11 + Vue 3 (islands).

> 🤖 **AI agent: đọc [`AGENTS.md`](AGENTS.md) trước khi làm bất cứ việc gì.**

---

## Bắt đầu

```bash
ddev start
ddev composer install
ddev drush site:install       # lần đầu
ddev launch
```

Yêu cầu: [DDEV](https://ddev.readthedocs.io/) + Docker.

## Công nghệ

| Lớp | Công nghệ |
|---|---|
| Backend + render | Drupal 11.4.3, PHP 8.3 |
| Frontend tương tác | Vue 3 + Vite |
| DB | MariaDB 11.8 |
| Dev env | DDEV (nginx-fpm) |

**Kiến trúc:** Drupal render HTML qua Twig (giữ SEO), Vue mount vào các "island" tương tác.
Không phải headless, không phải SPA. Xem [ADR-001](docs/decisions/ADR-001-frontend-architecture.md).

## Cấu trúc

```
nidqc/
├── web/            # Drupal (core, modules, themes/custom/nidqc)
├── frontend/       # Vue 3 islands
├── design/         # 🔒 12 file design — nguồn chân lý, chỉ đọc
├── docs/           # tài liệu
├── tasks/          # TASK-xxx.md
├── prompts/        # prompt chuẩn cho AI
├── scripts/        # extract-design.py
├── .ddev/          # môi trường dev
└── AGENTS.md       # luật cho AI
```

## Đọc design

`design/*.html` là HTML **tự giải nén** — mở bằng editor sẽ không thấy markup.

```bash
python3 scripts/extract-design.py "design/NIDQC FAQ.html" -o /tmp/out
python3 scripts/extract-design.py --all --colors      # thống kê màu
python3 scripts/extract-design.py --all --resources   # liệt kê font/ảnh nhúng
```

## Quy trình

```
Design → Phân tích component → API contract → Drupal Backend
      → Vue Frontend → Security Review → Test → UAT → Deploy
```

Mỗi task = một file `tasks/TASK-xxx.md` với `allowed_files` cụ thể.
AI chỉ được sửa trong phạm vi đó.

## Tài liệu

| Bắt đầu từ đây | |
|---|---|
| [`AGENTS.md`](AGENTS.md) | **Luật cho AI** — đọc đầu tiên |
| [`docs/PROJECT_CONTEXT.md`](docs/PROJECT_CONTEXT.md) | Bối cảnh, phạm vi |
| [`docs/DEFINITION_OF_DONE.md`](docs/DEFINITION_OF_DONE.md) | Thế nào là xong |

| Thiết kế | |
|---|---|
| [`docs/design/DESIGN_SYSTEM.md`](docs/design/DESIGN_SYSTEM.md) | Token màu/font (trích từ design thật) |
| [`docs/design/PAGE_MAPPING.md`](docs/design/PAGE_MAPPING.md) | 12 trang → route Drupal |

| Kiến trúc | |
|---|---|
| [`docs/architecture/SYSTEM_ARCHITECTURE.md`](docs/architecture/SYSTEM_ARCHITECTURE.md) | Tổng thể |
| [`docs/architecture/BACKEND_ARCHITECTURE.md`](docs/architecture/BACKEND_ARCHITECTURE.md) | Drupal |
| [`docs/architecture/FRONTEND_ARCHITECTURE.md`](docs/architecture/FRONTEND_ARCHITECTURE.md) | Vue islands |
| [`docs/decisions/`](docs/decisions/) | ADR |

| Chuẩn & bảo mật | |
|---|---|
| [`docs/api/API_CONTRACT.md`](docs/api/API_CONTRACT.md) | Hợp đồng API |
| [`docs/security/SECURITY_POLICY.md`](docs/security/SECURITY_POLICY.md) | Chính sách bảo mật |
| [`docs/standards/`](docs/standards/) | Chuẩn code, git |
| [`docs/testing/`](docs/testing/) | Test, UAT |
| [`docs/deployment/`](docs/deployment/) | Deploy, rollback |

## ⚠️ Trạng thái hiện tại

Dự án **mới ở giai đoạn dựng khung**. Chưa có:

- [ ] `git init` — **việc đầu tiên cần làm** (xem [`GIT_WORKFLOW.md`](docs/standards/GIT_WORKFLOW.md))
- [ ] Theme custom `web/themes/custom/nidqc/`
- [ ] `frontend/` (Vue + Vite)
- [ ] Content type (cần chốt [`ENTITY_MAPPING.md`](docs/database/ENTITY_MAPPING.md) trước)
- [ ] Hạ tầng test
- [ ] Staging / production

**Câu hỏi phạm vi chưa được trả lời** — xem [`PROJECT_CONTEXT.md`](docs/PROJECT_CONTEXT.md) §5:
tra cứu chất chuẩn · anchor `#chat-chuan`/`#dich-vu` · tiếng Anh · đăng nhập hệ thống · migrate dữ liệu

## Đóng góp

Một task = một nhánh = một PR. Xem [`docs/standards/GIT_WORKFLOW.md`](docs/standards/GIT_WORKFLOW.md).
