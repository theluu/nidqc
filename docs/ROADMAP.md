# Roadmap — NIDQC

> Kế hoạch đưa 11 trang design vào project. Cập nhật 2026-07-16.
>
> Kiến trúc: **Drupal Twig render nội dung + Vue island cho tương tác** (`decisions/ADR-001`).
> **Không** chuyển trang sang Vue SPA — xem §5.

---

## 1. Trạng thái

| Task | Việc | Trạng thái |
|---|---|---|
| [TASK-001](../tasks/TASK-001.md) | Khung theme `nidqc` + `tokens.css` (21 token) | ✅ xong, chờ review |
| [TASK-002](../tasks/TASK-002.md) | `config_sync_directory` — config tái lập được | ✅ xong, chờ review |
| [TASK-003](../tasks/TASK-003.md) | `page.html.twig` — 5 vùng chung + sửa 2 lỗi tài liệu | ✅ xong, chờ review |
| [TASK-004](../tasks/TASK-004.md) | Self-host font Lexend + Be Vietnam Pro | ✅ xong, chờ review |
| [TASK-005](../tasks/TASK-005.md) | Dựng `frontend/` — Vite + island bootstrap | ✅ xong, chờ review |
| [TASK-006](../tasks/TASK-006.md) | Island `mega-menu` | 📝 ready ⚠️ **chỉ code được island; menu THẬT chặn bởi 007** |
| **TASK-007** | **Content type + field** | 🔴 **BỊ CHẶN** — xem §3 |
| TASK-008+ | 11 trang Twig + 5 island còn lại | 🔴 chặn bởi TASK-007 |

## 2. Đường găng

```
TASK-003 ✅ (Twig khung) ─┐
TASK-004 ✅ (font)       ─┤
TASK-005 ✅ (Vite)  ── TASK-006 (mega menu island)  <- tiếp theo
                         │
      🔴 TASK-007 (content type) ──── 11 trang Twig + 5 island
              ↑
      BỊ CHẶN bởi 3 câu hỏi chưa hỏi NIDQC
```

**Làm được ngay:** ~~003~~ ✅ → ~~004~~ ✅ → ~~005~~ ✅ → **TASK-006** (một phần).

> ⚠️ **Sửa đánh giá sai trước đó.** ROADMAP từng ghi TASK-006 "✅ làm được" vì menu `main` đã tồn
> tại. Kiểm thật thì menu `main` có **0 link** ("Nhà" là plugin tĩnh, không phải menu link), và
> 9 mục trong design trỏ tới trang **chưa tồn tại**. **Code island thì được; menu thật thì không.**

Sau TASK-006 thì **hết việc làm được** — mọi thứ còn lại cần content type.

**Mọi thứ còn lại chặn bởi TASK-007.**

## 3. 🔴 Blocker: không có content type

Site hiện có **0 content type, 0 node** (kiểm 2026-07-16). Không có content type thì:
- Không dựng được `node--*.html.twig`
- Không có dữ liệu cho island lọc văn bản / lọc tin / FAQ
- Không có gì để SEO

TASK-007 **không thể soạn** cho tới khi NIDQC trả lời 3 câu ở [`PROJECT_CONTEXT.md`](PROJECT_CONTEXT.md) §5:

| Câu hỏi | Chặn cái gì |
|---|---|
| **Tra cứu chất chuẩn là gì?** Được design link **20 lần** nhưng `/tim-kiem-chat-chuan` **không có file design** | Có thể là content type lớn nhất dự án. Nghiệp vụ lõi của Viện. |
| **`#chat-chuan`, `#dich-vu`** là anchor trang chủ hay trang riêng? | Cấu trúc menu + SEO + có cần content type không |
| **Tiếng Anh** có nội dung thật không? | Bật `content_translation` = đổi cấu trúc **mọi** entity. Làm sau = migrate đau. |

> ⛔ **Không đoán để đi tiếp.** Đoán sai content type thì phải migrate dữ liệu thật của Viện —
> tốn gấp nhiều lần thời gian tiết kiệm được. `AGENTS.md` §2.

**Việc cần làm:** gửi 3 câu này cho NIDQC. Đây là việc của **người**, không phải AI.

## 4. Task còn lại (soạn khi hết chặn)

Chưa soạn **có chủ đích** — soạn task về content mà chưa biết content type là bịa spec.

