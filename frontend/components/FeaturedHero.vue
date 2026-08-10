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
          <img
            v-if="item.image"
            :src="item.image"
            alt=""
            class="hero__img"
            :loading="i === 0 ? 'eager' : 'lazy'"
            :fetchpriority="i === 0 ? 'high' : undefined"
          >
          <div class="hero__overlay">
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

      <template v-if="count > 1">
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
      </template>
    </div>
  </div>
</template>

<style scoped>
.hero {
  display: flex;
  flex-direction: column;
  background: #0D2870;
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
/* min-height chứ không phải height cố định: khối hero nằm cùng hàng grid với cột
   tab bên phải và align-items:stretch, nên nó cao bằng cột cao hơn. Khoá height thì
   phần dư lộ ra thành một dải xanh trơn dưới ảnh. Ảnh trải tuyệt đối để phủ kín dù
   chiều cao thực là bao nhiêu. */
.hero__slide {
  position: relative;
  flex: 0 0 100%;
  min-width: 100%;
  min-height: 430px;
  text-decoration: none;
  background: #0D2870;
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

/* Chữ nằm ĐÈ lên ảnh: khối hero cao cố định nên tiêu đề dài ngắn khác nhau không
   làm giật chiều cao khi chuyển slide. Nền chuyển sắc để chữ trắng luôn đọc được
   dù ảnh nền sáng hay tối. */
.hero__overlay {
  position: absolute;
  inset: auto 0 0 0;
  padding: 60px 26px 22px;
  background: linear-gradient(to top, rgba(8, 24, 66, 0.94) 12%, rgba(8, 24, 66, 0.78) 45%, rgba(8, 24, 66, 0) 100%);
  color: #fff;
}
.hero__badge {
  display: inline-block;
  background: #E11D48;
  font-family: 'Lexend', sans-serif;
  font-weight: 700;
  font-size: 11px;
  letter-spacing: 0.6px;
  text-transform: uppercase;
  padding: 5px 11px;
  margin-bottom: 12px;
}
.hero__title {
  font-family: 'Lexend', sans-serif;
  font-weight: 700;
  font-size: 25px;
  line-height: 33px;
  color: #fff;
  margin: 0 0 9px;
  /* Khoá 2 dòng: tiêu đề tin của Viện rất dài, không clamp thì chữ tràn hết ảnh. */
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}
.hero__slide:hover .hero__title {
  text-decoration: underline;
  text-underline-offset: 4px;
}
.hero__summary {
  font-size: 14.5px;
  line-height: 22px;
  color: rgba(255, 255, 255, 0.86);
  margin: 0 0 12px;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}
.hero__date {
  display: inline-flex;
  align-items: center;
  gap: 7px;
  font-size: 12.5px;
  color: rgba(255, 255, 255, 0.75);
}

/* ===== Điều hướng ===== */
.hero__nav {
  position: absolute;
  top: 40%;
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
  right: 26px;
  bottom: 24px;
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
  .hero__slide { min-height: 300px; }
  .hero__overlay { padding: 48px 18px 18px; }
  .hero__title { font-size: 19px; line-height: 26px; }
  .hero__summary { -webkit-line-clamp: 2; font-size: 13.5px; line-height: 20px; }
  .hero__nav { opacity: 1; width: 34px; height: 34px; }
  .hero__dots { right: 18px; bottom: 16px; }
}

@media (prefers-reduced-motion: reduce) {
  .hero__track,
  .hero__img,
  .hero__dot { transition: none; }
  .hero__slide:hover .hero__img { transform: none; }
}
</style>
