<script setup>
const activeCat = ref('all')
const route = useRoute()
const { data } = await useAsyncData('news-list', async () => {
  const cats = await fetchJsonApi('/taxonomy_term/news_category', { sort: 'weight' })
  const { data, included } = await fetchJsonApi('/node/news', {
    'filter[status]': 1, sort: '-field_date,-created', include: 'field_image,field_category', 'page[limit]': 50,
  })
  return {
    categories: cats.data.map((t) => ({ id: t.id, label: t.attributes.name })),
    news: data.map((n) => ({
      id: n.attributes.drupal_internal__nid, title: n.attributes.title,
      date: formatDate(n.attributes.field_date || n.attributes.created),
      tag: n.attributes.field_tag || termLabel(n, 'field_category', included),
      catId: n.relationships?.field_category?.data?.id ?? null,
      image: imageUrl(n, included),
      alias: n.attributes.path?.alias || `/tin-tuc/${n.attributes.drupal_internal__nid}`,
    })),
  }
})
if (route.query.cat) activeCat.value = route.query.cat
const filtered = computed(() => activeCat.value === 'all' ? data.value.news : data.value.news.filter((n) => n.catId === activeCat.value))
useSeoMeta({ title: 'Tin tức & Thông báo — NIDQC', description: 'Thông báo, tin hoạt động, mua sắm - đấu thầu, tuyển dụng và các sự kiện của Viện Kiểm nghiệm thuốc Trung ương.', ogTitle: 'Tin tức & Thông báo — NIDQC', ogDescription: 'Thông báo, tin hoạt động, mua sắm - đấu thầu, tuyển dụng và các sự kiện của Viện Kiểm nghiệm thuốc Trung ương.' })
</script>
<template>
  <div>
    <PageBand title="Tin tức & Thông báo" description="Thông báo, tin hoạt động, mua sắm - đấu thầu và các sự kiện của Viện Kiểm nghiệm thuốc Trung ương." />
    <section style="background:#fff;padding:28px 0 60px;">
      <div style="max-width:1280px;margin:0 auto;padding:0 24px;">
        <div style="display:flex;flex-wrap:wrap;gap:8px;margin-bottom:26px;">
          <button @click="activeCat='all'" :style="`border:1px solid #CCCCCC;padding:7px 16px;font-size:13.5px;cursor:pointer;background:${activeCat==='all'?'#0F3093':'#fff'};color:${activeCat==='all'?'#fff':'#495057'};`">Tất cả</button>
          <button v-for="c in data.categories" :key="c.id" @click="activeCat=c.id" :style="`border:1px solid #CCCCCC;padding:7px 16px;font-size:13.5px;cursor:pointer;background:${activeCat===c.id?'#0F3093':'#fff'};color:${activeCat===c.id?'#fff':'#495057'};`">{{ c.label }}</button>
        </div>
        <p v-if="!filtered.length" style="color:#777;">Không có tin nào trong chuyên mục này.</p>
        <div v-else class="nidqc-grid-4" style="display:grid;grid-template-columns:repeat(4,1fr);gap:18px;">
          <NuxtLink v-for="item in filtered" :key="item.id" :to="item.alias" style="display:block;background:#fff;border:1px solid #ECECEC;text-decoration:none;">
            <div style="width:100%;height:150px;background:#E8F0F7;overflow:hidden;"><img v-if="item.image" :src="item.image" style="width:100%;height:100%;object-fit:cover;"></div>
            <div style="padding:16px 18px 20px;">
              <span v-if="item.tag" style="display:inline-block;background:#E8F0F7;color:#0F3093;font-size:10.5px;font-weight:600;text-transform:uppercase;letter-spacing:0.4px;padding:3px 9px;margin-bottom:10px;">{{ item.tag }}</span>
              <div style="font-size:14px;line-height:20px;color:#212529;font-weight:500;margin-bottom:10px;">{{ item.title }}</div>
              <div style="color:#777;font-size:12px;">{{ item.date }}</div>
            </div>
          </NuxtLink>
        </div>
      </div>
    </section>
  </div>
</template>
