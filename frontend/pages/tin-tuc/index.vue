<script setup>
// Danh sách Tin tức — PHÂN TRANG PHÍA SERVER (12 tin/trang).
// Trạng thái nằm trong URL: ?cat=<uuid>&trang=N -> SSR đúng, chia sẻ link được, F5 giữ nguyên.
// Không tải toàn bộ rồi lọc phía JS (có >700 tin): mỗi trang chỉ nạp 12 tin qua
// endpoint /api/v1/news/list, trả kèm meta.total nên số trang có ngay trong 1 request.
const PAGE_SIZE = 12
const route = useRoute()
const listTop = ref(null)

const categorySlug = (value) => String(value || '')
  .normalize('NFD')
  .replace(/[\u0300-\u036f]/g, '')
  .replace(/đ/g, 'd')
  .replace(/Đ/g, 'D')
  .toLowerCase()
  .replace(/[^a-z0-9]+/g, '-')
  .replace(/^-|-$/g, '')
const page = computed(() => Math.max(1, parseInt(String(route.query.trang || '1'), 10) || 1))

const mapItem = (n) => ({
  id: n.id,
  title: n.title,
  date: formatDate(n.created),
  tag: n.tag,
  image: newsImageUrl(n.image),
  alias: n.alias,
})

// Chuyên mục — ít đổi, cache dùng chung. Lấy kèm request danh sách đầu tiên.
const { data: categories } = await useCachedData('news-categories', async () => {
  const { categories } = await fetchNewsList({ limit: 1, categories: true })
  return categories ?? []
})
const cat = computed(() => {
  const requested = String(route.query.cat || 'all')
  if (requested === 'all') return 'all'
  return categories.value?.find((item) => item.id === requested || categorySlug(item.label) === requested)?.id || 'all'
})

// 12 tin của trang hiện tại + TỔNG SỐ trong cùng một request (endpoint trả meta.total
// bằng một câu COUNT). Trước đây tổng số phải đếm bằng cách liệt kê hết 705 tin —
// 18 request JSON:API, 5.5s khi cache nguội. Key theo (cat, trang) nên đổi trang tự
// refetch, mỗi trang cache riêng, quay lại tức thì mà vẫn đúng dữ liệu.
const { data: result, pending } = await useCachedData(
  () => `news-list-${cat.value}-p${page.value}`,
  async () => {
    const res = await fetchNewsList({
      cat: cat.value,
      page: page.value - 1,
      limit: PAGE_SIZE,
    })
    return { items: res.data.map(mapItem), total: res.meta.total }
  },
)
const news = computed(() => result.value?.items || [])
const totalPages = computed(() => Math.max(1, Math.ceil((result.value?.total || 0) / PAGE_SIZE)))

const selectCat = (id) => {
  const q = { ...route.query }
  delete q.trang // đổi chuyên mục -> về trang 1
  if (id === 'all') delete q.cat
  else q.cat = categorySlug(categories.value?.find((item) => item.id === id)?.label || id)
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

        <NewsGrid v-else :items="news" :loading="pending" />

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

</style>
