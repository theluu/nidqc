<script setup>
// Chi tiết tin ở CẤP GỐC: /<slug> — alias khớp trang gốc nidqc.gov.vn (bỏ prefix /tin-tuc).
// Nuxt ưu tiên route tĩnh (gioi-thieu-chung, tin-tuc, lien-he...) hơn [slug] nên chỉ
// những đường dẫn không khớp trang tĩnh mới rơi vào đây -> resolve như 1 bài tin.
const route = useRoute()
const reqUrl = useRequestURL()
const slug = computed(() => String(route.params.slug))

const { data } = await useCachedData(`news-slug-${route.params.slug}`, async () => {
  // Phân giải alias -> nid qua endpoint cache (/__resolve-news) thay vì lặp JSON:API
  // (trước đây mở node lần đầu tốn ~16 request, cold ~15s). Xem server/utils/newsAlias.ts.
  const { nid } = await $fetch('/__resolve-news', { params: { alias: `/${slug.value}` } })
  if (!nid) return null
  const res = await fetchJsonApi('/node/news', {
    'filter[drupal_internal__nid]': nid, include: 'field_image,field_category,field_attachments',
  })
  if (!res.data.length) return null
  const n = res.data[0]
  const categoryName = termLabel(n, 'field_category', res.included)
  const node = {
    nid,
    title: n.attributes.title,
    date: formatDate(n.attributes.field_date || n.attributes.created),
    tag: n.attributes.field_tag || categoryName,
    category: categoryName,
    image: imageUrl(n, res.included),
    body: n.attributes.body?.processed || '',
    attachments: attachments(n, res.included),
  }

  const mapItem = (x, inc) => ({
    id: x.attributes.drupal_internal__nid,
    title: x.attributes.title,
    date: formatDate(x.attributes.field_date || x.attributes.created),
    category: termLabel(x, 'field_category', inc),
    image: imageUrl(x, inc),
    alias: x.attributes.path?.alias || `/tin-tuc/${x.attributes.drupal_internal__nid}`,
  })
  const listParams = (extra) => ({
    'filter[status]': 1, sort: '-field_date', 'page[limit]': 8,
    include: 'field_image,field_category', ...extra,
  })
  const catFilter = categoryName ? {
    'filter[cat][condition][path]': 'field_category.name',
    'filter[cat][condition][operator]': 'IN',
    'filter[cat][condition][value][0]': categoryName,
  } : {}

  const [relRes, latestRes] = await Promise.all([
    categoryName ? fetchJsonApi('/node/news', listParams(catFilter)) : Promise.resolve({ data: [], included: [] }),
    fetchJsonApi('/node/news', listParams({})),
  ])

  const latest = latestRes.data.map((x) => mapItem(x, latestRes.included)).filter((i) => i.id !== nid)
  let related = relRes.data.map((x) => mapItem(x, relRes.included)).filter((i) => i.id !== nid)
  if (!related.length) related = latest
  return { node, related: related.slice(0, 3), latest: latest.slice(0, 5) }
})

const node = computed(() => data.value?.node || null)
const related = computed(() => data.value?.related || [])
const latest = computed(() => data.value?.latest || [])

const excerpt = computed(() => {
  const t = (node.value?.body || '').replace(/<[^>]+>/g, ' ').replace(/\s+/g, ' ').trim()
  return t.slice(0, 160) || node.value?.title || ''
})

// Chia sẻ
const shareUrl = computed(() => reqUrl.href)
const fbShare = computed(() => `https://www.facebook.com/sharer/sharer.php?u=${encodeURIComponent(shareUrl.value)}`)
const copied = ref(false)
async function copyLink() {
  try {
    await navigator.clipboard.writeText(shareUrl.value)
    copied.value = true
    setTimeout(() => { copied.value = false }, 2000)
  } catch { /* clipboard bị chặn: bỏ qua */ }
}

