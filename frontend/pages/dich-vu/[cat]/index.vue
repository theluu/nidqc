<script setup>
// Danh sách bài viết của MỘT dịch vụ: /dich-vu/<danh-muc>[?trang=N]
//
// Feedback 08/2026: bấm vào một dịch vụ ở trang chủ phải ra danh sách bài viết
// (như /tin-hoat-dong của NIFC) chứ không phải một trang tĩnh. Route này có đoạn
// tĩnh `dich-vu` nên Nuxt ưu tiên hơn pages/[section]/[slug].vue — các trang tĩnh
// hai cấp khác (/danh-muc-nang-luc/…, /hoat-dong-chuyen-mon/…) vẫn đi đường cũ.
//
// Trạng thái nằm trong URL (?trang=N) -> SSR đúng, chia sẻ link được, F5 giữ nguyên.
// Phân trang phía server, 12 bài/trang, giống hệt /tin-tuc.
const PAGE_SIZE = 12
const route = useRoute()
const reqUrl = useRequestURL()
const listTop = ref(null)

const cat = computed(() => String(route.params.cat))
const page = computed(() => Math.max(1, parseInt(String(route.query.trang || '1'), 10) || 1))

const mapItem = (n) => ({
  id: n.id,
  title: n.title,
  date: formatDate(n.created),
  tag: n.tag,
  image: newsImageUrl(n.image),
  alias: n.alias,
})

// MỘT request cho cả ba thứ trang cần: bài của trang hiện tại, tổng số bài và
// danh sách dịch vụ để vẽ thanh chuyển dịch vụ. Key theo (cat, trang) nên đổi
// dịch vụ hoặc đổi trang là refetch, quay lại thì lấy từ cache.
const { data: result, status, error, pending } = await useCachedData(
  () => `service-list-${cat.value}-p${page.value}`,
  async () => {
    const res = await fetchServiceList({
      cat: cat.value,
      page: page.value - 1,
      limit: PAGE_SIZE,
      categories: true,
    })
    if (!res) return null

    return {
      items: res.data.map(mapItem),
      total: res.meta.total,
      category: res.category,
      categories: res.categories || [],
    }
  },
)

const notFound = () => createError({ statusCode: 404, statusMessage: 'Không tìm thấy dịch vụ', fatal: true })

// SSR kết luận ngay: handler đã chạy xong, không có dữ liệu nghĩa là slug dịch vụ
// không tồn tại -> trả đúng mã 404 cho trình duyệt và bot.
if (import.meta.server && !result.value) {
  throw notFound()
}

// Client TUYỆT ĐỐI không được kết luận 404 khi fetch chưa xong — xem ghi chú dài ở
// pages/[slug].vue: useAsyncData trả null ngay lúc hydrate nếu payload rỗng.
if (import.meta.client) {
  watch(status, (value) => {
    if (value === 'success' && !result.value) showError(notFound())
    else if (value === 'error' && error.value) showError(error.value)
  }, { immediate: true })
}

const category = computed(() => result.value?.category || null)
const categories = computed(() => result.value?.categories || [])
const posts = computed(() => result.value?.items || [])
const totalPages = computed(() => Math.max(1, Math.ceil((result.value?.total || 0) / PAGE_SIZE)))

const changePage = (n) => {
  navigateTo({ query: { ...route.query, trang: String(n) } })
  if (import.meta.client) {
    listTop.value?.scrollIntoView({ behavior: 'smooth', block: 'start' })
  }
}

// Mô tả cho thẻ meta: phần giới thiệu dịch vụ đã chuyển từ trang tĩnh cũ về mô tả
// của term, nên lấy chính nó (bỏ thẻ HTML) làm description.
const excerpt = computed(() => {
  const text = (category.value?.description || '').replace(/<[^>]+>/g, ' ').replace(/\s+/g, ' ').trim()
  return text.slice(0, 160)
})

useSeoMeta({
  title: () => `${category.value?.label || 'Dịch vụ'} — Viện Kiểm nghiệm thuốc Trung ương`,
  description: () => excerpt.value,
  ogTitle: () => category.value?.label,
  ogDescription: () => excerpt.value,
  ogUrl: () => reqUrl.href,
})
useHead({ link: [{ rel: 'canonical', href: () => reqUrl.href }] })
</script>

<template>
  <div>
    <PageBand :title="category ? category.label : 'Dịch vụ'" :crumbs="['Dịch vụ']" />

    <section class="svc">
      <div ref="listTop" class="svc__wrap">
        <!-- Chuyển nhanh sang dịch vụ khác: cùng dạng pill với bộ lọc chuyên mục
             ở /tin-tuc để hai trang danh sách trông như một hệ thống. -->
        <nav v-if="categories.length" class="filters" aria-label="Các dịch vụ">
          <NuxtLink
            v-for="c in categories" :key="c.id"
            :to="c.url"
            class="pill" :class="{ 'is-active': c.slug === cat }"
          >{{ c.label }}</NuxtLink>
        </nav>

        <!-- Giới thiệu dịch vụ (mô tả của danh mục) — nội dung của trang tĩnh
             /dich-vu/… cũ, giữ lại chứ không mất khi danh sách thay chỗ. -->
        <div v-if="category && category.description" v-html="category.description" class="svc__intro nidqc-article-body"></div>

        <p v-if="!posts.length" class="empty">Dịch vụ này chưa có bài viết nào.</p>

        <NewsGrid v-else :items="posts" :loading="pending" />

        <Pagination :current="page" :total="totalPages" @change="changePage" />

        <div class="svc__back">
          <NuxtLink to="/dich-vu">
            <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="m12 19-7-7 7-7M19 12H5" /></svg>
            Quay lại Dịch vụ
          </NuxtLink>
        </div>
      </div>
    </section>
  </div>
</template>

<style scoped>
.svc {
  background: #fff;
  padding: 28px 0 64px;
}
.svc__wrap {
  max-width: 1280px;
  margin: 0 auto;
  padding: 0 24px;
  scroll-margin-top: 80px;
}

/* Thanh chuyển dịch vụ */
.filters {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
  margin-bottom: 24px;
}
.pill {
  border: 1px solid #D8DEE6;
  background: #fff;
  color: #495057;
  padding: 8px 16px;
  font-size: 13.5px;
  font-weight: 500;
  border-radius: 999px;
  text-decoration: none;
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

.svc__intro {
  background: #F5F8FC;
  border: 1px solid #E4E9F0;
  border-left: 4px solid #0F3093;
  padding: 18px 22px;
  margin-bottom: 28px;
  font-size: 15px;
  line-height: 24px;
  color: #495057;
}
.svc__intro :deep(p:last-child) {
  margin-bottom: 0;
}

.empty {
  color: #777;
  padding: 24px 0;
}

.svc__back {
  margin-top: 32px;
  padding-top: 22px;
  border-top: 1px solid #ECECEC;
}
.svc__back a {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  color: #0F3093;
  font-weight: 600;
  font-size: 14.5px;
  text-decoration: none;
}

@media (prefers-reduced-motion: reduce) {
  .pill { transition: none; }
}
</style>
