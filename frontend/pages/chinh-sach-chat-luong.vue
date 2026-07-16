<script setup>
const route = useRoute()
const { data: node } = await useAsyncData('page-' + route.path, async () => {
  const { data } = await fetchJsonApi('/node/page', { 'filter[path.alias]': route.path, 'page[limit]': 1 })
  if (!data.length) return null
  return { title: data[0].attributes.title, body: data[0].attributes.body?.processed || data[0].attributes.body?.value || '' }
})
useHead({ title: () => (node.value ? node.value.title : 'Trang') + ' — NIDQC' })
</script>
<template>
  <div>
    <PageBand :title="node ? node.title : 'Trang'" :crumbs="['Giới thiệu']" />
    <section style="background:#fff;padding:34px 0 60px;"><div style="max-width:900px;margin:0 auto;padding:0 24px;">
      <p v-if="!node" style="color:#b00020;">Không tìm thấy trang.</p>
      <div v-else v-html="node.body" style="font-size:16px;line-height:26px;color:#212529;"></div>
    </div></section>
  </div>
</template>
