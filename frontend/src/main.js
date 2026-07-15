import { createApp } from 'vue';

/**
 * @file
 * Bootstrap Vue island.
 *
 * NGUYÊN TẮC SỐ MỘT: Vue nâng cấp HTML có sẵn. Vue KHÔNG sinh ra nội dung.
 * 97,9% design là nội dung tĩnh -> Twig render. Chỉ 7 khối tương tác -> island.
 * Nếu bạn định render nội dung mà Google cần đọc bằng Vue -> SAI KIẾN TRÚC, dừng lại.
 * Xem docs/decisions/ADR-001-frontend-architecture.md và docs/ROADMAP.md §5.
 */

/**
 * Island đăng ký ở đây. Mỗi entry lazy-load riêng để trang chỉ tải code nó cần.
 *
 * HIỆN TẠI RỖNG — đúng. TASK-005 chỉ dựng đường ray; island đầu tiên
 * (`mega-menu`) là TASK-006. Xem docs/ROADMAP.md §6.
 */
const registry = {
  // 'mega-menu': () => import('./islands/MegaMenu.vue'),
};

/**
 * Đọc props từ <script type="application/json" data-props> bên trong island.
 *
 * Twig là nguồn dữ liệu, truyền MỘT CHIỀU sang Vue. Không parse DOM để lấy dữ
 * liệu — xem docs/standards/VUE_CODING_STANDARD.md §4.
 */
function readProps(el) {
  const tag = el.querySelector(':scope > script[data-props]');
  if (!tag) return {};
  try {
    return JSON.parse(tag.textContent);
  } catch (e) {
    // Props hỏng không được làm chết cả trang: nội dung do Twig render vẫn phải
    // đọc được. Island suy giảm về HTML tĩnh.
    console.error('[nidqc] data-props không phải JSON hợp lệ:', el.dataset.island, e);
    return {};
  }
}

async function mountIsland(el) {
  const name = el.dataset.island;
  const loader = registry[name];

  // Không có island khớp -> im lặng bỏ qua. KHÔNG throw: HTML do Twig render
  // vẫn dùng được, đó là điểm mấu chốt của progressive enhancement.
  if (!loader) return;

  try {
    const { default: Component } = await loader();
    createApp(Component, readProps(el)).mount(el);
  } catch (e) {
    console.error('[nidqc] không mount được island:', name, e);
  }
}

document.querySelectorAll('[data-island]').forEach(mountIsland);
