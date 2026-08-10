<script setup>
// Một cột danh sách tin — dùng cho section "Tin thông báo | Tin mua sắm" ở trang chủ
// (feedback 10).
//
// MỌI tin cùng một khuôn: ảnh nhỏ + tiêu đề + ngày. Bản trước cho tin đầu tiên một
// ảnh lớn để cột có điểm nhìn, nhưng hai cột nằm cạnh nhau mà tin đầu nổi hẳn lên
// thì danh sách đọc như hai khối rời; đồng dạng hết thì mắt lướt theo cột dễ hơn.
defineProps({
  title: { type: String, required: true },
  // [{ id, title, date, image, alias }]
  items: { type: Array, required: true },
  // Đường dẫn trang danh sách đầy đủ của chuyên mục.
  to: { type: String, required: true },
})
</script>

<template>
  <div v-if="items.length" class="col">
    <div class="nidqc-heading">
      <span class="nidqc-heading__bar"></span>
      <h2 class="nidqc-heading__text">{{ title }}</h2>
    </div>

    <ul class="col__list">
      <li v-for="item in items" :key="item.id">
        <NuxtLink :to="item.alias" class="col__row">
          <span class="col__thumb">
            <img v-if="item.image" :src="item.image" alt="" loading="lazy">
          </span>
          <span class="col__body">
            <span class="col__title">{{ item.title }}</span>
            <span class="col__date">
              <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><rect x="3" y="4" width="18" height="18" rx="2" /><path d="M16 2v4M8 2v4M3 10h18" /></svg>
              {{ item.date }}
            </span>
          </span>
        </NuxtLink>
      </li>
    </ul>

    <NuxtLink :to="to" class="col__all">
      Xem tất cả
      <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M13 6l6 6-6 6" /></svg>
    </NuxtLink>
  </div>
</template>

<style scoped>
.col { display: flex; flex-direction: column; min-width: 0; }

.col__list {
  list-style: none;
  margin: 0;
  padding: 0;
  /* Nở hết chiều cao còn lại để đáy hai cột trong section thẳng nhau. */
  flex: 1;
  display: flex;
  flex-direction: column;
  border-top: 1px solid #ECECEC;
}
.col__list > li { display: flex; flex: 1; max-height: 96px; }
.col__row {
  display: flex;
  flex: 1;
  gap: 14px;
  align-items: center;
  padding: 12px 0;
  border-bottom: 1px solid #ECECEC;
  text-decoration: none;
}
.col__row:hover .col__title { color: var(--nidqc-primary); }
.col__thumb {
  flex: 0 0 96px;
  width: 96px;
  height: 66px;
  background: #E8F0F7;
  overflow: hidden;
}
.col__thumb img {
  width: 100%;
  height: 100%;
  object-fit: cover;
  display: block;
  transition: transform .35s ease;
}
.col__row:hover .col__thumb img { transform: scale(1.05); }
.col__body { min-width: 0; }
.col__title {
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
  font-size: 14.5px;
  line-height: 21px;
  font-weight: 500;
  color: #212529;
}
.col__date {
  display: inline-flex;
  align-items: center;
  gap: 5px;
  margin-top: 6px;
  font-size: 12px;
  color: #777;
}

.col__all {
  display: inline-flex;
  align-items: center;
  gap: 7px;
  align-self: flex-start;
  margin-top: 14px;
  color: var(--nidqc-primary);
  font-size: 13.5px;
  font-weight: 600;
  text-decoration: none;
}
.col__all:hover { text-decoration: underline; }
.col__row:focus-visible,
.col__all:focus-visible { outline: 2px solid #1D6AC5; outline-offset: 2px; }

@media (max-width: 480px) {
  .col__thumb { flex: 0 0 78px; width: 78px; height: 56px; }
}

@media (prefers-reduced-motion: reduce) {
  .col__thumb img { transition: none; }
  .col__row:hover .col__thumb img { transform: none; }
}
</style>
