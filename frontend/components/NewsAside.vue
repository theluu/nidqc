<script setup>
// Cột phải của trang chuyên mục tin tức (/tin-tuc?cat=…).
//
// Dựng theo cột phải của trang mẫu https://nifc.gov.vn/noi-bat, gồm ba khối xếp dọc:
//   1. Chuyên mục   — điều hướng dọc giữa các chuyên mục, mục đang xem tô đậm
//   2. Video        — một video mới nhất trong Thư viện, bấm vào mở bài
//   3. Liên kết web — logo + tên các cơ quan liên quan
//
// Dữ liệu lấy từ đúng những endpoint trang chủ đã dùng (khoá cache 'home-media' và
// 'home-blocks' trùng với trang chủ) nên đi từ trang chủ sang đây không tốn thêm
// request nào.
const props = defineProps({
  // [{ id, label, count }] — danh sách chuyên mục đã fetch ở trang cha.
  categories: { type: Array, default: () => [] },
  // id chuyên mục đang xem, hoặc 'all'.
  activeCat: { type: String, default: 'all' },
})

const emit = defineEmits(['select'])

const { data: mediaPosts } = await useCachedData('home-media', () => fetchMediaLibrary(12))
const { data: blocks } = await useCachedData('home-blocks', fetchHomeBlocks)

// Ưu tiên bài có video; không có thì lấy bài media mới nhất để khối không trống.
const videoPost = computed(() => {
  const list = mediaPosts.value || []
  return list.find((p) => p.kind === 'video') || list[0] || null
})
const webLinks = computed(() => (blocks.value?.web_links || []).slice(0, 6))
</script>

<template>
  <aside class="aside">
    <!-- 1. Chuyên mục -->
    <section v-if="categories.length" class="aside__box">
      <h2 class="aside__title">Chuyên mục</h2>
      <ul class="aside__cats">
        <li>
          <button type="button" class="aside__cat" :class="{ 'is-active': activeCat === 'all' }" @click="emit('select', 'all')">
            <span>Tất cả</span>
          </button>
        </li>
        <li v-for="c in categories" :key="c.id">
          <button type="button" class="aside__cat" :class="{ 'is-active': activeCat === c.id }" @click="emit('select', c.id)">
            <span>{{ c.label }}</span>
            <span v-if="c.count" class="aside__count">{{ c.count }}</span>
          </button>
        </li>
      </ul>
    </section>

    <!-- 2. Video -->
    <section v-if="videoPost" class="aside__box">
      <h2 class="aside__title">Video</h2>
      <NuxtLink :to="videoPost.alias" class="aside__video">
        <span class="aside__video-media">
          <img v-if="mediaUrl(videoPost.thumbnail)" :src="mediaUrl(videoPost.thumbnail)" :alt="videoPost.title" loading="lazy">
          <span class="aside__play" aria-hidden="true">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor"><path d="M8 5v14l11-7z" /></svg>
          </span>
        </span>
        <span class="aside__video-title">{{ videoPost.title }}</span>
      </NuxtLink>
    </section>

    <!-- 3. Liên kết web -->
    <section v-if="webLinks.length" class="aside__box">
      <h2 class="aside__title">Liên kết web</h2>
      <ul class="aside__links">
        <li v-for="(l, i) in webLinks" :key="i">
          <component :is="l.url ? 'a' : 'div'" :href="l.url || undefined"
            :target="l.url ? '_blank' : undefined" :rel="l.url ? 'noopener' : undefined" class="aside__link">
            <span class="aside__logo">
              <img v-if="l.image" :src="l.image" :alt="l.title" loading="lazy">
            </span>
            <span class="aside__link-label">{{ l.title }}</span>
          </component>
        </li>
      </ul>
    </section>
  </aside>
</template>

<style scoped>
.aside {
  display: flex;
  flex-direction: column;
  gap: 24px;
  min-width: 0;
}
.aside__box { min-width: 0; }
.aside__title {
  margin: 0 0 12px;
  padding: 10px 14px;
  background: var(--nidqc-primary);
  color: #fff;
  font-family: 'Lexend', sans-serif;
  font-size: 14px;
  font-weight: 700;
  letter-spacing: 0.4px;
  text-transform: uppercase;
}

/* 1. Chuyên mục */
.aside__cats,
.aside__links {
  list-style: none;
  margin: 0;
  padding: 0;
  border: 1px solid #E4E9F0;
  border-bottom: 0;
}
.aside__cat {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 10px;
  width: 100%;
  padding: 11px 14px;
  border: 0;
  border-bottom: 1px solid #E4E9F0;
  background: #fff;
  font: inherit;
  font-size: 13.5px;
  color: #333;
  text-align: left;
  cursor: pointer;
  transition: background .15s ease, color .15s ease;
}
.aside__cat:hover { background: #F3F7FC; color: var(--nidqc-primary); }
.aside__cat.is-active {
  background: #EEF4FB;
  color: var(--nidqc-primary);
  font-weight: 600;
  /* Vạch trái đánh dấu mục đang xem — không dùng riêng màu chữ vì người mù màu
     đọc không ra khác biệt. */
  box-shadow: inset 3px 0 0 var(--nidqc-primary);
}
.aside__count {
  flex: 0 0 auto;
  font-size: 11.5px;
  color: #888;
  font-variant-numeric: tabular-nums;
}

/* 2. Video */
.aside__video { display: block; text-decoration: none; }
.aside__video-media {
  position: relative;
  display: block;
  aspect-ratio: 16 / 9;
  background: #0D2870;
  overflow: hidden;
}
.aside__video-media img { width: 100%; height: 100%; object-fit: cover; display: block; }
.aside__play {
  position: absolute;
  inset: 0;
  display: flex;
  align-items: center;
  justify-content: center;
  color: #fff;
}
.aside__play svg {
  background: rgba(13, 40, 112, 0.82);
  border-radius: 50%;
  padding: 10px;
  width: 44px;
  height: 44px;
}
.aside__video:hover .aside__play svg { background: var(--nidqc-primary); }
.aside__video-title {
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
  margin-top: 9px;
  font-size: 13.5px;
  line-height: 19px;
  font-weight: 600;
  color: #212529;
}
.aside__video:hover .aside__video-title { color: var(--nidqc-primary); }

/* 3. Liên kết web */
.aside__link {
  display: flex;
  align-items: center;
  gap: 11px;
  padding: 9px 14px;
  border-bottom: 1px solid #E4E9F0;
  text-decoration: none;
  transition: background .15s ease;
}
a.aside__link:hover { background: #F3F7FC; }
.aside__logo {
  flex: 0 0 30px;
  width: 30px;
  height: 30px;
  display: flex;
  align-items: center;
  justify-content: center;
  background: #fff;
  border: 1px solid #ECECEC;
  overflow: hidden;
}
.aside__logo img { width: 100%; height: 100%; object-fit: contain; }
.aside__link-label {
  min-width: 0;
  font-size: 13px;
  line-height: 18px;
  color: #333;
}
a.aside__link:hover .aside__link-label { color: var(--nidqc-primary); }

.aside__cat:focus-visible,
.aside__video:focus-visible,
.aside__link:focus-visible { outline: 2px solid #1D6AC5; outline-offset: -2px; }

@media (prefers-reduced-motion: reduce) {
  .aside__cat, .aside__link { transition: none; }
}
</style>
