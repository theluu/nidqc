<script setup>
// Thanh "Tin mới" ngay dưới main menu — mỗi lần hiện một tiêu đề, chuyển cảnh TRƯỢT
// từ phải sang trái (feedback). Có nút điều hướng (desktop) / chấm tròn (mobile),
// tự chạy và dừng khi rê chuột vào.
//
// Giống FeaturedHero: cả N tiêu đề nằm sẵn trong DOM (xếp chồng cùng ô grid) để crawler
// đọc được toàn bộ, chỉ đổi transform/opacity khi chạy. Chiều cao cố định một dòng nên
// thanh này KHÔNG làm xê dịch nội dung bên dưới khi đổi tin (tránh layout shift).
const props = defineProps({
  // [{ id, title, alias }]
  items: { type: Array, required: true },
  interval: { type: Number, default: 4000 },
  // Ngày/giờ hiện tại (layout truyền xuống). Đặt ở đây chứ không ở thanh menu vì menu
  // ngang 8 mục đã chiếm gần hết container; thanh này còn chỗ và cũng là dải thông tin.
  date: { type: String, default: '' },
})

const active = ref(0)
const prev = ref(-1)
// Hướng trượt: 1 = tin sau (vào từ phải, ra bên trái), -1 = tin trước (ngược lại).
const dir = ref(1)
const paused = ref(false)
let timer = null
let reducedMotion = false

const count = computed(() => props.items.length)

function stop() {
  if (timer !== null) {
    clearInterval(timer)
    timer = null
  }
}

function restart() {
  stop()
  if (reducedMotion || paused.value || count.value < 2) return
  timer = setInterval(() => go(1), props.interval)
}

/** Sang tin kế tiếp (step 1) hoặc tin trước (step -1). */
function go(step) {
  if (count.value < 2) return
  dir.value = step
  prev.value = active.value
  active.value = (active.value + step + count.value) % count.value
}

/** Bấm chấm tròn: nhảy thẳng tới tin i, hướng trượt theo vị trí tương đối. */
function goTo(i) {
  if (i === active.value) return
  dir.value = i > active.value ? 1 : -1
  prev.value = active.value
  active.value = i
  restart()
}

