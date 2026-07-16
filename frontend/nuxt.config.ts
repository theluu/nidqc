// Nuxt SSR cho NIDQC (ADR-004). Frontend convert từ design, Drupal headless qua JSON:API.
export default defineNuxtConfig({
  compatibilityDate: '2025-01-01',
  ssr: true,
  css: ['~/assets/css/main.css'],

  runtimeConfig: {
    // Server-side fetch Drupal (trong DDEV container).
    drupalInternal: process.env.DRUPAL_INTERNAL || 'https://nidqc.ddev.site',
    public: {
      // Client-side fetch (trình duyệt) — cùng domain qua reverse proxy ở production.
      drupalBase: process.env.DRUPAL_BASE || 'https://nidqc.ddev.site',
    },
  },

  app: {
    head: {
      htmlAttrs: { lang: 'vi' },
      title: 'Viện Kiểm nghiệm thuốc Trung ương',
      link: [{ rel: 'stylesheet', href: 'https://nidqc.ddev.site/themes/custom/nidqc/css/fonts.css' }],
    },
  },

  nitro: {
    // Cho phép Nuxt server gọi Drupal (self-signed cert của DDEV).
    routeRules: {},
  },
})
