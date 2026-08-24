<script setup>
import { ref, computed, nextTick, provide, onMounted, onUnmounted } from 'vue';

/**
 * Layout dùng chung mọi trang — tái tạo từ design đã duyệt.
 * Top bar · banner · nav (mega menu) · footer.
 */

const now = ref('');
const nowWeekday = ref('');
const openMenu = ref(null);
const mobileOpen = ref(false);
const navBottom = ref(50);
const headerEl = ref(null);
const curLang = ref('vi');
// Ô tìm kiếm nằm sau icon kính lúp trên thanh menu (feedback 3) chứ không chiếm chỗ
// thường trực trên banner nữa.
const searchOpen = ref(false);
const searchInput = ref(null);
// Bộ đếm chạy MỘT lần ở layout rồi chia xuống trang: gọi useOnlineCounter() thêm
// lần nữa trong trang chủ sẽ dựng thêm một interval heartbeat thứ hai, tức là mỗi
// khách gửi gấp đôi request và bị đếm hai lần.
const { onlineCount, visits } = await useOnlineCounter();
provide('nidqcOnline', { onlineCount, visits });

// Thanh tin chạy dưới main menu — dùng chung cho MỌI trang nên fetch ở layout.
// useCachedData khoá theo key nên điều hướng client-side không gọi lại Drupal; lỗi
// fetch trả data = null và component tự ẩn, không được phép làm hỏng cả layout.
const { data: tickerNews } = await useCachedData('layout-ticker', async () => {
  const res = await fetchNewsList({ limit: 5 });
  return res.data.map((n) => ({ id: n.id, title: n.title, alias: n.alias }));
});
let timer;

const days = ['Chủ Nhật', 'Thứ Hai', 'Thứ Ba', 'Thứ Tư', 'Thứ Năm', 'Thứ Sáu', 'Thứ Bảy'];
// Thứ tách RIÊNG khỏi ngày/giờ: trên điện thoại hẹp chỉ bỏ chữ "Thứ Bảy," để vừa
// một dòng, phần ngày giờ — thứ người dùng thật sự cần — vẫn còn (trước đây cả cụm
// bị ẩn từ 760px trở xuống).
function tick() {
  const d = new Date();
  const p = (n) => String(n).padStart(2, '0');
  nowWeekday.value = days[d.getDay()];
  now.value = `${p(d.getDate())}/${p(d.getMonth() + 1)}/${d.getFullYear()} · ${p(d.getHours())}:${p(d.getMinutes())}`;
}

/**
 * Đa ngôn ngữ — CHỈ hai thứ tiếng (feedback 1):
 *  - Tiếng Việt: nội dung chính thức.
 *  - English: Google Translate dịch máy toàn trang.
 *
 * Dropdown ~100 thứ tiếng của Google Translate đã bỏ: Viện chỉ phục vụ hai ngôn
 * ngữ này, danh sách dài chỉ làm rối thanh tiện ích.
 *
 * ⚠️ Đây là site về THUỐC. Dịch MÁY nội dung y tế/dược có thể sai nghĩa. Bản dịch
 * máy chỉ để tham khảo; nội dung chính thức là tiếng Việt.
 */
const defaultLanguage = { code: 'vi', label: 'Tiếng Việt' };
const languages = [
  defaultLanguage,
  { code: 'en', label: 'English' },
];

// Cấu hình chân trang + mạng xã hội, quản trị trong Drupal tại
// /admin/config/nidqc/settings. Chỉ kênh nào điền URL mới được trả về, nên không
// cần lọc thêm ở đây.
const SOCIAL_LABELS = { facebook: 'Facebook', youtube: 'YouTube', zalo: 'Zalo', tiktok: 'TikTok' };
const { data: siteConfig } = await useCachedData('layout-site-config', async () => {
  const res = await fetchPublicConfig();
  return {
    social: (res.social || []).map((s) => ({
      key: s.key,
      url: s.url,
      label: SOCIAL_LABELS[s.key] || s.key,
    })),
    contact: res.footer,
    customerServices: res.customer_services || [],
  };
});
const socialLinks = computed(() => siteConfig.value?.social || []);
const footerContact = computed(() => siteConfig.value?.contact || null);
const customerServices = computed(() => siteConfig.value?.customerServices || []);

