<script setup>
const props = defineProps({
  error: {
    type: Object,
    required: true,
  },
})

const isNotFound = computed(() => props.error?.statusCode === 404)
const pageTitle = computed(() => isNotFound.value ? 'Không tìm thấy trang' : 'Đã xảy ra lỗi')
const pageMessage = computed(() => isNotFound.value
  ? 'Đường dẫn có thể đã thay đổi, hết hiệu lực hoặc chưa từng tồn tại.'
  : 'Hệ thống chưa thể hoàn thành yêu cầu. Vui lòng thử lại sau.')

useSeoMeta({
  title: () => `${pageTitle.value} — NIDQC`,
  robots: 'noindex, nofollow',
})

const goHome = () => clearError({ redirect: '/' })
</script>

<template>
  <NuxtLayout>
    <main class="error-page">
      <div class="error-page__glow error-page__glow--one" aria-hidden="true"></div>
      <div class="error-page__glow error-page__glow--two" aria-hidden="true"></div>

      <div class="error-page__container">
        <section class="error-card" aria-labelledby="error-title">
          <div class="error-card__visual" aria-hidden="true">
            <div class="error-card__orbit error-card__orbit--outer"></div>
            <div class="error-card__orbit error-card__orbit--inner"></div>
            <div class="error-card__number">{{ isNotFound ? '404' : error.statusCode }}</div>
            <svg class="error-card__mark" viewBox="0 0 64 64">
              <path d="M32 7 53 16v14c0 13-8.7 22.8-21 28C19.7 52.8 11 43 11 30V16L32 7Z" />
              <path d="m24 32 6 6 11-13" />
            </svg>
          </div>

          <div class="error-card__content">
            <p class="error-card__eyebrow">Viện Kiểm nghiệm thuốc Trung ương</p>
            <h1 id="error-title">{{ pageTitle }}</h1>
            <p class="error-card__message">{{ pageMessage }}</p>

            <div class="error-card__actions">
              <button type="button" class="error-card__primary" @click="goHome">
                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="m3 11 9-8 9 8v9a1 1 0 0 1-1 1h-5v-7H9v7H4a1 1 0 0 1-1-1v-9Z" /></svg>
                Về trang chủ
              </button>
              <button type="button" class="error-card__secondary" @click="$router.back()">
                Quay lại trang trước
              </button>
            </div>
          </div>
        </section>

        <nav class="error-links" aria-label="Liên kết hữu ích">
          <NuxtLink to="/tin-tuc"><span>Tin tức</span><small>Cập nhật hoạt động của Viện</small></NuxtLink>
          <NuxtLink to="/van-ban-tai-lieu"><span>Văn bản – Tài liệu</span><small>Tra cứu tài liệu chuyên môn</small></NuxtLink>
          <NuxtLink to="/lien-he"><span>Liên hệ hỗ trợ</span><small>Kết nối với NIDQC</small></NuxtLink>
        </nav>
      </div>
    </main>
  </NuxtLayout>
</template>

