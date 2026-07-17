<script setup>
// Thanh điều hướng phụ (tab) dưới header — theo design các trang "Giới thiệu".
// Link đang xem được gạch chân xanh. Dùng chung cho 4 trang cùng nhóm.
const props = defineProps({
  links: { type: Array, required: true }, // [{ label, to }]
})
const route = useRoute()
</script>
<template>
  <div style="background:#fff;border-bottom:1px solid #ECECEC;">
    <div style="max-width:1280px;margin:0 auto;padding:0 24px;">
      <nav style="display:flex;flex-wrap:wrap;gap:28px;">
        <NuxtLink
          v-for="l in links"
          :key="l.to"
          :to="l.to"
          :style="`display:inline-block;padding:16px 2px;font-size:14.5px;text-decoration:none;border-bottom:3px solid ${route.path === l.to ? '#0F3093' : 'transparent'};color:${route.path === l.to ? '#0F3093' : '#495057'};font-weight:${route.path === l.to ? 700 : 500};`"
        >{{ l.label }}</NuxtLink>
      </nav>
    </div>
  </div>
</template>