// Địa chỉ các cơ sở cho cột 1 của chân trang — quản trị bằng content type "Cơ sở",
// lấy từ cùng endpoint với các khối trang chủ. Dùng CHUNG khoá 'home-blocks' với
// pages/index.vue: useCachedData khoá theo key nên trang chủ không gọi thêm lần nào.
const { data: homeBlocks } = await useCachedData('home-blocks', fetchHomeBlocks);
const offices = computed(() => homeBlocks.value?.offices || []);

function googleTranslateCookieDomains() {
  const hostname = location.hostname;
  const domains = [hostname, `.${hostname}`];
  const parts = hostname.split('.');

  if (parts.length > 2) {
    domains.push(`.${parts.slice(1).join('.')}`);
  }

  return [...new Set(domains)];
}

function writeGoogleTranslateCookie(value, maxAge) {
  const secure = location.protocol === 'https:' ? ';secure' : '';
  const base = `googtrans=${value};path=/;SameSite=Lax;max-age=${maxAge}${secure}`;

  document.cookie = base;
  googleTranslateCookieDomains().forEach((domain) => {
    document.cookie = `${base};domain=${domain}`;
  });
}

function resetGoogleTranslate() {
  curLang.value = defaultLanguage.code;
  writeGoogleTranslateCookie('', 0);
  writeGoogleTranslateCookie('/vi/vi', 60);
}

function loadGoogleTranslate() {
  if (window.__gtLoaded) return;
  window.__gtLoaded = true;
  window.googleTranslateElementInit = () => {
    new window.google.translate.TranslateElement(
      // includedLanguages: chỉ nạp tiếng Anh — widget không còn phải dựng danh sách
      // ~100 thứ tiếng mà giao diện đã bỏ.
      { pageLanguage: 'vi', includedLanguages: 'en', autoDisplay: false },
      'google_translate_element',
    );
  };
  const s = document.createElement('script');
  s.src = 'https://translate.google.com/translate_a/element.js?cb=googleTranslateElementInit';
  document.head.appendChild(s);
}

/** Đổi ngôn ngữ: 'vi' = gốc; 'en' = Google Translate dịch toàn trang. */
function setLang(code) {
  curLang.value = code;

  if (code === 'vi') {
    resetGoogleTranslate();
    location.reload();
    return;
  }

  writeGoogleTranslateCookie(`/vi/${code}`, 60 * 60 * 12);

  const combo = document.querySelector('.goog-te-combo');
  if (combo) {
    combo.value = code;
    combo.dispatchEvent(new Event('change'));
  } else {
    location.reload();
  }
}

// Drawer menu mobile dùng position:fixed (KHÔNG absolute) để nổi TRÊN nội dung
// trang: drawer absolute bị "nhốt" trong stacking context của <header> sticky nên
// bị nội dung <main> vẽ đè (bấm hamburger như không hiện gì); z-index không cứu được.
// top của drawer bám đáy nav bar qua biến CSS --nav-bottom, cập nhật khi cuộn/resize.
function syncNavBottom() {
  if (headerEl.value) navBottom.value = Math.round(headerEl.value.getBoundingClientRect().bottom);
}
function setMobile(open) {
  mobileOpen.value = open;
  if (open) {
    syncNavBottom();
    window.addEventListener('scroll', syncNavBottom, { passive: true });
    window.addEventListener('resize', syncNavBottom);
  } else {
    openMenu.value = null;
    window.removeEventListener('scroll', syncNavBottom);
    window.removeEventListener('resize', syncNavBottom);
  }
}

/**
 * Mở/đóng hộp tìm kiếm sau icon kính lúp trên thanh menu.
 *
 * Mở thì đưa con trỏ thẳng vào ô nhập: người dùng bấm kính lúp là để gõ ngay, bắt
 * họ bấm thêm một nhát nữa vào ô là thừa. Đóng bằng Esc như mọi hộp thoại khác.
 */
function toggleSearch(open) {
  searchOpen.value = open;
  if (open) {
    setMobile(false);
    nextTick(() => searchInput.value?.focus());
  }
}

