<script setup>
// Kết quả tìm kiếm Tin tức — dùng chung NewsGrid/NewsCard với /tin-tuc để hai trang
// không lệch nhau khi đổi design.
const PAGE_SIZE = 12
const route = useRoute()
const listTop = ref(null)

const keyword = computed(() => String(route.query.q || '').trim())
const page = computed(() => Math.max(1, parseInt(String(route.query.trang || '1'), 10) || 1))
const isValidKeyword = computed(() => keyword.value.length >= 2 && keyword.value.length <= 200)

// Key PHẢI chứa từ khoá và trang. Với key tĩnh, mọi truy vấn dùng chung một ô dữ
// liệu trong payload nên kết quả của truy vấn này đè lên truy vấn kia khi hydrate.
const { data: result, pending, error } = await useAsyncData(
  () => `news-search-${keyword.value}-p${page.value}`,
  async () => {
    if (!isValidKeyword.value) {
      return { data: [], meta: { total: 0, page: 0, limit: PAGE_SIZE } }
    }
    const response = await searchNews(keyword.value, page.value - 1, PAGE_SIZE)
    return {
      ...response,
      data: response.data.map((item) => ({
        ...item,
        date: formatDate(item.created),
        image: newsSearchImageUrl(item.image),
        // NewsCard dùng `alias`; API tìm kiếm trả trường `url`.
        alias: item.url,
      })),
    }
  },
  { watch: [keyword, page] },
)

const totalPages = computed(() => Math.max(1, Math.ceil((result.value?.meta.total || 0) / PAGE_SIZE)))

function changePage(nextPage) {
  navigateTo({ query: { q: keyword.value, trang: String(nextPage) } })
  if (import.meta.client) {
    listTop.value?.scrollIntoView({ behavior: 'smooth', block: 'start' })
  }
}

useSeoMeta({
  title: () => keyword.value ? `Tìm kiếm “${keyword.value}” — NIDQC` : 'Tìm kiếm Tin tức — NIDQC',
  description: 'Tìm kiếm trong nội dung Tin tức của Viện Kiểm nghiệm thuốc Trung ương.',
  robots: 'noindex,follow',
})
</script>

<template>
  <div>
    <PageBand title="Tìm kiếm Tin tức" description="Tìm trong tiêu đề và nội dung Tin tức của Viện Kiểm nghiệm thuốc Trung ương." />

    <section class="news-search">
      <div ref="listTop" class="news-search__wrap">
        <form class="news-search__form" action="/tim-kiem" method="get">
          <label for="news-search-query">Từ khóa</label>
          <div class="news-search__form-row">
            <input id="news-search-query" name="q" type="search" :value="keyword" minlength="2" maxlength="200" required placeholder="Nhập từ khóa tìm kiếm…">
            <button type="submit">Tìm kiếm</button>
          </div>
        </form>

        <p v-if="keyword && !isValidKeyword" class="news-search__message">
          Từ khóa phải có từ 2 đến 200 ký tự.
        </p>
        <p v-else-if="!keyword" class="news-search__message">
          Vui lòng nhập từ khóa để tìm kiếm Tin tức.
        </p>
        <p v-else-if="error" class="news-search__message" role="alert">
          Không thể tải kết quả tìm kiếm. Vui lòng thử lại sau.
        </p>
        <p v-else-if="!pending" class="news-search__summary">
          Tìm thấy <strong>{{ result?.meta.total || 0 }}</strong> kết quả cho “{{ keyword }}”.
        </p>

        <p v-if="isValidKeyword && !pending && !error && !result?.data.length" class="news-search__message">
          Không tìm thấy Tin tức phù hợp.
        </p>

        <NewsGrid v-else-if="result?.data.length" :items="result.data" :loading="pending" />

        <Pagination v-if="isValidKeyword && !error" :current="page" :total="totalPages" @change="changePage" />
      </div>
    </section>
  </div>
</template>

<style scoped>
.news-search {
  padding: 28px 0 64px;
  background: var(--nidqc-white);
}
.news-search__wrap {
  max-width: var(--nidqc-container);
  margin: 0 auto;
  padding: 0 var(--nidqc-gutter);
  scroll-margin-top: 80px;
}
.news-search__form {
  max-width: 720px;
  margin-bottom: 28px;
}
.news-search__form label {
  display: block;
  margin-bottom: 8px;
  color: var(--nidqc-text);
  font-weight: 600;
}
.news-search__form-row {
  display: flex;
  gap: 10px;
}
.news-search__form input {
  min-width: 0;
  flex: 1;
  height: 46px;
  padding: 0 14px;
  border: 1px solid var(--nidqc-border-strong);
  border-radius: 6px;
  color: var(--nidqc-text);
  font: inherit;
}
.news-search__form button {
  min-height: 46px;
  padding: 0 22px;
  border: 1px solid var(--nidqc-primary);
  border-radius: 6px;
  background: var(--nidqc-primary);
  color: var(--nidqc-white);
  font: inherit;
  font-weight: 600;
  cursor: pointer;
}
.news-search__form input:focus-visible,
.news-search__form button:focus-visible {
  outline: 3px solid var(--nidqc-primary-light);
  outline-offset: 3px;
}
.news-search__summary,
.news-search__message {
  margin: 0 0 24px;
  color: var(--nidqc-text-light);
}
@media (max-width: 520px) {
  .news-search__form-row { flex-direction: column; }
  .news-search__form button { width: 100%; }
}
</style>
