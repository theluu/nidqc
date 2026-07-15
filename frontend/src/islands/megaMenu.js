/**
 * @file
 * Island `mega-menu` — loại NÂNG CẤP (ADR-002).
 *
 * Twig đã render toàn bộ cây menu ra HTML thô để Google đọc được. Module này
 * CHỈ thêm hành vi:
 *   - bàn phím: Enter mở, Esc đóng, mũi tên đi lại
 *   - cảm ứng: chạm lần 1 mở, lần 2 đi
 *   - aria-expanded đúng trạng thái
 *
 * ⛔ KHÔNG import Vue. ⛔ KHÔNG ghi vào el.innerHTML.
 *
 * Tắt JS -> layout.css lo bằng :hover/:focus-within. Class `is-enhanced` báo cho
 * CSS biết island đã tiếp quản, để trạng thái do aria-expanded quyết định thay vì
 * :hover — nhờ vậy bàn phím và cảm ứng mới điều khiển được.
 */

const PARENT = '.nidqc-megamenu__link--parent';

export function enhance(el) {
  const parents = [...el.querySelectorAll(PARENT)];
  if (!parents.length) return () => {};

  const listeners = [];
  const on = (target, type, fn, opts) => {
    target.addEventListener(type, fn, opts);
    listeners.push([target, type, fn, opts]);
  };

  let openParent = null;

  const subLinksOf = (parent) => {
    const sub = parent.nextElementSibling;
    return sub ? [...sub.querySelectorAll('a')] : [];
  };

  const closeAll = () => {
    parents.forEach((p) => p.setAttribute('aria-expanded', 'false'));
    openParent = null;
  };

  const open = (parent) => {
    if (openParent === parent) return;
    closeAll();
    parent.setAttribute('aria-expanded', 'true');
    openParent = parent;
  };

  parents.forEach((parent) => {
    const item = parent.closest('.nidqc-megamenu__item');

    // Hover bằng chuột. Bỏ qua pointerType 'touch': trên cảm ứng ta muốn chạm
    // điều khiển chứ không phải hover giả lập.
    on(item, 'pointerenter', (e) => {
      if (e.pointerType !== 'touch') open(parent);
    });

    // Cảm ứng: chạm lần 1 mở và KHÔNG điều hướng; lần 2 mới đi.
    // Dùng pointerType thay vì đoán theo độ rộng màn hình — máy vừa có chuột
    // vừa có cảm ứng thì đoán theo màn hình là sai.
    on(parent, 'pointerdown', (e) => {
      if (e.pointerType !== 'touch') return;
      if (parent.getAttribute('aria-expanded') !== 'true') {
        e.preventDefault();
        open(parent);
      }
    });

    on(parent, 'keydown', (e) => {
      if (e.key === 'Enter' || e.key === ' ') {
        // Mục cha là link thật. Enter lần 1 mở submenu, lần 2 mới đi — người
        // dùng bàn phím vẫn tới được trang cha.
        if (parent.getAttribute('aria-expanded') !== 'true') {
          e.preventDefault();
          open(parent);
        }
        return;
      }
      if (e.key === 'ArrowDown') {
        e.preventDefault();
        open(parent);
        subLinksOf(parent)[0]?.focus();
        return;
      }
      if (e.key === 'Escape') {
        closeAll();
        parent.focus();
      }
    });

    subLinksOf(parent).forEach((link) => {
      on(link, 'keydown', (e) => {
        const links = subLinksOf(parent);
        const i = links.indexOf(e.target);

        if (e.key === 'Escape') {
          e.preventDefault();
          closeAll();
          parent.focus();
          return;
        }
        if (e.key === 'ArrowDown') {
          e.preventDefault();
          links[Math.min(i + 1, links.length - 1)]?.focus();
          return;
        }
        if (e.key === 'ArrowUp') {
          e.preventDefault();
          if (i === 0) parent.focus();
          else links[i - 1]?.focus();
        }
        // Tab KHÔNG bị chặn — người dùng phải thoát menu được. Không bẫy focus.
      });
    });
  });

  // Rời cả header mới đóng, như design (onmouseleave trên <header>).
  const header = el.closest('.nidqc-nav-main') ?? el;
  on(header, 'pointerleave', (e) => {
    if (e.pointerType !== 'touch') closeAll();
  });

  // Focus rời hẳn khối menu -> đóng.
  on(el, 'focusout', (e) => {
    if (!el.contains(e.relatedTarget)) closeAll();
  });

  // Chạm ra ngoài menu -> đóng.
  on(document, 'pointerdown', (e) => {
    if (!el.contains(e.target)) closeAll();
  });

  // Gắn SAU khi đã gắn listener, để không có khoảng thời gian nào mà :hover đã
  // bị tắt nhưng island chưa sẵn sàng — lúc đó menu sẽ không mở được bằng cách nào.
  el.classList.add('is-enhanced');

  return () => {
    listeners.forEach(([t, type, fn, opts]) => t.removeEventListener(type, fn, opts));
    el.classList.remove('is-enhanced');
    closeAll();
  };
}
