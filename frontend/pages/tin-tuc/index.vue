<script setup>
// Tin tức & Thông báo — MỘT trang, HAI dạng hiển thị (feedback khách 22/08):
//
//   /tin-tuc              -> TRANG TỔNG HỢP, mỗi chuyên mục một khối tiêu đề + danh
//                            sách tiêu đề bài, giống https://nifc.gov.vn/tin-tuc-new
//   /tin-tuc?cat=thong-bao -> DANH SÁCH một chuyên mục: ảnh + tiêu đề + ngày + trích
//                            dẫn, có phân trang, giống https://nifc.gov.vn/noi-bat
//
// Gộp vào một route chứ không tách thành hai trang: bộ lọc pill vẫn chuyển qua lại
// được bằng query string, link cũ /tin-tuc?cat=... không hỏng, và SEO giữ nguyên
// một canonical cho mỗi chuyên mục.
//
// Trạng thái nằm trong URL (?cat=&trang=N) -> SSR đúng, chia sẻ link được, F5 giữ
// nguyên. Không tải toàn bộ rồi lọc phía JS (có >700 tin).
const PAGE_SIZE = 12
// Số tin mỗi khối ở trang tổng hợp: 1 tin đầu có ảnh lớn + 5 tin chỉ tiêu đề.
// Nhiều hơn thì trang dài lê thê mà vẫn không thay được trang danh sách của chính
// chuyên mục đó.
const HUB_SIZE = 6

// Mỗi chuyên mục một màu nhấn, gán theo THỨ TỰ khối chứ không theo tên: admin thêm
// hay đổi tên chuyên mục thì màu vẫn chạy đều, không có khối nào rơi vào mặc định.
// Sáu màu đủ tương phản với nhau nhưng vẫn giữ tông cơ quan nhà nước — không dùng
// màu bão hoà cao.
const ACCENTS = ['#0F3093', '#C0392B', '#B7791F', '#1E7A5E', '#6B3FA0', '#0E7490']

const route = useRoute()
const listTop = ref(null)

// categorySlug dùng chung với trang chủ — xem composables/useNewsDetail.ts.
const page = computed(() => Math.max(1, parseInt(String(route.query.trang || '1'), 10) || 1))

