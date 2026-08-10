<script setup>
// Icon mạng xã hội dùng chung cho header và footer.
//
// Tách ra thành component vì feedback yêu cầu cùng bộ kênh xuất hiện ở CẢ HAI nơi
// (header: yt/fb/zalo — footer: thêm tiktok). Chép SVG sang hai chỗ thì thêm một
// kênh mới là phải sửa hai file và chắc chắn sẽ có lúc lệch nhau.
defineProps({
  // Khoá kênh do Drupal trả về: facebook | youtube | zalo | tiktok.
  channel: { type: String, required: true },
  size: { type: Number, default: 16 },
})
</script>

<template>
  <svg v-if="channel === 'facebook'" :width="size" :height="size" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
    <path d="M14 8.5V7a1.5 1.5 0 0 1 1.5-1.5H17V2.6A15 15 0 0 0 14.9 2.5C12.3 2.5 10.5 4.1 10.5 7v1.5H8v3.2h2.5V21h3.5v-9.3h2.6l.4-3.2H14z" />
  </svg>

  <svg v-else-if="channel === 'youtube'" :width="size" :height="size" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
    <path d="M21.6 7.2a2.5 2.5 0 0 0-1.8-1.8C18.2 5 12 5 12 5s-6.2 0-7.8.4A2.5 2.5 0 0 0 2.4 7.2 26 26 0 0 0 2 12a26 26 0 0 0 .4 4.8 2.5 2.5 0 0 0 1.8 1.8C5.8 19 12 19 12 19s6.2 0 7.8-.4a2.5 2.5 0 0 0 1.8-1.8A26 26 0 0 0 22 12a26 26 0 0 0-.4-4.8zM10 15.2V8.8l5.2 3.2z" />
  </svg>

  <!-- TikTok: nốt nhạc rút gọn, vẽ đặc để đọc được ở cỡ 16px. -->
  <svg v-else-if="channel === 'tiktok'" :width="size" :height="size" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
    <path d="M16.5 2h-3v13.2a2.6 2.6 0 1 1-2-2.53V9.6a5.7 5.7 0 1 0 5 5.65V9.1a7 7 0 0 0 4 1.27V7.3a4.1 4.1 0 0 1-4-4.1z" />
  </svg>

  <!-- Zalo: bong bóng chat + chữ Z (không dùng logo gốc để khỏi vẽ sai nhận diện). -->
  <svg v-else :width="size" :height="size" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
    <path d="M21 11.5a8.5 8.5 0 0 1-8.5 8.5 8.4 8.4 0 0 1-3.8-.9L3 21l1.9-5.7A8.4 8.4 0 0 1 4 11.5 8.5 8.5 0 0 1 12.5 3 8.5 8.5 0 0 1 21 11.5z" />
    <path d="M9.8 9h5l-5 5.5h5" />
  </svg>
</template>
