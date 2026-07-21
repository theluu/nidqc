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
      recaptchaSiteKey: process.env.NIDQC_RECAPTCHA_SITE_KEY || '',
    },
  },

  app: {
    head: {
      htmlAttrs: { lang: 'vi' },
      title: 'Viện Kiểm nghiệm thuốc Trung ương',
      link: [
        { rel: 'icon', type: 'image/png', href: '/favicon.png' },
        { rel: 'apple-touch-icon', href: '/apple-touch-icon.png' },
        // Same-origin: nginx (dev DDEV và prod) route /themes → Drupal. Không hard-code domain
        // để build chạy đúng ở mọi môi trường (ddev.site, staging, production).
        { rel: 'stylesheet', href: '/themes/custom/nidqc/css/fonts.css' },
        { rel: 'stylesheet', href: '/themes/custom/nidqc/css/tokens.css' },
      ],
    },
  },

  nitro: {
    // Cho phép Nuxt server gọi Drupal (self-signed cert của DDEV).
    routeRules: {},
  },
})
