<script setup>
// Thư viện Video & Hình ảnh.
//
// Cấu trúc bám theo block Tin nổi bật: một ô media LỚN ở trên, dải thumbnail bên dưới
// vừa để điều hướng vừa làm chỉ báo đang xem bài thứ mấy. Bấm vào ô lớn thì mở
// lightbox xem trọn bộ ảnh/video của bài đó.
//
// Khác Tin nổi bật ở một điểm: KHÔNG tự chạy. Ô lớn ở đây là video/ảnh người dùng
// đang định xem, tự nhảy sang bài khác giữa chừng là phá thao tác của họ.
const props = defineProps({
  // [{ id, title, kind, thumbnail, count, items[] }] — đã chuẩn hoá ở API.
  posts: { type: Array, required: true },
})

const active = ref(0)
const thumbStrip = ref(null)

const count = computed(() => props.posts.length)

function go(next) {
  if (!count.value) return
  active.value = ((active.value + next) % count.value + count.value) % count.value
}

function select(i) {
  active.value = i
}

// Thumbnail đang chọn phải nằm trong tầm nhìn khi dải cuộn ngang trên mobile.
watch(active, (i) => {
  const thumb = thumbStrip.value?.children[i]
  thumb?.scrollIntoView({ behavior: 'smooth', block: 'nearest', inline: 'nearest' })
})

// ===== Lightbox =====
const openPost = ref(null)
const index = ref(0)
const closeBtn = ref(null)
let lastFocused = null

const current = computed(() => openPost.value?.items?.[index.value] ?? null)
const total = computed(() => openPost.value?.items?.length ?? 0)

function open(post) {
  if (!post?.items?.length) return
  lastFocused = document.activeElement
  openPost.value = post
  index.value = 0
  // Khoá cuộn nền để trang phía sau không trôi khi lăn chuột trong lightbox.
  document.body.style.overflow = 'hidden'
  nextTick(() => closeBtn.value?.focus())
}

function close() {
  openPost.value = null
  document.body.style.overflow = ''
  // Trả tiêu điểm về đúng chỗ vừa bấm, không nhảy lên đầu trang.
  lastFocused?.focus?.()
  lastFocused = null
}

function step(next) {
  if (!total.value) return
  index.value = ((index.value + next) % total.value + total.value) % total.value
}

function onKey(event) {
  if (!openPost.value) return
  if (event.key === 'Escape') close()
  else if (event.key === 'ArrowRight') step(1)
  else if (event.key === 'ArrowLeft') step(-1)
}

onMounted(() => window.addEventListener('keydown', onKey))
onBeforeUnmount(() => {
  window.removeEventListener('keydown', onKey)
  document.body.style.overflow = ''
})

// nocookie + autoplay: người dùng vừa chủ động bấm mở nên autoplay là đúng ý.
function youtubeSrc(id) {
  return `https://www.youtube-nocookie.com/embed/${id}?autoplay=1&rel=0&modestbranding=1`
}

function formatDate(iso) {
  const d = new Date(iso)
  const p = (n) => String(n).padStart(2, '0')
  return `${p(d.getDate())}/${p(d.getMonth() + 1)}/${d.getFullYear()}`
}
</script>

