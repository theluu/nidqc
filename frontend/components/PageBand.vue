<script setup>
defineProps({
  title: { type: String, required: true },
  description: { type: String, default: '' },
  crumbs: { type: Array, default: () => [] },
});
</script>
<template>
  <!-- Breadcrumb -->
  <div style="background:#F5F5F5;border-bottom:1px solid #ECECEC;">
    <div style="max-width:1280px;margin:0 auto;padding:14px 24px;display:flex;align-items:center;gap:8px;font-size:13px;color:#777;">
      <NuxtLink to="/" style="color:#0F3093;font-weight:500;text-decoration:none;">Trang chủ</NuxtLink>
      <template v-for="(c, i) in crumbs" :key="i">
        <span>/</span>
        <span style="color:#495057;">{{ c }}</span>
      </template>
      <span>/</span>
      <span style="color:#495057;font-weight:600;">{{ title }}</span>
    </div>
  </div>
  <!-- Title band -->
  <div style="background:#F3F7FC;border-bottom:1px solid #ECECEC;">
    <div style="max-width:1280px;margin:0 auto;padding:44px 24px 34px;">
      <h1 style="font-family:'Lexend',sans-serif;font-weight:700;font-size:34px;color:#212529;margin:0 0 12px;">{{ title }}</h1>
      <p v-if="description" style="font-size:15.5px;line-height:24px;color:#495057;margin:0;max-width:760px;">{{ description }}</p>
    </div>
  </div>
</template>
