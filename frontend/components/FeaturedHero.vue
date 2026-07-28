<script setup>
// Khối hero "Tin tức & sự kiện" ở trang chủ — slider các tin đã tích "Tin nổi bật".
//
// Cả N slide đều nằm sẵn trong DOM (xếp chồng trong CÙNG một ô grid, chuyển cảnh bằng
// opacity) chứ không chỉ render slide đang hiện: SEO/GEO là yêu cầu bắt buộc của dự án
// (ADR-004), crawler phải đọc được tiêu đề + link của mọi tin nổi bật ngay trong HTML
// mà server trả về, không phụ thuộc JS. Slide ẩn dùng visibility:hidden nên cũng tự
// rơi khỏi thứ tự tab, không cần quản lý tabindex bằng tay.
const props = defineProps({
  // [{ id, title, date, image, alias }] — đã map ở trang gọi.
  items: { type: Array, required: true },
  // Nhịp tự chạy; đủ dài để đọc hết một tiêu đề dài.
  interval: { type: Number, default: 6000 },
})

const active = ref(0)
const paused = ref(false)
const thumbStrip = ref(null)
let timer = null

const count = computed(() => props.items.length)

function go(index) {
  if (count.value === 0) return
  active.value = ((index % count.value) + count.value) % count.value
}

function select(index) {
  go(index)
  // Bấm tay thì cấp lại trọn một nhịp, không để slide vừa chọn biến mất sau 0.5s.
  restart()
}

// Autoplay chỉ chạy phía client. Người dùng chọn "giảm chuyển động" thì đứng yên hẳn —
// slider tự chạy là thứ gây khó chịu nhất với nhóm này, thumbnail vẫn bấm được bình thường.
let reducedMotion = false

function stop() {
  if (timer !== null) {
    clearInterval(timer)
    timer = null
  }
}

function restart() {
  stop()
  if (reducedMotion || paused.value || count.value < 2) return
  timer = setInterval(() => go(active.value + 1), props.interval)
}

onMounted(() => {
  reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches
  restart()
})

onBeforeUnmount(stop)

watch(paused, restart)

// Thumbnail đang chọn phải luôn nằm trong tầm nhìn: trên mobile dải thumbnail cuộn
// ngang nên slide thứ 4-5 sẽ nằm ngoài khung nếu không kéo theo.
watch(active, (index) => {
  const strip = thumbStrip.value
  if (!strip) return
  const thumb = strip.children[index]
  thumb?.scrollIntoView({ behavior: reducedMotion ? 'auto' : 'smooth', block: 'nearest', inline: 'nearest' })
})
</script>

<template>
  <div
    class="hero"
    role="region"
    aria-roledescription="carousel"
    aria-label="Tin nổi bật"
    @mouseenter="paused = true"
    @mouseleave="paused = false"
    @focusin="paused = true"
    @focusout="paused = false"
  >
    <div class="hero__stage">
      <NuxtLink
        v-for="(item, i) in items"
        :key="item.id"
        :to="item.alias"
        class="hero__slide"
        :class="{ 'is-active': i === active }"
        :aria-hidden="i === active ? undefined : 'true'"
      >
        <div class="hero__media">
          <img
            v-if="item.image"
            :src="item.image"
            alt=""
            class="hero__img"
            :loading="i === 0 ? 'eager' : 'lazy'"
            :fetchpriority="i === 0 ? 'high' : undefined"
          >
          <span class="hero__badge">Tin nổi bật</span>
        </div>
        <div class="hero__body">
          <div class="hero__date">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><rect x="3" y="4" width="18" height="18" rx="2" /><path d="M16 2v4M8 2v4M3 10h18" /></svg>
            <span>Cập nhật: {{ item.date }}</span>
          </div>
          <h2 class="hero__title">{{ item.title }}</h2>
        </div>
      </NuxtLink>
    </div>

    <div v-if="count > 1" ref="thumbStrip" class="hero__thumbs">
      <button
        v-for="(item, i) in items"
        :key="`t-${item.id}`"
        type="button"
        class="hero__thumb"
        :class="{ 'is-active': i === active }"
        :aria-label="`Xem tin nổi bật ${i + 1}: ${item.title}`"
        :aria-current="i === active ? 'true' : undefined"
        @click="select(i)"
      >
        <img v-if="item.image" :src="item.image" alt="" loading="lazy">
        <span v-else class="hero__thumb-empty" aria-hidden="true"></span>
        <span class="hero__thumb-veil"></span>
      </button>
    </div>
  </div>
</template>

<style scoped>
.hero {
  display: flex;
  flex-direction: column;
  background: #fff;
  border: 1px solid #CCCCCC;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.06);
}