/** Bấm nút điều hướng: đổi tin và đếm lại từ đầu để không bị nhảy tiếp ngay. */
function nav(step) {
  go(step)
  restart()
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
    v-if="count"
    class="ticker"
    aria-label="Tin mới nhất"
    @mouseenter="paused = true"
    @mouseleave="paused = false"
    @focusin="paused = true"
    @focusout="paused = false"
  >
    <div class="ticker__inner" data-container>
      <span v-if="date" class="ticker__date">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/></svg>
        {{ date }}
      </span>

      <span class="ticker__label">
        <span class="ticker__dot" aria-hidden="true"></span>
        Tin mới
      </span>

      <!-- --dir điều khiển hướng trượt: tin chờ nằm sẵn ở phía tin mới đi vào,
           tin cũ trượt ra phía đối diện. -->
      <!-- KHÔNG đặt aria-live: thanh tự chạy 4s/tin, để live region thì trình đọc màn
           hình đọc chen ngang liên tục. Tin nào không hiện đã có aria-hidden. -->
      <div class="ticker__stage" :style="{ '--dir': dir }">
        <NuxtLink
          v-for="(item, i) in items"
          :key="item.id"
          :to="item.alias"
          class="ticker__item"
          :class="{ 'is-active': i === active, 'is-leaving': i === prev && i !== active }"
          :aria-hidden="i === active ? undefined : 'true'"
          :tabindex="i === active ? undefined : -1"
        >
          {{ item.title }}
        </NuxtLink>
      </div>

      <!-- Điều hướng: nút mũi tên trên desktop, chấm tròn trên mobile (feedback). -->
      <div v-if="count > 1" class="ticker__nav">
        <button type="button" class="ticker__arrow" aria-label="Tin trước" @click="nav(-1)">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="m15 18-6-6 6-6" /></svg>
        </button>
        <button type="button" class="ticker__arrow" aria-label="Tin sau" @click="nav(1)">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="m9 18 6-6-6-6" /></svg>
        </button>
      </div>

      <div v-if="count > 1" class="ticker__dots">
        <button
          v-for="(item, i) in items"
          :key="item.id"
          type="button"
          class="ticker__dot-btn"
          :class="{ 'is-active': i === active }"
          :aria-label="`Tin ${i + 1}`"
          :aria-current="i === active ? 'true' : undefined"
          @click="goTo(i)"
        ></button>
      </div>

      <NuxtLink to="/tin-tuc" class="ticker__all">
        Xem tất cả
        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="m9 18 6-6-6-6" /></svg>
      </NuxtLink>
    </div>
  </div>
</template>

<style scoped>
.ticker {
  background: #fff;
  border-bottom: 1px solid #E4E9F0;
}
.ticker__inner {
  max-width: 1280px;
  margin: 0 auto;
  padding: 0 24px;
  height: 42px;
  display: flex;
  align-items: center;
  gap: 14px;
}

.ticker__date {
  display: inline-flex;
  align-items: center;
  gap: 7px;
  flex: 0 0 auto;
  padding-right: 14px;
  border-right: 1px solid #E4E9F0;
  color: #5A6478;
  font-size: 12.5px;
  white-space: nowrap;
}

.ticker__label {
  display: inline-flex;
  align-items: center;
  gap: 7px;
  flex: 0 0 auto;
  background: #0F3093;
  color: #fff;
  font-family: 'Lexend', sans-serif;
  font-weight: 700;
  font-size: 11.5px;
  letter-spacing: 0.6px;
  text-transform: uppercase;
  padding: 5px 11px;
}
.ticker__dot {
  width: 6px;
  height: 6px;
  border-radius: 50%;
  background: #FFC107;
  box-shadow: 0 0 0 3px rgba(255, 193, 7, 0.28);
}

/* Các tiêu đề chồng lên nhau -> chiều cao luôn bằng một dòng, đổi tin không giật.
   overflow:hidden để tin đang trượt ra/vào bị cắt ở mép khung thay vì đè lên nhãn. */
.ticker__stage {
  position: relative;
  display: grid;
  flex: 1;
  min-width: 0;
  overflow: hidden;
}
/* Trạng thái CHỜ: nằm sẵn ngoài khung, phía tin mới sẽ đi vào (phải khi chạy xuôi).
   Không transition ở đây để lúc đổi hướng, tin chờ nhảy sang mép kia tức thì. */
.ticker__item {
  grid-area: 1 / 1;
  min-width: 0;
  font-size: 13.5px;
  line-height: 20px;
  color: #212529;
  text-decoration: none;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
  opacity: 0;
  visibility: hidden;
  transform: translateX(calc(100% * var(--dir, 1)));
  transition: none;
}
.ticker__item.is-active,
.ticker__item.is-leaving {
  visibility: visible;
  transition: transform .5s cubic-bezier(.22, .61, .36, 1), opacity .38s ease;
}
.ticker__item.is-active {
  opacity: 1;
  transform: none;
}
/* Tin cũ trượt tiếp ra mép đối diện (chạy xuôi = ra bên trái). */
.ticker__item.is-leaving {
  opacity: 0;
  transform: translateX(calc(-100% * var(--dir, 1)));
}
.ticker__item:hover {
  color: #0F3093;
  text-decoration: underline;
}

.ticker__nav {
  display: flex;
  align-items: center;
  gap: 2px;
  flex: 0 0 auto;
}
.ticker__arrow {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 24px;
  height: 24px;
  padding: 0;
  border: 1px solid #D9E1EE;
  border-radius: 50%;
  background: #fff;
  color: #0F3093;
  cursor: pointer;
  transition: background .15s ease, border-color .15s ease;
}
.ticker__arrow:hover { background: #EDF2FA; border-color: #B9C8DF; }

/* Chấm tròn: dành cho mobile (không đủ chỗ cho nút, ngón tay bấm chấm dễ hơn). */
.ticker__dots {
  display: none;
  align-items: center;
  gap: 6px;
  flex: 0 0 auto;
}
.ticker__dot-btn {
  width: 7px;
  height: 7px;
  padding: 0;
  border: 0;
  border-radius: 50%;
  background: #C9D4E5;
  cursor: pointer;
  transition: background .15s ease, transform .15s ease;
}
.ticker__dot-btn.is-active {
  background: #0F3093;
  transform: scale(1.35);
}

.ticker__arrow:focus-visible,
.ticker__dot-btn:focus-visible {
  outline: 2px solid #1D6AC5;
  outline-offset: 2px;
}

.ticker__all {
  flex: 0 0 auto;
  display: inline-flex;
  align-items: center;
  gap: 4px;
  color: #1D6AC5;
  font-size: 12.5px;
  text-decoration: none;
}
.ticker__all:hover {
  text-decoration: underline;
}

/* Máy hẹp: bỏ ngày/giờ trước (trang trí), nhường chỗ cho tiêu đề tin. */
@media (max-width: 900px) {
  .ticker__date { display: none; }
}

@media (max-width: 640px) {
  .ticker__inner { padding: 0 16px; gap: 10px; }
  .ticker__all { display: none; }
  .ticker__nav { display: none; }
  .ticker__dots { display: flex; }
}

@media (prefers-reduced-motion: reduce) {
  .ticker__item,
  .ticker__item.is-active,
  .ticker__item.is-leaving { transition: none; transform: none; }
  .ticker__item.is-leaving { visibility: hidden; }
}
</style>
