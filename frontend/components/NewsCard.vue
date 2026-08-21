<script setup>
// Thẻ tin dùng chung cho danh sách /tin-tuc và kết quả /tim-kiem.
//
// Bố cục HÀNG NGANG, xếp từ trên xuống (feedback 21/08: "Chuyển thành bố cục dạng
// list từ trên xuống dưới"). Bản trước là thẻ dọc trong lưới 4 cột: tiêu đề tin của
// Viện thường dài 3 dòng nên trong ô hẹp bị cắt cụt, đọc lướt cả trang rất khó.
// Hàng ngang cho tiêu đề cả chiều rộng trang và thêm được trích dẫn.
//
// Tách ra để hai trang KHÔNG lệch nhau: trước đây mỗi trang tự viết markup + CSS
// riêng, đổi design là phải sửa hai chỗ và dễ quên một.
defineProps({
  // { title, date, tag, summary, image, alias } — dạng item đã map ở trang gọi.
  // `summary` có thể thiếu: API tìm kiếm không trả trường này, khi đó bỏ qua dòng
  // trích dẫn chứ không để khoảng trống.
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
      <p v-if="item.summary" class="card__summary">{{ item.summary }}</p>
    </div>
  </NuxtLink>
</template>

<style scoped>
.card {
  display: flex;
  gap: 20px;
  background: #fff;
  border: 1px solid #ECECEC;
  border-radius: 8px;
  overflow: hidden;
  text-decoration: none;
  transition: box-shadow .18s, border-color .18s;
}
.card:hover {
  box-shadow: 0 6px 18px rgba(15, 48, 147, 0.10);
  border-color: #C7D4E6;
}
/* Ảnh cố định bề ngang + khoá tỷ lệ 16:10: ảnh admin tải lên đủ mọi kích thước vẫn
   ra một cột ảnh thẳng hàng từ trên xuống. */
.card__thumb {
  flex: 0 0 240px;
  width: 240px;
  aspect-ratio: 16 / 10;
  background: #E8F0F7;
  overflow: hidden;
}
.card__thumb img {
  width: 100%;
  height: 100%;
  object-fit: cover;
  display: block;
  transition: transform .3s;
}
.card:hover .card__thumb img {
  transform: scale(1.05);
}
.card__body {
  display: flex;
  flex-direction: column;
  flex: 1;
  min-width: 0;
  padding: 16px 20px 16px 0;
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
  margin-bottom: 9px;
}
.card__title {
  font-size: 16px;
  line-height: 23px;
  color: #212529;
  font-weight: 600;
  margin: 0 0 8px;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}
.card:hover .card__title { color: var(--nidqc-primary, #0F3093); }
.card__date {
  display: flex;
  align-items: center;
  gap: 6px;
  color: #777;
  font-size: 12px;
}
.card__summary {
  margin: 8px 0 0;
  font-size: 13.5px;
  line-height: 20px;
  color: #555;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

@media (max-width: 900px) {
  .card__thumb { flex: 0 0 180px; width: 180px; }
  .card__title { font-size: 15px; line-height: 22px; }
}
/* Máy hẹp: ảnh lên trên, chữ xuống dưới — 180px ảnh cộng chữ trên màn 480px thì
   phần chữ chỉ còn ~200px, tiêu đề vỡ thành từng chữ một dòng. */
@media (max-width: 560px) {
  .card { flex-direction: column; gap: 0; }
  .card__thumb { flex: none; width: 100%; }
  .card__body { padding: 14px 16px 16px; }
}

@media (prefers-reduced-motion: reduce) {
  .card,
  .card__thumb img { transition: none; }
  .card:hover .card__thumb img { transform: none; }
}
</style>
