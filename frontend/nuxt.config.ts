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
    // PHẢI bật khi có route rules swr ở dưới, dù trước đây tắt có lý do.
    //
    // Cờ này chỉ điều khiển phía CLIENT. Phía server, renderer của Nuxt 3.21 quyết
    // định tách payload bằng `routeOptions.isr || routeOptions.cache` và KHÔNG hề
    // đọc cờ này (.output/server/chunks/routes/renderer.mjs). Rule `'/**': { swr }`
    // làm mọi route đều có `cache` -> server LUÔN tách data ra `_payload.json`, còn
    // client build với cờ tắt thì `loadPayload()` bị tree-shake thành `return null`
    // nên không bao giờ tải file đó. Kết quả: `payload.data` rỗng lúc hydrate.
    //
    // Hậu quả thật: useAsyncData thấy không có cached data + đang hydrate nên hoãn
    // fetch tới onBeforeMount và trả data = null NGAY, khiến `pages/[slug].vue` ném
    // createError(404) đè lên bài viết mà SSR vừa render đúng — mở tin tức rồi F5 là
    // ra trang 404, dù access log ghi 200. Mọi trang khác thì fetch lại toàn bộ dữ
    // liệu lúc hydrate (Drupal nhận gấp đôi request, nội dung nháy).
    //
    // Lý do tắt trước đây — `_payload.json` chỉ mang path, không mang query nên trang
    // theo query nhận payload của bản không query rồi ghi đè lúc hydrate — nay không
    // còn: khoá useAsyncData của /tim-kiem và /tin-tuc đều đã gắn query
    // (`news-search-${q}-p${trang}`, `news-list-${cat}-p${trang}`). Khoá không khớp
    // thì trang tự fetch lại như hiện tại, KHÔNG bị đè bằng dữ liệu sai.
    payloadExtraction: true,
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
      // Cache-Control mà rule swr sinh ra được viết lại trong
      // server/plugins/browser-revalidate.ts. KHÔNG đặt `headers` ở đây: cache handler
      // của Nitro gắn header sau route rules nên khai báo tại chỗ này bị bỏ qua —
      // đã thử và xác nhận header không hề đổi.
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
