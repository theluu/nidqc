<script setup>
const { data: projects } = await useAsyncData('projects', async () => {
  const { data } = await fetchJsonApi('/node/project', { 'filter[status]': 1, 'page[limit]': 50 })
  return data.map((n) => ({ title: n.attributes.title, year: n.attributes.field_year || '', desc: n.attributes.field_description?.processed || n.attributes.field_description?.value || '' }))
})
useSeoMeta({ title: 'Đào tạo & NCKH — NIDQC', description: 'Đào tạo tiến sỹ và các đề tài nghiên cứu khoa học của Viện Kiểm nghiệm thuốc Trung ương.', ogTitle: 'Đào tạo & NCKH — NIDQC', ogDescription: 'Đào tạo tiến sỹ và các đề tài nghiên cứu khoa học của Viện Kiểm nghiệm thuốc Trung ương.' })
</script>
<template>
  <div>
    <PageBand title="Đào tạo & Nghiên cứu khoa học" description="Hoạt động đào tạo tiến sỹ và các đề tài nghiên cứu khoa học của Viện." />
    <section style="background:#fff;padding:34px 0 60px;"><div style="max-width:1280px;margin:0 auto;padding:0 24px;">
      <h2 style="font-family:'Lexend',sans-serif;font-weight:700;font-size:20px;color:#0F3093;margin:0 0 20px;">Đề tài nghiên cứu khoa học</h2>
      <div style="display:flex;flex-direction:column;gap:14px;">
        <div v-for="(p, i) in projects" :key="i" style="background:#F5F8FC;border:1px solid #ECECEC;padding:20px 24px;display:flex;gap:20px;align-items:flex-start;">
          <span v-if="p.year" style="background:#0F3093;color:#fff;font-family:'Lexend',sans-serif;font-weight:700;font-size:14px;padding:8px 14px;flex:0 0 auto;">{{ p.year }}</span>
          <div><h3 style="font-family:'Lexend',sans-serif;font-weight:600;font-size:16px;color:#212529;margin:0 0 6px;">{{ p.title }}</h3><p style="font-size:14px;line-height:21px;color:#495057;margin:0;" v-html="p.desc"></p></div>
        </div>
      </div>
    </div></section>
  </div>
</template>