/* Các slide chồng lên nhau trong cùng một ô grid -> chiều cao khối bằng slide cao
   nhất, không giật khi chuyển tin có tiêu đề dài ngắn khác nhau. */
.hero__stage {
  display: grid;
  flex: 1;
}
.hero__slide {
  grid-area: 1 / 1;
  display: flex;
  flex-direction: column;
  text-decoration: none;
  opacity: 0;
  visibility: hidden;
  transition: opacity .5s ease;
}
.hero__slide.is-active {
  opacity: 1;
  visibility: visible;
}

.hero__media {
  position: relative;
  width: 100%;
  height: 340px;
  overflow: hidden;
  background: #0D2870;
}
.hero__img {
  width: 100%;
  height: 100%;
  object-fit: cover;
  transition: transform .5s ease;
}
.hero__slide:hover .hero__img {
  transform: scale(1.03);
}
.hero__badge {
  position: absolute;
  top: 16px;
  left: 16px;
  background: #0F3093;
  color: #fff;
  font-family: 'Lexend', sans-serif;
  font-weight: 700;
  font-size: 11.5px;
  letter-spacing: 0.6px;
  text-transform: uppercase;
  padding: 6px 12px;
}

.hero__body {
  padding: 22px 24px 20px;
}
.hero__date {
  display: flex;
  align-items: center;
  gap: 7px;
  color: #777;
  font-size: 12.5px;
  margin-bottom: 10px;
}
.hero__title {
  font-family: 'Lexend', sans-serif;
  font-weight: 700;
  font-size: 25px;
  line-height: 31px;
  color: #212529;
  margin: 0;
  /* Khoá 2 dòng: tiêu đề tin của Viện rất dài, không clamp thì khối hero cao vọt
     và dải thumbnail bị đẩy xuống dưới nếp gấp. */
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}
.hero__slide:hover .hero__title {
  color: #0F3093;
}

/* ===== Dải thumbnail: vừa là điều hướng, vừa là chỉ báo đang ở tin thứ mấy ===== */
.hero__thumbs {
  display: flex;
  gap: 10px;
  /* padding-top chừa chỗ cho vòng viền ngoài của ô đang chọn: overflow-x:auto kéo
     theo overflow-y:auto nên viền tràn ra ngoài sẽ bị cắt. */
  padding: 3px 24px 22px;
  overflow-x: auto;
  scrollbar-width: none;
}
.hero__thumbs::-webkit-scrollbar {
  display: none;
}
.hero__thumb {
  position: relative;
  flex: 1 1 0;
  min-width: 88px;
  aspect-ratio: 16 / 10;
  padding: 0;
  border: 0;
  background: #E8F0F7;
  cursor: pointer;
  overflow: hidden;
  /* Viền chọn vẽ bằng shadow để không làm đổi kích thước ô khi active. */
  box-shadow: inset 0 0 0 2px transparent;
  transition: box-shadow .25s ease;
}
.hero__thumb img {
  width: 100%;
  height: 100%;
  object-fit: cover;
  display: block;
}
.hero__thumb-empty {
  display: block;
  width: 100%;
  height: 100%;
}
/* Lớp phủ làm mờ các tin chưa chọn, để mắt bám vào tin đang hiện ở khối lớn. */
.hero__thumb-veil {
  position: absolute;
  inset: 0;
  background: rgba(13, 40, 112, 0.45);
  transition: opacity .25s ease;
}
.hero__thumb:hover .hero__thumb-veil,
.hero__thumb.is-active .hero__thumb-veil {
  opacity: 0;
}
/* Vòng trắng bên trong (nổi trên ảnh chụp) + vòng xanh bên ngoài (nổi trên nền thẻ
   trắng): chỉ một trong hai là không đủ tương phản với mọi ảnh tin. */
.hero__thumb.is-active {
  box-shadow: inset 0 0 0 2px #fff, 0 0 0 2px #0F3093;
}
.hero__thumb:focus-visible {
  outline: 2px solid #1D6AC5;
  outline-offset: 2px;
}

@media (max-width: 900px) {
  .hero__media { height: 240px; }
  .hero__title { font-size: 20px; line-height: 27px; }
  .hero__body { padding: 18px 18px 16px; }
  .hero__thumbs { padding: 0 18px 18px; }
  .hero__thumb { flex: 0 0 96px; }
}

@media (prefers-reduced-motion: reduce) {
  .hero__slide,
  .hero__img,
  .hero__thumb,
  .hero__thumb-veil { transition: none; }
  .hero__slide:hover .hero__img { transform: none; }
}
</style>