<template>
  <div class="lib">
    <div class="lib__head">
      <div class="lib__title-wrap">
        <span class="lib__bar"></span>
        <h2 class="lib__title">Thư viện Video &amp; Hình ảnh</h2>
      </div>
      <div v-if="count > 1" class="lib__nav">
        <button type="button" class="lib__arrow" aria-label="Mục trước" @click="go(-1)">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="m15 18-6-6 6-6" /></svg>
        </button>
        <button type="button" class="lib__arrow" aria-label="Mục tiếp theo" @click="go(1)">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="m9 18 6-6-6-6" /></svg>
        </button>
      </div>
    </div>

    <div class="lib__box">
      <!-- Ô media lớn: mọi bài đều nằm trong DOM, chuyển cảnh bằng opacity — crawler
           đọc được tiêu đề của cả thư viện chứ không riêng bài đang hiện (SEO/GEO). -->
      <div class="lib__stage">
        <button v-for="(post, i) in posts" :key="post.id" type="button"
          class="lib__slide" :class="{ 'is-active': i === active }"
          :aria-hidden="i === active ? undefined : 'true'"
          :aria-label="`Mở thư viện: ${post.title}`" @click="open(post)">
          <span class="lib__media">
            <img v-if="post.thumbnail" :src="post.thumbnail" :alt="post.title" :loading="i === 0 ? 'eager' : 'lazy'">
            <span class="lib__kind">
              <svg v-if="post.kind === 'video'" width="13" height="13" viewBox="0 0 24 24" fill="currentColor"><path d="M8 5v14l11-7z" /></svg>
              <svg v-else width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="2" /><circle cx="8.5" cy="8.5" r="1.5" /><path d="m21 15-5-5L5 21" /></svg>
              {{ post.kind === 'video' ? 'Video' : 'Hình ảnh' }} · {{ post.count }}
            </span>
            <span class="lib__play" aria-hidden="true">
              <svg v-if="post.kind === 'video'" width="26" height="26" viewBox="0 0 24 24" fill="currentColor"><path d="M8 5v14l11-7z" /></svg>
              <svg v-else width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><path d="M15 3h6v6M9 21H3v-6M21 3l-7 7M3 21l7-7" /></svg>
            </span>
          </span>
          <span class="lib__body">
            <span class="lib__date">
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><rect x="3" y="4" width="18" height="18" rx="2" /><path d="M16 2v4M8 2v4M3 10h18" /></svg>
              {{ formatDate(post.created) }}
            </span>
            <span class="lib__name">{{ post.title }}</span>
          </span>
        </button>
      </div>

      <div v-if="count > 1" ref="thumbStrip" class="lib__thumbs">
        <button v-for="(post, i) in posts" :key="`t-${post.id}`" type="button"
          class="lib__thumb" :class="{ 'is-active': i === active }"
          :aria-label="`Xem mục ${i + 1}: ${post.title}`"
          :aria-current="i === active ? 'true' : undefined" @click="select(i)">
          <img v-if="post.thumbnail" :src="post.thumbnail" alt="" loading="lazy">
          <span v-else class="lib__thumb-blank"></span>
          <span v-if="post.kind === 'video'" class="lib__thumb-play" aria-hidden="true">
            <svg width="12" height="12" viewBox="0 0 24 24" fill="currentColor"><path d="M8 5v14l11-7z" /></svg>
          </span>
          <span class="lib__thumb-veil"></span>
        </button>
      </div>
    </div>

    <Teleport to="body">
      <div v-if="openPost" class="viewer" role="dialog" aria-modal="true" :aria-label="openPost.title" @mousedown.self="close">
        <div class="viewer__box">
          <div class="viewer__top">
            <h3 class="viewer__title">{{ openPost.title }}</h3>
            <button ref="closeBtn" type="button" class="viewer__close" aria-label="Đóng" @click="close">
              <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><path d="M6 6l12 12M18 6 6 18" /></svg>
            </button>
          </div>

          <div class="viewer__stage">
            <!-- :key ép Vue thay hẳn phần tử khi đổi media: iframe YouTube bị gỡ và
                 <video> bị huỷ -> video cũ dừng hẳn, không còn tiếng chạy ngầm. -->
            <div :key="index" class="viewer__media">
              <img v-if="current?.type === 'image'" :src="current.src" :alt="current.alt || openPost.title">
              <iframe v-else-if="current?.type === 'youtube'" :src="youtubeSrc(current.video_id)"
                :title="openPost.title" allow="accelerometer; autoplay; encrypted-media; gyroscope; picture-in-picture"
                allowfullscreen></iframe>
              <video v-else-if="current?.type === 'video'" :src="current.src" controls autoplay playsinline></video>
            </div>

            <template v-if="total > 1">
              <button type="button" class="viewer__arrow is-prev" aria-label="Trước" @click="step(-1)">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="m15 18-6-6 6-6" /></svg>
              </button>
              <button type="button" class="viewer__arrow is-next" aria-label="Tiếp" @click="step(1)">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="m9 18 6-6-6-6" /></svg>
              </button>
            </template>
          </div>

          <div class="viewer__foot">
            <span class="viewer__count">{{ index + 1 }} / {{ total }}</span>
            <div v-if="total > 1" class="viewer__thumbs">
              <button v-for="(item, i) in openPost.items" :key="i" type="button"
                class="viewer__thumb" :class="{ 'is-active': i === index }"
                :aria-label="`Xem mục ${i + 1}`" :aria-current="i === index ? 'true' : undefined"
                @click="index = i">
                <img v-if="item.thumbnail" :src="item.thumbnail" alt="" loading="lazy">
                <span v-else class="viewer__thumb-blank"></span>
                <span v-if="item.type !== 'image'" class="viewer__thumb-play" aria-hidden="true">
                  <svg width="12" height="12" viewBox="0 0 24 24" fill="currentColor"><path d="M8 5v14l11-7z" /></svg>
                </span>
              </button>
            </div>
          </div>
        </div>
      </div>
    </Teleport>
  </div>
</template>

