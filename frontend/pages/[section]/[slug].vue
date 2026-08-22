<script setup>
// Trang tĩnh hai cấp: /dich-vu/…, /danh-muc-nang-luc/…, /hoat-dong-chuyen-mon/…
//
// Feedback 6 và 8 yêu cầu mỗi dịch vụ, mỗi mục "Danh mục năng lực" và mỗi hoạt động
// chuyên môn đều bấm được sang một bài viết riêng. Một route động duy nhất phục vụ
// cả ba nhóm — Nuxt ưu tiên route có đoạn tĩnh (pages/tin-tuc/[id].vue) hơn route
// này, nên tin tức vẫn đi đúng đường cũ.
const route = useRoute()
const reqUrl = useRequestURL()
const alias = computed(() => `/${route.params.section}/${route.params.slug}`)

// Nhãn breadcrumb theo đoạn đầu của đường dẫn. Đoạn lạ thì không hiện cấp nào chứ
// không đoán bừa một cái tên.
const SECTION_LABELS = {
  'dich-vu': { label: 'Dịch vụ', to: '/dich-vu' },
  // "Danh mục năng lực" là một khối nằm TRONG trang Dịch vụ, không có trang riêng.
  'danh-muc-nang-luc': { label: 'Danh mục năng lực', to: '/dich-vu' },
  'hoat-dong-chuyen-mon': { label: 'Hoạt động chuyên môn', to: '/hoat-dong-chuyen-mon' },
}
const section = computed(() => SECTION_LABELS[String(route.params.section)] || null)

const { data: page, status, error } = await useCachedData(
  `static-page-${route.params.section}-${route.params.slug}`,
  () => fetchStaticPage(alias.value),
)

const notFound = () => createError({ statusCode: 404, statusMessage: 'Không tìm thấy trang', fatal: true })

// SSR kết luận ngay: handler đã chạy xong, không có node nghĩa là alias không tồn tại.
if (import.meta.server && !page.value) {
  throw notFound()
}

// Client TUYỆT ĐỐI không được kết luận 404 khi fetch chưa xong — xem ghi chú dài ở
// pages/[slug].vue: useAsyncData trả null ngay lúc hydrate nếu payload rỗng.
if (import.meta.client) {
  watch(status, (value) => {
    if (value === 'success' && !page.value) showError(notFound())
    else if (value === 'error' && error.value) showError(error.value)
  }, { immediate: true })
}

const excerpt = computed(() => {
  const text = (page.value?.body || '').replace(/<[^>]+>/g, ' ').replace(/\s+/g, ' ').trim()
  return text.slice(0, 160) || page.value?.title || ''
})

useSeoMeta({
  title: () => `${page.value?.title || 'Trang'} — Viện Kiểm nghiệm thuốc Trung ương`,
  description: () => excerpt.value,
  ogTitle: () => page.value?.title,
  ogDescription: () => excerpt.value,
  ogType: 'article',
  ogUrl: () => reqUrl.href,
})
useHead({ link: [{ rel: 'canonical', href: () => reqUrl.href }] })
</script>

<template>
  <div>
    <PageBand :title="page ? page.title : 'Trang'" :crumbs="section ? [section.label] : []" />

    <section style="background:#fff;padding:34px 0 56px;">
      <div data-container style="max-width:900px;margin:0 auto;padding:0 24px;">
        <p v-if="!page" style="color:#b00020;font-size:16px;">Không tìm thấy trang.</p>

        <template v-else>
          <figure v-if="page.image" style="margin:0 0 26px;">
            <img :src="page.image" :alt="page.title" style="width:100%;height:auto;display:block;border:1px solid #E4E9F0;">
          </figure>

          <div v-if="page.body" v-html="page.body" class="nidqc-article-body"></div>
          <p v-else style="color:#777;font-style:italic;">Nội dung chi tiết đang được cập nhật.</p>

          <div v-if="page.attachments && page.attachments.length" style="margin-top:34px;background:#F5F8FC;border:1px solid #E4E9F0;padding:20px 22px;">
            <h2 style="display:flex;align-items:center;gap:8px;font-family:'Lexend',sans-serif;font-size:15px;font-weight:700;color:#0F3093;margin:0 0 14px;">
              <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="#0F3093" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m21.44 11.05-9.19 9.19a6 6 0 0 1-8.49-8.49l9.19-9.19a4 4 0 0 1 5.66 5.66l-9.2 9.19a2 2 0 0 1-2.83-2.83l8.49-8.48"/></svg>
              Tài liệu đính kèm
            </h2>
            <ul style="list-style:none;padding:0;margin:0;display:flex;flex-direction:column;gap:2px;">
              <li v-for="(f, i) in page.attachments" :key="i">
                <a :href="f.url" target="_blank" rel="noopener noreferrer" class="nidqc-aside-item"
                  style="display:flex;align-items:center;gap:10px;padding:10px;color:#212529;text-decoration:none;font-size:14.5px;">
                  <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#1D6AC5" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="flex:0 0 16px;"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6M16 13H8M16 17H8M10 9H8"/></svg>
                  <span style="text-decoration:underline;">{{ f.label }}</span>
                </a>
              </li>
            </ul>
          </div>

          <div v-if="section" style="margin-top:32px;padding-top:22px;border-top:1px solid #ECECEC;">
            <NuxtLink :to="section.to" style="display:inline-flex;align-items:center;gap:8px;color:#0F3093;font-weight:600;font-size:14.5px;text-decoration:none;">
              <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="m12 19-7-7 7-7M19 12H5"/></svg>
              Quay lại {{ section.label }}
            </NuxtLink>
          </div>
        </template>
      </div>
    </section>
  </div>
</template>
