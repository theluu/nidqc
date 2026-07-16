# NIDQC — Website Viện Kiểm nghiệm thuốc Trung ương

Website `nidqc.gov.vn` — Drupal 11 + Vue 3 (islands).

> 🤖 **AI agent: đọc [`AGENTS.md`](AGENTS.md) trước khi làm bất cứ việc gì.**

---

## Bắt đầu

```bash
ddev start
ddev composer install
ddev drush si standard -y --account-name=admin --account-pass=<mật khẩu của bạn>

# ⚠️ BẮT BUỘC: khớp UUID site với config, nếu không `cim` sẽ THẤT BẠI.
# Drupal từ chối import config từ "site khác", mà `si` luôn sinh UUID mới.
ddev drush config:set system.site uuid $(grep '^uuid:' config/sync/system.site.yml | cut -d' ' -f2) -y

ddev drush cim -y             # dựng lại toàn bộ cấu hình từ git
ddev drush locale:update      # nạp bản dịch tiếng Việt (không nằm trong config)
ddev drush cr
ddev launch
```

Sau `cim` bạn sẽ có: 8 content type · 8 pathauto pattern · 9 taxonomy term · menu 9 mục · theme `nidqc`.
Đã kiểm chứng từ DB trống (`TASK-007` §11).

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

## Lộ trình

Kế hoạch đầy đủ: [`docs/ROADMAP.md`](docs/ROADMAP.md).

| Task | Việc | Trạng thái |
|---|---|---|
| [001](tasks/TASK-001.md) | Khung theme + `tokens.css` | ✅ chờ review |
| [002](tasks/TASK-002.md) | Config tái lập được | ✅ chờ review |
| [003](tasks/TASK-003.md) | `page.html.twig` — 5 vùng chung | ✅ chờ review |
| [004](tasks/TASK-004.md) | Self-host font | ✅ chờ review |
| [005](tasks/TASK-005.md) | Dựng `frontend/` (Vite) | ✅ chờ review |
| [006](tasks/TASK-006.md) | Island `mega-menu` | 📝 ready (một phần) |
| 007+ | Content type → 11 trang Twig → island | 🔴 **bị chặn** |

> 🔴 **Blocker:** site có **0 content type**, và **không thể chốt** cho tới khi NIDQC trả lời.
> Design là mockup **thị giác**, không phải đặc tả **dữ liệu** — cả trang "Văn bản tài liệu" chỉ
> có `{ title, meta }`, không file tải về, không số hiệu, không ngày ban hành.
>
> 📄 **Văn bản hỏi Viện đã soạn sẵn: [`docs/CAU_HOI_NIDQC.md`](docs/CAU_HOI_NIDQC.md)** — chỉ việc gửi.
> Câu 1, 2, 4 chặn gần như toàn bộ phần còn lại. Đây là việc của **người**, không phải AI.

### ⚠️ Không chuyển các trang design sang Vue

Đã đo: **97,9% design là nội dung tĩnh** (32/1.510 phần tử cần JS). Toàn bộ tương tác của 11 trang
chỉ gồm 7 khối. Chuyển sang Vue SPA = đẩy 1.478 phần tử nội dung ra sau JavaScript để phục vụ 32
phần tử — giết SEO, và làm `metatag`/`pathauto`/`simple_sitemap` thành vô dụng.

**97,9% → Twig. 7 khối tương tác → Vue island.** Xem [`ADR-001`](docs/decisions/ADR-001-frontend-architecture.md)
và [`ROADMAP.md`](docs/ROADMAP.md) §5.

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

- [x] `git init` + baseline
- [x] Theme custom `web/themes/custom/nidqc/` — khung + 21 design token
- [x] Config tái lập được (`config/sync/`, 232 file)
- [x] `frontend/` — Vite 8 + bootstrap island ([TASK-005](tasks/TASK-005.md))
- [x] Font self-host: Lexend + Be Vietnam Pro, tiếng Việt đủ dấu ([TASK-004](tasks/TASK-004.md))
- [x] `page.html.twig` — 5 vùng chung theo design ([TASK-003](tasks/TASK-003.md))
- [ ] Content type — 🔴 **bị chặn**, cần NIDQC trả lời
- [ ] 11 trang Twig + 6 Vue island — chặn bởi content type
- [ ] Hạ tầng test
- [ ] Staging / production

**Câu hỏi phạm vi chưa được trả lời** — xem [`PROJECT_CONTEXT.md`](docs/PROJECT_CONTEXT.md) §5:
tra cứu chất chuẩn · anchor `#chat-chuan`/`#dich-vu` · tiếng Anh · đăng nhập hệ thống · migrate dữ liệu

Nợ kỹ thuật đã ghi nhận: [`ROADMAP.md`](docs/ROADMAP.md) §7.

## Đóng góp

Một task = một nhánh = một PR. Xem [`docs/standards/GIT_WORKFLOW.md`](docs/standards/GIT_WORKFLOW.md).
