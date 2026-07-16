<script setup>
const route = useRoute()
const { data: node } = await useAsyncData(`news-${route.params.id}`, async () => {
  const { data, included } = await fetchJsonApi('/node/news', {
    'filter[drupal_internal__nid]': route.params.id, include: 'field_image,field_category',
  })
  if (!data.length) return null
  const n = data[0]
  return {
    title: n.attributes.title,
    date: formatDate(n.attributes.field_date || n.attributes.created),
    tag: n.attributes.field_tag || termLabel(n, 'field_category', included),
    image: imageUrl(n, included),
    body: n.attributes.body?.processed || '',
  }
})
// Tóm tắt từ body (bỏ thẻ HTML) cho meta description.
const excerpt = computed(() => {
  const t = (node.value?.body || '').replace(/<[^>]+>/g, ' ').replace(/\s+/g, ' ').trim()
  return t.slice(0, 160) || node.value?.title || ''
})
const site = useRuntimeConfig().public.drupalBase

useSeoMeta({
  title: () => (node.value ? node.value.title : 'Chi tiết tin') + ' — NIDQC',
  description: () => excerpt.value,
  ogTitle: () => node.value?.title,
  ogDescription: () => excerpt.value,
  ogType: 'article',
  ogImage: () => node.value?.image,
})

// JSON-LD Article — cho GEO (AI crawler) hiểu đây là bài viết.
useHead({
  script: [{
    type: 'application/ld+json',
    innerHTML: () => JSON.stringify({
      '@context': 'https://schema.org',
      '@type': 'NewsArticle',
      headline: node.value?.title,
      image: node.value?.image ? [node.value.image] : undefined,
      articleSection: node.value?.tag,
      inLanguage: 'vi',
      publisher: { '@type': 'GovernmentOrganization', name: 'Viện Kiểm nghiệm thuốc Trung ương' },
    }),
  }],
})
</script>
<template>
  <div>
    <PageBand :title="node ? node.title : 'Chi tiết tin'" :crumbs="['Tin tức & Thông báo']" />
    <section style="background:#fff;padding:34px 0 60px;">
      <div style="max-width:860px;margin:0 auto;padding:0 24px;">
        <p v-if="!node" style="color:#b00020;">Không tìm thấy tin.</p>
        <template v-else>
          <div style="display:flex;align-items:center;gap:10px;color:#777;font-size:13px;margin-bottom:18px;">
            <span v-if="node.tag" style="background:#E8F0F7;color:#0F3093;font-size:11px;font-weight:600;text-transform:uppercase;padding:3px 10px;">{{ node.tag }}</span>
            <span>{{ node.date }}</span>
          </div>
          <div v-if="node.image" style="margin-bottom:24px;"><img :src="node.image" style="width:100%;height:auto;"></div>
          <div v-if="node.body" v-html="node.body" style="font-size:16px;line-height:26px;color:#212529;"></div>
          <p v-else style="color:#777;font-style:italic;">Nội dung chi tiết đang được cập nhật.</p>
        </template>
      </div>
    </section>
  </div>
</template>