function onKeydown(event) {
  if (event.key === 'Escape' && searchOpen.value) toggleSearch(false);
}

onMounted(() => {
  tick();
  timer = setInterval(tick, 30000);
  resetGoogleTranslate();
  loadGoogleTranslate();
  window.addEventListener('keydown', onKeydown);
});
onUnmounted(() => {
  clearInterval(timer);
  window.removeEventListener('keydown', onKeydown);
  window.removeEventListener('scroll', syncNavBottom);
  window.removeEventListener('resize', syncNavBottom);
});

// Menu chính dùng chung với trang chủ — xem composables/useMainNav.ts.
//
// Submenu "Dịch vụ" và "Hoạt động chuyên môn" lấy thẳng từ hai khối cùng tên ở
// trang chủ: cùng dữ liệu homeBlocks đã fetch ở trên nên KHÔNG tốn thêm request, mà
// admin sửa khối trong Drupal là menu đổi theo ngay. Trước đây cả hai danh sách chép
// cứng trong useMainNav.ts, trùng khớp chỉ vì có người nhớ sửa cả hai chỗ.
const nav = computed(() => mainNavWithBlocks(homeBlocks.value));

// Sơ đồ menu ngay trên chân trang (feedback 12). Nằm ở LAYOUT chứ không ở trang chủ:
// khách 24/08 muốn dải menu này có mặt ở chân MỌI trang, không riêng trang chủ.
// Dùng lại đúng `nav` ở trên nên không thêm request và không thể lệch với menu đầu
// trang. Bỏ mục "Trang chủ": đây là dải điều hướng phụ, link về chính trang đang xem
// không dẫn đi đâu.
const siteMap = computed(() => nav.value.filter((item) => item.to !== '/'));
</script>

