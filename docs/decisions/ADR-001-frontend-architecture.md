# ADR-001 — Kiến trúc frontend: Drupal theme + Vue islands

- **Trạng thái:** Accepted
- **Ngày:** 2026-07-15
- **Người quyết:** Chủ dự án
- **Ảnh hưởng:** toàn bộ frontend, SEO, deploy

---

## Bối cảnh

Cần chọn cách tích hợp Vue với Drupal cho website NIDQC. Ràng buộc thực tế:

1. Site cơ quan nhà nước, **nặng nội dung, ít tương tác** — 11 trang thì phần lớn là nội dung tĩnh.
2. **SEO là yêu cầu bắt buộc.** Nội dung phải được Google index đầy đủ.
3. Repo đã cài sẵn `metatag`, `pathauto`, `simple_sitemap`, `redirect` — bộ module SEO của Drupal.
4. Design là 12 file HTML tĩnh, inline style, không có state phức tạp.
5. Không có Node runtime trên hạ tầng hiện tại (DDEV: nginx-fpm + PHP + MariaDB).

## Các phương án đã cân nhắc

### A. Headless — Vue SPA + JSON:API
Drupal chỉ làm API, Vue render toàn bộ.
- ➕ Tách bạch FE/BE rõ ràng.
- ➖ **SEO phải tự lo** bằng SSR/prerender.
- ➖ `metatag`, `pathauto`, `simple_sitemap` gần như **vô dụng** — đã cài rồi.
- ➖ Bỏ phí toàn bộ render layer của Drupal cho một site chủ yếu là nội dung.

### B. Drupal theme + Vue islands ✅ **CHỌN**
Drupal render HTML qua Twig. Vue mount vào các khối tương tác.
- ➕ Giữ nguyên sức mạnh SEO của Drupal — đúng với bộ module đã cài.
- ➕ Vue chỉ ở chỗ thật sự cần: mega menu, FAQ accordion, bộ lọc, tabs, tìm kiếm.
- ➕ Không cần Node runtime ở production.
- ➕ Progressive enhancement tự nhiên — tắt JS thì nội dung vẫn đọc được.
- ➖ Hai hệ build (Twig + Vite) — chấp nhận được.

### C. Decoupled + Nuxt SSR
Drupal API, Nuxt 3 SSR lo SEO.
- ➕ SEO tốt + tách bạch.
- ➖ Phải vận hành thêm Node runtime — hạ tầng và chi phí tăng đáng kể.
- ➖ **Quá mức cần thiết** cho một site nội dung 11 trang.

## Quyết định

**Chọn phương án B — Drupal theme + Vue islands.**

Lý do quyết định: SEO là ràng buộc cứng, và nội dung chiếm phần lớn site. Phương án A đánh đổi
chính xác thứ dự án không được phép đánh đổi. Phương án C giải được SEO nhưng bắt hạ tầng gánh
một Node runtime cho lượng tương tác quá nhỏ để xứng đáng.

## Hệ quả

### Bắt buộc
- Nội dung Google cần đọc → **Twig, server-side**. Không ngoại lệ.
- Vue **chỉ** cho hành vi tương tác.
- Mỗi island phải hoạt động ở chế độ suy giảm (không JS → vẫn đọc được nội dung).
- Vite build ra asset tĩnh, nhúng qua Drupal library. **Không có Node ở production.**

### Island đã xác định
`mega-menu` · `faq-accordion` · `doc-filter` · `news-filter` · `tabs` · `standard-search`

Xem `docs/design/DESIGN_SYSTEM.md` §5.

### Nếu muốn đổi
Đổi kiến trúc = ADR mới + duyệt. Agent **không được** tự chuyển sang SPA/Nuxt. Xem `AGENTS.md` §2.

## Xem lại khi nào

- Nếu tương tác phía client tăng mạnh (nhiều form phức tạp, dashboard nội bộ).
- Nếu `/tim-kiem-chat-chuan` hoá ra là một ứng dụng tra cứu lớn — có thể cần đánh giá riêng cho **một** route đó.