<style scoped>
.error-page {
  position: relative;
  isolation: isolate;
  min-height: 610px;
  overflow: hidden;
  padding: 72px 24px 84px;
  background:
    linear-gradient(135deg, var(--nidqc-bg-blue-2), var(--nidqc-white) 52%, var(--nidqc-primary-pale));
}
.error-page__container {
  position: relative;
  z-index: 1;
  width: min(1060px, 100%);
  margin: 0 auto;
}
.error-page__glow {
  position: absolute;
  border-radius: 50%;
  background: var(--nidqc-primary-pale);
  opacity: .72;
  filter: blur(4px);
}
.error-page__glow--one { width: 420px; height: 420px; top: -230px; right: -110px; }
.error-page__glow--two { width: 300px; height: 300px; bottom: -180px; left: -80px; }
.error-card {
  display: grid;
  grid-template-columns: .9fr 1.1fr;
  align-items: center;
  min-height: 390px;
  overflow: hidden;
  background: var(--nidqc-white);
  border: 1px solid var(--nidqc-border);
  border-radius: 28px;
  box-shadow: 0 24px 70px rgba(13, 40, 112, .15);
}
.error-card__visual {
  position: relative;
  display: grid;
  place-items: center;
  align-self: stretch;
  min-height: 390px;
  overflow: hidden;
  color: var(--nidqc-white);
  background: var(--nidqc-primary);
}
.error-card__visual::before {
  content: '';
  position: absolute;
  inset: 28px;
  border: 1px solid rgba(255, 255, 255, .18);
  border-radius: 50%;
}
.error-card__orbit { position: absolute; border: 1px solid rgba(255, 255, 255, .22); border-radius: 50%; }
.error-card__orbit--outer { width: 330px; height: 330px; }
.error-card__orbit--inner { width: 220px; height: 220px; }
.error-card__number {
  position: relative;
  font-family: var(--nidqc-font-heading);
  font-size: clamp(86px, 12vw, 150px);
  font-weight: 700;
  line-height: 1;
  letter-spacing: -8px;
  text-shadow: 0 10px 30px rgba(0, 0, 0, .18);
}
.error-card__mark {
  position: absolute;
  right: 34px;
  bottom: 30px;
  width: 62px;
  fill: var(--nidqc-accent);
  stroke: var(--nidqc-white);
  stroke-width: 3;
  stroke-linecap: round;
  stroke-linejoin: round;
}
.error-card__content { padding: 54px 58px; }
.error-card__eyebrow {
  margin: 0 0 14px;
  color: var(--nidqc-primary);
  font-size: 12px;
  font-weight: 700;
  letter-spacing: 1.3px;
  text-transform: uppercase;
}
.error-card h1 {
  margin: 0 0 16px;
  color: var(--nidqc-text);
  font-family: var(--nidqc-font-heading);
  font-size: clamp(30px, 4vw, 46px);
  line-height: 1.18;
}
.error-card__message {
  max-width: 520px;
  margin: 0;
  color: var(--nidqc-text-muted);
  font-size: 16px;
  line-height: 1.75;
}
.error-card__actions { display: flex; flex-wrap: wrap; gap: 12px; margin-top: 30px; }
.error-card__actions button {
  min-height: 48px;
  padding: 0 20px;
  border-radius: 9px;
  font: 600 14px/1 var(--nidqc-font);
  cursor: pointer;
}
.error-card__primary {
  display: inline-flex;
  align-items: center;
  gap: 9px;
  color: var(--nidqc-white);
  background: var(--nidqc-primary);
  border: 1px solid var(--nidqc-primary);
}
.error-card__primary:hover { background: var(--nidqc-primary-dark); }
.error-card__primary svg { width: 18px; fill: none; stroke: currentColor; stroke-width: 2; }
.error-card__secondary {
  color: var(--nidqc-primary);
  background: var(--nidqc-white);
  border: 1px solid var(--nidqc-primary-light);
}
.error-card__actions button:focus-visible,
.error-links a:focus-visible { outline: 3px solid var(--nidqc-accent); outline-offset: 3px; }
.error-links {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 14px;
  margin-top: 18px;
}
.error-links a {
  display: flex;
  flex-direction: column;
  gap: 5px;
  min-height: 92px;
  padding: 19px 20px;
  color: var(--nidqc-text);
  background: rgba(255, 255, 255, .9);
  border: 1px solid var(--nidqc-border);
  border-radius: 14px;
  text-decoration: none;
  transition: transform .18s ease, border-color .18s ease, box-shadow .18s ease;
}
.error-links a:hover {
  transform: translateY(-3px);
  border-color: var(--nidqc-primary-light);
  box-shadow: 0 10px 28px rgba(13, 40, 112, .1);
}
.error-links span { color: var(--nidqc-primary); font-weight: 700; }
.error-links small { color: var(--nidqc-text-muted); font-size: 12.5px; line-height: 1.5; }
@media (max-width: 780px) {
  .error-page { padding: 34px 16px 56px; }
  .error-card { grid-template-columns: 1fr; border-radius: 20px; }
  .error-card__visual { min-height: 230px; }
  .error-card__number { font-size: 96px; }
  .error-card__content { padding: 36px 28px 40px; }
  .error-links { grid-template-columns: 1fr; }
}
@media (prefers-reduced-motion: reduce) {
  .error-links a { transition: none; }
}
</style>