| # | Việc | Phụ thuộc |
|---|---|---|
| 007 | Content type + field theo `database/ENTITY_MAPPING.md` | 🔴 NIDQC trả lời |
| 008 | `page--front.html.twig` — trang chủ (cấu trúc khác hẳn) | 007 |
| 009 | `node--page` — Giới thiệu, Chính sách, Cơ cấu, Năng lực, Đào tạo | 007 |
| 010 | Tin tức: danh sách + chi tiết | 007 |
| 011 | Văn bản + island `doc-filter` | 007 |
| 012 | FAQ + island `faq-accordion` | 007 |
| 013 | Form liên hệ | 007 |
| 014 | Island `tabs` (Năng lực, Đào tạo) | 007 |
| 015 | Island `standard-search` | 🔴 **thiếu design** |

## 5. Vì sao KHÔNG chuyển trang sang Vue

Đã đo toàn bộ design (2026-07-16):

| | |
|---|---|
| Phần tử có xử lý sự kiện (cần JS) | **32** |
| Tổng phần tử | **1.510** |
| **Nội dung tĩnh** | **97,9%** |

Toàn bộ tương tác của **cả 11 trang** chỉ gồm 7 thứ: mega menu · hiển thị ngày · FAQ accordion ·
form liên hệ · lọc tin · tabs văn bản · video trang chủ.

Chuyển sang Vue SPA = dựng lại **1.478 phần tử tĩnh** trong Vue và đẩy toàn bộ nội dung ra sau
JavaScript, để phục vụ **32 phần tử tương tác**. Đó là phương án A đã bị loại ở `ADR-001`:
giết SEO và làm `metatag`/`pathauto`/`simple_sitemap` (đã cài) thành vô dụng.

**97,9% → Twig. 7 khối tương tác → Vue island.**

Kiểm lại số này bất cứ lúc nào:
```bash
python3 scripts/extract-design.py --all -o /tmp/tpl
# đếm handler / tổng phần tử trên /tmp/tpl/*.tpl.html
```

## 6. Island đã xác định

| Island | Trang | Dữ liệu từ | Chặn? |
|---|---|---|---|
| `mega-menu` | tất cả | Menu `main` — **hiện 0 link** | ⚠️ code được, menu thật chặn bởi 007 |
| `faq-accordion` | FAQ | Twig render sẵn | 🔴 007 |
| `doc-filter` | Văn bản | `/api/v1/documents` | 🔴 007 |
| `news-filter` | Tin tức | `/api/v1/news` | 🔴 007 |
| `tabs` | Năng lực, Đào tạo | Twig render sẵn | 🔴 007 |
| `standard-search` | Trang chủ | `/api/v1/standards/search` | 🔴 thiếu design |

Mọi island **phải** hoạt động khi tắt JS (`FRONTEND_ARCHITECTURE.md` §5).

## 7. Nợ kỹ thuật đã ghi nhận

| # | Việc | Nguồn |
|---|---|---|
| 1 | ~~10 block bị dồn vào `header_top`~~ | ✅ đã dọn ở TASK-003 |
| 2 | `settings.php` là bản DDEV sinh từ **Drupal 10**, thiếu `media_oembed_discovery_trusted_host_patterns` | `TASK-002` §9.2 |
| 3 | `dev@nidqc.local` trong `update.settings.yml` | `TASK-002` §11 |
| 4 | Lexend không có weight 400 | `TASK-004` §9.1 |
| 5 | `rgba()` trên nền xanh chưa có token | `TASK-003` §9.2 |
| 6 | Dải breadcrumb rỗng hiện ở trang chủ | `TASK-003` §9.4 → TASK-008 |
| 7 | `site_branding` tràn mép ở banner (placeholder) | `TASK-003` §9.5 |
| 8 | Chưa có hạ tầng test | `testing/TEST_STRATEGY.md` §1 |
| 9 | Chưa có staging / production | `deployment/DEPLOYMENT.md` §1 |
| 10 | 🔴 **Menu link là content → `drush cex` không export → menu không tái lập được.** Cần quyết định (hook_install / recipe / default_content). | `TASK-006` §9.1 |

## 8. Chưa có kế hoạch

- **Migrate dữ liệu** từ site cũ — chưa biết có hay không (`PROJECT_CONTEXT.md` §5)
- **Đăng nhập hệ thống** — design có link ở top bar, chưa rõ cho ai
- **Test** — cần dựng PHPUnit + Vitest
- **Deploy** — chưa có hạ tầng
