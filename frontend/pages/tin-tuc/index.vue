<script setup>
// Danh sách Tin tức — PHÂN TRANG PHÍA SERVER (12 tin/trang).
// Trạng thái nằm trong URL: ?cat=<uuid>&trang=N -> SSR đúng, chia sẻ link được, F5 giữ nguyên.
// Không tải toàn bộ rồi lọc phía JS (có >700 tin): mỗi trang chỉ nạp 12 tin, lọc chuyên
// mục bằng filter[field_category.id]; tổng số trang tính từ countNews (đếm id-only).
const PAGE_SIZE = 12
const route = useRoute()
const listTop = ref(null)

const cat = computed(() => String(route.query.cat || 'all'))
const page = computed(() => Math.max(1, parseInt(String(route.query.trang || '1'), 10) || 1))

const mapItem = (n, included) => ({
  id: n.attributes.drupal_internal__nid,
  title: n.attributes.title,
  date: formatDate(n.attributes.field_date || n.attributes.created),
  tag: n.attributes.field_tag || termLabel(n, 'field_category', included),
  image: imageUrl(n, included),
  alias: n.attributes.path?.alias || `/tin-tuc/${n.attributes.drupal_internal__nid}`,
})

// Chuyên mục — ít đổi, cache dùng chung.
const { data: categories } = await useCachedData('news-categories', async () => {
  const { data } = await fetchJsonApi('/taxonomy_term/news_category', { sort: 'weight' })
  return data.map((t) => ({ id: t.id, label: t.attributes.name }))
})

// Tổng số tin theo chuyên mục -> số trang. Key chỉ phụ thuộc cat (đổi trang không đếm lại).
const { data: total } = await useCachedData(
  () => `news-count-${cat.value}`,
  () => countNews(cat.value),
)
const totalPages = computed(() => Math.max(1, Math.ceil((total.value || 0) / PAGE_SIZE)))

// 12 tin của trang hiện tại. Key theo (cat, trang) -> đổi trang tự refetch, mỗi trang
// cache riêng nên quay lại tức thì mà vẫn đúng dữ liệu.
const { data: news, pending } = await useCachedData(
  () => `news-list-${cat.value}-p${page.value}`,
  async () => {
    const params = {
      'filter[status]': 1, sort: '-field_date,-created', include: 'field_image,field_category',
      'page[limit]': PAGE_SIZE, 'page[offset]': (page.value - 1) * PAGE_SIZE,
    }
    if (cat.value !== 'all') params['filter[field_category.id]'] = cat.value
    const { data, included } = await fetchJsonApi('/node/news', params)
    return data.map((n) => mapItem(n, included))
  },
)

const selectCat = (id) => {
  const q = { ...route.query }
  delete q.trang // đổi chuyên mục -> về trang 1
  if (id === 'all') delete q.cat
  else q.cat = id
  navigateTo({ query: q })
}

const changePage = (n) => {
  navigateTo({ query: { ...route.query, trang: String(n) } })
  if (import.meta.client) {
    listTop.value?.scrollIntoView({ behavior: 'smooth', block: 'start' })
  }
}

useSeoMeta({
  title: 'Tin tức & Thông báo — NIDQC',
  description: 'Thông báo, tin hoạt động, mua sắm - đấu thầu, tuyển dụng và các sự kiện của Viện Kiểm nghiệm thuốc Trung ương.',
  ogTitle: 'Tin tức & Thông báo — NIDQC',
  ogDescription: 'Thông báo, tin hoạt động, mua sắm - đấu thầu, tuyển dụng và các sự kiện của Viện Kiểm nghiệm thuốc Trung ương.',
})
</script>

