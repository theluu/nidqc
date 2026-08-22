<script setup>
// Trang "Dịch vụ" — /dich-vu
//
// Trước đây mục "Dịch vụ" trên menu trỏ mỏ neo /#dich-vu, tức là luôn ném người
// dùng về TRANG CHỦ rồi nhảy xuống giữa trang: đang đọc một bài chi tiết mà bấm
// menu là mất trang đang xem, và cái mỏ neo đó cũng không chia sẻ được như một
// trang riêng. Nay là một trang thật.
//
// Dùng chung nguồn với khối "Dịch vụ" ở trang chủ (/api/v1/home/blocks) và cùng
// khoá cache 'home-blocks', nên admin sửa trong Drupal là cả hai nơi đổi theo, mà
// đi từ trang chủ sang đây không tốn thêm request.
const { data: blocks } = await useCachedData('home-blocks', fetchHomeBlocks)

const services = computed(() => blocks.value?.services || [])
const capabilities = computed(() => blocks.value?.capabilities || [])
const standards = computed(() => blocks.value?.standards || null)

const DESC = 'Các dịch vụ phân tích - kiểm nghiệm, hiệu chuẩn, đào tạo, tư vấn kỹ thuật và cung ứng chất chuẩn của Viện Kiểm nghiệm thuốc Trung ương.'
useSeoMeta({
  title: 'Dịch vụ — Viện Kiểm nghiệm thuốc Trung ương',
  description: DESC,
  ogTitle: 'Dịch vụ — Viện Kiểm nghiệm thuốc Trung ương',
  ogDescription: DESC,
})
</script>

<template>
  <div>
    <PageBand title="Dịch vụ" :description="DESC" />

    <section class="nidqc-section" style="background:#fff;">
      <div class="nidqc-service-grid" data-container style="max-width:1280px;margin:0 auto;padding:0 24px;display:grid;grid-template-columns:minmax(0,2fr) minmax(0,1fr);gap:32px;">
        <div>
          <a v-if="standards && standards.url" :href="standards.url" target="_blank" rel="noopener" class="nidqc-standards-cta">
            <span class="nidqc-standards-cta__icon" aria-hidden="true">
              <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="7"/><path d="m21 21-4.3-4.3"/></svg>
            </span>
            <span class="nidqc-standards-cta__body">
              <strong>{{ standards.label }}</strong>
              <span v-if="standards.note">{{ standards.note }}</span>
            </span>
            <svg class="nidqc-standards-cta__arrow" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M13 6l6 6-6 6"/></svg>
          </a>

          <div class="nidqc-grid-3" style="display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:18px;">
            <component :is="s.url ? 'a' : 'div'" v-for="(s, i) in services" :key="i"
              :href="s.url || undefined"
              :target="s.url && /^https?:/.test(s.url) ? '_blank' : undefined"
              :rel="s.url && /^https?:/.test(s.url) ? 'noopener' : undefined"
              class="nidqc-tile">
              <span class="nidqc-tile__media">
                <img v-if="s.image" :src="s.image" :alt="s.title" loading="lazy">
              </span>
              <span class="nidqc-tile__label">{{ s.title }}</span>
            </component>
          </div>
        </div>

        <div v-if="capabilities.length" class="nidqc-col">
          <div class="nidqc-heading">
            <span class="nidqc-heading__bar"></span>
            <h2 class="nidqc-heading__text">Danh mục năng lực</h2>
          </div>
          <ol class="nidqc-capability-list">
            <li v-for="(c, i) in capabilities" :key="i">
              <component :is="c.url ? 'a' : 'span'" :href="c.url || undefined" class="nidqc-capability">
                <span class="nidqc-capability__num">{{ i + 1 }}</span>
                <span class="nidqc-capability__body">
                  <span class="nidqc-capability__label">{{ c.title }}</span>
                  <span v-if="c.description" class="nidqc-capability__desc nidqc-clamp-2">{{ c.description }}</span>
                </span>
              </component>
            </li>
          </ol>
        </div>
      </div>
    </section>
  </div>
</template>
