# Architecture Decision Records (ADR)

Ghi lại **quyết định kiến trúc quan trọng**: chọn gì, vì sao, đánh đổi gì.

## Vì sao cần

Sáu tháng sau, không ai nhớ vì sao chọn islands thay vì headless. Không có ADR thì
người mới (hoặc AI) sẽ "sửa" lại theo hiểu biết của mình và phá vỡ ràng buộc đã cân nhắc kỹ.

ADR không phải thủ tục hành chính. Nó là cách làm cho quyết định **chống lại được sự quên**.

## Danh sách

| # | Quyết định | Trạng thái | Ngày |
|---|---|---|---|
| [001](ADR-001-frontend-architecture.md) | Drupal theme + Vue islands | ✅ Accepted | 2026-07-15 |
| [002](ADR-002-island-types.md) | Hai loại island: JS thuần nâng cấp, Vue render | ✅ Accepted | 2026-07-16 |

## Khi nào viết ADR

**Có:** chọn kiến trúc · thêm dependency lớn · đổi cách tích hợp FE/BE · quyết định về schema
gốc · đánh đổi bảo mật/hiệu năng · quyết định ảnh hưởng nhiều task

**Không:** đặt tên biến · chi tiết cài đặt một component · sửa lỗi

## Trạng thái

| Trạng thái | Nghĩa |
|---|---|
| `Proposed` | Đang đề xuất, chưa chốt |
| `Accepted` | Đã chốt — **đang có hiệu lực** |
| `Deprecated` | Không còn áp dụng |
| `Superseded by ADR-xxx` | Bị thay bởi ADR khác |

**Không sửa ADR đã `Accepted`.** Đổi ý → viết ADR mới, đánh dấu cái cũ `Superseded`.
Lịch sử quyết định có giá trị riêng, kể cả khi quyết định đã sai.

## Mẫu

```markdown
# ADR-xxx — <Tiêu đề>

- **Trạng thái:** Proposed
- **Ngày:** YYYY-MM-DD
- **Người quyết:**
- **Ảnh hưởng:**

## Bối cảnh
<Vấn đề là gì. Ràng buộc thực tế nào chi phối.>

## Các phương án đã cân nhắc
### A. <Tên>
- ➕
- ➖

## Quyết định
<Chọn gì. VÌ SAO — phần quan trọng nhất.>

## Hệ quả
<Kéo theo điều gì. Cả tốt lẫn xấu.>

## Xem lại khi nào
<Điều kiện nào khiến quyết định này nên được xem lại.>
```

## Quy tắc cho AI

⛔ **AI không được tự viết ADR `Accepted`.** ADR là quyết định của con người.
AI có thể soạn `Proposed` để người xem xét.

⛔ **AI không được đi ngược ADR `Accepted`.** Thấy ADR có vấn đề → nói ra, không tự sửa code
theo ý mình. Xem `AGENTS.md` §2.
