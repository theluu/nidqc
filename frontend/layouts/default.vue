<script setup>
import { ref, onMounted, onUnmounted } from 'vue';

/**
 * Layout dùng chung mọi trang — tái tạo từ design đã duyệt.
 * Top bar · banner · nav (mega menu) · footer.
 */

const now = ref('');
const openMenu = ref(null);
const mobileOpen = ref(false);
const langOpen = ref(false);
const curLang = ref('vi');
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

onMounted(() => {
  tick();
  timer = setInterval(tick, 30000);
  resetGoogleTranslate();
  loadGoogleTranslate();
});
onUnmounted(() => {
  clearInterval(timer);
  clearTimeout(googleLanguageSyncTimer);
});

// Menu lấy từ design (const NAV). href trỏ route Vue.
// Menu đầy đủ theo design (const NAV). 9 mục, submenu 2 tầng.
const nav = [
  { label: 'Trang chủ', to: '/', children: [] },
  { label: 'Giới thiệu', to: '/gioi-thieu-chung', children: [
    { label: 'Giới thiệu chung', to: '/gioi-thieu-chung' },
    { label: 'Chính sách chất lượng', to: '/chinh-sach-chat-luong' },
    { label: 'Năng lực', to: '/nang-luc' },
    { label: 'Cơ cấu tổ chức', to: '/co-cau-to-chuc' },
  ] },
  { label: 'Hoạt động chuyên môn', to: '/#hoat-dong-chuyen-mon', children: [
    { label: 'Chỉ đạo tuyến', to: '/#hoat-dong-chuyen-mon' },
    { label: 'Kiểm nghiệm và giám sát chất lượng thuốc', to: '/#hoat-dong-chuyen-mon' },
    { label: 'Hợp tác quốc tế', to: '/#hoat-dong-chuyen-mon' },
    { label: 'Hoạt động NRA', to: '/#hoat-dong-chuyen-mon' },
    { label: 'Tạp chí Kiểm nghiệm Dược và Mỹ phẩm', to: '/#hoat-dong-chuyen-mon' },
  ] },
  { label: 'Đào tạo & NCKH', to: '/dao-tao-nckh', children: [
    { label: 'Đào tạo tiến sỹ', to: '/dao-tao-nckh' },
    { label: 'Nghiên cứu khoa học', to: '/dao-tao-nckh' },
  ] },
  { label: 'Dịch vụ', to: '/#dich-vu', children: [
    { label: 'Phân tích - Kiểm nghiệm', to: '/#dich-vu' },
    { label: 'Đánh giá tương đương sinh học (TĐSH)', to: '/#dich-vu' },
    { label: 'Đào tạo và tư vấn kỹ thuật', to: '/#dich-vu' },
    { label: 'Hiệu chuẩn', to: '/#dich-vu' },
    { label: 'Nghiên cứu - Chuyển giao', to: '/#dich-vu' },
    { label: 'Thử nghiệm thành thạo', to: '/#dich-vu' },
    { label: 'Cung ứng chất chuẩn', to: '/#chat-chuan' },
  ] },
  { label: 'Tin tức & Thông báo', to: '/tin-tuc', children: [
    { label: 'Thông báo', to: '/tin-tuc' },
    { label: 'Tin hoạt động', to: '/tin-tuc' },
    { label: 'Mua sắm, đấu thầu & công khai minh bạch', to: '/tin-tuc' },
    { label: 'Đào tạo', to: '/tin-tuc' },
    { label: 'Hội nghị - Hội thảo', to: '/tin-tuc' },
    { label: 'Tuyển dụng', to: '/tin-tuc' },
  ] },
  { label: 'Văn bản - Tài liệu', to: '/van-ban-tai-lieu', children: [
    { label: 'Văn bản pháp quy', to: '/van-ban-tai-lieu' },
    { label: 'Tài liệu chuyên môn', to: '/van-ban-tai-lieu' },
  ] },
  { label: 'Liên hệ & hỗ trợ', to: '/lien-he', children: [
    { label: 'Liên hệ', to: '/lien-he' },
    { label: 'Câu hỏi thường gặp', to: '/faq' },
  ] },
  { label: 'Tra cứu', to: '/#chat-chuan', children: [] },
];

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
        <div class="nidqc-topbar-actions" style="display:flex;align-items:center;gap:14px;font-size:12.5px;">
          <!-- Dropdown mọi thứ tiếng (Google Translate) -->
          <div style="position:relative;">
            <button data-testid="language-menu-toggle" @click="langOpen = !langOpen" style="background:none;border:0;cursor:pointer;color:#fff;display:flex;align-items:center;gap:4px;font-size:12.5px;">
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><circle cx="12" cy="12" r="10"/><path d="M2 12h20M12 2a15 15 0 0 1 0 20 15 15 0 0 1 0-20z"/></svg>
              Ngôn ngữ
              <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round"><path d="m6 9 6 6 6-6"/></svg>
            </button>
            <div v-if="langOpen" data-testid="language-menu" style="position:absolute;top:100%;right:0;margin-top:6px;background:#fff;box-shadow:0 4px 14px rgba(0,0,0,0.2);z-index:70;min-width:220px;max-height:420px;overflow-y:auto;padding:6px 0;">
              <button v-for="l in languages" :key="l.code" data-testid="language-option" @click="setLang(l.code)"
                :style="`display:block;width:100%;text-align:left;background:none;border:0;cursor:pointer;padding:8px 16px;font-size:13px;color:#212529;${curLang===l.code?'font-weight:700;background:#E8F0F7;':''}`">
                {{ l.label }}
              </button>
            </div>
          </div>
          <span style="width:1px;height:14px;background:rgba(255,255,255,0.25);"></span>
          <a href="/user/login" class="nidqc-login" style="color:#fff;display:flex;align-items:center;gap:5px;text-decoration:none;white-space:nowrap;">
            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"/><path d="M10 17l5-5-5-5"/><path d="M15 12H3"/></svg>
            <span class="nidqc-login-text">Đăng nhập hệ thống</span>
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
    <header style="background:#0F3093;box-shadow:0 2px 4px rgba(0,0,0,0.10);position:sticky;top:0;z-index:40;"
            @mouseleave="openMenu = null">
      <div class="nidqc-nav-inner" data-container style="max-width:1280px;margin:0 auto;padding:0 24px;min-height:50px;display:flex;align-items:stretch;">
        <!-- Hamburger: chỉ hiện trên mobile -->
        <button class="nidqc-hamburger" @click="mobileOpen = !mobileOpen" :aria-expanded="mobileOpen" aria-label="Mở menu"
          style="display:none;align-items:center;justify-content:center;width:44px;height:50px;background:none;border:0;cursor:pointer;color:#fff;">
          <svg v-if="!mobileOpen" width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><path d="M4 6h16M4 12h16M4 18h16"/></svg>
          <svg v-else width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><path d="M6 6l12 12M18 6L6 18"/></svg>
        </button>

        <nav class="nidqc-nav-list" :class="{ 'is-open': mobileOpen }" style="display:flex;align-items:stretch;flex:1;min-width:0;">
          <div v-for="(item, i) in nav" :key="i" class="nidqc-nav-item"
               style="display:flex;align-items:stretch;flex:0 0 auto;position:relative;"
               @mouseenter="openMenu = item.children.length ? i : null">
            <NuxtLink :to="item.to" @click="mobileOpen = false"
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
              <NuxtLink v-for="(c, j) in item.children" :key="j" :to="c.to" @click="mobileOpen = false"
                style="display:block;padding:11px 18px;font-size:13.5px;line-height:18px;color:#212529;border-bottom:1px solid #F0F0F0;white-space:nowrap;text-decoration:none;">
                {{ c.label }}
              </NuxtLink>
            </div>
          </div>
        </nav>
        <a href="https://nidqc.gov.vn/tim-kiem-chat-chuan" title="Tra cứu" class="nidqc-nav-search"
           style="align-self:center;display:flex;align-items:center;justify-content:center;width:42px;height:34px;background:#1D6AC5;border-radius:18px;margin-left:10px;flex:0 0 auto;">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2.2" stroke-linecap="round"><circle cx="11" cy="11" r="7"/><path d="m21 21-4.3-4.3"/></svg>
        </a>
      </div>
    </header>

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
        <div style="max-width:1280px;margin:0 auto;padding:16px 24px;font-size:12.5px;color:rgba(255,255,255,0.6);text-align:center;">Bản quyền © 2026 thuộc về Viện Kiểm nghiệm thuốc Trung ương.</div>
      </div>
    </footer>
  </div>
</template>
