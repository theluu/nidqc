<script setup>
// Trang chủ — SSR fetch từ Drupal (ADR-004). Tin tức đi qua /api/v1/news/list của
// module nidqc_content (ảnh đã qua image style); các khối còn lại vẫn dùng JSON:API.
//
// Design tách 2 khối: hero = tin SỰ KIỆN, section "Thông báo" = thông báo hành chính.
// KHÔNG lấy chung 50 tin mới rồi lọc phía JS: tin "Mua sắm - đấu thầu" áp đảo (>500)
// nên top-50 toàn thông báo, hero rỗng. Truy vấn TÁCH RIÊNG theo chuyên mục để hero
// luôn lấy đúng tin sự kiện mới nhất (Tin hoạt động, Hội nghị - Hội thảo).
const EVENT_CATS = ['Tin hoạt động', 'Hội nghị - Hội thảo']
const NOTICE_CATS = ['Thông báo', 'Mua sắm - đấu thầu', 'Tuyển dụng', 'Đào tạo']

const mapItem = (n) => ({
  id: n.id,
  title: n.title,
  date: formatDate(n.created),
  tag: n.tag,
  category: n.category,
  // Ảnh đã qua image style ở phía Drupal (trước đây trả file gốc, ~170KB/ảnh).
  image: newsImageUrl(n.image),
  alias: n.alias,
})

const { data: split } = await useCachedData('home-news', async () => {
  const [ev, an, ft] = await Promise.all([
    fetchNewsList({ cat: EVENT_CATS.join(','), limit: 6 }),
    // categories=1 đi ghép luôn vào request thông báo — cột chuyên mục cần số tin
    // của từng mục, không phải gọi thêm một vòng nữa.
    fetchNewsList({ cat: NOTICE_CATS.join(','), limit: 4, categories: true }),
    // Tin nổi bật do biên tập viên tự tích, KHÔNG lọc chuyên mục: một thông báo quan
    // trọng cũng phải lên được hero nếu người quản trị muốn.
    fetchNewsList({ featured: true, limit: 5 }),
  ])
  return {
    events: ev.data.map(mapItem),
    announcements: an.data.map(mapItem),
    featured: ft.data.map(mapItem),
    categories: an.categories || [],
  }
})
// PHẢI guard null: useAsyncData trả data = null khi handler lỗi (Drupal chậm/khởi
// động lại). Thiếu `?.` thì computed ném "Cannot read properties of null" ngay trong
// lúc render -> Nuxt bỏ cả trang chủ để hiện trang lỗi 500, chỉ vì mất khối tin tức.
// Các computed khác trong file này đã guard sẵn; hai dòng này trước đây bị sót.
const news = computed(() => split.value?.events || [])
const announcements = computed(() => split.value?.announcements || [])
// Chưa tích tin nổi bật nào -> hero lùi về tin sự kiện mới nhất, trang chủ không bao
// giờ trống khối lớn.
const featured = computed(() => {
  const picked = split.value?.featured || []
  return picked.length ? picked : news.value.slice(0, 1)
})

// Cột phải khối hero = submenu "Tin tức & Thông báo" của main menu, không phải danh
// sách tin: sửa menu ở useMainNav.ts là trang chủ đổi theo.
// Cột phải khối hero = submenu "Tin tức & Thông báo" của main menu, kèm SỐ TIN của
// từng chuyên mục. Con số mới là thứ phân biệt cột này với đúng 6 link nằm ngay trên
// thanh menu — không có nó thì đây chỉ là bản sao của menu.
// Ghép theo slug (?cat=…) chứ không theo nhãn: nhãn trên menu và tên chuyên mục
// trong Drupal viết khác nhau ("Mua sắm, đấu thầu & công khai minh bạch" với
// "Mua sắm - đấu thầu") nhưng cùng quy về một slug.
const newsNavLinks = computed(() => {
  const counts = new Map(
    (split.value?.categories || []).map((c) => [categorySlug(c.label), c.count]),
  )
  return navChildren('Tin tức & Thông báo').map((link) => ({
    ...link,
    count: counts.get(link.to.split('cat=')[1] || '') ?? null,
  }))
})

const newsTotal = computed(() =>
  (split.value?.categories || []).reduce((sum, c) => sum + (c.count || 0), 0),
)

// Thư viện Video & Hình ảnh — bài thuộc danh mục Videos / Hình ảnh, media đã chuẩn
// hoá sẵn ở Drupal nên mở lightbox không phải gọi thêm request nào.
const { data: mediaPosts } = await useCachedData('home-media', () => fetchMediaLibrary(12))

