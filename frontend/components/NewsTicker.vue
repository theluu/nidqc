<script setup>
// Thanh "Tin mới nhất" ngay dưới main menu — mỗi lần hiện một tiêu đề, chuyển cảnh fade.
//
// Giống FeaturedHero: cả N tiêu đề nằm sẵn trong DOM (xếp chồng cùng ô grid) để crawler
// đọc được toàn bộ, chỉ đổi opacity khi chạy. Chiều cao cố định một dòng nên thanh này
// KHÔNG làm xê dịch nội dung bên dưới khi đổi tin (tránh layout shift).
const props = defineProps({
  // [{ id, title, alias }]
  items: { type: Array, required: true },
  interval: { type: Number, default: 4000 },
})

const active = ref(0)
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
  timer = setInterval(() => {
    active.value = (active.value + 1) % count.value
  }, props.interval)
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
      <span class="ticker__label">
        <span class="ticker__dot" aria-hidden="true"></span>
        Tin mới
      </span>

      <div class="ticker__stage">
        <NuxtLink
          v-for="(item, i) in items"
          :key="item.id"
          :to="item.alias"
          class="ticker__item"
          :class="{ 'is-active': i === active }"
          :aria-hidden="i === active ? undefined : 'true'"
        >
          {{ item.title }}
        </NuxtLink>
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

/* Các tiêu đề chồng lên nhau -> chiều cao luôn bằng một dòng, đổi tin không giật. */
.ticker__stage {
  position: relative;
  display: grid;
  flex: 1;
  min-width: 0;
}
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
  /* Nhích lên vài px khi hiện — "fade in" có hướng, đỡ phẳng hơn fade thuần. */
  transform: translateY(4px);
  transition: opacity .45s ease, transform .45s ease;
}
.ticker__item.is-active {
  opacity: 1;
  visibility: visible;
  transform: none;
}
.ticker__item:hover {
  color: #0F3093;
  text-decoration: underline;
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

@media (max-width: 640px) {
  .ticker__inner { padding: 0 16px; gap: 10px; }
  .ticker__all { display: none; }
}

@media (prefers-reduced-motion: reduce) {
  .ticker__item { transition: none; transform: none; }
}
</style>
