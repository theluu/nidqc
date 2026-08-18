<script setup>
// Trang chủ — SSR fetch từ Drupal (ADR-004).
//
// Thứ tự section bám đúng tài liệu "Feedback NIDQC" (08/2026):
//   1. Tin nổi bật (1 tin, hiệu ứng lướt) + cột tab chuyên mục tin tức   (feedback 4-5)
//   2. Dịch vụ (title + ảnh) | Danh mục năng lực, kèm nút Tra cứu chất chuẩn (6)
//   3. Banner quảng cáo slideshow                                          (7)
//   4. Hoạt động chuyên môn (title + ảnh) | Liên kết nổi bật               (8)
//   5. Banner quảng cáo slideshow                                          (9)
//   6. Tin thông báo | Tin mua sắm                                         (10)
//   7. Thư viện video/hình ảnh | Liên kết web | Thống kê truy cập          (11)
//   8. Sơ đồ menu                                                          (12)
// Chân trang (13) nằm ở layouts/default.vue.
import { inject } from 'vue'

const mapItem = (n) => ({
  id: n.id,
  title: n.title,
  date: formatDate(n.created),
  summary: n.summary || '',
  tag: n.tag,
  category: n.category,
  // Ảnh đã qua image style ở phía Drupal (trước đây trả file gốc, ~170KB/ảnh).
  image: newsImageUrl(n.image),
  alias: n.alias,
})

// Submenu "Tin tức & Thông báo" của main menu vừa là nhãn tab, vừa là danh sách
// chuyên mục cần lấy tin — sửa menu ở useMainNav.ts là trang chủ đổi theo.
const newsMenu = navChildren('Tin tức & Thông báo')

const { data: news } = await useCachedData('home-news', async () => {
  // Lấy song song: tin nổi bật cho hero, và danh sách chuyên mục kèm số bài.
  //
  // Cần danh sách chuyên mục vì nhãn trên menu và tên chuyên mục trong Drupal viết
  // khác nhau ("Mua sắm, đấu thầu & công khai minh bạch" với "Mua sắm - đấu thầu")
  // nhưng cùng quy về một slug — API lọc theo TÊN nên phải tra ngược slug -> tên.
  const [featuredRes, catsRes] = await Promise.all([
    // Tin nổi bật do biên tập viên tự tích, KHÔNG lọc chuyên mục: một thông báo
    // quan trọng cũng phải lên được hero nếu người quản trị muốn.
    fetchNewsList({ featured: true, limit: 5 }),
    fetchNewsList({ limit: 1, categories: true }),
  ])

  const bySlug = new Map((catsRes.categories || []).map((c) => [categorySlug(c.label), c.label]))
  const wanted = newsMenu.map((link) => {
    const slug = link.to.split('cat=')[1] || ''
    return {
      label: link.label,
      to: link.to,
      slug,
      // Nhãn menu không khớp chuyên mục nào thì cứ hỏi Drupal bằng chính nhãn đó:
      // sai thì tab rỗng và tự bị ẩn, không làm hỏng cả khối.
      cat: bySlug.get(slug) || link.label,
    }
  })

  // limit 5 phục vụ cột tab (feedback 5); section "Tin thông báo | Tin mua sắm"
  // (feedback 10) dùng lại đúng hai danh sách này nên không phải gọi thêm.
  const lists = await Promise.all(
    wanted.map((tab) => fetchNewsList({ cat: tab.cat, limit: 5 })),
  )

  let featured = featuredRes.data.map(mapItem)
  // Chưa tích tin nổi bật nào -> hero lùi về tin mới nhất, trang chủ không bao giờ
  // trống mất khối lớn nhất.
  if (!featured.length) {
    featured = (lists.find((l) => l.data.length)?.data || []).slice(0, 1).map(mapItem)
  }

  return {
    featured,
    tabs: wanted.map((tab, i) => ({
      label: tab.label,
      to: tab.to,
      slug: tab.slug,
      items: lists[i].data.map(mapItem),
    })),
  }
})

const featured = computed(() => news.value?.featured || [])
const newsTabs = computed(() => news.value?.tabs || [])

// Section "Tin thông báo | Tin mua sắm" (feedback 10) — lấy lại đúng hai chuyên mục
// đã fetch cho cột tab, tra theo slug chứ không theo nhãn menu (nhãn có thể đổi).
const newsColumn = (slug) => computed(() => newsTabs.value.find((tab) => tab.slug === slug) || null)
const noticeColumn = newsColumn('thong-bao')
const procurementColumn = newsColumn('mua-sam-dau-thau')

