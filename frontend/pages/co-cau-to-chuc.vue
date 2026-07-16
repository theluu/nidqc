<script setup>
const { data: depts } = await useAsyncData('depts', async () => {
  const { data } = await fetchJsonApi('/node/department', { 'filter[status]': 1, 'page[limit]': 50 })
  return data.map((n) => ({ title: n.attributes.title, desc: n.attributes.field_description?.processed || n.attributes.field_description?.value || '' }))
})
useSeoMeta({ title: 'Cơ cấu tổ chức — NIDQC', description: 'Các phòng, khoa và đơn vị trực thuộc Viện Kiểm nghiệm thuốc Trung ương.', ogTitle: 'Cơ cấu tổ chức — NIDQC', ogDescription: 'Các phòng, khoa và đơn vị trực thuộc Viện Kiểm nghiệm thuốc Trung ương.' })
</script>
<template>
  <div>
    <PageBand title="Cơ cấu tổ chức" :crumbs="['Giới thiệu']" description="Các phòng, khoa và đơn vị trực thuộc Viện Kiểm nghiệm thuốc Trung ương." />
    <section style="background:#fff;padding:34px 0 60px;"><div style="max-width:1280px;margin:0 auto;padding:0 24px;">
      <div class="nidqc-grid-2" style="display:grid;grid-template-columns:repeat(2,1fr);gap:18px;">
        <div v-for="(d, i) in depts" :key="i" style="background:#F5F8FC;border:1px solid #ECECEC;border-left:4px solid #0F3093;padding:20px 24px;">
          <h3 style="font-family:'Lexend',sans-serif;font-weight:600;font-size:16.5px;color:#0F3093;margin:0 0 8px;">{{ d.title }}</h3>
          <p style="font-size:14px;line-height:21px;color:#495057;margin:0;" v-html="d.desc"></p>
        </div>
      </div>
    </div></section>
  </div>
</template>
