<script setup>
const { data: node } = await useAsyncData('page-gioi-thieu-chung', () => fetchPageByAlias('/gioi-thieu-chung'))
const gioiThieuLinks = [
  { label: 'Giới thiệu chung', to: '/gioi-thieu-chung' },
  { label: 'Chính sách chất lượng', to: '/chinh-sach-chat-luong' },
  { label: 'Năng lực', to: '/nang-luc' },
  { label: 'Cơ cấu tổ chức', to: '/co-cau-to-chuc' },
]
const quickFacts = [
  { icon: 'M8 7V3m8 4V3M3 11h18M5 5h14a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V7a2 2 0 0 1 2-2z', text: 'Trực thuộc Bộ Y tế' },
  { icon: 'M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z M12 10a2.5 2.5 0 1 0 0-5 2.5 2.5 0 0 0 0 5z', text: '2 cơ sở hoạt động tại Hà Nội' },
  { icon: 'M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2 M9 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8z M23 21v-2a4 4 0 0 0-3-3.87', text: 'Đội ngũ cán bộ khoa học kỹ thuật đa chuyên ngành' },
  { icon: 'M12 2l3 7h7l-5.5 4.5L18 22l-6-4-6 4 1.5-8.5L2 9h7z', text: 'Cơ quan kiểm nghiệm thuốc đầu ngành cả nước' },
]
useSeoMeta({
  title: () => (node.value ? node.value.title : 'Giới thiệu chung') + ' — NIDQC',
  description: 'Giới thiệu về Viện Kiểm nghiệm thuốc Trung ương — cơ quan khoa học kỹ thuật đầu ngành trực thuộc Bộ Y tế, kiểm tra và giám sát chất lượng thuốc trên cả nước.',
})
</script>
<template>
  <div>
    <SectionSubNav :links="gioiThieuLinks" />
    <PageBand title="Giới thiệu chung" :crumbs="['Giới thiệu']" description="Viện Kiểm nghiệm thuốc Trung ương là cơ quan khoa học kỹ thuật đầu ngành, trực thuộc Bộ Y tế, chịu trách nhiệm kiểm tra và giám sát chất lượng thuốc, nguyên liệu làm thuốc và mỹ phẩm trên phạm vi cả nước." />
    <section style="background:#fff;padding:40px 0 60px;"><div class="nidqc-two-col" style="max-width:1280px;margin:0 auto;padding:0 24px;display:grid;grid-template-columns:1fr 340px;gap:44px;align-items:start;">
      <div>
        <p v-if="!node" style="color:#b00020;">Không tìm thấy nội dung.</p>
        <div v-else v-html="node.body" style="font-size:16px;line-height:26px;color:#212529;"></div>
      </div>
      <aside style="background:#F5F8FC;border:1px solid #E4EBF4;padding:26px 24px;">
        <h3 style="font-family:'Lexend',sans-serif;font-weight:700;font-size:14px;letter-spacing:0.6px;text-transform:uppercase;color:#0F3093;margin:0 0 18px;">Thông tin nhanh</h3>
        <ul style="list-style:none;margin:0;padding:0;display:flex;flex-direction:column;gap:16px;">
          <li v-for="(f, i) in quickFacts" :key="i" style="display:flex;gap:12px;align-items:flex-start;">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#0F3093" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="flex-shrink:0;margin-top:2px;"><path :d="f.icon"/></svg>
            <span style="font-size:14px;line-height:20px;color:#334155;">{{ f.text }}</span>
          </li>
        </ul>
      </aside>
    </div></section>
  </div>
</template>
