<script setup>
// Khối tin nổi bật ở trang chủ — MỘT tin mỗi lần, trượt ngang tự động.
//
// Feedback 08/2026 (tham chiếu nifc.gov.vn): mỗi lần chỉ hiện một tin gồm ảnh,
// tiêu đề và mô tả; chuyển cảnh bằng hiệu ứng lướt. Bản trước xếp chồng slide và
// mờ dần, kèm dải thumbnail — thumbnail đã bỏ vì với một tin mỗi lần thì chấm tròn
// đủ để biết đang ở tin thứ mấy, mà lại không ăn mất chiều cao của ảnh.
//
// Cả N slide đều nằm sẵn trong DOM (một track flex, dịch bằng transform) chứ không
// chỉ render slide đang hiện: SEO/GEO là yêu cầu bắt buộc của dự án (ADR-004),
// crawler phải đọc được tiêu đề + link của mọi tin nổi bật ngay trong HTML server
// trả về, không phụ thuộc JS.
const props = defineProps({
  // [{ id, title, date, summary, image, alias }] — đã map ở trang gọi.
  items: { type: Array, required: true },
  // Nhịp tự chạy; đủ dài để đọc hết một tiêu đề dài.
  interval: { type: Number, default: 6000 },
})

const active = ref(0)
const paused = ref(false)
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
// slider tự chạy là thứ gây khó chịu nhất với nhóm này, nút bấm vẫn dùng bình thường.
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
    <div class="hero__viewport">
      <div class="hero__track" :style="{ transform: `translateX(-${active * 100}%)` }">
        <NuxtLink
          v-for="(item, i) in items"
          :key="item.id"
          :to="item.alias"
          class="hero__slide"
          :aria-hidden="i === active ? undefined : 'true'"
          :tabindex="i === active ? undefined : -1"
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
          </div>
          <div class="hero__body">
            <span class="hero__badge">Tin nổi bật</span>
            <h2 class="hero__title">{{ item.title }}</h2>
            <p v-if="item.summary" class="hero__summary">{{ item.summary }}</p>
            <span class="hero__date">
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><rect x="3" y="4" width="18" height="18" rx="2" /><path d="M16 2v4M8 2v4M3 10h18" /></svg>
              {{ item.date }}
            </span>
          </div>
        </NuxtLink>
      </div>

      <div v-if="count > 1" class="hero__controls" aria-hidden="false">
        <button type="button" class="hero__nav is-prev" aria-label="Tin nổi bật trước" @click="select(active - 1)">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="m15 18-6-6 6-6" /></svg>
        </button>
        <button type="button" class="hero__nav is-next" aria-label="Tin nổi bật kế tiếp" @click="select(active + 1)">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="m9 18 6-6-6-6" /></svg>
        </button>

        <div class="hero__dots">
          <button
            v-for="(item, i) in items"
            :key="`d-${item.id}`"
            type="button"
            class="hero__dot"
            :class="{ 'is-active': i === active }"
            :aria-label="`Xem tin nổi bật ${i + 1}: ${item.title}`"
            :aria-current="i === active ? 'true' : undefined"
            @click="select(i)"
          />
        </div>
      </div>
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

/* Viewport cắt phần thừa; track dài N lần và dịch ngang -> hiệu ứng lướt. */
.hero__viewport {
  position: relative;
  flex: 1;
  overflow: hidden;
}
.hero__track {
  display: flex;
  height: 100%;
  transition: transform .55s cubic-bezier(.4, 0, .2, 1);
}
/* Ảnh TRÊN, chữ DƯỚI — không còn lớp chữ đè lên ảnh (feedback 21/08: "Phần tiêu
   đề đặt dưới hình ảnh, không chồng lên hình ảnh").
   Slide là cột: ảnh khoá tỷ lệ 16:9, khối chữ nở ra lấp phần cao còn lại. Hero nằm
   cùng hàng grid với cột tab (align-items:stretch) nên chiều cao do cột cao hơn
   quyết định; phần dư rơi vào khối chữ nền trắng chứ không lộ ra dải xanh trơn. */
.hero__slide {
  display: flex;
  flex-direction: column;
  flex: 0 0 100%;
  min-width: 100%;
  text-decoration: none;
  background: #fff;
}
.hero__media {
  position: relative;
  aspect-ratio: 16 / 9;
  background: #0D2870;
  overflow: hidden;
}
.hero__img {
  position: absolute;
  inset: 0;
  width: 100%;
  height: 100%;
  object-fit: cover;
  display: block;
  transition: transform .5s ease;
}
.hero__slide:hover .hero__img {
  transform: scale(1.03);
}

