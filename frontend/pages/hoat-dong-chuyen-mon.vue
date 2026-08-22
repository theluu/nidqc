<script setup>
// Trang "Hoạt động chuyên môn" — /hoat-dong-chuyen-mon
//
// Lý do tồn tại giống pages/dich-vu/index.vue: mục menu này trước trỏ mỏ neo
// /#hoat-dong-chuyen-mon nên bấm ở bất kỳ trang nào cũng bị ném về trang chủ.
//
// Đặt là MỘT FILE (không phải thư mục hoat-dong-chuyen-mon/index.vue) để không
// chen vào route động pages/[section]/[slug].vue đang phục vụ các bài con
// /hoat-dong-chuyen-mon/<slug>.
const { data: blocks } = await useCachedData('home-blocks', fetchHomeBlocks)
const expertise = computed(() => blocks.value?.expertise || [])

const DESC = 'Chỉ đạo tuyến, kiểm nghiệm và giám sát chất lượng thuốc, hợp tác quốc tế và các hoạt động NRA của Viện Kiểm nghiệm thuốc Trung ương.'
useSeoMeta({
  title: 'Hoạt động chuyên môn — Viện Kiểm nghiệm thuốc Trung ương',
  description: DESC,
  ogTitle: 'Hoạt động chuyên môn — Viện Kiểm nghiệm thuốc Trung ương',
  ogDescription: DESC,
})
</script>

<template>
  <div>
    <PageBand title="Hoạt động chuyên môn" :description="DESC" />

    <section class="nidqc-section" style="background:#fff;">
      <div data-container style="max-width:1280px;margin:0 auto;padding:0 24px;">
        <div class="nidqc-grid-4" style="display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:18px;">
          <component :is="a.url ? 'a' : 'div'" v-for="(a, i) in expertise" :key="i"
            :href="a.url || undefined"
            :target="a.url && /^https?:/.test(a.url) ? '_blank' : undefined"
            :rel="a.url && /^https?:/.test(a.url) ? 'noopener' : undefined"
            class="nidqc-tile">
            <span class="nidqc-tile__media">
              <img v-if="a.image" :src="a.image" :alt="a.title" loading="lazy">
            </span>
            <span class="nidqc-tile__label">{{ a.title }}</span>
          </component>
        </div>
        <p v-if="!expertise.length" style="padding:40px 0;text-align:center;color:#777;font-size:15px;">
          Nội dung đang được cập nhật.
        </p>
      </div>
    </section>
  </div>
</template>

<style scoped>
@media (max-width: 1100px) { .nidqc-grid-4 { grid-template-columns: repeat(3, minmax(0, 1fr)) !important; } }
@media (max-width: 768px)  { .nidqc-grid-4 { grid-template-columns: repeat(2, minmax(0, 1fr)) !important; } }
@media (max-width: 480px)  { .nidqc-grid-4 { grid-template-columns: 1fr !important; } }
</style>
