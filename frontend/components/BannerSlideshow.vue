<script setup>
// Dải banner quảng cáo dạng slideshow (feedback 7 và 9).
//
// Dùng lại cho cả hai vị trí trên trang chủ; nội dung quản trị trong Drupal ở
// content type "Banner & liên kết ảnh", phân biệt bằng field "Vị trí hiển thị".
//
// Không có banner nào thì component tự ẩn — dải trống giữa hai section còn xấu hơn
// là không có dải.
const props = defineProps({
  // [{ title, image, url }] — dạng do /api/v1/home/blocks trả về.
  items: { type: Array, required: true },
  interval: { type: Number, default: 5000 },
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
  restart()
}

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
  <section
    v-if="count"
    class="ads"
    aria-roledescription="carousel"
    aria-label="Banner giới thiệu"
    @mouseenter="paused = true"
    @mouseleave="paused = false"
    @focusin="paused = true"
    @focusout="paused = false"
  >
    <div data-container class="ads__inner">
      <div class="ads__viewport">
        <div class="ads__track" :style="{ transform: `translateX(-${active * 100}%)` }">
          <component
            :is="item.url ? 'a' : 'div'"
            v-for="(item, i) in items"
            :key="i"
            :href="item.url || undefined"
            :target="item.url && /^https?:/.test(item.url) ? '_blank' : undefined"
            :rel="item.url && /^https?:/.test(item.url) ? 'noopener' : undefined"
            class="ads__slide"
            :aria-hidden="i === active ? undefined : 'true'"
            :tabindex="i === active ? undefined : -1"
          >
            <img :src="item.image" :alt="item.title" loading="lazy" class="ads__img">
            <span v-if="item.title" class="ads__caption">{{ item.title }}</span>
          </component>
        </div>

        <div v-if="count > 1" class="ads__dots">
          <button
            v-for="(item, i) in items"
            :key="`d-${i}`"
            type="button"
            class="ads__dot"
            :class="{ 'is-active': i === active }"
            :aria-label="`Xem banner ${i + 1}: ${item.title}`"
            :aria-current="i === active ? 'true' : undefined"
            @click="select(i)"
          />
        </div>
      </div>
    </div>
  </section>
</template>

<style scoped>
.ads {
  background: #F3F7FC;
  border-top: 1px solid #ECECEC;
  border-bottom: 1px solid #ECECEC;
  /* Cùng nhịp dọc với .nidqc-section ở main.css — hai dải banner nằm xen giữa các
     section nên lệch padding là nhìn thấy ngay. */
  padding: 48px 0;
}
.ads__inner {
  max-width: 1280px;
  margin: 0 auto;
  padding: 0 24px;
}
.ads__viewport {
  position: relative;
  overflow: hidden;
  border: 1px solid #DCE4EF;
  background: #0D2870;
}
.ads__track {
  display: flex;
  transition: transform .55s cubic-bezier(.4, 0, .2, 1);
}
.ads__slide {
  position: relative;
  flex: 0 0 100%;
  min-width: 100%;
  /* Tỷ lệ cố định: ảnh banner do admin tải lên đủ mọi kích thước, không khoá tỷ lệ
     thì mỗi lần chuyển slide chiều cao dải lại nhảy một kiểu. */
  aspect-ratio: 1232 / 260;
  display: block;
  text-decoration: none;
}
.ads__img {
  width: 100%;
  height: 100%;
  object-fit: cover;
  display: block;
}
.ads__caption {
  position: absolute;
  left: 0;
  bottom: 0;
  max-width: 70%;
  padding: 10px 18px;
  background: rgba(8, 24, 66, 0.82);
  color: #fff;
  font-family: 'Lexend', sans-serif;
  font-size: 14px;
  font-weight: 600;
}
.ads__dots {
  position: absolute;
  right: 16px;
  bottom: 14px;
  display: flex;
  gap: 7px;
}
.ads__dot {
  width: 9px;
  height: 9px;
  padding: 0;
  border: 0;
  border-radius: 50%;
  background: rgba(255, 255, 255, 0.5);
  cursor: pointer;
  transition: background .2s ease, width .2s ease;
}
.ads__dot.is-active { width: 22px; border-radius: 999px; background: #fff; }
.ads__dot:focus-visible { outline: 2px solid #fff; outline-offset: 3px; }

@media (max-width: 640px) {
  .ads { padding: 32px 0; }
  .ads__slide { aspect-ratio: 16 / 9; }
  .ads__caption { max-width: 100%; font-size: 12.5px; padding: 8px 14px; }
}

@media (prefers-reduced-motion: reduce) {
  .ads__track, .ads__dot { transition: none; }
}
</style>
