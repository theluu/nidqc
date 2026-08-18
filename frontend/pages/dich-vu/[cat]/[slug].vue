<script setup>
// Chi tiết một bài viết dịch vụ: /dich-vu/<danh-muc>/<tieu-de>
//
// Route có đoạn tĩnh `dich-vu` nên Nuxt ưu tiên hơn pages/[section]/[slug].vue.
// Node đọc qua /api/v1/page (tra alias trên bảng path_alias) — bundle service_post
// đã nằm trong ALLOWED_BUNDLES của StaticPageController; sidebar "Bài viết khác"
// lấy từ chính endpoint danh sách của dịch vụ.
const route = useRoute()
const reqUrl = useRequestURL()
const cat = computed(() => String(route.params.cat))
const alias = computed(() => `/dich-vu/${route.params.cat}/${route.params.slug}`)

const { data, status, error } = await useCachedData(
  () => `service-post-${route.params.cat}-${route.params.slug}`,
  async () => {
    // Hai request song song: bài đang mở + các bài khác cùng dịch vụ. Chờ tuần tự
    // sẽ cộng dồn round-trip mà chúng chẳng phụ thuộc nhau.
    const [node, list] = await Promise.all([
      fetchStaticPage(alias.value),
      fetchServiceList({ cat: cat.value, limit: 6 }),
    ])
    if (!node) return null

    return {
      node,
      category: list?.category || null,
      others: (list?.data || [])
        // Bỏ chính bài đang mở khỏi sidebar.
        .filter((item) => item.id !== node.nid)
        .slice(0, 5)
        .map((item) => ({
          id: item.id,
          title: item.title,
          date: formatDate(item.created),
          image: newsImageUrl(item.image),
          alias: item.alias,
        })),
    }
  },
)

const notFound = () => createError({ statusCode: 404, statusMessage: 'Không tìm thấy trang', fatal: true })

// SSR kết luận ngay: handler đã chạy xong, không có node nghĩa là alias không tồn tại.
if (import.meta.server && !data.value?.node) {
  throw notFound()
}

// Client TUYỆT ĐỐI không được kết luận 404 khi fetch chưa xong — xem ghi chú dài ở
// pages/[slug].vue: useAsyncData trả null ngay lúc hydrate nếu payload rỗng.
if (import.meta.client) {
  watch(status, (value) => {
    if (value === 'success' && !data.value?.node) showError(notFound())
    else if (value === 'error' && error.value) showError(error.value)
  }, { immediate: true })
}

const node = computed(() => data.value?.node || null)
const category = computed(() => data.value?.category || null)
const others = computed(() => data.value?.others || [])
const backUrl = computed(() => category.value?.url || `/dich-vu/${cat.value}`)

const excerpt = computed(() => {
  const text = (node.value?.body || '').replace(/<[^>]+>/g, ' ').replace(/\s+/g, ' ').trim()
  return text.slice(0, 160) || node.value?.title || ''
})

useSeoMeta({
  title: () => `${node.value?.title || 'Bài viết dịch vụ'} — Viện Kiểm nghiệm thuốc Trung ương`,
  description: () => excerpt.value,
  ogTitle: () => node.value?.title,
  ogDescription: () => excerpt.value,
  ogType: 'article',
  ogImage: () => node.value?.image,
  ogUrl: () => reqUrl.href,
})
useHead({ link: [{ rel: 'canonical', href: () => reqUrl.href }] })
</script>

