<script setup>
const open = ref(0)
const { data: items } = await useAsyncData('faq', async () => {
  const { data, included } = await fetchJsonApi('/node/faq', { 'filter[status]': 1, include: 'field_group', 'page[limit]': 50 })
  return data.map((n, i) => ({ id: i, q: n.attributes.title, a: n.attributes.field_answer?.processed || n.attributes.field_answer?.value || '', group: termLabel(n, 'field_group', included) }))
})
const grouped = computed(() => {
  const m = {}
  for (const it of items.value) (m[it.group || 'Khác'] ??= []).push(it)
  return m
})
const lienHeLinks = [
  { label: 'Liên hệ', to: '/lien-he' },
  { label: 'Câu hỏi thường gặp', to: '/faq' },
]
useSeoMeta({ title: 'Câu hỏi thường gặp — NIDQC', description: 'Giải đáp thắc mắc về dịch vụ, quy trình và hoạt động kiểm nghiệm của Viện.', ogTitle: 'Câu hỏi thường gặp — NIDQC', ogDescription: 'Giải đáp thắc mắc về dịch vụ, quy trình và hoạt động kiểm nghiệm của Viện.' })
</script>
<template>
  <div>
    <SectionSubNav :links="lienHeLinks" />
    <PageBand title="Câu hỏi thường gặp" :crumbs="['Liên hệ & hỗ trợ']" description="Giải đáp các thắc mắc thường gặp về dịch vụ, quy trình và hoạt động của Viện." />
    <section style="background:#fff;padding:34px 0 70px;">
      <div style="max-width:900px;margin:0 auto;padding:0 24px;">
        <div v-for="(list, group) in grouped" :key="group" style="margin-bottom:34px;">
          <h2 style="font-family:'Lexend',sans-serif;font-weight:700;font-size:18px;color:#0F3093;margin:0 0 14px;padding-left:12px;border-left:4px solid #0F3093;">{{ group }}</h2>
          <div v-for="it in list" :key="it.id" style="border:1px solid #ECECEC;margin-bottom:10px;">
            <button @click="open = open === it.id ? null : it.id" style="width:100%;text-align:left;background:#F5F8FC;border:none;padding:16px 20px;font-size:15px;font-weight:600;color:#212529;cursor:pointer;display:flex;justify-content:space-between;align-items:center;gap:16px;">
              <span>{{ it.q }}</span>
              <span :style="`transition:transform .2s;transform:rotate(${open===it.id?45:0}deg);color:#0F3093;font-size:22px;flex:0 0 auto;`">+</span>
            </button>
            <div v-show="open === it.id" style="padding:16px 20px;font-size:15px;line-height:24px;color:#495057;" v-html="it.a"></div>
          </div>
        </div>
      </div>
    </section>
  </div>
</template>
