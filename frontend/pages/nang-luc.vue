<script setup>
const tab = ref('equipment')
const { data } = await useAsyncData('capacity', async () => {
  const [e, c] = await Promise.all([
    fetchJsonApi('/node/equipment', { 'filter[status]': 1, 'page[limit]': 50 }),
    fetchJsonApi('/node/certificate', { 'filter[status]': 1, 'page[limit]': 50 }),
  ])
  const map = (d) => d.map((n) => ({ title: n.attributes.title, desc: n.attributes.field_description?.processed || n.attributes.field_description?.value || '' }))
  return { equipment: map(e.data), certs: map(c.data) }
})
useHead({ title: 'Năng lực — NIDQC' })
</script>
<template>
  <div>
    <PageBand title="Năng lực" :crumbs="['Giới thiệu']" description="Trang thiết bị hiện đại và các chứng nhận, công nhận năng lực của Viện." />
    <section style="background:#fff;padding:28px 0 60px;"><div style="max-width:1280px;margin:0 auto;padding:0 24px;">
      <div style="display:flex;gap:6px;border-bottom:1px solid #ECECEC;margin-bottom:26px;">
        <button @click="tab='equipment'" :style="`background:none;border:none;border-bottom:2px solid ${tab==='equipment'?'#0F3093':'transparent'};padding:12px 18px;font-size:14px;font-weight:600;cursor:pointer;color:${tab==='equipment'?'#0F3093':'#777'};`">Trang thiết bị</button>
        <button @click="tab='certs'" :style="`background:none;border:none;border-bottom:2px solid ${tab==='certs'?'#0F3093':'transparent'};padding:12px 18px;font-size:14px;font-weight:600;cursor:pointer;color:${tab==='certs'?'#0F3093':'#777'};`">Chứng nhận</button>
      </div>
      <div class="nidqc-grid-2" style="display:grid;grid-template-columns:repeat(2,1fr);gap:18px;">
        <div v-for="(item, i) in (tab==='equipment'?data.equipment:data.certs)" :key="i" style="background:#F5F5F5;border:1px solid #CCCCCC;padding:20px 24px;">
          <h3 style="font-family:'Lexend',sans-serif;font-weight:600;font-size:16px;color:#0F3093;margin:0 0 8px;">{{ item.title }}</h3>
          <p style="font-size:14px;line-height:21px;color:#495057;margin:0;" v-html="item.desc"></p>
        </div>
      </div>
    </div></section>
  </div>
</template>