// Thư viện Video & Hình ảnh — bài thuộc danh mục Videos / Hình ảnh, media đã chuẩn
// hoá sẵn ở Drupal nên mở lightbox không phải gọi thêm request nào.
const { data: mediaPosts } = await useCachedData('home-media', () => fetchMediaLibrary(12))

// Khối trang chủ động (quản trị trong Drupal): Dịch vụ, Danh mục năng lực, Hoạt
// động chuyên môn, Banner, Liên kết web và nút Tra cứu chất chuẩn — tất cả trong
// MỘT request, ảnh đã qua image style (xem composables/useHomeBlocks.ts).
const { data: blocks } = await useCachedData('home-blocks', fetchHomeBlocks)

const services = computed(() => blocks.value?.services || [])
const capabilities = computed(() => blocks.value?.capabilities || [])
const expertise = computed(() => blocks.value?.expertise || [])
const ads1 = computed(() => blocks.value?.banners?.ads_1 || [])
const ads2 = computed(() => blocks.value?.banners?.ads_2 || [])
const sidebarLinks = computed(() => blocks.value?.banners?.sidebar || [])
const webLinks = computed(() => blocks.value?.web_links || [])
// Nút "Tra cứu chất chuẩn" — nổi bật hẳn so với các link dịch vụ vì nó dẫn sang
// trang mua chuẩn chứ không phải một bài giới thiệu (feedback 6).
const standards = computed(() => blocks.value?.standards || null)

// Thống kê truy cập lấy từ bộ đếm đã chạy sẵn ở layout (xem provide ở default.vue).
const online = inject('nidqcOnline', { onlineCount: ref(null), visits: ref(null) })
const visitRows = computed(() => [
  { label: 'Đang trực tuyến', value: online.onlineCount.value, live: true },
  { label: 'Hôm nay', value: online.visits.value?.today ?? null },
  { label: 'Trong tháng', value: online.visits.value?.month ?? null },
  { label: 'Trong năm', value: online.visits.value?.year ?? null },
  { label: 'Tổng truy cập', value: online.visits.value?.total ?? null },
])
const formatNumber = (value) => (value === null || value === undefined ? '—' : value.toLocaleString('vi-VN'))

// Sơ đồ menu ở cuối trang (feedback 12) — cùng nguồn với thanh menu trên đầu, kể cả
// submenu "Hoạt động chuyên môn" lấy động từ khối expertise ở trên.
const siteMap = computed(() => mainNavWithExpertise(blocks.value?.expertise).filter((item) => item.children.length))

useSeoMeta({ title: 'Trang chủ — Viện Kiểm nghiệm thuốc Trung ương', description: 'Tin tức, thông báo, dịch vụ kiểm nghiệm, tra cứu chất chuẩn và hoạt động chuyên môn của Viện Kiểm nghiệm thuốc Trung ương.', ogTitle: 'Trang chủ — Viện Kiểm nghiệm thuốc Trung ương', ogDescription: 'Tin tức, thông báo, dịch vụ kiểm nghiệm, tra cứu chất chuẩn và hoạt động chuyên môn của Viện Kiểm nghiệm thuốc Trung ương.' })
</script>

