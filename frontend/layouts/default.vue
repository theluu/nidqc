<script setup>
import { nextTick, ref, onMounted, onUnmounted } from 'vue';

/**
 * Layout dùng chung mọi trang — tái tạo từ design đã duyệt.
 * Top bar · banner · nav (mega menu) · footer.
 */

const now = ref('');
const openMenu = ref(null);
const mobileOpen = ref(false);
const navBottom = ref(50);
const headerEl = ref(null);
const langOpen = ref(false);
const searchOpen = ref(false);
const searchInput = ref(null);
const searchTrigger = ref(null);
const curLang = ref('vi');
const { onlineCount } = await useOnlineCounter();

// Thanh tin chạy dưới main menu — dùng chung cho MỌI trang nên fetch ở layout.
// useCachedData khoá theo key nên điều hướng client-side không gọi lại Drupal; lỗi
// fetch trả data = null và component tự ẩn, không được phép làm hỏng cả layout.
const { data: tickerNews } = await useCachedData('layout-ticker', async () => {
  const res = await fetchNewsList({ limit: 5 });
  return res.data.map((n) => ({ id: n.id, title: n.title, alias: n.alias }));
});
let timer;
let googleLanguageSyncTimer;

const days = ['Chủ Nhật', 'Thứ Hai', 'Thứ Ba', 'Thứ Tư', 'Thứ Năm', 'Thứ Sáu', 'Thứ Bảy'];
function tick() {
  const d = new Date();
  const p = (n) => String(n).padStart(2, '0');
  now.value = `${days[d.getDay()]}, ${p(d.getDate())}/${p(d.getMonth() + 1)}/${d.getFullYear()} · ${p(d.getHours())}:${p(d.getMinutes())}`;
}

/**
 * Đa ngôn ngữ:
 *  - Tiếng Việt: nội dung chính thức.
 *  - English và các ngôn ngữ khác: Google Translate dịch máy toàn trang.
 *
 * ⚠️ Đây là site về THUỐC. Dịch MÁY nội dung y tế/dược có thể sai nghĩa. Bản dịch
 * máy chỉ để tham khảo; nội dung chính thức là tiếng Việt.
 */
const defaultLanguage = { code: 'vi', label: 'Tiếng Việt' };
const languages = ref([defaultLanguage]);

// Hai ngôn ngữ bấm nhanh trên top bar. Phần còn lại (~100 thứ tiếng của Google
// Translate) vẫn nằm trong dropdown bên cạnh.
const quickLanguages = [
  { code: 'vi', short: 'VI', label: 'Tiếng Việt' },
  { code: 'en', short: 'EN', label: 'English' },
];

// Link mạng xã hội quản trị trong Drupal: /admin/config/nidqc/settings (mục "Mạng xã
// hội"). Chỉ kênh nào điền URL mới được trả về, nên không cần lọc thêm ở đây.
const SOCIAL_LABELS = { facebook: 'Facebook', youtube: 'YouTube', zalo: 'Zalo' };
const { data: socialLinks } = await useCachedData('layout-social', async () => {
  const res = await fetchPublicConfig();
  return (res.social || []).map((s) => ({
    key: s.key,
    url: s.url,
    label: SOCIAL_LABELS[s.key] || s.key,
  }));
});

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

function syncGoogleLanguages(attempt = 0) {
  const combo = document.querySelector('.goog-te-combo');
  const options = combo ? Array.from(combo.options).filter((option) => option.value) : [];

  if (options.length) {
    languages.value = [
      defaultLanguage,
      ...options
        .map((option) => ({
          code: option.value,
          label: (option.textContent || '').trim().replace(/\s+/g, ' ') || option.value,
        }))
        .filter((language) => language.code !== defaultLanguage.code),
    ];
    return;
  }

  if (attempt < 40) {
    googleLanguageSyncTimer = window.setTimeout(() => syncGoogleLanguages(attempt + 1), 250);
  }
}

function loadGoogleTranslate() {
  if (window.__gtLoaded) {
    syncGoogleLanguages();
    return;
  }
  window.__gtLoaded = true;
  window.googleTranslateElementInit = () => {
    new window.google.translate.TranslateElement(
      { pageLanguage: 'vi', autoDisplay: false },
      'google_translate_element',
    );
    syncGoogleLanguages();
  };
  const s = document.createElement('script');
  s.src = 'https://translate.google.com/translate_a/element.js?cb=googleTranslateElementInit';
  document.head.appendChild(s);
}