/* Khối chữ nền trắng. Tiêu đề và mô tả đều clamp cứng số dòng: tiêu đề tin của
   Viện dài ngắn rất khác nhau, không khoá thì mỗi lần đổi slide cả khối lại nhảy
   chiều cao. */
.hero__body {
  flex: 1;
  display: flex;
  flex-direction: column;
  align-items: flex-start;
  padding: 16px 22px 20px;
}
.hero__badge {
  display: inline-block;
  background: #E11D48;
  color: #fff;
  font-family: 'Lexend', sans-serif;
  font-weight: 700;
  font-size: 11px;
  letter-spacing: 0.6px;
  text-transform: uppercase;
  padding: 5px 11px;
  margin-bottom: 11px;
}
.hero__title {
  font-family: 'Lexend', sans-serif;
  font-weight: 700;
  font-size: 22px;
  line-height: 30px;
  color: var(--nidqc-text, #212529);
  margin: 0 0 8px;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}
.hero__slide:hover .hero__title {
  color: var(--nidqc-primary, #0F3093);
  text-decoration: underline;
  text-underline-offset: 4px;
}
.hero__summary {
  font-size: 14.5px;
  line-height: 22px;
  color: #555;
  margin: 0 0 12px;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}
/* KHÔNG margin-top:auto ghim ngày xuống đáy: bài chưa nhập tóm tắt thì giữa tiêu
   đề và ngày hở ra một mảng trắng cả trăm pixel. Cho chữ dồn lên trên, phần cao dư
   (khối hero cao bằng cột tab bên cạnh) rơi xuống dưới cùng, không ai để ý. */
.hero__date {
  display: inline-flex;
  align-items: center;
  gap: 7px;
  font-size: 12.5px;
  color: #777;
}

/* Lớp điều khiển trùm ĐÚNG vùng ảnh (cùng tỷ lệ 16:9 với .hero__media) — nếu để
   nguyên trong viewport thì mũi tên và chấm tròn rơi xuống đè lên khối chữ trắng,
   chấm trắng trên nền trắng coi như biến mất. */
.hero__controls {
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  aspect-ratio: 16 / 9;
  pointer-events: none;
}
.hero__controls > * { pointer-events: auto; }

/* ===== Điều hướng ===== */
.hero__nav {
  position: absolute;
  top: 50%;
  transform: translateY(-50%);
  display: flex;
  align-items: center;
  justify-content: center;
  width: 40px;
  height: 40px;
  padding: 0;
  border: 0;
  border-radius: 50%;
  background: rgba(8, 24, 66, 0.5);
  color: #fff;
  cursor: pointer;
  opacity: 0;
  transition: opacity .2s ease, background .2s ease;
}
.hero:hover .hero__nav,
.hero__nav:focus-visible { opacity: 1; }
.hero__nav:hover { background: rgba(8, 24, 66, 0.82); }
.hero__nav.is-prev { left: 14px; }
.hero__nav.is-next { right: 14px; }
.hero__nav:focus-visible { outline: 2px solid #fff; outline-offset: 2px; }

.hero__dots {
  position: absolute;
  right: 16px;
  bottom: 14px;
  display: flex;
  gap: 8px;
}
.hero__dot {
  width: 9px;
  height: 9px;
  padding: 0;
  border: 0;
  border-radius: 50%;
  background: rgba(255, 255, 255, 0.45);
  cursor: pointer;
  transition: background .2s ease, width .2s ease;
}
.hero__dot.is-active {
  width: 24px;
  border-radius: 999px;
  background: #fff;
}
.hero__dot:focus-visible { outline: 2px solid #fff; outline-offset: 3px; }

@media (max-width: 900px) {
  .hero__body { padding: 14px 16px 16px; }
  .hero__title { font-size: 18px; line-height: 25px; }
  .hero__summary { font-size: 13.5px; line-height: 20px; }
  .hero__nav { opacity: 1; width: 34px; height: 34px; }
  .hero__dots { right: 12px; bottom: 10px; }
}

@media (prefers-reduced-motion: reduce) {
  .hero__track,
  .hero__img,
  .hero__dot { transition: none; }
  .hero__slide:hover .hero__img { transform: none; }
}
</style>
