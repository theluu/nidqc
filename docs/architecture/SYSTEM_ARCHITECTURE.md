# System Architecture — NIDQC

## 1. Tổng thể

```
                    ┌─────────────────────────────┐
   Người dùng ──────▶  nginx (DDEV / production)  │
                    └──────────────┬──────────────┘
                                   │
                    ┌──────────────▼──────────────┐
                    │      Drupal 11.4.3          │
                    │      PHP 8.3 / FPM          │
                    │                             │
                    │  ┌─── Twig render HTML ───┐ │
                    │  │  · nội dung (SEO)      │ │
                    │  │  · metatag, alias      │ │
                    │  │  · <div data-island>   │ │
                    │  └────────────────────────┘ │
                    │  ┌─── Custom modules ─────┐ │
                    │  │  web/modules/custom/   │ │
                    │  └────────────────────────┘ │
                    └──────────────┬──────────────┘
                                   │
                    ┌──────────────▼──────────────┐
                    │      MariaDB 11.8           │
                    └─────────────────────────────┘

   Asset tĩnh (Vite build) ──▶ Vue island mount vào [data-island]
```

**Không có Node runtime ở production.** Vite chỉ chạy lúc build, xuất ra JS/CSS tĩnh.

## 2. Luồng một request

1. nginx nhận request → PHP-FPM → Drupal.
2. Drupal resolve route (alias qua Pathauto, redirect nếu URL cũ).
3. Twig render **HTML đầy đủ**: nội dung, metatag, structured data.
4. HTML chứa `<div data-island="...">` kèm dữ liệu khởi tạo.
5. Trình duyệt tải HTML → **nội dung hiển thị ngay, không chờ JS**.
6. JS island tải xong → Vue mount vào các `[data-island]`, nâng cấp tương tác.

Bước 5 là điểm mấu chốt: **nội dung không phụ thuộc bước 6**.

## 3. Phân lớp

| Lớp | Vị trí | Trách nhiệm |
|---|---|---|
| Presentation | `web/themes/custom/nidqc/` | Twig, CSS, token, region |
| Interaction | `frontend/src/islands/` | Vue island |
| Domain | `web/modules/custom/` | Logic nghiệp vụ, entity, service |
| Data | Drupal Entity API | Node, taxonomy, field |
| Contrib | `web/modules/contrib/` | ⛔ chỉ đọc |
| Core | `web/core/`, `vendor/` | ⛔ chỉ đọc |

## 4. Cấu trúc thư mục

```
nidqc/
├── web/                        # Drupal (giữ nguyên tại root)
│   ├── core/                   # ⛔ cấm sửa
│   ├── modules/
│   │   ├── contrib/            # ⛔ cấm sửa
│   │   └── custom/             # ✅ code dự án
│   ├── themes/custom/nidqc/    # ✅ theme dự án
│   └── sites/default/
├── vendor/                     # ⛔ cấm sửa
├── config/sync/                # ⚠️ chỉ sửa khi task cho phép (= đổi schema)
├── frontend/                   # ✅ Vue 3 + Vite
│   ├── src/islands/
│   └── vite.config.js
├── design/                     # 🔒 chỉ đọc — nguồn chân lý
├── docs/                       # tài liệu
├── tasks/                      # TASK-xxx.md
├── prompts/                    # prompt chuẩn cho AI
├── scripts/                    # tiện ích (extract-design.py)
├── .ddev/                      # môi trường dev
├── AGENTS.md
├── README.md
└── CHANGELOG.md
```

> **Không có `docker-compose.yml`** — DDEV đã lo môi trường dev. Thêm docker-compose song song
> với DDEV sẽ tạo hai nguồn sự thật xung đột. Xem `docs/deployment/DEPLOYMENT.md`.
>
> **Không có `backend/`** — Drupal ở root theo chuẩn `drupal/recommended-project`. Di chuyển vào
> `backend/` là refactor lớn không mang lại giá trị tương xứng.

## 5. Ranh giới Twig ↔ Vue

**Twig chịu trách nhiệm:** toàn bộ nội dung, cấu trúc HTML, metatag, SEO, dữ liệu khởi tạo cho island.

**Vue chịu trách nhiệm:** hành vi tương tác *sau khi* HTML đã có.

Truyền dữ liệu **một chiều**, Twig → Vue, qua `data-*` hoặc `<script type="application/json">`:

```twig
<div data-island="faq-accordion">
  {# Nội dung thật, render sẵn — Google đọc được, không JS vẫn đọc được #}
  {% for item in faqs %}
    <details><summary>{{ item.question }}</summary>{{ item.answer }}</details>
  {% endfor %}
</div>
```

Vue nâng cấp khối `<details>` sẵn có thành accordion có animation — **không render lại nội dung**.

> ⚠️ Không bao giờ để Vue là nơi *duy nhất* sinh ra nội dung. Nếu đang định làm vậy → sai kiến trúc, dừng lại.

## 6. Quyết định liên quan

- `docs/decisions/ADR-001-frontend-architecture.md` — vì sao islands
- `docs/architecture/BACKEND_ARCHITECTURE.md`
- `docs/architecture/FRONTEND_ARCHITECTURE.md`
