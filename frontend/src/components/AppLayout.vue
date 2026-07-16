<script setup>
import { ref, onMounted, onUnmounted } from 'vue';
import { RouterLink } from 'vue-router';

/**
 * Layout dùng chung mọi trang — tái tạo từ design đã duyệt.
 * Top bar · banner · nav (mega menu) · footer.
 */

const now = ref('');
const openMenu = ref(null);
let timer;

const days = ['Chủ Nhật', 'Thứ Hai', 'Thứ Ba', 'Thứ Tư', 'Thứ Năm', 'Thứ Sáu', 'Thứ Bảy'];
function tick() {
  const d = new Date();
  const p = (n) => String(n).padStart(2, '0');
  now.value = `${days[d.getDay()]}, ${p(d.getDate())}/${p(d.getMonth() + 1)}/${d.getFullYear()} · ${p(d.getHours())}:${p(d.getMinutes())}`;
}
onMounted(() => { tick(); timer = setInterval(tick, 30000); });
onUnmounted(() => clearInterval(timer));

// Menu lấy từ design (const NAV). href trỏ route Vue.
const nav = [
  { label: 'Trang chủ', to: '/', children: [] },
  { label: 'Giới thiệu', to: '/gioi-thieu-chung', children: [
    { label: 'Giới thiệu chung', to: '/gioi-thieu-chung' },
    { label: 'Chính sách chất lượng', to: '/chinh-sach-chat-luong' },
    { label: 'Năng lực', to: '/nang-luc' },
    { label: 'Cơ cấu tổ chức', to: '/co-cau-to-chuc' },
  ] },
  { label: 'Hoạt động chuyên môn', to: '/#hoat-dong', children: [] },
  { label: 'Đào tạo & NCKH', to: '/dao-tao-nckh', children: [] },
  { label: 'Dịch vụ', to: '/#dich-vu', children: [] },
  { label: 'Tin tức & Thông báo', to: '/tin-tuc', children: [
    { label: 'Thông báo', to: '/tin-tuc' },
    { label: 'Tin hoạt động', to: '/tin-tuc' },
  ] },
  { label: 'Văn bản - Tài liệu', to: '/van-ban-tai-lieu', children: [] },
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
      <div style="max-width:1280px;margin:0 auto;padding:0 24px;height:34px;display:flex;align-items:center;justify-content:space-between;">
        <div style="display:flex;align-items:center;gap:8px;font-size:12.5px;color:rgba(255,255,255,0.82);">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/></svg>
          <span>{{ now }}</span>
        </div>
        <div style="display:flex;align-items:center;gap:14px;font-size:12.5px;">
          <a href="#" style="color:#fff;font-weight:700;border-bottom:2px solid #fff;padding-bottom:1px;">Tiếng Việt</a>
          <a href="#" style="color:rgba(255,255,255,0.7);text-decoration:none;">English</a>
          <span style="width:1px;height:14px;background:rgba(255,255,255,0.25);"></span>
          <a href="#" style="color:#fff;display:flex;align-items:center;gap:5px;text-decoration:none;">
            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"/><path d="M10 17l5-5-5-5"/><path d="M15 12H3"/></svg>
            Đăng nhập hệ thống
          </a>
        </div>
      </div>
    </div>

    <!-- ===== BANNER ===== -->
    <div style="background:#fff;border-bottom:1px solid #ECECEC;">
      <RouterLink to="/" style="display:block;">
        <img src="https://nidqc.gov.vn/sites/all/themes/nidqc/images/upload/banner-header.jpg"
             alt="Viện Kiểm nghiệm thuốc Trung ương" style="display:block;width:100%;height:auto;">
      </RouterLink>
    </div>

    <!-- ===== NAV ===== -->
    <header style="background:#0F3093;box-shadow:0 2px 4px rgba(0,0,0,0.10);position:sticky;top:0;z-index:40;"
            @mouseleave="openMenu = null">
      <div style="max-width:1280px;margin:0 auto;padding:0 24px;height:50px;display:flex;align-items:stretch;">
        <nav style="display:flex;align-items:stretch;flex:1;min-width:0;">
          <div v-for="(item, i) in nav" :key="i"
               style="display:flex;align-items:stretch;flex:0 0 auto;position:relative;"
               @mouseenter="openMenu = item.children.length ? i : null">
            <RouterLink :to="item.to"
              style="display:flex;align-items:center;padding:0 14px;color:#fff;font-weight:600;font-size:14px;text-decoration:none;white-space:nowrap;"
              :style="openMenu === i ? 'background:#0D2870;' : ''">
              {{ item.label }}
            </RouterLink>
            <div v-if="item.children.length && openMenu === i"
                 style="position:absolute;top:100%;left:0;min-width:220px;background:#fff;box-shadow:0 4px 12px rgba(0,0,0,0.14);z-index:50;">
              <RouterLink v-for="(c, j) in item.children" :key="j" :to="c.to"
                style="display:block;padding:11px 18px;font-size:13.5px;line-height:18px;color:#212529;border-bottom:1px solid #F0F0F0;white-space:nowrap;text-decoration:none;">
                {{ c.label }}
              </RouterLink>
            </div>
          </div>
        </nav>
        <a href="https://nidqc.gov.vn/tim-kiem-chat-chuan" title="Tra cứu"
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
      <div style="max-width:1280px;margin:0 auto;padding:46px 24px 20px;display:grid;grid-template-columns:2fr 1fr 1.3fr;gap:40px;">
        <div>
          <h3 style="font-family:'Lexend',sans-serif;font-weight:700;font-size:18px;margin:0 0 12px;line-height:24px;">Viện Kiểm nghiệm thuốc Trung ương</h3>
          <p style="font-size:13.5px;line-height:21px;color:rgba(255,255,255,0.72);margin:0;max-width:430px;">Cơ quan khoa học kỹ thuật đầu ngành về kiểm tra, giám sát chất lượng thuốc, nguyên liệu làm thuốc và mỹ phẩm trên phạm vi cả nước.</p>
        </div>
        <div>
          <h4 style="font-family:'Lexend',sans-serif;font-weight:700;font-size:13.5px;letter-spacing:0.5px;text-transform:uppercase;color:rgba(255,255,255,0.6);margin:0 0 14px;">Liên kết nhanh</h4>
          <div style="display:flex;flex-direction:column;gap:9px;font-size:13.5px;">
            <RouterLink v-for="(l, i) in footerLinks" :key="i" :to="l.to" style="color:rgba(255,255,255,0.85);text-decoration:none;">{{ l.label }}</RouterLink>
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