// Khối trang chủ động (quản trị trong Drupal): Hoạt động chuyên môn, Dịch vụ,
// Cơ sở/Liên hệ, và CTA + Video (node home_block).
// field_map lưu URL nhúng Google Maps (dùng cho iframe). Trang chủ giờ chỉ cần một
// LINK mở bản đồ, nên rút toạ độ từ tham số pb của URL nhúng (!2d = kinh độ,
// !3d = vĩ độ) — chính xác đúng điểm quản trị viên đã ghim. Không rút được thì lùi
// về tìm theo địa chỉ, vẫn ra đúng chỗ.
function mapsLink(embed, address) {
  const m = String(embed || '').match(/!2d(-?\d+(?:\.\d+)?)!3d(-?\d+(?:\.\d+)?)/)
  if (m) {
    return `https://www.google.com/maps/search/?api=1&query=${m[2]},${m[1]}`
  }
  return address
    ? `https://www.google.com/maps/search/?api=1&query=${encodeURIComponent(address)}`
    : null
}

const { data: blocks } = await useCachedData('home-blocks', async () => {
  const [exp, svc, off, hb] = await Promise.all([
    fetchJsonApi('/node/expertise', { 'filter[status]': 1, sort: 'field_weight', 'page[limit]': 30 }),
    fetchJsonApi('/node/service', { 'filter[status]': 1, sort: 'field_weight', 'page[limit]': 30 }),
    fetchJsonApi('/node/office', { 'filter[status]': 1, sort: 'field_weight', 'page[limit]': 10 }),
    fetchJsonApi('/node/home_block', { 'filter[status]': 1, 'page[limit]': 1 }),
  ])
  const plain = (f) => (f?.processed || f?.value || '').replace(/<[^>]+>/g, ' ').replace(/\s+/g, ' ').trim()
  const ytEmbed = (v) => {
    if (!v) return null
    const m = String(v).match(/(?:v=|youtu\.be\/|embed\/)([\w-]{11})/)
    const id = m ? m[1] : (/^[\w-]{11}$/.test(String(v)) ? String(v) : null)
    return id ? `https://www.youtube.com/embed/${id}` : null
  }
  const h = hb.data[0]?.attributes
  return {
    expertise: exp.data.map((n) => ({ t: n.attributes.title, d: plain(n.attributes.field_description) })),
    services: svc.data.map((n) => ({ label: n.attributes.title, href: n.attributes.field_link?.uri || null })),
    offices: off.data.map((n) => ({
      t: n.attributes.title,
      addr: n.attributes.field_address,
      tel: n.attributes.field_phone,
      email: n.attributes.field_email || null,
      map: mapsLink(n.attributes.field_map, n.attributes.field_address),
    })),
    cta: h ? { heading: h.title, desc: plain(h.field_description), btnLabel: h.field_link?.title || 'Xem thêm', btnHref: h.field_link?.uri || '#' } : null,
    videoUrl: ytEmbed(h?.field_video),
  }
})
const expertise = computed(() => blocks.value?.expertise || [])
const services = computed(() => blocks.value?.services || [])
const offices = computed(() => blocks.value?.offices || [])
const cta = computed(() => blocks.value?.cta || null)
const videoUrl = computed(() => blocks.value?.videoUrl || null)

// Liên kết web — quản trị trong Drupal (node type web_link): title + URL + logo + mô tả.
const { data: webLinks } = await useCachedData('home-weblinks', async () => {
  const { data, included } = await fetchJsonApi('/node/web_link', {
    'filter[status]': 1, sort: 'field_weight', 'page[limit]': 12, include: 'field_image',
  })
  return data.map((n) => ({
    label: n.attributes.title,
    href: n.attributes.field_link?.uri || null,
    image: imageUrl(n, included),
    description: (n.attributes.field_description?.processed || n.attributes.field_description?.value || '')
      .replace(/<[^>]+>/g, ' ').replace(/\s+/g, ' ').trim(),
  }))
})

useSeoMeta({ title: 'Trang chủ — Viện Kiểm nghiệm thuốc Trung ương', description: 'Tin tức, thông báo, dịch vụ kiểm nghiệm, tra cứu chất chuẩn và hoạt động chuyên môn của Viện Kiểm nghiệm thuốc Trung ương.', ogTitle: 'Trang chủ — Viện Kiểm nghiệm thuốc Trung ương', ogDescription: 'Tin tức, thông báo, dịch vụ kiểm nghiệm, tra cứu chất chuẩn và hoạt động chuyên môn của Viện Kiểm nghiệm thuốc Trung ương.' })
</script>

