---
id: TASK-000
title: <Mô tả ngắn, một câu>
status: draft            # draft | ready | in_progress | review | done | blocked
step: 4                  # bước trong quy trình 1–8
owner: <người chịu trách nhiệm>
reviewer: <người review — KHÔNG được trùng owner>
created: YYYY-MM-DD

# ⛔ Cờ nguy hiểm — mặc định false. Đặt true = phải có người duyệt.
schema_change: false     # tạo/sửa content type, field, taxonomy, view mode
new_package: false       # composer require / npm install
config_change: false     # sửa config/sync/

# ✅ Agent CHỈ được sửa các đường dẫn dưới đây. Ra ngoài = dừng và hỏi.
allowed_files:
  - web/themes/custom/nidqc/templates/...
  - frontend/src/islands/...

# 📖 Agent được đọc (ngoài các file luôn bắt buộc)
read_only:
  - design/NIDQC ....html
  - docs/design/DESIGN_SYSTEM.md
---

# TASK-000 — <Tiêu đề>

## 1. Mục tiêu

<Một đoạn. Xong task này thì cái gì hoạt động được mà trước đó không?>

## 2. Bối cảnh

<Vì sao cần task này. Link tới trang design, ADR, task liên quan.>

- Design: `design/NIDQC ....html` (trích bằng `python3 scripts/extract-design.py`)
- Trang: `docs/design/PAGE_MAPPING.md` §___
- Liên quan: TASK-___

## 3. Phạm vi

### Trong phạm vi
-

### ⛔ Ngoài phạm vi
- <Ghi rõ những gì DỄ BỊ CÁM DỖ làm nhưng KHÔNG được làm>
- Refactor code xung quanh
- Đổi schema
- Cài package

## 4. Yêu cầu

- [ ] R1 —
- [ ] R2 —

## 5. Tiêu chí chấp nhận

> Phải kiểm chứng được. "Hoạt động tốt" không phải tiêu chí.

- [ ] AC1 — Khi ___ thì ___
- [ ] AC2 — Tắt JS thì ___ vẫn ___
- [ ] AC3 — Màu/font khớp `DESIGN_SYSTEM.md`, không hard-code hex

## 6. Cách verify

> Lệnh cụ thể + kết quả mong đợi. Agent phải **chạy thật** và **dán output**.

```bash
ddev drush cr
ddev composer phpcs
cd frontend && npm run build
```

Kiểm bằng mắt:
1.
2. Tắt JS trong DevTools → kiểm tra fallback

## 7. Bảo mật

- [ ] Đã chạy `docs/security/SECURITY_CHECKLIST.md`
- Rủi ro cụ thể của task này:

## 8. Định nghĩa hoàn thành

Xem `docs/DEFINITION_OF_DONE.md`. Bổ sung riêng task này:
- [ ]

## 9. Câu hỏi mở

> Agent gặp điều chưa rõ → **ghi vào đây và DỪNG**. Không suy đoán, không tự quyết.

-

## 10. Nhật ký

| Ngày | Người/Agent | Việc |
|---|---|---|
| | | |
