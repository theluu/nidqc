<script setup>
const { data: depts } = await useCachedData('depts', async () => {
  const { data } = await fetchJsonApi('/node/department', { 'filter[status]': 1, 'page[limit]': 50 })
  return data.map((n) => ({ title: n.attributes.title, desc: n.attributes.field_description?.processed || n.attributes.field_description?.value || '' }))
})
// Sub-nav nhóm "Giới thiệu" (theo mega-menu Giới thiệu trong layout).
const gioiThieuLinks = [
  { label: 'Giới thiệu chung', to: '/gioi-thieu-chung' },
  { label: 'Chính sách chất lượng', to: '/chinh-sach-chat-luong' },
  { label: 'Năng lực', to: '/nang-luc' },
  { label: 'Cơ cấu tổ chức', to: '/co-cau-to-chuc' },
]
useSeoMeta({ title: 'Cơ cấu tổ chức — NIDQC', description: 'Sơ đồ tổ chức, các phòng, khoa và trung tâm trực thuộc Viện Kiểm nghiệm thuốc Trung ương.', ogTitle: 'Cơ cấu tổ chức — NIDQC', ogDescription: 'Sơ đồ tổ chức, các phòng, khoa và trung tâm trực thuộc Viện Kiểm nghiệm thuốc Trung ương.' })
</script>
<template>
  <div>
    <SectionSubNav :links="gioiThieuLinks" />
    <PageBand title="Cơ cấu tổ chức" :crumbs="['Giới thiệu']" description="Bộ máy tổ chức của Viện Kiểm nghiệm thuốc Trung ương gồm Ban lãnh đạo và các phòng, khoa, trung tâm chuyên môn – nghiệp vụ." />

    <!-- Sơ đồ tổ chức -->
    <section style="background:#fff;padding:44px 0 20px;"><div style="max-width:1280px;margin:0 auto;padding:0 24px;">
      <h2 style="font-family:'Lexend',sans-serif;font-weight:700;font-size:22px;color:#212529;text-align:center;margin:0 0 34px;">Sơ đồ tổ chức</h2>
      <div style="display:flex;flex-direction:column;align-items:center;">
        <!-- Viện trưởng -->
        <div style="background:#0F3093;color:#fff;font-family:'Lexend',sans-serif;font-weight:700;font-size:15px;padding:14px 40px;border-radius:2px;">Viện trưởng</div>
        <div style="width:1px;height:26px;background:#B9C6DE;"></div>
        <!-- Phó viện trưởng -->
        <div class="nidqc-org-vice" style="display:flex;gap:80px;justify-content:center;position:relative;">
          <div style="position:absolute;top:-13px;left:25%;right:25%;height:1px;background:#B9C6DE;"></div>
          <div style="background:#EAF0F8;border:1px solid #C6D4EA;color:#0F3093;font-weight:600;font-size:13.5px;padding:13px 22px;border-radius:2px;text-align:center;">Phó Viện trưởng phụ trách chuyên môn</div>
          <div style="background:#EAF0F8;border:1px solid #C6D4EA;color:#0F3093;font-weight:600;font-size:13.5px;padding:13px 22px;border-radius:2px;text-align:center;">Phó Viện trưởng phụ trách hành chính</div>
        </div>
        <div style="width:1px;height:26px;background:#B9C6DE;"></div>
        <div style="width:80%;max-width:1000px;height:1px;background:#B9C6DE;margin-bottom:22px;"></div>
        <!-- Đơn vị trực thuộc (từ dữ liệu department) -->
        <div class="nidqc-org-units nidqc-grid-4" style="width:100%;display:grid;grid-template-columns:repeat(4,1fr);gap:14px;">
          <div v-for="(d, i) in depts" :key="i" style="background:#fff;border:1px solid #D6DEE9;color:#212529;font-size:13px;font-weight:500;padding:14px 12px;border-radius:2px;text-align:center;">{{ d.title }}</div>
        </div>
      </div>
    </div></section>

    <!-- Các phòng, khoa, trung tâm -->
    <section style="background:#fff;padding:40px 0 60px;"><div style="max-width:1280px;margin:0 auto;padding:0 24px;">
      <h2 style="font-family:'Lexend',sans-serif;font-weight:700;font-size:22px;color:#212529;margin:0 0 26px;">Các phòng, khoa, trung tâm</h2>
      <div class="nidqc-grid-3" style="display:grid;grid-template-columns:repeat(3,1fr);gap:20px;">
        <div v-for="(d, i) in depts" :key="i" style="background:#fff;border:1px solid #ECECEC;padding:24px;">
          <div style="width:42px;height:42px;background:#EAF0F8;border-radius:6px;display:flex;align-items:center;justify-content:center;margin-bottom:16px;">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#0F3093" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="7" width="20" height="14" rx="2"/><path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"/></svg>
          </div>
          <h3 style="font-family:'Lexend',sans-serif;font-weight:600;font-size:16.5px;color:#0F3093;margin:0 0 8px;">{{ d.title }}</h3>
          <p style="font-size:14px;line-height:21px;color:#495057;margin:0;" v-html="d.desc"></p>
        </div>
      </div>
    </div></section>
  </div>
</template>
