<script setup>
import { useRoute } from 'vue-router';
const route = useRoute();
</script>
<template>
  <section style="max-width:1280px;margin:0 auto;padding:60px 24px;min-height:300px;">
    <h1 style="font-family:'Lexend',sans-serif;font-weight:700;font-size:28px;color:#0F3093;">{{ route.meta.title }}</h1>
    <p style="color:#495057;margin-top:12px;">Trang đang được convert từ design. Nội dung đã có sẵn trong backend Drupal (JSON:API).</p>
  </section>
</template>