<template>
  <div>
    <PageBand
      :title="node ? node.title : 'Bài viết dịch vụ'"
      :crumbs="category ? ['Dịch vụ', category.label] : ['Dịch vụ']"
    />

    <section class="post">
      <div class="post__wrap">
        <p v-if="!node" class="post__missing">Không tìm thấy bài viết.</p>

        <div v-else class="nidqc-article-grid">
          <article style="min-width:0;">
            <div class="post__meta">
              <span v-if="category" class="post__tag">{{ category.label }}</span>
              <span class="post__date">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><rect x="3" y="4" width="18" height="18" rx="2" /><path d="M16 2v4M8 2v4M3 10h18" /></svg>
                {{ formatDate(node.created) }}
              </span>
            </div>

            <figure v-if="node.image" class="post__hero">
              <img :src="node.image" :alt="node.title">
            </figure>

            <div v-if="node.body" v-html="node.body" class="nidqc-article-body"></div>
            <p v-else class="post__pending">Nội dung chi tiết đang được cập nhật.</p>

            <div v-if="node.attachments && node.attachments.length" class="post__files">
              <h2>
                <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="#0F3093" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m21.44 11.05-9.19 9.19a6 6 0 0 1-8.49-8.49l9.19-9.19a4 4 0 0 1 5.66 5.66l-9.2 9.19a2 2 0 0 1-2.83-2.83l8.49-8.48" /></svg>
                Tài liệu đính kèm
              </h2>
              <ul>
                <li v-for="(f, i) in node.attachments" :key="i">
                  <a :href="f.url" target="_blank" rel="noopener noreferrer" class="nidqc-aside-item">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#1D6AC5" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z" /><path d="M14 2v6h6M16 13H8M16 17H8M10 9H8" /></svg>
                    <span>{{ f.label }}</span>
                  </a>
                </li>
              </ul>
            </div>

            <div class="post__back">
              <NuxtLink :to="backUrl">
                <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="m12 19-7-7 7-7M19 12H5" /></svg>
                Quay lại {{ category ? category.label : 'danh sách dịch vụ' }}
              </NuxtLink>
            </div>
          </article>

          <aside v-if="others.length" class="nidqc-article-aside" style="min-width:0;">
            <div class="post__aside">
              <div class="post__aside-head">
                <h2>Bài viết khác</h2>
              </div>
              <div>
                <NuxtLink v-for="item in others" :key="item.id" :to="item.alias" class="nidqc-aside-item post__aside-item">
                  <span class="post__aside-thumb">
                    <img v-if="item.image" :src="item.image" alt="" loading="lazy">
                  </span>
                  <span style="flex:1;min-width:0;">
                    <span class="nidqc-aside-title nidqc-clamp-2 post__aside-title">{{ item.title }}</span>
                    <span class="post__aside-date">{{ item.date }}</span>
                  </span>
                </NuxtLink>
              </div>
              <NuxtLink :to="backUrl" class="post__aside-all">Xem tất cả →</NuxtLink>
            </div>
          </aside>
        </div>
      </div>
    </section>
  </div>
</template>

<style scoped>
.post {
  background: #fff;
  padding: 34px 0 56px;
}
.post__wrap {
  max-width: 1200px;
  margin: 0 auto;
  padding: 0 24px;
}
.post__missing {
  color: #b00020;
  font-size: 16px;
}

.post__meta {
  display: flex;
  align-items: center;
  gap: 12px;
  flex-wrap: wrap;
  color: #777;
  font-size: 13px;
  padding-bottom: 18px;
  margin-bottom: 24px;
  border-bottom: 1px solid #ECECEC;
}
.post__tag {
  background: #0F3093;
  color: #fff;
  font-family: 'Lexend', sans-serif;
  font-size: 11px;
  font-weight: 700;
  letter-spacing: .5px;
  text-transform: uppercase;
  padding: 5px 12px;
}
.post__date {
  display: inline-flex;
  align-items: center;
  gap: 6px;
}

.post__hero {
  margin: 0 0 26px;
}
.post__hero img {
  width: 100%;
  height: auto;
  display: block;
  border: 1px solid #E4E9F0;
}
.post__pending {
  color: #777;
  font-style: italic;
}

.post__files {
  margin-top: 34px;
  background: #F5F8FC;
  border: 1px solid #E4E9F0;
  padding: 20px 22px;
}
.post__files h2 {
  display: flex;
  align-items: center;
  gap: 8px;
  font-family: 'Lexend', sans-serif;
  font-size: 15px;
  font-weight: 700;
  color: #0F3093;
  margin: 0 0 14px;
}
.post__files ul {
  list-style: none;
  padding: 0;
  margin: 0;
  display: flex;
  flex-direction: column;
  gap: 2px;
}
.post__files a {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 10px;
  color: #212529;
  text-decoration: none;
  font-size: 14.5px;
}
.post__files a span {
  text-decoration: underline;
}

.post__back {
  margin-top: 32px;
  padding-top: 22px;
  border-top: 1px solid #ECECEC;
}
.post__back a {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  color: #0F3093;
  font-weight: 600;
  font-size: 14.5px;
  text-decoration: none;
}

.post__aside {
  border: 1px solid #E4E9F0;
  background: #fff;
}
.post__aside-head {
  background: #0F3093;
  color: #fff;
  padding: 13px 18px;
}
.post__aside-head h2 {
  font-family: 'Lexend', sans-serif;
  font-weight: 700;
  font-size: 15px;
  letter-spacing: .4px;
  text-transform: uppercase;
  margin: 0;
}
.post__aside-item {
  display: flex;
  gap: 12px;
  padding: 13px 16px;
  border-bottom: 1px solid #ECECEC;
  text-decoration: none;
  align-items: flex-start;
}
.post__aside-thumb {
  width: 72px;
  height: 56px;
  flex: 0 0 72px;
  background: #E8F0F7;
  overflow: hidden;
}
.post__aside-thumb img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}
.post__aside-title {
  display: block;
  font-size: 13.5px;
  line-height: 19px;
  color: #212529;
  font-weight: 500;
}
.post__aside-date {
  display: block;
  font-size: 12px;
  color: #777;
  margin-top: 5px;
}
.post__aside-all {
  display: block;
  text-align: center;
  padding: 12px;
  color: #1D6AC5;
  font-size: 13.5px;
  font-weight: 500;
  text-decoration: none;
}
</style>