<template>
  <div>
    <!-- ===== 1. TIN NỔI BẬT + TAB CHUYÊN MỤC (feedback 4-5) ===== -->
    <section class="nidqc-section" style="background:#F5F5F5;border-bottom:1px solid #ECECEC;">
      <div class="nidqc-hero-grid" data-container style="max-width:1280px;margin:0 auto;padding:0 24px;display:grid;grid-template-columns:minmax(0,2.1fr) minmax(0,1fr);gap:24px;align-items:stretch;">
        <FeaturedHero v-if="featured.length" :items="featured" />
        <NewsTabs :tabs="newsTabs" />
      </div>
    </section>

    <!-- ===== 2. DỊCH VỤ & DANH MỤC NĂNG LỰC (feedback 6) ===== -->
    <section class="nidqc-section" style="background:#fff;" id="dich-vu">
      <div class="nidqc-service-grid" data-container style="max-width:1280px;margin:0 auto;padding:0 24px;display:grid;grid-template-columns:minmax(0,2fr) minmax(0,1fr);gap:32px;">
        <div>
          <div class="nidqc-heading">
            <span class="nidqc-heading__bar"></span>
            <h2 class="nidqc-heading__text">Dịch vụ</h2>
          </div>

          <!-- Nút tra cứu chất chuẩn đặt TRÊN lưới dịch vụ và tô đậm: nó dẫn sang
               trang mua chuẩn, khác hẳn các thẻ bên dưới (đều là bài giới thiệu). -->
          <a v-if="standards && standards.url" :href="standards.url" target="_blank" rel="noopener" class="nidqc-standards-cta">
            <span class="nidqc-standards-cta__icon" aria-hidden="true">
              <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="7"/><path d="m21 21-4.3-4.3"/></svg>
            </span>
            <span class="nidqc-standards-cta__body">
              <strong>{{ standards.label }}</strong>
              <span v-if="standards.note">{{ standards.note }}</span>
            </span>
            <svg class="nidqc-standards-cta__arrow" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M13 6l6 6-6 6"/></svg>
          </a>

          <div class="nidqc-grid-3" style="display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:18px;">
            <component :is="s.url ? 'a' : 'div'" v-for="(s, i) in services" :key="i"
              :href="s.url || undefined"
              :target="s.url && /^https?:/.test(s.url) ? '_blank' : undefined"
              :rel="s.url && /^https?:/.test(s.url) ? 'noopener' : undefined"
              class="nidqc-tile">
              <span class="nidqc-tile__media">
                <img v-if="s.image" :src="s.image" :alt="s.title" loading="lazy">
              </span>
              <span class="nidqc-tile__label">{{ s.title }}</span>
            </component>
          </div>
        </div>

        <div v-if="capabilities.length" class="nidqc-col">
          <div class="nidqc-heading">
            <span class="nidqc-heading__bar"></span>
            <h2 class="nidqc-heading__text">Danh mục năng lực</h2>
          </div>
          <ol class="nidqc-capability-list">
            <li v-for="(c, i) in capabilities" :key="i">
              <component :is="c.url ? 'a' : 'span'" :href="c.url || undefined" class="nidqc-capability">
                <span class="nidqc-capability__num">{{ i + 1 }}</span>
                <span class="nidqc-capability__body">
                  <span class="nidqc-capability__label">{{ c.title }}</span>
                  <span v-if="c.description" class="nidqc-capability__desc nidqc-clamp-2">{{ c.description }}</span>
                </span>
              </component>
            </li>
          </ol>
        </div>
      </div>
    </section>

    <!-- ===== 3. BANNER QUẢNG CÁO (feedback 7) ===== -->
    <BannerSlideshow :items="ads1" />

    <!-- ===== 4. HOẠT ĐỘNG CHUYÊN MÔN + LIÊN KẾT NỔI BẬT (feedback 8) ===== -->
    <section class="nidqc-section" style="background:#F3F7FC;border-top:1px solid #ECECEC;border-bottom:1px solid #ECECEC;" id="hoat-dong-chuyen-mon">
      <div class="nidqc-service-grid" data-container style="max-width:1280px;margin:0 auto;padding:0 24px;display:grid;grid-template-columns:minmax(0,2fr) minmax(0,1fr);gap:32px;">
        <div>
          <div class="nidqc-heading">
            <span class="nidqc-heading__bar"></span>
            <h2 class="nidqc-heading__text">Hoạt động chuyên môn</h2>
          </div>
          <div class="nidqc-grid-3" style="display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:18px;">
            <component :is="a.url ? 'a' : 'div'" v-for="(a, i) in expertise" :key="i"
              :href="a.url || undefined"
              :target="a.url && /^https?:/.test(a.url) ? '_blank' : undefined"
              :rel="a.url && /^https?:/.test(a.url) ? 'noopener' : undefined"
              class="nidqc-tile">
              <span class="nidqc-tile__media">
                <img v-if="a.image" :src="a.image" :alt="a.title" loading="lazy">
              </span>
              <span class="nidqc-tile__label">{{ a.title }}</span>
            </component>
          </div>
        </div>

        <div v-if="sidebarLinks.length" class="nidqc-col">
          <div class="nidqc-heading">
            <span class="nidqc-heading__bar"></span>
            <h2 class="nidqc-heading__text">Liên kết nổi bật</h2>
          </div>
          <div class="nidqc-highlight-list">
            <a v-for="(l, i) in sidebarLinks" :key="i"
              :href="l.url || undefined"
              :target="l.url && /^https?:/.test(l.url) ? '_blank' : undefined"
              :rel="l.url && /^https?:/.test(l.url) ? 'noopener' : undefined"
              class="nidqc-highlight">
              <img :src="l.image" :alt="l.title" loading="lazy">
              <span>{{ l.title }}</span>
            </a>
          </div>
        </div>
      </div>
    </section>

    <!-- ===== 5. BANNER QUẢNG CÁO (feedback 9) ===== -->
    <BannerSlideshow :items="ads2" />

    <!-- ===== 6. TIN THÔNG BÁO | TIN MUA SẮM (feedback 10) ===== -->
    <section v-if="noticeColumn || procurementColumn" class="nidqc-section" style="background:#fff;" id="thong-bao">
      <div class="nidqc-two-col" data-container style="max-width:1280px;margin:0 auto;padding:0 24px;display:grid;grid-template-columns:minmax(0,1fr) minmax(0,1fr);gap:32px;">
        <NewsColumn v-if="noticeColumn" title="Tin thông báo" :items="noticeColumn.items" :to="noticeColumn.to" />
        <NewsColumn v-if="procurementColumn" title="Tin mua sắm" :items="procurementColumn.items" :to="procurementColumn.to" />
      </div>
    </section>

    <!-- ===== 7. THƯ VIỆN · LIÊN KẾT WEB · THỐNG KÊ TRUY CẬP (feedback 11) ===== -->
    <section class="nidqc-section" style="background:#fff;border-top:1px solid #ECECEC;">
      <div class="nidqc-three-col" data-container style="max-width:1280px;margin:0 auto;padding:0 24px;display:grid;grid-template-columns:minmax(0,1.7fr) minmax(0,1fr) minmax(0,0.85fr);gap:28px;">
        <div class="nidqc-col">
          <MediaLibrary v-if="mediaPosts && mediaPosts.length" :posts="mediaPosts" />
        </div>

        <div v-if="webLinks && webLinks.length" class="nidqc-col">
          <div class="nidqc-heading">
            <span class="nidqc-heading__bar"></span>
            <h2 class="nidqc-heading__text">Liên kết web</h2>
          </div>
          <div class="nidqc-weblink-list">
            <component :is="l.url ? 'a' : 'div'" v-for="(l, i) in webLinks" :key="i"
              :href="l.url || undefined" :target="l.url ? '_blank' : undefined" :rel="l.url ? 'noopener' : undefined"
              class="nidqc-weblink">
              <span class="nidqc-weblink__logo">
                <img loading="lazy" v-if="l.image" :src="l.image" :alt="l.title">
                <svg v-else width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#1D6AC5" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"/><path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"/></svg>
              </span>
              <span class="nidqc-weblink__label">{{ l.title }}</span>
            </component>
          </div>
        </div>

        <div class="nidqc-col">
          <div class="nidqc-heading">
            <span class="nidqc-heading__bar"></span>
            <h2 class="nidqc-heading__text">Thống kê truy cập</h2>
          </div>
          <ul class="nidqc-stats" aria-live="polite">
            <li v-for="(row, i) in visitRows" :key="i" :class="{ 'is-live': row.live }">
              <span class="nidqc-stats__label">
                <span v-if="row.live" class="nidqc-stats__dot" aria-hidden="true"></span>
                {{ row.label }}
              </span>
              <strong class="nidqc-stats__value">{{ formatNumber(row.value) }}</strong>
            </li>
          </ul>
        </div>
      </div>
    </section>

    <!-- ===== 8. SƠ ĐỒ MENU (feedback 12) ===== -->
    <section class="nidqc-section" style="background:#F5F5F5;border-top:1px solid #ECECEC;">
      <div class="nidqc-sitemap" data-container style="max-width:1280px;margin:0 auto;padding:0 24px;">
        <div v-for="(item, i) in siteMap" :key="i" class="nidqc-sitemap__col">
          <NuxtLink :to="item.to" class="nidqc-sitemap__head">{{ item.label }}</NuxtLink>
          <!-- Mục con lấy từ mainNav nên dùng khoá `label`, KHÁC các khối lấy từ
               /api/v1/home/blocks (dùng `title`). -->
          <NuxtLink v-for="(child, j) in item.children" :key="j" :to="child.to" class="nidqc-sitemap__link"
            :target="isExternalLink(child.to) ? '_blank' : undefined"
            :rel="isExternalLink(child.to) ? 'noopener' : undefined">{{ child.label }}</NuxtLink>
        </div>
      </div>
    </section>
  </div>
</template>