/** Đổi ngôn ngữ: 'vi' = gốc; còn lại = Google Translate dịch toàn trang. */
function setLang(code) {
  curLang.value = code;
  langOpen.value = false;

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

async function openSearch(event) {
  event?.preventDefault();
  setMobile(false);
  searchOpen.value = true;
  await nextTick();
  searchInput.value?.focus();
}

async function closeSearch() {
  searchOpen.value = false;
  await nextTick();
  searchTrigger.value?.focus();
}

function handleEscape(event) {
  if (event.key === 'Escape' && searchOpen.value) {
    closeSearch();
  }
}

onMounted(() => {
  tick();
  timer = setInterval(tick, 30000);
  resetGoogleTranslate();
  loadGoogleTranslate();
  document.addEventListener('keydown', handleEscape);
});
onUnmounted(() => {
  clearInterval(timer);
  clearTimeout(googleLanguageSyncTimer);
  window.removeEventListener('scroll', syncNavBottom);
  window.removeEventListener('resize', syncNavBottom);
  document.removeEventListener('keydown', handleEscape);
});

// Menu chính dùng chung với trang chủ — xem composables/useMainNav.ts.
const nav = mainNav;

const footerLinks = [
  { label: 'Giới thiệu chung', to: '/gioi-thieu-chung' },
  { label: 'Tin tức & Thông báo', to: '/tin-tuc' },
  { label: 'Văn bản - Tài liệu', to: '/van-ban-tai-lieu' },
  { label: 'Câu hỏi thường gặp', to: '/faq' },
  { label: 'Liên hệ & hỗ trợ', to: '/lien-he' },
];
</script>

<template>
  <div style="background:#FFFFFF;min-height:100vh;font-family:'Be Vietnam Pro',sans-serif;color:#333;">

    <!-- ===== TOP BAR ===== -->
    <div style="background:#0D2870;color:#fff;">
      <div class="nidqc-topbar" style="max-width:1280px;margin:0 auto;padding:0 24px;height:34px;display:flex;align-items:center;justify-content:space-between;">
        <div class="nidqc-topbar-date" style="display:flex;align-items:center;gap:8px;font-size:12.5px;color:rgba(255,255,255,0.82);min-width:0;">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/></svg>
          <span>{{ now }}</span>
        </div>
        <div class="nidqc-topbar-actions" style="display:flex;align-items:center;gap:12px;font-size:12.5px;">
          <!-- Bấm nhanh VI / EN — hai ngôn ngữ dùng nhiều nhất, khỏi phải mở dropdown. -->
          <div class="nidqc-lang-quick">
            <button v-for="l in quickLanguages" :key="l.code" type="button"
              class="nidqc-lang-btn" :class="{ 'is-active': curLang === l.code }"
              :aria-pressed="curLang === l.code ? 'true' : 'false'"
              :title="l.label" @click="setLang(l.code)">
              {{ l.short }}
            </button>
          </div>
          <!-- Dropdown mọi thứ tiếng (Google Translate) -->
          <div style="position:relative;">
            <button data-testid="language-menu-toggle" @click="langOpen = !langOpen"
              class="nidqc-icon-btn" title="Ngôn ngữ khác" aria-haspopup="true" :aria-expanded="langOpen ? 'true' : 'false'">
              <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><circle cx="12" cy="12" r="10"/><path d="M2 12h20M12 2a15 15 0 0 1 0 20 15 15 0 0 1 0-20z"/></svg>
              <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round"><path d="m6 9 6 6 6-6"/></svg>
              <span class="nidqc-visually-hidden">Ngôn ngữ khác</span>
            </button>
            <div v-if="langOpen" data-testid="language-menu" class="nidqc-lang-menu" style="position:absolute;top:100%;right:0;margin-top:6px;background:#fff;box-shadow:0 4px 14px rgba(0,0,0,0.2);z-index:70;min-width:220px;max-height:420px;overflow-y:auto;padding:6px 0;">
              <button v-for="l in languages" :key="l.code" data-testid="language-option" @click="setLang(l.code)"
                :style="`display:block;width:100%;text-align:left;background:none;border:0;cursor:pointer;padding:8px 16px;font-size:13px;color:#212529;${curLang===l.code?'font-weight:700;background:#E8F0F7;':''}`">
                {{ l.label }}
              </button>
            </div>
          </div>
          <span v-if="socialLinks && socialLinks.length" class="nidqc-topbar-sep"></span>

          <!-- Mạng xã hội (cấu hình trong Drupal) -->
          <div v-if="socialLinks && socialLinks.length" class="nidqc-social-group">
            <a v-for="s in socialLinks" :key="s.key" :href="s.url" target="_blank" rel="noopener"
              class="nidqc-social" :class="`is-${s.key}`" :title="s.label">
              <svg v-if="s.key === 'facebook'" width="15" height="15" viewBox="0 0 24 24" fill="currentColor"><path d="M14 8.5V7a1.5 1.5 0 0 1 1.5-1.5H17V2.6A15 15 0 0 0 14.9 2.5C12.3 2.5 10.5 4.1 10.5 7v1.5H8v3.2h2.5V21h3.5v-9.3h2.6l.4-3.2H14z"/></svg>
              <svg v-else-if="s.key === 'youtube'" width="15" height="15" viewBox="0 0 24 24" fill="currentColor"><path d="M21.6 7.2a2.5 2.5 0 0 0-1.8-1.8C18.2 5 12 5 12 5s-6.2 0-7.8.4A2.5 2.5 0 0 0 2.4 7.2 26 26 0 0 0 2 12a26 26 0 0 0 .4 4.8 2.5 2.5 0 0 0 1.8 1.8C5.8 19 12 19 12 19s6.2 0 7.8-.4a2.5 2.5 0 0 0 1.8-1.8A26 26 0 0 0 22 12a26 26 0 0 0-.4-4.8zM10 15.2V8.8l5.2 3.2z"/></svg>
              <!-- Zalo: bong bóng chat + chữ Z (không dùng logo gốc để khỏi vẽ sai nhận diện). -->
              <svg v-else width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 11.5a8.5 8.5 0 0 1-8.5 8.5 8.4 8.4 0 0 1-3.8-.9L3 21l1.9-5.7A8.4 8.4 0 0 1 4 11.5 8.5 8.5 0 0 1 12.5 3 8.5 8.5 0 0 1 21 11.5z"/><path d="M9.8 9h5l-5 5.5h5"/></svg>
              <span class="nidqc-visually-hidden">{{ s.label }}</span>
            </a>
          </div>

          <span class="nidqc-topbar-sep"></span>

          <a href="/user/login" class="nidqc-icon-btn nidqc-login" title="Đăng nhập hệ thống">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"/><path d="M10 17l5-5-5-5"/><path d="M15 12H3"/></svg>
            <span class="nidqc-visually-hidden">Đăng nhập hệ thống</span>
          </a>
          <!-- Element ẩn cho Google Translate -->
          <div id="google_translate_element" style="display:none;"></div>
        </div>
      </div>
    </div>

    <!-- ===== BANNER ===== -->
    <div class="nidqc-banner" style="background:#fff;border-bottom:1px solid #ECECEC;">
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
                style="display:block;padding:11px 18px;font-size:13.5px;line-height:18px;color:#212529;border-bottom:1px solid #F0F0F0;white-space:nowrap;text-decoration:none;">
                {{ c.label }}
              </NuxtLink>
            </div>
          </div>
        </nav>
        <!-- Thẻ <a> THƯỜNG, không phải NuxtLink: NuxtLink tự điều hướng qua Vue Router
             nên preventDefault() không chặn được -> bấm icon vừa mở popup vừa nhảy sang
             /tim-kiem. Với <a> thường, có JS thì @click.prevent mở popup và ở lại trang;
             không JS thì href đưa thẳng tới trang tìm kiếm (progressive enhancement).
             ref trên phần tử thường cũng trả về DOM node nên .focus() chạy đúng. -->
        <a ref="searchTrigger" href="/tim-kiem" title="Tìm kiếm Tin tức" class="nidqc-nav-search"
           aria-haspopup="dialog" :aria-expanded="searchOpen ? 'true' : 'false'" @click.prevent="openSearch"
           style="align-self:center;display:flex;align-items:center;justify-content:center;width:42px;height:34px;background:#1D6AC5;border-radius:18px;margin-left:10px;flex:0 0 auto;">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2.2" stroke-linecap="round"><circle cx="11" cy="11" r="7"/><path d="m21 21-4.3-4.3"/></svg>
          <span class="nidqc-visually-hidden">Tìm kiếm Tin tức</span>
        </a>
      </div>
    </header>

    <NewsTicker v-if="tickerNews && tickerNews.length" :items="tickerNews" />

    <div v-if="searchOpen" class="nidqc-search-overlay" role="presentation" @mousedown.self="closeSearch">
      <section class="nidqc-search-dialog" role="dialog" aria-modal="true" aria-labelledby="nidqc-search-title">
        <button type="button" class="nidqc-search-close" aria-label="Đóng tìm kiếm" @click="closeSearch">
          <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><path d="M6 6l12 12M18 6 6 18"/></svg>
        </button>
        <h2 id="nidqc-search-title">Tìm kiếm Tin tức</h2>
        <p>Nhập từ khóa để tìm trong tiêu đề và nội dung Tin tức.</p>
        <form class="nidqc-search-form" action="/tim-kiem" method="get">
          <label for="nidqc-search-input">Từ khóa</label>
          <div class="nidqc-search-form__row">
            <input id="nidqc-search-input" ref="searchInput" name="q" type="search" minlength="2" maxlength="200" required autocomplete="off" placeholder="Nhập từ khóa tìm kiếm…">
            <button type="submit">Tìm kiếm</button>
          </div>
        </form>
      </section>
    </div>

    <!-- ===== NỘI DUNG TRANG ===== -->
    <main>
      <slot />
    </main>

    <!-- ===== FOOTER ===== -->
    <footer style="background:#0D2870;color:#fff;">
      <div class="nidqc-footer-grid" data-container style="max-width:1280px;margin:0 auto;padding:46px 24px 20px;display:grid;grid-template-columns:2fr 1fr 1.3fr;gap:40px;">
        <div>
          <h3 style="font-family:'Lexend',sans-serif;font-weight:700;font-size:18px;margin:0 0 12px;line-height:24px;">Viện Kiểm nghiệm thuốc Trung ương</h3>
          <p style="font-size:13.5px;line-height:21px;color:rgba(255,255,255,0.72);margin:0;max-width:430px;">Cơ quan khoa học kỹ thuật đầu ngành về kiểm tra, giám sát chất lượng thuốc, nguyên liệu làm thuốc và mỹ phẩm trên phạm vi cả nước.</p>
        </div>
        <div>
          <h4 style="font-family:'Lexend',sans-serif;font-weight:700;font-size:13.5px;letter-spacing:0.5px;text-transform:uppercase;color:rgba(255,255,255,0.6);margin:0 0 14px;">Liên kết nhanh</h4>
          <div style="display:flex;flex-direction:column;gap:9px;font-size:13.5px;">
            <NuxtLink v-for="(l, i) in footerLinks" :key="i" :to="l.to" style="color:rgba(255,255,255,0.85);text-decoration:none;">{{ l.label }}</NuxtLink>
          </div>
        </div>
        <div>
          <h4 style="font-family:'Lexend',sans-serif;font-weight:700;font-size:13.5px;letter-spacing:0.5px;text-transform:uppercase;color:rgba(255,255,255,0.6);margin:0 0 14px;">Liên hệ</h4>
          <p style="font-size:13.5px;line-height:21px;color:rgba(255,255,255,0.72);margin:0;">48 Hai Bà Trưng, Hoàn Kiếm, Hà Nội<br>ĐT: (024) 3825 5075</p>
        </div>
      </div>
      <div style="border-top:1px solid rgba(255,255,255,0.14);">
        <div class="nidqc-footer-bar" data-container style="max-width:1280px;margin:0 auto;padding:16px 24px;font-size:12.5px;color:rgba(255,255,255,0.6);">
          <span>Bản quyền © 2026 thuộc về Viện Kiểm nghiệm thuốc Trung ương.</span>
          <span class="nidqc-online-counter" aria-live="polite" aria-atomic="true">
            <span class="nidqc-online-counter__dot" aria-hidden="true"></span>
            Đang trực tuyến:
            <strong>{{ onlineCount ?? '—' }}</strong>
          </span>
        </div>
      </div>
    </footer>
  </div>
</template>
