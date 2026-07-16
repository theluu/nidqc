<script setup>
const activeGroup = ref('all')
const { data } = await useAsyncData('documents', async () => {
  const g = await fetchJsonApi('/taxonomy_term/document_group', { sort: 'weight' })
  const { data, included } = await fetchJsonApi('/node/document', { 'filter[status]': 1, sort: '-created', include: 'field_group', 'page[limit]': 50 })
  return {
    groups: g.data.map((t) => ({ id: t.id, label: t.attributes.name })),
    docs: data.map((n) => ({
      id: n.attributes.drupal_internal__nid, title: n.attributes.title,
      meta: n.attributes.field_meta || '', groupId: n.relationships?.field_group?.data?.id ?? null,
    })),
  }
})
const filtered = computed(() => activeGroup.value === 'all' ? data.value.docs : data.value.docs.filter((d) => d.groupId === activeGroup.value))
useHead({ title: 'Văn bản - Tài liệu — NIDQC' })
</script>
<template>
  <div>
    <PageBand title="Văn bản - Tài liệu" description="Văn bản pháp quy và tài liệu chuyên môn phục vụ hoạt động kiểm nghiệm." />
    <section style="background:#fff;padding:28px 0 60px;">
      <div style="max-width:1280px;margin:0 auto;padding:0 24px;">
        <div style="display:flex;gap:6px;border-bottom:1px solid #ECECEC;margin-bottom:26px;">
          <button @click="activeGroup='all'" :style="`background:none;border:none;border-bottom:2px solid ${activeGroup==='all'?'#0F3093':'transparent'};padding:12px 18px;font-size:14px;font-weight:600;cursor:pointer;color:${activeGroup==='all'?'#0F3093':'#777'};`">Tất cả</button>
          <button v-for="g in data.groups" :key="g.id" @click="activeGroup=g.id" :style="`background:none;border:none;border-bottom:2px solid ${activeGroup===g.id?'#0F3093':'transparent'};padding:12px 18px;font-size:14px;font-weight:600;cursor:pointer;color:${activeGroup===g.id?'#0F3093':'#777'};`">{{ g.label }}</button>
        </div>
        <div class="nidqc-grid-3" style="display:grid;grid-template-columns:repeat(3,1fr);gap:18px;">
          <div v-for="d in filtered" :key="d.id" style="background:#F5F5F5;border:1px solid #CCCCCC;padding:20px;display:flex;flex-direction:column;gap:12px;">
            <span style="width:40px;height:40px;background:#fff;border-radius:8px;display:flex;align-items:center;justify-content:center;color:#777;"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6"/></svg></span>
            <h3 style="font-weight:600;font-size:14.5px;line-height:20px;color:#495057;margin:0;">{{ d.title }}</h3>
            <span style="font-size:12.5px;color:#777;margin-top:auto;">{{ d.meta }}</span>
          </div>
        </div>
      </div>
    </section>
  </div>
</template>