<template>
  <div style="background:#FFFFFF;min-height:100vh;font-family:'Be Vietnam Pro',sans-serif;color:#333;">

    <!-- ===== TOP BAR: ngày/giờ · ngôn ngữ =====
         Feedback 1: chỉ hai cờ Vi/En (bỏ danh sách ~100 thứ tiếng). Ô tìm kiếm đã
         chuyển thành icon kính lúp trên thanh menu (feedback 3); nút đăng nhập đã
         gỡ hẳn (feedback 21/08).

         Dải riêng NẰM TRÊN banner, nền cùng màu thanh menu. Bản trước đè lên ảnh
         banner: cụm icon phải tự vẽ nền trắng cho đọc được, và ở mỗi bề ngang màn
         hình nó lại rơi vào một chỗ khác trên ảnh. Dải riêng thì vị trí cố định và
         khung xanh trên–dưới ôm lấy banner thành một khối header gọn. -->
    <div class="nidqc-topbar">
      <div class="nidqc-topbar__inner" data-container>
        <!-- Ngày/giờ nằm ở nửa trái top bar: trước đây nó ở thanh "Tin mới" và đẩy
             tiêu đề tin sang phải, còn nửa trái top bar thì bỏ trống. Chỉ render sau
             khi mount (now = '' lúc SSR) vì giờ máy khách khác giờ máy chủ — in ra
             ngay trong HTML là hydrate lệch. -->
        <span v-if="now" class="nidqc-topbar__time">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/></svg>
          <span class="nidqc-topbar__dow">{{ nowWeekday }},</span>
          {{ now }}
        </span>

        <div class="nidqc-lang-quick">
          <button v-for="l in languages" :key="l.code" type="button"
            class="nidqc-lang-btn" :class="{ 'is-active': curLang === l.code }"
            :aria-pressed="curLang === l.code ? 'true' : 'false'"
            :title="l.label" @click="setLang(l.code)">
            <!-- Cờ vẽ bằng SVG inline: không thêm request ảnh, nét sắc ở mọi DPI. -->
            <svg v-if="l.code === 'vi'" class="nidqc-flag" viewBox="0 0 30 20" role="img" aria-hidden="true">
              <rect width="30" height="20" fill="#DA251D"/>
              <path fill="#FFFF00" d="M15 5 16.23 8.8 20.23 8.8 17 11.15 18.23 14.95 15 12.6 11.77 14.95 13 11.15 9.77 8.8 13.77 8.8Z"/>
            </svg>
            <svg v-else class="nidqc-flag" viewBox="0 0 60 40" role="img" aria-hidden="true">
              <clipPath id="nidqc-flag-uk">
                <path d="M30 20h30v20zv20H0zH0V0zV0h30z"/>
              </clipPath>
              <rect width="60" height="40" fill="#012169"/>
              <path d="M0 0 60 40M60 0 0 40" stroke="#FFFFFF" stroke-width="8"/>
              <path d="M0 0 60 40M60 0 0 40" stroke="#C8102E" stroke-width="5" clip-path="url(#nidqc-flag-uk)"/>
              <path d="M30 0V40M0 20H60" stroke="#FFFFFF" stroke-width="13"/>
              <path d="M30 0V40M0 20H60" stroke="#C8102E" stroke-width="8"/>
            </svg>
            <span class="nidqc-topbar__code">{{ l.code.toUpperCase() }}</span>
            <span class="nidqc-visually-hidden">{{ l.label }}</span>
          </button>
        </div>

        <!-- KHÔNG có lối đăng nhập trên giao diện công khai (feedback 21/08:
             "Không cần hiển thị trên trang web"). Biên tập viên vào thẳng
             /user/login bằng đường dẫn, trang bán chuẩn đã tách sang site riêng.
             Icon mạng xã hội nằm ở chân trang, không lặp lại ở đây. -->

        <!-- Element ẩn cho Google Translate (phải nằm trong DOM để script gắn vào) -->
        <div id="google_translate_element" style="display:none;"></div>
      </div>
    </div>

    <!-- ===== BANNER ===== -->
    <div class="nidqc-banner" style="background:#fff;">
      <NuxtLink to="/" style="display:block;">
        <img src="https://nidqc.gov.vn/sites/all/themes/nidqc/images/upload/banner-header.jpg"
             alt="Viện Kiểm nghiệm thuốc Trung ương" style="display:block;width:100%;height:auto;">
      </NuxtLink>
    </div>

    <!-- ===== NAV ===== -->
    <header ref="headerEl" :style="{ '--nav-bottom': navBottom + 'px' }" style="background:#0F3093;box-shadow:0 2px 4px rgba(0,0,0,0.10);position:sticky;top:0;z-index:40;"
            @mouseleave="openMenu = null">
      <div class="nidqc-nav-inner" data-container style="max-width:1280px;margin:0 auto;padding:0 24px;min-height:50px;display:flex;align-items:stretch;">
        <!-- Hamburger: chỉ hiện trên mobile -->
        <button class="nidqc-hamburger" @click="setMobile(!mobileOpen)" :aria-expanded="mobileOpen" aria-label="Mở menu"
          style="display:none;align-items:center;justify-content:center;width:44px;height:50px;background:none;border:0;cursor:pointer;color:#fff;">
          <svg v-if="!mobileOpen" width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><path d="M4 6h16M4 12h16M4 18h16"/></svg>
          <svg v-else width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><path d="M6 6l12 12M18 6L6 18"/></svg>
        </button>

        <nav class="nidqc-nav-list" :class="{ 'is-open': mobileOpen }" style="display:flex;align-items:stretch;flex:1;min-width:0;">
          <div v-for="(item, i) in nav" :key="i" class="nidqc-nav-item"
               style="display:flex;align-items:stretch;flex:0 0 auto;position:relative;"
               @mouseenter="openMenu = item.children.length ? i : null">
            <NuxtLink :to="item.to" @click="setMobile(false)"
              style="display:flex;align-items:center;padding:0 14px;color:#fff;font-weight:600;font-size:14px;text-decoration:none;white-space:nowrap;"
              :style="openMenu === i ? 'background:#0D2870;' : ''">
              {{ item.label }}
            </NuxtLink>
            <!-- nút mở submenu trên mobile -->
            <button v-if="item.children.length" class="nidqc-sub-toggle" @click="openMenu = openMenu === i ? null : i"
              :aria-label="`Mở mục con ${item.label}`"
              style="display:none;background:none;border:0;color:#fff;padding:0 14px;cursor:pointer;">
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" :style="openMenu===i?'transform:rotate(180deg)':''"><path d="m6 9 6 6 6-6"/></svg>
            </button>
            <div v-if="item.children.length && openMenu === i" class="nidqc-submenu"
                 style="position:absolute;top:100%;left:0;min-width:220px;background:#fff;box-shadow:0 4px 12px rgba(0,0,0,0.14);z-index:50;">
              <NuxtLink v-for="(c, j) in item.children" :key="j" :to="c.to" @click="setMobile(false)"
                :target="isExternalLink(c.to) ? '_blank' : undefined"
                :rel="isExternalLink(c.to) ? 'noopener' : undefined"
                style="display:block;padding:11px 18px;font-size:13.5px;line-height:18px;color:#212529;border-bottom:1px solid #F0F0F0;white-space:nowrap;text-decoration:none;">
                {{ c.label }}
              </NuxtLink>
            </div>
          </div>
        </nav>
        <!-- Feedback 3: icon tìm kiếm nằm trên thanh menu. Thanh này dính đỉnh trang
             nên với tới được ở mọi vị trí cuộn — ô tìm kiếm thường trực trên banner
             thì cuộn xuống là mất. Nút đăng nhập đã chuyển lên header (feedback 2). -->
        <div class="nidqc-nav-right">
          <button type="button" class="nidqc-nav-search" :aria-expanded="searchOpen"
            aria-controls="nidqc-nav-search-panel" title="Tìm kiếm" @click="toggleSearch(!searchOpen)">
            <svg v-if="!searchOpen" width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><circle cx="11" cy="11" r="7"/><path d="m21 21-4.3-4.3"/></svg>
            <svg v-else width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><path d="M6 6l12 12M18 6L6 18"/></svg>
            <span class="nidqc-visually-hidden">Tìm kiếm</span>
          </button>
        </div>
      </div>

      <!-- Hộp tìm kiếm mở ra ngay dưới thanh menu, chiều rộng bằng container.
           Form GET thật: không có JS vẫn submit sang /tim-kiem?q=… bình thường. -->
      <div v-if="searchOpen" id="nidqc-nav-search-panel" class="nidqc-nav-search-panel">
        <form class="nidqc-nav-search-form" data-container action="/tim-kiem" method="get" role="search"
          @submit="toggleSearch(false)">
          <label for="nidqc-nav-search-input" class="nidqc-visually-hidden">Tìm kiếm trên website</label>
          <input id="nidqc-nav-search-input" ref="searchInput" name="q" type="search" minlength="2" maxlength="200"
                 required autocomplete="off" placeholder="Nhập từ khoá cần tìm…">
          <button type="submit">
            <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><circle cx="11" cy="11" r="7"/><path d="m21 21-4.3-4.3"/></svg>
            Tìm kiếm
          </button>
        </form>
      </div>
    </header>

    <NewsTicker v-if="tickerNews && tickerNews.length" :items="tickerNews" />

    <!-- ===== NỘI DUNG TRANG ===== -->
    <main>
      <slot />
    </main>

    <!-- ===== FOOTER (feedback 13) =====
         Ba cột: thông tin Viện · đầu mối dành cho khách hàng · mạng xã hội.
         Địa chỉ và link bản đồ lấy từ content type "Cơ sở"; điện thoại/fax/email và
         bốn đầu mối dịch vụ lấy từ /admin/config/nidqc/settings. -->
    <!-- ===== SƠ ĐỒ MENU (feedback 12) — mọi trang, ngay trên chân trang ===== -->
    <section class="nidqc-section" style="background:#F5F5F5;border-top:1px solid #ECECEC;">
      <div class="nidqc-sitemap" data-container style="max-width:1280px;margin:0 auto;padding:0 24px;">
        <NuxtLink v-for="(item, i) in siteMap" :key="i" :to="item.to" class="nidqc-sitemap__head">
          {{ item.label }}
        </NuxtLink>
      </div>
    </section>

    <footer style="background:#0D2870;color:#fff;">
      <div class="nidqc-footer-grid" data-container style="max-width:1280px;margin:0 auto;padding:44px 24px 22px;display:grid;grid-template-columns:1.5fr 1fr 1fr;gap:40px;">
        <div>
          <h3 class="nidqc-footer__title">Viện Kiểm nghiệm thuốc Trung ương</h3>

          <h4 class="nidqc-footer__label">Địa chỉ</h4>
          <ul class="nidqc-footer__list">
            <li v-for="(o, i) in offices || []" :key="i">
              <span class="nidqc-footer__office">{{ o.title }}:</span>
              {{ o.address }}
              <a v-if="o.map" :href="o.map" target="_blank" rel="noopener" class="nidqc-footer__map">
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0z"/><circle cx="12" cy="10" r="3"/></svg>
                Bản đồ
              </a>
            </li>
          </ul>

          <h4 class="nidqc-footer__label">Thông tin liên hệ công việc</h4>
          <ul class="nidqc-footer__list">
            <li v-if="footerContact?.tel">
              Tel: <a :href="`tel:${footerContact.tel.replace(/[^+\d]/g, '')}`">{{ footerContact.tel }}</a>
              <span v-if="footerContact.tel_note" class="nidqc-footer__note">&nbsp;({{ footerContact.tel_note }})</span>
            </li>
            <li v-if="footerContact?.fax">Fax: {{ footerContact.fax }}</li>
            <li v-if="footerContact?.email">
              Email: <a :href="`mailto:${footerContact.email}`">{{ footerContact.email }}</a>
            </li>
          </ul>
        </div>

        <!-- Cột giữa CHỈ dành cho đầu mối liên hệ theo dịch vụ (feedback 24/08: "cột ở
             giữa để anh cho các thông tin cụ thể dành cho khách hàng"). Dải link nhanh
             Giới thiệu chung / Tin tức / Văn bản... đã bỏ theo đúng yêu cầu đó — các
             mục này vẫn còn ở menu chính và ở khối sơ đồ menu ngay phía trên chân trang.
             Nội dung bốn nhóm dịch vụ nhập ở /admin/config/nidqc/settings. -->
        <div>
          <h4 class="nidqc-footer__label">Thông tin dành cho khách hàng</h4>
          <ol v-if="customerServices.length" class="nidqc-footer__services">
            <li v-for="(s, i) in customerServices" :key="i">
              <span class="nidqc-footer__service-name">{{ s.label }}</span>
              <a v-if="s.email" :href="`mailto:${s.email}`">{{ s.email }}</a>
              <a v-if="s.hotline" :href="`tel:${s.hotline.replace(/[^+\d]/g, '')}`">{{ s.hotline }}</a>
            </li>
          </ol>
          <p v-else class="nidqc-footer__empty">
            Đầu mối liên hệ theo từng dịch vụ đang được cập nhật. Trong thời gian này,
            xin liên hệ qua số điện thoại và email chung của Viện.
          </p>
        </div>

        <div>
          <h4 class="nidqc-footer__label">Liên kết mạng xã hội</h4>
          <div v-if="socialLinks.length" class="nidqc-footer__social">
            <a v-for="s in socialLinks" :key="s.key" :href="s.url" target="_blank" rel="noopener"
              class="nidqc-social" :class="`is-${s.key}`" :title="s.label">
              <SocialIcon :channel="s.key" :size="17" />
              <span class="nidqc-visually-hidden">{{ s.label }}</span>
            </a>
          </div>
          <p class="nidqc-footer__about">
            Cơ quan khoa học kỹ thuật đầu ngành về kiểm tra, giám sát chất lượng thuốc,
            nguyên liệu làm thuốc và mỹ phẩm trên phạm vi cả nước.
          </p>
        </div>
      </div>
      <div style="border-top:1px solid rgba(255,255,255,0.14);">
        <!-- Chỉ còn dòng bản quyền: số người đang trực tuyến đã có ở khối "Thống kê
             truy cập" trên trang chủ, để hai chỗ là đếm một thứ hai lần. -->
        <div class="nidqc-footer-bar" data-container style="max-width:1280px;margin:0 auto;padding:16px 24px;font-size:12.5px;color:rgba(255,255,255,0.6);">
          <span>Bản quyền © 2026 thuộc về Viện Kiểm nghiệm thuốc Trung ương.</span>
        </div>
      </div>
    </footer>
  </div>
</template>