useSeoMeta({
  title: () => (node.value ? node.value.title : 'Chi tiết tin') + ' — NIDQC',
  description: () => excerpt.value,
  ogTitle: () => node.value?.title,
  ogDescription: () => excerpt.value,
  ogType: 'article',
  ogImage: () => node.value?.image,
  ogUrl: () => shareUrl.value,
})
// Canonical trỏ về alias gốc.
useHead({
  link: [{ rel: 'canonical', href: () => shareUrl.value }],
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

    <section style="background:#fff;padding:34px 0 56px;">
      <div data-container style="max-width:1200px;margin:0 auto;padding:0 24px;">
        <p v-if="!node" style="color:#b00020;font-size:16px;">Không tìm thấy tin.</p>

        <div v-else class="nidqc-article-grid">
          <!-- ===== CỘT BÀI VIẾT ===== -->
          <article style="min-width:0;">
            <!-- Meta + chia sẻ -->
            <div style="display:flex;align-items:center;justify-content:space-between;gap:16px;flex-wrap:wrap;padding-bottom:18px;margin-bottom:24px;border-bottom:1px solid #ECECEC;">
              <div style="display:flex;align-items:center;gap:12px;color:#777;font-size:13px;">
                <span v-if="node.tag" style="background:#0F3093;color:#fff;font-family:'Lexend',sans-serif;font-size:11px;font-weight:700;letter-spacing:0.5px;text-transform:uppercase;padding:5px 12px;">{{ node.tag }}</span>
                <span style="display:inline-flex;align-items:center;gap:6px;">
                  <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/></svg>
                  {{ node.date }}
                </span>
              </div>
              <div style="display:flex;align-items:center;gap:9px;">
                <span style="font-size:12.5px;color:#777;">Chia sẻ:</span>
                <a :href="fbShare" target="_blank" rel="noopener" aria-label="Chia sẻ Facebook" class="nidqc-share-btn"
                  style="display:inline-flex;align-items:center;justify-content:center;width:34px;height:34px;border:1px solid #CCCCCC;color:#0F3093;text-decoration:none;">
                  <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#0F3093" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 2h-3a5 5 0 0 0-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 0 1 1-1h3z"/></svg>
                </a>
                <button type="button" @click="copyLink" :aria-label="copied ? 'Đã sao chép link' : 'Sao chép link'" class="nidqc-share-btn"
                  style="display:inline-flex;align-items:center;justify-content:center;width:34px;height:34px;border:1px solid #CCCCCC;background:#fff;color:#0F3093;padding:0;">
                  <svg v-if="!copied" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#0F3093" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"/><path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"/></svg>
                  <svg v-else width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#1a7f37" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>
                </button>
              </div>
            </div>

            <!-- Ảnh đại diện -->
            <figure v-if="node.image" style="margin:0 0 26px;">
              <img :src="node.image" :alt="node.title" style="width:100%;height:auto;display:block;border:1px solid #E4E9F0;">
            </figure>

            <!-- Nội dung -->
            <div v-if="node.body" v-html="node.body" class="nidqc-article-body"></div>
            <p v-else style="color:#777;font-style:italic;">Nội dung chi tiết đang được cập nhật.</p>

            <!-- Tài liệu đính kèm -->
            <div v-if="node.attachments && node.attachments.length" style="margin-top:34px;background:#F5F8FC;border:1px solid #E4E9F0;padding:20px 22px;">
              <h2 style="display:flex;align-items:center;gap:8px;font-family:'Lexend',sans-serif;font-size:15px;font-weight:700;color:#0F3093;margin:0 0 14px;">
                <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="#0F3093" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m21.44 11.05-9.19 9.19a6 6 0 0 1-8.49-8.49l9.19-9.19a4 4 0 0 1 5.66 5.66l-9.2 9.19a2 2 0 0 1-2.83-2.83l8.49-8.48"/></svg>
                Tài liệu đính kèm
              </h2>
              <ul style="list-style:none;padding:0;margin:0;display:flex;flex-direction:column;gap:2px;">
                <li v-for="(f, i) in node.attachments" :key="i">
                  <a :href="f.url" target="_blank" rel="noopener noreferrer" class="nidqc-aside-item"
                    style="display:flex;align-items:center;gap:10px;padding:10px 10px;color:#212529;text-decoration:none;font-size:14.5px;">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#1D6AC5" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="flex:0 0 16px;"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6M16 13H8M16 17H8M10 9H8"/></svg>
                    <span style="text-decoration:underline;">{{ f.label }}</span>
                  </a>
                </li>
              </ul>
            </div>

            <!-- Quay lại -->
            <div style="margin-top:32px;padding-top:22px;border-top:1px solid #ECECEC;">
              <NuxtLink to="/tin-tuc" style="display:inline-flex;align-items:center;gap:8px;color:#0F3093;font-weight:600;font-size:14.5px;text-decoration:none;">
                <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="m12 19-7-7 7-7M19 12H5"/></svg>
                Quay lại danh sách tin tức
              </NuxtLink>
            </div>
          </article>

          <!-- ===== SIDEBAR: TIN MỚI NHẤT ===== -->
          <aside class="nidqc-article-aside" style="min-width:0;">
            <div style="border:1px solid #E4E9F0;background:#fff;">
              <div style="background:#0F3093;color:#fff;padding:13px 18px;">
                <h2 style="font-family:'Lexend',sans-serif;font-weight:700;font-size:15px;letter-spacing:0.4px;text-transform:uppercase;margin:0;">Tin mới nhất</h2>
              </div>
              <div>
                <NuxtLink v-for="item in latest" :key="item.id" :to="item.alias" class="nidqc-aside-item"
                  style="display:flex;gap:12px;padding:13px 16px;border-bottom:1px solid #ECECEC;text-decoration:none;align-items:flex-start;">
                  <div style="width:72px;height:56px;flex:0 0 72px;background:#E8F0F7;overflow:hidden;">
                    <img v-if="item.image" :src="item.image" alt="" style="width:100%;height:100%;object-fit:cover;">
                  </div>
                  <span style="flex:1;min-width:0;">
                    <span class="nidqc-aside-title nidqc-clamp-2" style="display:block;font-size:13.5px;line-height:19px;color:#212529;font-weight:500;">{{ item.title }}</span>
                    <span style="display:block;font-size:12px;color:#777;margin-top:5px;">{{ item.date }}</span>
                  </span>
                </NuxtLink>
              </div>
              <NuxtLink to="/tin-tuc" style="display:block;text-align:center;padding:12px;color:#1D6AC5;font-size:13.5px;font-weight:500;text-decoration:none;">Xem tất cả →</NuxtLink>
            </div>
          </aside>
        </div>
      </div>
    </section>

    <!-- ===== TIN LIÊN QUAN ===== -->
    <section v-if="node && related.length" style="background:#F5F8FC;border-top:1px solid #E4E9F0;padding:48px 0 56px;">
      <div data-container style="max-width:1200px;margin:0 auto;padding:0 24px;">
        <div style="display:flex;align-items:center;justify-content:space-between;gap:16px;margin-bottom:26px;">
          <h2 style="font-family:'Lexend',sans-serif;font-weight:700;font-size:22px;color:#0F3093;margin:0;padding-left:12px;border-left:4px solid #0F3093;">Tin liên quan</h2>
          <NuxtLink to="/tin-tuc" style="color:#1D6AC5;font-size:14px;text-decoration:none;white-space:nowrap;">Xem tất cả →</NuxtLink>
        </div>
        <div class="nidqc-grid-3" style="display:grid;grid-template-columns:repeat(3,1fr);gap:22px;">
          <NuxtLink v-for="item in related" :key="item.id" :to="item.alias" class="nidqc-related-card"
            style="display:block;background:#fff;border:1px solid #E4E9F0;text-decoration:none;">
            <div class="nidqc-related-thumb" style="position:relative;width:100%;padding-top:56.25%;background:#0D2870;">
              <img v-if="item.image" :src="item.image" alt="" style="position:absolute;inset:0;width:100%;height:100%;object-fit:cover;">
              <span v-if="item.category" style="position:absolute;top:12px;left:12px;background:rgba(15,48,147,0.92);color:#fff;font-family:'Lexend',sans-serif;font-size:10.5px;font-weight:700;letter-spacing:0.5px;text-transform:uppercase;padding:4px 10px;">{{ item.category }}</span>
            </div>
            <div style="padding:16px 18px 20px;">
              <h3 class="nidqc-related-title nidqc-clamp-2" style="font-family:'Lexend',sans-serif;font-weight:600;font-size:16px;line-height:23px;color:#212529;margin:0 0 12px;min-height:46px;">{{ item.title }}</h3>
              <div style="display:flex;align-items:center;gap:6px;color:#777;font-size:12.5px;">
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/></svg>
                {{ item.date }}
              </div>
            </div>
          </NuxtLink>
        </div>
      </div>
    </section>
  </div>
</template>