<template>
  <div>
    <!-- HERO -->
    <section style="background:#F5F5F5;border-bottom:1px solid #ECECEC;">
      <div class="nidqc-hero-grid" data-container style="max-width:1280px;margin:0 auto;padding:36px 24px 40px;display:grid;grid-template-columns:2.25fr 1fr;gap:28px;align-items:stretch;">
        <template v-if="news && news.length">
          <FeaturedHero :items="featured" />
          <!-- Chuyên mục Tin tức: lấy thẳng submenu "Tin tức & Thông báo" trên main
               menu (useMainNav) để hai nơi không bao giờ lệch nhau. -->
          <div style="background:#fff;border:1px solid #CCCCCC;display:flex;flex-direction:column;">
            <div style="background:#0F3093;color:#fff;padding:14px 20px;display:flex;align-items:center;justify-content:space-between;">
              <h3 style="font-family:'Lexend',sans-serif;font-weight:700;font-size:16px;letter-spacing:0.3px;text-transform:uppercase;margin:0;">Tin tức &amp; Thông báo</h3>
              <NuxtLink to="/tin-tuc" style="color:rgba(255,255,255,0.85);font-size:12.5px;text-decoration:none;display:flex;align-items:center;gap:4px;">Xem tất cả
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="m9 18 6-6-6-6"/></svg>
              </NuxtLink>
            </div>
            <div class="nidqc-cat-list">
              <NuxtLink v-for="(link, i) in newsNavLinks" :key="i" :to="link.to" class="nidqc-cat-row">
                <span class="nidqc-cat-label">{{ link.label }}</span>
                <span v-if="link.count !== null" class="nidqc-cat-count">{{ link.count }}</span>
              </NuxtLink>
              <NuxtLink v-if="newsTotal" to="/tin-tuc" class="nidqc-cat-total">
                <span>Toàn bộ tin &amp; thông báo</span>
                <span class="nidqc-cat-total-num">{{ newsTotal }}</span>
              </NuxtLink>
            </div>
          </div>
        </template>
      </div>
    </section>

    <!-- HOẠT ĐỘNG CHUYÊN MÔN -->
    <section style="background:#F3F7FC;border-bottom:1px solid #ECECEC;padding:44px 0;" id="hoat-dong-chuyen-mon">
      <div data-container style="max-width:1280px;margin:0 auto;padding:0 24px;">
        <h2 style="font-family:'Lexend',sans-serif;font-weight:700;font-size:22px;color:#0F3093;margin:0 0 24px;padding-left:12px;border-left:4px solid #0F3093;">Hoạt động chuyên môn</h2>
        <div class="nidqc-grid-3" style="display:grid;grid-template-columns:repeat(3,1fr);gap:18px;">
          <div v-for="(a, i) in expertise" :key="i" style="background:#fff;border:1px solid #CCCCCC;padding:22px 24px;">
            <h3 style="font-family:'Lexend',sans-serif;font-weight:600;font-size:16px;line-height:22px;color:#0F3093;margin:0 0 10px;">{{ a.t }}</h3>
            <p style="font-size:14px;line-height:21px;color:#495057;margin:0;">{{ a.d }}</p>
          </div>
        </div>
      </div>
    </section>

    <!-- THÔNG BÁO -->
    <section style="background:#F5F5F5;border-bottom:1px solid #ECECEC;padding:44px 0;" id="thong-bao">
      <div data-container style="max-width:1280px;margin:0 auto;padding:0 24px;">
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:24px;">
          <h2 style="font-family:'Lexend',sans-serif;font-weight:700;font-size:22px;color:#0F3093;margin:0;padding-left:12px;border-left:4px solid #0F3093;">Thông báo</h2>
          <NuxtLink to="/tin-tuc" style="color:#1D6AC5;font-size:14px;text-decoration:none;">Xem tất cả thông báo →</NuxtLink>
        </div>
        <div class="nidqc-grid-4" style="display:grid;grid-template-columns:repeat(4,1fr);gap:18px;">
          <NuxtLink v-for="item in announcements.slice(0, 4)" :key="item.id" :to="item.alias" style="display:block;background:#fff;border:1px solid #ECECEC;text-decoration:none;">
            <div style="width:100%;height:150px;background:#E8F0F7;overflow:hidden;">
              <img loading="lazy" v-if="item.image" :src="item.image" alt="" style="width:100%;height:100%;object-fit:cover;">
            </div>
            <div style="padding:16px 18px 20px;">
              <span v-if="item.tag" style="display:inline-block;background:#E8F0F7;color:#0F3093;font-size:10.5px;font-weight:600;text-transform:uppercase;letter-spacing:0.4px;padding:3px 9px;margin-bottom:10px;">{{ item.tag }}</span>
              <div style="font-size:14px;line-height:20px;color:#212529;font-weight:500;margin-bottom:10px;">{{ item.title }}</div>
              <div style="display:flex;align-items:center;gap:6px;color:#777;font-size:12px;">
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/></svg>
                {{ item.date }}
              </div>
            </div>
          </NuxtLink>
        </div>
      </div>
    </section>

    <!-- DỊCH VỤ -->
    <section style="background:#fff;padding:44px 0;" id="dich-vu">
      <div style="max-width:1280px;margin:0 auto;padding:0 24px;">
        <h2 style="font-family:'Lexend',sans-serif;font-weight:700;font-size:22px;color:#0F3093;margin:0 0 6px;">Dịch vụ &amp; tra cứu</h2>
        <p style="color:#495057;font-size:15px;margin:0 0 24px;">Các dịch vụ khoa học kỹ thuật và công cụ tra cứu của Viện.</p>
        <div class="nidqc-grid-3" style="display:grid;grid-template-columns:repeat(3,1fr);gap:18px;">
          <a v-for="(s, i) in services" :key="i" :href="s.href || '#dich-vu'" :target="s.href ? '_blank' : undefined" :rel="s.href ? 'noopener' : undefined" style="display:block;background:#F5F8FC;border:1px solid #CCCCCC;padding:20px 22px;text-decoration:none;">
            <h3 style="font-family:'Lexend',sans-serif;font-weight:600;font-size:16px;color:#0F3093;margin:0;">{{ s.label }}</h3>
          </a>
        </div>
      </div>
    </section>

    <!-- CTA CHẤT CHUẨN -->
    <section v-if="cta" style="background:#0F3093;padding:44px 0;" id="chat-chuan">
      <div style="max-width:1280px;margin:0 auto;padding:0 24px;display:flex;align-items:center;justify-content:space-between;gap:28px;flex-wrap:wrap;">
        <div>
          <h2 style="font-family:'Lexend',sans-serif;font-weight:700;font-size:24px;color:#fff;margin:0 0 6px;">{{ cta.heading }}</h2>
          <p v-if="cta.desc" style="color:rgba(255,255,255,0.85);font-size:15px;margin:0;max-width:620px;">{{ cta.desc }}</p>
        </div>
        <a :href="cta.btnHref" target="_blank" rel="noopener" style="display:inline-block;background:#fff;color:#0F3093;font-weight:600;font-size:14px;padding:11px 22px;text-decoration:none;">{{ cta.btnLabel }}</a>
      </div>
    </section>

    <!-- THƯ VIỆN VIDEO + LIÊN KẾT WEB -->
    <section style="background:#fff;padding:44px 0;">
      <div class="nidqc-two-col" data-container style="max-width:1280px;margin:0 auto;padding:0 24px;display:grid;grid-template-columns:1.6fr 1fr;gap:32px;">
        <div>
          <!-- Có bài Thư viện (Videos / Hình ảnh) thì hiện slider; chưa có bài nào
               thì giữ nguyên video giới thiệu cũ lấy từ node home_block. -->
          <MediaLibrary v-if="mediaPosts && mediaPosts.length" :posts="mediaPosts" />
          <template v-else>
            <div style="display:flex;align-items:center;gap:11px;border-bottom:2px solid #0F3093;padding-bottom:12px;margin-bottom:20px;">
              <span style="width:6px;height:26px;background:#0F3093;display:inline-block;"></span>
              <h2 style="font-family:'Lexend',sans-serif;font-weight:700;font-size:24px;letter-spacing:0.3px;text-transform:uppercase;color:#212529;margin:0;">Thư viện video</h2>
            </div>
            <div style="position:relative;width:100%;padding-top:56.25%;border:1px solid #CCCCCC;">
              <iframe v-if="videoUrl" :src="videoUrl" title="Video giới thiệu NIDQC" allowfullscreen
                allow="accelerometer; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                style="position:absolute;inset:0;width:100%;height:100%;border:0;"></iframe>
            </div>
          </template>
        </div>
        <!-- Cột này cao bằng khối Thư viện bên trái: các hàng chia đều phần chiều cao
             còn lại (.nidqc-weblink-list) nên đáy hai cột thẳng nhau. -->
        <div v-if="webLinks && webLinks.length" class="nidqc-weblinks-col">
          <div style="display:flex;align-items:center;gap:11px;border-bottom:2px solid #0F3093;padding-bottom:12px;margin-bottom:20px;">
            <span style="width:6px;height:26px;background:#0F3093;display:inline-block;"></span>
            <h2 style="font-family:'Lexend',sans-serif;font-weight:700;font-size:24px;letter-spacing:0.3px;text-transform:uppercase;color:#212529;margin:0;">Liên kết web</h2>
          </div>
          <div class="nidqc-weblink-list">
            <component :is="l.href ? 'a' : 'div'" v-for="(l, i) in webLinks" :key="i"
              :href="l.href || undefined" :target="l.href ? '_blank' : undefined" :rel="l.href ? 'noopener' : undefined"
              class="nidqc-weblink"
              style="display:flex;align-items:center;gap:12px;background:#F5F8FC;border:1px solid #ECECEC;padding:12px 14px;color:#212529;text-decoration:none;">
              <span style="width:44px;height:44px;flex:0 0 44px;background:#fff;border:1px solid #ECECEC;display:flex;align-items:center;justify-content:center;overflow:hidden;">
                <img loading="lazy" v-if="l.image" :src="l.image" :alt="l.label" style="width:100%;height:100%;object-fit:contain;">
                <svg v-else width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#1D6AC5" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"/><path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"/></svg>
              </span>
              <span style="flex:1;min-width:0;">
                <span style="display:block;font-family:'Lexend',sans-serif;font-size:14px;font-weight:600;color:#0F3093;">{{ l.label }}</span>
                <span v-if="l.description" class="nidqc-clamp-2" style="display:block;font-size:12.5px;line-height:18px;color:#495057;margin-top:3px;">{{ l.description }}</span>
              </span>
            </component>
          </div>
        </div>
      </div>
    </section>

    <!-- LIÊN HỆ (Cơ sở 1 & 2) -->
    <section style="background:#F5F5F5;padding:44px 0;" id="lien-he">
      <div data-container style="max-width:1280px;margin:0 auto;padding:0 24px;">
        <div style="display:flex;align-items:center;gap:11px;border-bottom:2px solid #0F3093;padding-bottom:12px;margin-bottom:20px;">
          <span style="width:6px;height:26px;background:#0F3093;display:inline-block;"></span>
          <h2 style="font-family:'Lexend',sans-serif;font-weight:700;font-size:24px;letter-spacing:0.3px;text-transform:uppercase;color:#212529;margin:0;">Liên hệ</h2>
        </div>
        <div class="nidqc-two-col" style="display:grid;grid-template-columns:1fr 1fr;gap:24px;">
          <!-- Bỏ iframe bản đồ (220px mỗi cơ sở) — thay bằng link mở Google Maps.
               Địa chỉ, điện thoại, email đều bấm được: trên điện thoại là gọi/gửi thư
               ngay, không phải bôi đen copy. -->
          <div v-for="(cs, i) in offices" :key="i" class="nidqc-office">
            <h3 class="nidqc-office__name">{{ cs.t }}</h3>
            <ul class="nidqc-office__list">
              <li>
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#1D6AC5" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0z"/><circle cx="12" cy="10" r="3"/></svg>
                <span>{{ cs.addr }}</span>
              </li>
              <li v-if="cs.tel">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#1D6AC5" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72c.13.96.36 1.9.7 2.81a2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.9.34 1.85.57 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
                <a :href="`tel:${cs.tel.replace(/[^+\d]/g, '')}`">{{ cs.tel }}</a>
              </li>
              <li v-if="cs.email">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#1D6AC5" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="4" width="20" height="16" rx="2"/><path d="m2 7 10 6 10-6"/></svg>
                <a :href="`mailto:${cs.email}`">{{ cs.email }}</a>
              </li>
            </ul>
            <!-- Chỉ một nút: form liên hệ dùng chung một hộp thư cho cả Viện, đặt nút
                 "Gửi liên hệ" trong từng cơ sở sẽ ngụ ý gửi riêng cho cơ sở đó. Nút
                 chung đã có ngay dưới khối này. -->
            <div class="nidqc-office__actions">
              <a v-if="cs.map" :href="cs.map" target="_blank" rel="noopener" class="nidqc-office__btn">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0z"/><circle cx="12" cy="10" r="3"/></svg>
                Xem trên bản đồ
              </a>
            </div>
          </div>
        </div>
      </div>
    </section>
  </div>
</template>
