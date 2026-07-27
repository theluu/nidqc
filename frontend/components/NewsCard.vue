<script setup>
// Thẻ tin dùng chung cho danh sách /tin-tuc và kết quả /tim-kiem.
//
// Tách ra để hai trang KHÔNG lệch nhau: trước đây mỗi trang tự viết markup + CSS
// riêng, đổi design là phải sửa hai chỗ và dễ quên một.
defineProps({
  // { title, date, tag, image, alias } — dạng item đã map ở trang gọi.
  item: { type: Object, required: true },
})
</script>

<template>
  <NuxtLink :to="item.alias" class="card">
    <div class="card__thumb">
      <img v-if="item.image" :src="item.image" :alt="item.title" loading="lazy">
    </div>
    <div class="card__body">
      <span v-if="item.tag" class="card__tag">{{ item.tag }}</span>
      <h3 class="card__title">{{ item.title }}</h3>
      <div class="card__date">
        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><rect x="3" y="4" width="18" height="18" rx="2" /><path d="M16 2v4M8 2v4M3 10h18" /></svg>
        {{ item.date }}
      </div>
    </div>
  </NuxtLink>
</template>

<style scoped>
.card {
  display: flex;
  flex-direction: column;
  background: #fff;
  border: 1px solid #ECECEC;
  border-radius: 8px;
  overflow: hidden;
  text-decoration: none;
  transition: transform .18s, box-shadow .18s, border-color .18s;
}
.card:hover {
  transform: translateY(-3px);
  box-shadow: 0 8px 20px rgba(15, 48, 147, 0.12);
  border-color: #D8DEE6;
}
.card__thumb {
  aspect-ratio: 16 / 10;
  background: #E8F0F7;
  overflow: hidden;
}
.card__thumb img {
  width: 100%;
  height: 100%;
  object-fit: cover;
  transition: transform .3s;
}
.card:hover .card__thumb img {
  transform: scale(1.05);
}
.card__body {
  display: flex;
  flex-direction: column;
  flex: 1;
  padding: 16px 18px 18px;
}
.card__tag {
  align-self: flex-start;
  background: #E8F0F7;
  color: #0F3093;
  font-size: 10.5px;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.4px;
  padding: 3px 9px;
  border-radius: 4px;
  margin-bottom: 10px;
}
.card__title {
  font-size: 14px;
  line-height: 20px;
  color: #212529;
  font-weight: 500;
  margin: 0 0 12px;
  display: -webkit-box;
  -webkit-line-clamp: 3;
  -webkit-box-orient: vertical;
  overflow: hidden;
}
.card__date {
  display: flex;
  align-items: center;
  gap: 6px;
  margin-top: auto;
  color: #777;
  font-size: 12px;
}

@media (prefers-reduced-motion: reduce) {
  .card,
  .card__thumb img { transition: none; }
  .card:hover { transform: none; }
  .card:hover .card__thumb img { transform: none; }
}
</style>