const mapItem = (n) => ({
  id: n.id,
  title: n.title,
  date: formatDate(n.created),
  tag: n.tag,
  // Thẻ tin dạng hàng ngang có chỗ cho trích dẫn 2 dòng (feedback 21/08).
  summary: n.summary || '',
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
const isHub = computed(() => cat.value === 'all')
const catLabel = computed(() => categories.value?.find((item) => item.id === cat.value)?.label || '')

// ===== Dạng 1: trang tổng hợp =====
// Mỗi chuyên mục một request nhỏ (limit 6). Chỉ chạy khi KHÔNG chọn chuyên mục —
// useCachedData nhận key là hàm nên đổi ?cat= là tự bỏ qua, không tốn 6 request
// thừa mỗi lần người dùng mở một chuyên mục.
const { data: hub } = await useCachedData(
  () => (isHub.value ? 'news-hub' : 'news-hub--bo-qua'),
  async () => {
    // Đang mở một chuyên mục thì khỏi gọi 6 request cho trang tổng hợp.
    if (!isHub.value) return []
    const list = categories.value ?? []
    const results = await Promise.all(
      list.map((c) => fetchNewsList({ cat: c.label, limit: HUB_SIZE })),
    )
    return list
      .map((c, i) => {
        const items = results[i].data.map(mapItem)
        return {
          label: c.label,
          to: `/tin-tuc?cat=${categorySlug(c.label)}`,
          total: c.count,
          // Tin mới nhất tách riêng để hiện ảnh lớn; phần còn lại chỉ tiêu đề.
          lead: items[0] || null,
          rest: items.slice(1),
          items,
        }
      })
      // Chuyên mục rỗng thì bỏ hẳn khối: một tiêu đề với khoảng trắng bên dưới
      // trông như trang lỗi.
      .filter((block) => block.items.length > 0)
  },
)

// ===== Dạng 2: danh sách một chuyên mục =====
// 12 tin của trang hiện tại + TỔNG SỐ trong cùng một request (endpoint trả meta.total
// bằng một câu COUNT). Key theo (cat, trang) nên đổi trang tự refetch, mỗi trang
// cache riêng, quay lại tức thì mà vẫn đúng dữ liệu.
const { data: result, pending } = await useCachedData(
  () => (isHub.value ? 'news-list--bo-qua' : `news-list-${cat.value}-p${page.value}`),
  async () => {
    // Trang tổng hợp không dùng danh sách phân trang này.
    if (isHub.value) return { items: [], total: 0 }
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

// Tiêu đề trang bám theo chuyên mục đang xem — trang mẫu /noi-bat cũng lấy chính
// tên chuyên mục làm tiêu đề chứ không dùng một tiêu đề chung.
const pageTitle = computed(() => (isHub.value ? 'Tin tức & Thông báo' : catLabel.value))
const pageDesc = computed(() => (isHub.value
  ? 'Thông báo, tin hoạt động, mua sắm - đấu thầu và các sự kiện của Viện Kiểm nghiệm thuốc Trung ương.'
  : `Các bài viết thuộc chuyên mục ${catLabel.value} của Viện Kiểm nghiệm thuốc Trung ương.`))

useSeoMeta({
  title: () => `${pageTitle.value} — NIDQC`,
  description: () => pageDesc.value,
  ogTitle: () => `${pageTitle.value} — NIDQC`,
  ogDescription: () => pageDesc.value,
})
</script>

<template>
  <div>
    <!-- Chuyên mục nằm sau "Tin tức & Thông báo" trên breadcrumb, không thay thế nó:
         người đọc phải thấy đường quay về trang tổng hợp. -->
    <PageBand
      :title="pageTitle"
      :description="pageDesc"
      :crumbs="isHub ? [] : ['Tin tức & Thông báo']"
    />

    <section class="news">
      <div ref="listTop" class="news__wrap">
        <!-- Bộ lọc chuyên mục dạng pill -->
        <div class="filters">
          <button class="pill" :class="{ 'is-active': isHub }" @click="selectCat('all')">Tất cả</button>
          <button
            v-for="c in categories" :key="c.id"
            class="pill" :class="{ 'is-active': cat === c.id }"
            @click="selectCat(c.id)"
          >{{ c.label }}</button>
        </div>

        <!-- ===== Dạng 1: TRANG TỔNG HỢP ===== -->
        <div v-if="isHub" class="hub">
          <section
            v-for="(block, bi) in hub || []" :key="block.label"
            class="hub__block"
            :style="{ '--accent': ACCENTS[bi % ACCENTS.length] }"
          >
            <div class="hub__head">
              <h2 class="hub__head-title">
                <NuxtLink :to="block.to" class="hub__head-link">{{ block.label }}</NuxtLink>
              </h2>
              <NuxtLink :to="block.to" class="hub__more">
                Xem tất cả<span v-if="block.total" class="hub__count">{{ block.total }}</span>
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M13 6l6 6-6 6" /></svg>
              </NuxtLink>
            </div>

            <!-- Tin mới nhất: ảnh lớn + tiêu đề + trích dẫn -->
            <NuxtLink v-if="block.lead" :to="block.lead.alias" class="hub__lead">
              <span class="hub__lead-media">
                <img v-if="block.lead.image" :src="block.lead.image" :alt="block.lead.title" loading="lazy">
                <span class="hub__lead-tag">{{ block.label }}</span>
              </span>
              <span class="hub__lead-title">{{ block.lead.title }}</span>
              <span class="hub__lead-meta">
                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><rect x="3" y="4" width="18" height="18" rx="2" /><path d="M16 2v4M8 2v4M3 10h18" /></svg>
                {{ block.lead.date }}
              </span>
              <span v-if="block.lead.summary" class="hub__lead-desc">{{ block.lead.summary }}</span>
            </NuxtLink>

            <!-- Các tin sau: chỉ tiêu đề + ngày, KHÔNG ảnh -->
            <ul v-if="block.rest.length" class="hub__list">
              <li v-for="item in block.rest" :key="item.id">
                <NuxtLink :to="item.alias" class="hub__item">
                  <span class="hub__title">{{ item.title }}</span>
                  <span class="hub__date">{{ item.date }}</span>
                </NuxtLink>
              </li>
            </ul>
          </section>
          <p v-if="hub && !hub.length" class="empty">Chưa có tin nào được đăng.</p>
        </div>

        <!-- ===== Dạng 2: DANH SÁCH MỘT CHUYÊN MỤC ===== -->
        <!-- Hai cột 3:1 như trang mẫu /noi-bat: danh sách bài bên trái, cột phải
             gồm menu chuyên mục, video và liên kết web. -->
        <div v-else class="cat">
          <div class="cat__main">
            <p v-if="!news || !news.length" class="empty">Không có tin nào trong chuyên mục này.</p>
            <NewsGrid v-else :items="news" :loading="pending" />
            <Pagination :current="page" :total="totalPages" @change="changePage" />
          </div>
          <NewsAside :categories="categories || []" :active-cat="cat" @select="selectCat" />
        </div>
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

/* ===== Trang tổng hợp: mỗi chuyên mục một khối ===== */
/* Hai cột trên màn rộng: 6 chuyên mục xếp dọc một cột thì trang dài gấp đôi mà
   nửa bên phải bỏ trống. align-items:start để khối ít tin không bị kéo cao bằng
   khối bên cạnh. */
.hub {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 34px 40px;
  align-items: start;
}
/* --accent: màu riêng của từng chuyên mục, gán ở template theo thứ tự khối. Dùng
   biến chứ không viết 6 bộ class: thêm bớt chuyên mục không phải sửa CSS. */
.hub__block {
  min-width: 0;
  background: #fff;
  border: 1px solid #E9EDF3;
  border-top: 3px solid var(--accent);
  padding: 0 18px 18px;
}

.hub__head {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 14px;
  margin: 0 -18px 16px;
  padding: 14px 18px;
  /* Dải nền nhạt pha từ chính màu nhấn — color-mix để khỏi khai báo tay 6 mã màu
     nhạt tương ứng. Khai báo xám trung tính TRƯỚC làm nền dự phòng: color-mix chỉ
     có từ Chrome 111 / Safari 16.2, máy ở cơ quan nhà nước thường cũ hơn thế và
     nếu không có fallback thì cả dải tiêu đề mất nền, trông như lỗi. */
  background: #F5F7FA;
  background: color-mix(in srgb, var(--accent) 7%, #fff);
  border-bottom: 1px solid #E9EDF3;
  border-bottom-color: color-mix(in srgb, var(--accent) 18%, #fff);
}
.hub__head-title {
  margin: 0;
  min-width: 0;
  font-family: 'Lexend', sans-serif;
  font-size: 17px;
  font-weight: 700;
  letter-spacing: 0.3px;
  text-transform: uppercase;
}
.hub__head-link {
  color: var(--accent);
  text-decoration: none;
}
.hub__head-link:hover { text-decoration: underline; }
.hub__more {
  flex: 0 0 auto;
  display: inline-flex;
  align-items: center;
  gap: 6px;
  font-size: 12.5px;
  font-weight: 600;
  color: var(--accent);
  text-decoration: none;
  white-space: nowrap;
}
.hub__more:hover { text-decoration: underline; }
.hub__count {
  padding: 1px 7px;
  border-radius: 999px;
  background: #EDF1F6;
  background: color-mix(in srgb, var(--accent) 12%, #fff);
  font-size: 11px;
  font-variant-numeric: tabular-nums;
}

/* Tin mới nhất: ảnh LỚN. Chỉ tin này có ảnh — các tin sau chỉ tiêu đề, đúng cấu
   trúc trang mẫu: khối để lướt nhanh, một ảnh là đủ neo mắt. */
.hub__lead {
  display: block;
  text-decoration: none;
  padding-bottom: 14px;
  margin-bottom: 4px;
  border-bottom: 1px solid #EDF0F5;
}
.hub__lead-media {
  position: relative;
  display: block;
  aspect-ratio: 16 / 9;
  background: #E8F0F7;
  overflow: hidden;
}
.hub__lead-media img {
  width: 100%;
  height: 100%;
  object-fit: cover;
  display: block;
  transition: transform .4s ease;
}
.hub__lead:hover .hub__lead-media img { transform: scale(1.04); }
.hub__lead-tag {
  position: absolute;
  left: 0;
  bottom: 0;
  padding: 5px 11px;
  background: var(--accent);
  color: #fff;
  font-family: 'Lexend', sans-serif;
  font-size: 10.5px;
  font-weight: 700;
  letter-spacing: 0.5px;
  text-transform: uppercase;
}
.hub__lead-title {
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
  margin-top: 12px;
  font-family: 'Lexend', sans-serif;
  font-size: 16px;
  line-height: 23px;
  font-weight: 600;
  color: #212529;
}
.hub__lead:hover .hub__lead-title { color: var(--accent); }
.hub__lead-meta {
  display: inline-flex;
  align-items: center;
  gap: 5px;
  margin-top: 6px;
  font-size: 11.5px;
  color: #888;
}
.hub__lead-desc {
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
  margin-top: 7px;
  font-size: 13px;
  line-height: 19px;
  color: #666;
}

/* Các tin sau: chỉ tiêu đề + ngày */
.hub__list {
  list-style: none;
  margin: 0;
  padding: 0;
}
.hub__item {
  display: flex;
  align-items: baseline;
  gap: 12px;
  padding: 9px 0;
  border-bottom: 1px solid #F3F5F8;
  text-decoration: none;
}
.hub__list > li:last-child .hub__item { border-bottom: 0; padding-bottom: 0; }
.hub__item::before {
  content: '';
  flex: 0 0 5px;
  width: 5px;
  height: 5px;
  border-radius: 50%;
  background: #C7D4E6;
  background: color-mix(in srgb, var(--accent) 45%, #fff);
  transform: translateY(-3px);
  transition: background .15s ease;
}
.hub__item:hover::before { background: var(--accent); }
.hub__item:hover .hub__title { color: var(--accent); }
.hub__title {
  flex: 1;
  min-width: 0;
  font-size: 14px;
  line-height: 20px;
  color: #333;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}
.hub__date {
  flex: 0 0 auto;
  font-size: 11.5px;
  color: #999;
  font-variant-numeric: tabular-nums;
}

.hub__item:focus-visible,
.hub__lead:focus-visible,
.hub__more:focus-visible,
.hub__head-link:focus-visible { outline: 2px solid var(--accent); outline-offset: 2px; }

/* ===== Trang chuyên mục: danh sách + cột phải ===== */
.cat {
  display: grid;
  grid-template-columns: minmax(0, 3fr) minmax(0, 1fr);
  gap: 34px;
  align-items: start;
}
.cat__main { min-width: 0; }
.hub__all {
  display: inline-flex;
  align-items: center;
  gap: 7px;
  margin-top: 14px;
  color: var(--nidqc-primary);
  font-size: 13.5px;
  font-weight: 600;
  text-decoration: none;
}
.hub__all:hover { text-decoration: underline; }
.hub__count { color: #888; font-weight: 500; }
.hub__item:focus-visible,
.hub__all:focus-visible,
.hub__head-link:focus-visible { outline: 2px solid #1D6AC5; outline-offset: 2px; }

@media (max-width: 1024px) {
  /* Cột phải xuống dưới danh sách: bóp còn ~200px thì tên chuyên mục và tên cơ quan
     đều vỡ dòng, đọc còn khó hơn là cuộn thêm. */
  .cat { grid-template-columns: 1fr; gap: 32px; }
}
@media (max-width: 900px) {
  .hub { grid-template-columns: 1fr; gap: 30px; }
}
@media (max-width: 420px) {
  /* Màn hẹp: ngày xuống dòng dưới tiêu đề thay vì bóp tiêu đề còn vài chữ. */
  .hub__item { flex-wrap: wrap; }
  .hub__date { flex-basis: 100%; margin-left: 17px; }
}

@media (prefers-reduced-motion: reduce) {
  .hub__lead-media img, .hub__item::before { transition: none; }
  .hub__lead:hover .hub__lead-media img { transform: none; }
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
  padding: 40px 0;
  text-align: center;
  color: #777;
  font-size: 15px;
}

@media (prefers-reduced-motion: reduce) {
  .pill { transition: none; }
}
</style>
