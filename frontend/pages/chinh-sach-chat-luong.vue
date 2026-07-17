<script setup>
const { data: node } = await useAsyncData('page-chinh-sach', () => fetchPageByAlias('/chinh-sach-chat-luong'))
const gioiThieuLinks = [
  { label: 'Giới thiệu chung', to: '/gioi-thieu-chung' },
  { label: 'Chính sách chất lượng', to: '/chinh-sach-chat-luong' },
  { label: 'Năng lực', to: '/nang-luc' },
  { label: 'Cơ cấu tổ chức', to: '/co-cau-to-chuc' },
]
const objectives = [
  'Đảm bảo 100% kết quả kiểm nghiệm chính xác, khách quan và có giá trị pháp lý.',
  'Duy trì và cải tiến liên tục hệ thống quản lý chất lượng phòng thử nghiệm.',
  'Rút ngắn thời gian trả kết quả kiểm nghiệm, đáp ứng yêu cầu của đơn vị gửi mẫu.',
  'Nâng cao năng lực chuyên môn của cán bộ thông qua đào tạo và hợp tác quốc tế.',
]
const standards = ['ISO/IEC 17025', 'GLP - Thực hành tốt phòng kiểm nghiệm', 'Tiêu chuẩn NRA - WHO']
useSeoMeta({
  title: 'Chính sách chất lượng — NIDQC',
  description: 'Cam kết của Viện Kiểm nghiệm thuốc Trung ương về tính chính xác, khách quan và tin cậy trong mọi hoạt động kiểm nghiệm; hệ thống quản lý theo ISO/IEC 17025 và GLP.',
})
</script>
<template>
  <div>
    <SectionSubNav :links="gioiThieuLinks" />

    <!-- Hero band xanh đậm + minh hoạ văn bản -->
    <section style="background:#0F3093;color:#fff;position:relative;overflow:hidden;">
      <div style="max-width:1280px;margin:0 auto;padding:52px 24px;position:relative;z-index:1;max-width:1280px;">
        <nav style="font-size:13px;color:#B9C6DE;margin-bottom:26px;">
          <NuxtLink to="/" style="color:#B9C6DE;text-decoration:none;">Trang chủ</NuxtLink> / Giới thiệu / <span style="color:#fff;">Chính sách chất lượng</span>
        </nav>
        <h1 style="font-family:'Lexend',sans-serif;font-weight:700;font-size:34px;margin:0 0 14px;">Chính sách chất lượng</h1>
        <p style="font-size:16px;line-height:25px;color:#DCE5F3;max-width:560px;margin:0;">Cam kết của Viện Kiểm nghiệm thuốc Trung ương về tính chính xác, khách quan và tin cậy trong mọi hoạt động kiểm nghiệm.</p>
      </div>
      <svg viewBox="0 0 200 200" style="position:absolute;right:6%;top:50%;transform:translateY(-50%);width:150px;height:150px;opacity:0.9;" fill="none" stroke="#fff" stroke-width="4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
        <path d="M50 20h70l30 30v130H50z"/><path d="M120 20v30h30"/><path d="M70 80h60M70 100h60M70 120h40"/>
      </svg>
    </section>

    <section style="background:#fff;padding:44px 0 60px;"><div style="max-width:1280px;margin:0 auto;padding:0 24px;">
      <!-- Tuyên bố chính sách -->
      <h2 style="font-family:'Lexend',sans-serif;font-weight:700;font-size:22px;color:#212529;margin:0 0 18px;">Tuyên bố chính sách chất lượng</h2>
      <blockquote v-if="node?.body" style="border-left:4px solid #0F3093;background:#F5F8FC;padding:20px 26px;margin:0 0 40px;font-style:italic;font-size:16px;line-height:26px;color:#334155;" v-html="node.body"></blockquote>
      <blockquote v-else style="border-left:4px solid #0F3093;background:#F5F8FC;padding:20px 26px;margin:0 0 40px;font-style:italic;font-size:16px;line-height:26px;color:#334155;">
        “Viện Kiểm nghiệm thuốc Trung ương cam kết thực hiện hoạt động kiểm nghiệm với độ chính xác, khách quan và tin cậy cao nhất, tuân thủ nghiêm ngặt các tiêu chuẩn kỹ thuật trong nước và quốc tế, không ngừng cải tiến hệ thống quản lý chất lượng để đáp ứng yêu cầu ngày càng cao của công tác bảo vệ sức khỏe nhân dân.”
      </blockquote>

      <!-- Mục tiêu chất lượng -->
      <h2 style="font-family:'Lexend',sans-serif;font-weight:700;font-size:22px;color:#212529;margin:0 0 22px;">Mục tiêu chất lượng</h2>
      <div class="nidqc-grid-2" style="display:grid;grid-template-columns:repeat(2,1fr);gap:18px;margin-bottom:44px;">
        <div v-for="(o, i) in objectives" :key="i" style="display:flex;gap:16px;align-items:flex-start;background:#fff;border:1px solid #ECECEC;padding:20px 22px;">
          <span style="flex-shrink:0;color:#0F3093;font-family:'Lexend',sans-serif;font-weight:700;font-size:16px;background:#EAF0F8;width:34px;height:34px;border-radius:4px;display:flex;align-items:center;justify-content:center;">{{ String(i + 1).padStart(2, '0') }}</span>
          <p style="font-size:14.5px;line-height:22px;color:#334155;margin:0;">{{ o }}</p>
        </div>
      </div>

      <!-- Hệ thống quản lý chất lượng -->
      <h2 style="font-family:'Lexend',sans-serif;font-weight:700;font-size:22px;color:#212529;margin:0 0 14px;">Hệ thống quản lý chất lượng</h2>
      <p style="font-size:15.5px;line-height:25px;color:#334155;max-width:820px;margin:0 0 20px;">Viện duy trì và vận hành hệ thống quản lý chất lượng phòng thử nghiệm phù hợp với tiêu chuẩn ISO/IEC 17025 và nguyên tắc Thực hành tốt phòng kiểm nghiệm (GLP), được đánh giá và công nhận định kỳ bởi các tổ chức trong nước và quốc tế.</p>
      <div style="display:flex;flex-wrap:wrap;gap:14px;">
        <span v-for="(s, i) in standards" :key="i" style="display:inline-flex;align-items:center;gap:9px;border:1px solid #C6D4EA;background:#F5F8FC;color:#0F3093;font-weight:600;font-size:14px;padding:11px 18px;">
          <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="#0F3093" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2l8 4v6c0 5-3.5 8-8 10-4.5-2-8-5-8-10V6z"/><path d="M9 12l2 2 4-4"/></svg>
          {{ s }}
        </span>
      </div>

      <!-- Tài liệu nội bộ (khoá) -->
      <div style="display:flex;flex-wrap:wrap;gap:16px;align-items:center;justify-content:space-between;margin-top:44px;background:#F5F8FC;border:1px solid #E4EBF4;padding:22px 26px;">
        <div style="display:flex;gap:14px;align-items:center;">
          <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#64748B" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
          <span style="font-size:14.5px;color:#475569;">Toàn văn chính sách chất lượng và các chứng nhận liên quan thuộc Văn bản – Tài liệu nội bộ.</span>
        </div>
        <a href="/user/login" style="background:#0F3093;color:#fff;text-decoration:none;font-size:14px;font-weight:600;padding:11px 22px;white-space:nowrap;">Đăng nhập để xem tài liệu</a>
      </div>
    </div></section>
  </div>
</template>
