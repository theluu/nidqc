<script setup>
// Lưới thẻ tin dùng chung cho /tin-tuc và /tim-kiem. Xem NewsCard.vue.
defineProps({
  items: { type: Array, default: () => [] },
  // Làm mờ lưới trong lúc chờ dữ liệu trang mới (đổi chuyên mục / đổi trang).
  loading: { type: Boolean, default: false },
})
</script>

<template>
  <div class="grid" :class="{ 'is-loading': loading }">
    <NewsCard v-for="item in items" :key="item.id" :item="item" />
  </div>
</template>

<style scoped>
.grid {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 20px;
  transition: opacity .2s;
}
.grid.is-loading {
  opacity: .5;
}
@media (max-width: 1024px) {
  .grid { grid-template-columns: repeat(3, 1fr); }
}
@media (max-width: 768px) {
  .grid { grid-template-columns: repeat(2, 1fr); }
}
@media (max-width: 480px) {
  .grid { grid-template-columns: 1fr; }
}
@media (prefers-reduced-motion: reduce) {
  .grid { transition: none; }
}
</style>