<style scoped>
.lib { display: flex; flex-direction: column; }

.lib__head {
  display: flex; align-items: center; justify-content: space-between; gap: 16px;
  border-bottom: 2px solid #0F3093; padding-bottom: 12px; margin-bottom: 20px;
}
.lib__title-wrap { display: flex; align-items: center; gap: 11px; min-width: 0; }
.lib__bar { width: 6px; height: 26px; background: #0F3093; flex: 0 0 6px; }
.lib__title {
  font-family: 'Lexend', sans-serif; font-weight: 700; font-size: 24px;
  letter-spacing: 0.3px; text-transform: uppercase; color: #212529; margin: 0;
}
.lib__nav { display: flex; gap: 8px; flex: 0 0 auto; }
.lib__arrow {
  width: 34px; height: 34px; display: flex; align-items: center; justify-content: center;
  background: #fff; border: 1px solid #CCCCCC; color: #0F3093; cursor: pointer;
  transition: background .15s ease, color .15s ease, border-color .15s ease;
}
.lib__arrow:hover { background: #0F3093; border-color: #0F3093; color: #fff; }

.lib__box {
  display: flex; flex-direction: column; background: #fff;
  border: 1px solid #CCCCCC; box-shadow: 0 2px 4px rgba(0, 0, 0, 0.06);
}

/* Các slide chồng trong cùng ô grid -> chiều cao khối bằng slide cao nhất, đổi mục
   không giật. Giống hệt cách FeaturedHero dựng khối Tin nổi bật. */
.lib__stage { display: grid; flex: 1; }
.lib__slide {
  grid-area: 1 / 1; display: flex; flex-direction: column; text-align: left;
  padding: 0; border: 0; background: none; cursor: pointer;
  opacity: 0; visibility: hidden; transition: opacity .45s ease;
}
.lib__slide.is-active { opacity: 1; visibility: visible; }
.lib__slide:focus-visible { outline: 2px solid #1D6AC5; outline-offset: -2px; }

.lib__media {
  position: relative; display: block; width: 100%; height: 300px;
  overflow: hidden; background: #0D2870;
}
.lib__media img { width: 100%; height: 100%; object-fit: cover; transition: transform .5s ease; }
.lib__slide:hover .lib__media img { transform: scale(1.03); }
.lib__kind {
  position: absolute; top: 14px; left: 14px; display: inline-flex; align-items: center; gap: 5px;
  background: #0F3093; color: #fff; font-family: 'Lexend', sans-serif; font-weight: 700;
  font-size: 11px; letter-spacing: 0.5px; text-transform: uppercase; padding: 5px 10px;
}
.lib__play {
  position: absolute; inset: 0; margin: auto; width: 60px; height: 60px;
  display: flex; align-items: center; justify-content: center;
  background: rgba(15, 48, 147, 0.85); color: #fff; border-radius: 50%;
  transition: background .2s ease, transform .2s ease;
}
.lib__slide:hover .lib__play { background: #0F3093; transform: scale(1.08); }

.lib__body { display: block; padding: 18px 20px 14px; }
.lib__date {
  display: flex; align-items: center; gap: 7px;
  color: #777; font-size: 12.5px; margin-bottom: 8px;
}
.lib__name {
  display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;
  font-family: 'Lexend', sans-serif; font-weight: 700; font-size: 19px; line-height: 26px; color: #212529;
}
.lib__slide:hover .lib__name { color: #0F3093; }

.lib__thumbs {
  display: flex; gap: 10px; padding: 3px 20px 18px;
  overflow-x: auto; scrollbar-width: none;
}
.lib__thumbs::-webkit-scrollbar { display: none; }
.lib__thumb {
  position: relative; flex: 1 1 0; min-width: 84px; aspect-ratio: 16 / 10;
  padding: 0; border: 0; background: #E8F0F7; cursor: pointer; overflow: hidden;
  box-shadow: inset 0 0 0 2px transparent; transition: box-shadow .25s ease;
}
.lib__thumb img { width: 100%; height: 100%; object-fit: cover; display: block; }
.lib__thumb-blank { display: block; width: 100%; height: 100%; }
.lib__thumb-play {
  position: absolute; inset: 0; margin: auto; width: 24px; height: 24px; z-index: 2;
  display: flex; align-items: center; justify-content: center;
  background: rgba(0, 0, 0, 0.55); color: #fff; border-radius: 50%;
}
.lib__thumb-veil {
  position: absolute; inset: 0; background: rgba(13, 40, 112, 0.45); transition: opacity .25s ease;
}
.lib__thumb:hover .lib__thumb-veil, .lib__thumb.is-active .lib__thumb-veil { opacity: 0; }
/* Vòng trắng trong + xanh ngoài: một mình vòng xanh chìm nghỉm trên ảnh chụp. */
.lib__thumb.is-active { box-shadow: inset 0 0 0 2px #fff, 0 0 0 2px #0F3093; }
.lib__thumb:focus-visible { outline: 2px solid #1D6AC5; outline-offset: 2px; }

/* ===== Lightbox ===== */
.viewer {
  position: fixed; inset: 0; z-index: 90; background: rgba(6, 18, 48, 0.92);
  display: flex; align-items: center; justify-content: center; padding: 24px;
}
.viewer__box { width: min(1100px, 100%); max-height: 100%; display: flex; flex-direction: column; gap: 12px; }
.viewer__top { display: flex; align-items: flex-start; gap: 16px; }
.viewer__title {
  flex: 1; min-width: 0; margin: 0; color: #fff;
  font-family: 'Lexend', sans-serif; font-weight: 600; font-size: 16px; line-height: 22px;
}
.viewer__close {
  flex: 0 0 auto; width: 36px; height: 36px; display: flex; align-items: center; justify-content: center;
  background: rgba(255, 255, 255, 0.12); border: 0; color: #fff; cursor: pointer; border-radius: 50%;
}
.viewer__close:hover { background: rgba(255, 255, 255, 0.26); }

.viewer__stage { position: relative; flex: 1; min-height: 0; }
.viewer__media {
  width: 100%; aspect-ratio: 16 / 9; max-height: calc(100vh - 210px);
  display: flex; align-items: center; justify-content: center; background: #000;
}
/* contain: ảnh dọc/ngang đều xem trọn vẹn, không bị cắt như cover. */
.viewer__media img { width: 100%; height: 100%; object-fit: contain; }
.viewer__media iframe, .viewer__media video { width: 100%; height: 100%; border: 0; display: block; background: #000; }

.viewer__arrow {
  position: absolute; top: 50%; transform: translateY(-50%);
  width: 42px; height: 42px; display: flex; align-items: center; justify-content: center;
  background: rgba(0, 0, 0, 0.55); border: 0; color: #fff; cursor: pointer; border-radius: 50%;
}
.viewer__arrow:hover { background: rgba(0, 0, 0, 0.8); }
.viewer__arrow.is-prev { left: 10px; }
.viewer__arrow.is-next { right: 10px; }

.viewer__foot { display: flex; align-items: center; gap: 14px; min-height: 62px; }
.viewer__count { color: rgba(255, 255, 255, 0.75); font-size: 12.5px; flex: 0 0 auto; }
.viewer__thumbs { display: flex; gap: 8px; overflow-x: auto; scrollbar-width: none; padding: 2px; }
.viewer__thumbs::-webkit-scrollbar { display: none; }
.viewer__thumb {
  position: relative; flex: 0 0 78px; width: 78px; aspect-ratio: 16 / 10;
  padding: 0; border: 0; background: #1A2C57; cursor: pointer; overflow: hidden;
  opacity: .55; transition: opacity .15s ease, box-shadow .15s ease;
}
.viewer__thumb img { width: 100%; height: 100%; object-fit: cover; display: block; }
.viewer__thumb-blank { display: block; width: 100%; height: 100%; }
.viewer__thumb-play {
  position: absolute; inset: 0; margin: auto; width: 22px; height: 22px;
  display: flex; align-items: center; justify-content: center;
  background: rgba(0, 0, 0, 0.6); color: #fff; border-radius: 50%;
}
.viewer__thumb:hover { opacity: .85; }
.viewer__thumb.is-active { opacity: 1; box-shadow: inset 0 0 0 2px #fff; }

@media (max-width: 900px) {
  .lib__media { height: 230px; }
  .lib__title { font-size: 19px; }
  .lib__name { font-size: 17px; line-height: 23px; }
  .lib__thumb { flex: 0 0 92px; }
}
@media (max-width: 520px) {
  .lib__media { height: 190px; }
  .lib__play { width: 48px; height: 48px; }
  .viewer { padding: 0; }
  .viewer__box { height: 100%; gap: 8px; padding: 10px; }
  .viewer__media { max-height: calc(100vh - 190px); }
  .viewer__arrow { width: 36px; height: 36px; }
}

@media (prefers-reduced-motion: reduce) {
  .lib__slide, .lib__media img, .lib__play, .lib__arrow,
  .lib__thumb, .lib__thumb-veil, .viewer__thumb { transition: none; }
  .lib__slide:hover .lib__media img { transform: none; }
  .lib__slide:hover .lib__play { transform: none; }
}
</style>
