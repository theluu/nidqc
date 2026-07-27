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

  experimental: {
    // BẮT BUỘC tắt khi có route rules swr ở dưới. Payload extraction làm client tải
    // `<route>/_payload.json` — URL này CHỈ có path, KHÔNG mang query string. Trang
    // nào lấy dữ liệu theo query (/tim-kiem?q=…, /tin-tuc?trang=…) sẽ nhận payload
    // của phiên bản không query rồi ghi đè kết quả SSR đúng lúc hydrate: /tim-kiem
    // hiện đúng 12 kết quả trong HTML rồi nháy về "0 kết quả".
    payloadExtraction: false,
  },

  nitro: {
    // Cache HTML đã render, phục vụ ngay rồi làm mới ở nền (SWR). Nội dung là tin
    // tức công khai, không có gì theo phiên trong HTML: CSRF token của form liên hệ
    // và bộ đếm online đều lấy phía client sau khi hydrate.
    //
    // Quy tắc phải là '/**': với preset node-server, Nitro bọc cache theo ROUTE ĐĂNG KÝ
    // của handler chứ không theo từng request; renderer của Nuxt đăng ký ở '/**' nên
    // rule hẹp hơn ('/tin-tuc/**'...) sẽ không bọc được gì. Cache key gồm cả query
    // string nên mỗi URL vẫn là một entry riêng.
    routeRules: {
      '/**': { swr: 600 },
      // Endpoint purge phải luôn chạy thật, không được trả từ cache.
      '/__purge': { cache: false },
    },

    // Cache xuống đĩa thay vì RAM: query tìm kiếm tạo key không giới hạn (mỗi ?q=
    // một entry) nên driver memory sẽ phình theo thời gian; đĩa cũng giữ được cache
    // qua lần restart daemon, tránh dồn tải lên Drupal ngay sau khi deploy.
    // Đường dẫn nằm ngoài thư mục dự án để không bị mutagen đồng bộ về host.
    //
    // NUXT_CACHE_DIR đọc lúc BUILD (nuxt.config chạy khi build, không phải khi khởi
    // động server) — đổi biến này phải build lại, đặt trong env của daemon vô ích.
    storage: {
      cache: {
        driver: 'fs',
        base: process.env.NUXT_CACHE_DIR || '/tmp/nidqc-nitro-cache',
      },
    },
  },
})
