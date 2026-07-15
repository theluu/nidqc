/**
 * @file
 * Bootstrap island.
 *
 * NGUYÊN TẮC: Twig SỞ HỮU nội dung. Island không được xoá nội dung Twig.
 * 97,9% design là nội dung tĩnh -> Twig render. Chỉ 7 khối tương tác -> island.
 * Xem docs/decisions/ADR-001 và ADR-002.
 *
 * HAI LOẠI ISLAND (ADR-002):
 *
 *   1. NÂNG CẤP — module export `enhance(el)`, trả hàm dọn dẹp.
 *      Dùng cho khối Twig ĐÃ render nội dung: mega-menu, faq-accordion, tabs.
 *      ⛔ KHÔNG import Vue. ⛔ KHÔNG ghi vào el.innerHTML.
 *
 *   2. RENDER — module export `default` là Vue component.
 *      CHỈ dùng khi container RỖNG và nội dung do JS sinh: doc-filter,
 *      news-filter, standard-search.
 *      ⚠️ createApp().mount(el) XOÁ SẠCH nội dung của el. Đã kiểm chứng.
 *      Mount vào khối có nội dung Twig = phá đúng thứ ADR-001 bảo vệ.
 */

const registry = {
  'mega-menu': () => import('./islands/megaMenu.js'),
  // Island render (Vue) đăng ký ở đây khi có task tương ứng:
  // 'doc-filter': () => import('./islands/DocFilter.vue'),
};

/**
 * Đọc props từ <script type="application/json" data-props> bên trong island.
 * Twig là nguồn dữ liệu, truyền MỘT CHIỀU sang island.
 * Xem docs/standards/VUE_CODING_STANDARD.md §4.
 */
function readProps(el) {
  const tag = el.querySelector(':scope > script[data-props]');
  if (!tag) return {};
  try {
    return JSON.parse(tag.textContent);
  } catch (e) {
    // Props hỏng không được làm chết trang: nội dung Twig vẫn phải đọc được.
    console.error('[nidqc] data-props không phải JSON hợp lệ:', el.dataset.island, e);
    return {};
  }
}

/** Module có phải Vue component không? Component có setup/render/template. */
function isVueComponent(mod) {
  const c = mod.default;
  return !!c && (typeof c.setup === 'function' || typeof c.render === 'function' || 'template' in c);
}

async function mountIsland(el) {
  const name = el.dataset.island;
  const loader = registry[name];

  // Không có island khớp -> im lặng bỏ qua. KHÔNG throw: HTML do Twig render
  // vẫn dùng được. Đó là điểm mấu chốt của progressive enhancement.
  if (!loader) return;

  try {
    const mod = await loader();

    // Loại 1: nâng cấp. Không đụng nội dung.
    if (typeof mod.enhance === 'function') {
      mod.enhance(el, readProps(el));
      return;
    }

    // Loại 2: render bằng Vue. Chỉ vào container rỗng.
    if (isVueComponent(mod)) {
      if (el.children.length || el.textContent.trim()) {
        // Chặn thẳng thay vì để Vue lặng lẽ xoá nội dung Twig. Lỗi này rất khó
        // phát hiện: trang vẫn chạy, chỉ là nội dung SEO biến mất.
        console.error(
          `[nidqc] island "${name}" là Vue component nhưng container có sẵn nội dung. ` +
          'createApp().mount() sẽ xoá nội dung đó. Dùng island loại "enhance" thay thế — ' +
          'xem docs/decisions/ADR-002-island-types.md.',
        );
        return;
      }
      const { createApp } = await import('vue');
      createApp(mod.default, readProps(el)).mount(el);
      return;
    }

    console.error(`[nidqc] island "${name}" không export enhance() cũng không phải Vue component.`);
  } catch (e) {
    console.error('[nidqc] không khởi tạo được island:', name, e);
  }
}

document.querySelectorAll('[data-island]').forEach(mountIsland);