<template>
  <div>
    <PageBand title="Tin tức & Thông báo" description="Thông báo, tin hoạt động, mua sắm - đấu thầu và các sự kiện của Viện Kiểm nghiệm thuốc Trung ương." />

    <section class="news">
      <div ref="listTop" class="news__wrap">
        <!-- Bộ lọc chuyên mục dạng pill -->
        <div class="filters">
          <button class="pill" :class="{ 'is-active': cat === 'all' }" @click="selectCat('all')">Tất cả</button>
          <button
            v-for="c in categories" :key="c.id"
            class="pill" :class="{ 'is-active': cat === c.id }"
            @click="selectCat(c.id)"
          >{{ c.label }}</button>
        </div>

        <p v-if="!news || !news.length" class="empty">Không có tin nào trong chuyên mục này.</p>

        <div v-else class="grid" :class="{ 'is-loading': pending }">
          <NuxtLink v-for="item in news" :key="item.id" :to="item.alias" class="card">
            <div class="card__thumb">
              <img v-if="item.image" :src="item.image" :alt="item.title" loading="lazy">
            </div>
            <div class="card__body">
              <span v-if="item.tag" class="card__tag">{{ item.tag }}</span>
              <h3 class="card__title">{{ item.title }}</h3>
              <div class="card__date">
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><rect x="3" y="4" width="18" height="18" rx="2" /><path d="M16 2v4M8 2v4M3 10h18" /></svg>
                {{ item.date }}
              </div>
            </div>
          </NuxtLink>
        </div>

        <Pagination :current="page" :total="totalPages" @change="changePage" />
      </div>
    </section>
  </div>
</template>

<style scoped>
.news {
  background: #fff;
  padding: 28px 0 64px;
}
.news__wrap {
  max-width: 1280px;
  margin: 0 auto;
  padding: 0 24px;
  scroll-margin-top: 80px;
}

/* Bộ lọc */
.filters {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
  margin-bottom: 28px;
}
.pill {
  border: 1px solid #D8DEE6;
  background: #fff;
  color: #495057;
  padding: 8px 16px;
  font-size: 13.5px;
  font-weight: 500;
  border-radius: 999px;
  cursor: pointer;
  transition: border-color .15s, background .15s, color .15s;
}
.pill:hover:not(.is-active) {
  border-color: #0F3093;
  color: #0F3093;
}
.pill.is-active {
  background: #0F3093;
  border-color: #0F3093;
  color: #fff;
}

.empty {
  color: #777;
  padding: 24px 0;
}

/* Lưới thẻ tin */
.grid {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 20px;
  transition: opacity .2s;
}
.grid.is-loading {
  opacity: .5;
}
@media (max-width: 1024px) {
  .grid { grid-template-columns: repeat(3, 1fr); }
}
@media (max-width: 768px) {
  .grid { grid-template-columns: repeat(2, 1fr); }
}
@media (max-width: 480px) {
  .grid { grid-template-columns: 1fr; }
}

.card {
  display: flex;
  flex-direction: column;
  background: #fff;
  border: 1px solid #ECECEC;
  border-radius: 8px;
  overflow: hidden;
  text-decoration: none;
  transition: transform .18s, box-shadow .18s, border-color .18s;
}
.card:hover {
  transform: translateY(-3px);
  box-shadow: 0 8px 20px rgba(15, 48, 147, 0.12);
  border-color: #D8DEE6;
}
.card__thumb {
  aspect-ratio: 16 / 10;
  background: #E8F0F7;
  overflow: hidden;
}
.card__thumb img {
  width: 100%;
  height: 100%;
  object-fit: cover;
  transition: transform .3s;
}
.card:hover .card__thumb img {
  transform: scale(1.05);
}
.card__body {
  display: flex;
  flex-direction: column;
  flex: 1;
  padding: 16px 18px 18px;
}
.card__tag {
  align-self: flex-start;
  background: #E8F0F7;
  color: #0F3093;
  font-size: 10.5px;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.4px;
  padding: 3px 9px;
  border-radius: 4px;
  margin-bottom: 10px;
}
.card__title {
  font-size: 14px;
  line-height: 20px;
  color: #212529;
  font-weight: 500;
  margin: 0 0 12px;
  display: -webkit-box;
  -webkit-line-clamp: 3;
  -webkit-box-orient: vertical;
  overflow: hidden;
}
.card__date {
  display: flex;
  align-items: center;
  gap: 6px;
  margin-top: auto;
  color: #777;
  font-size: 12px;
}
</style>
