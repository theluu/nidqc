<script setup>
// Danh sách thẻ tin (một tin mỗi dòng) dùng chung cho /tin-tuc và /tim-kiem.
// Xem NewsCard.vue.
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
/* MỘT tin mỗi dòng, xếp từ trên xuống (feedback 21/08). Trước đây là lưới 4 cột.
   Tên class giữ nguyên "grid" để không phải sửa hai trang đang dùng. */
.grid {
  display: flex;
  flex-direction: column;
  gap: 14px;
  transition: opacity .2s;
}
.grid.is-loading {
  opacity: .5;
}
@media (prefers-reduced-motion: reduce) {
  .grid { transition: none; }
}
</style>
