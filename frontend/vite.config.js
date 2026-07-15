import { fileURLToPath, URL } from 'node:url';
import { defineConfig } from 'vite';
import vue from '@vitejs/plugin-vue';

/**
 * Vite build cho Vue island của NIDQC.
 *
 * KHÔNG phải SPA. Drupal render nội dung; Vue chỉ nâng cấp các khối tương tác.
 * Xem docs/decisions/ADR-001-frontend-architecture.md.
 *
 * Production KHÔNG chạy Node — Vite chỉ chạy lúc build, xuất ra JS/CSS tĩnh cho
 * Drupal library nạp.
 */
export default defineConfig({
  plugins: [vue()],

  resolve: {
    alias: {
      '@': fileURLToPath(new URL('./src', import.meta.url)),
    },
  },

  build: {
    // Drupal library trỏ tới đường dẫn CỐ ĐỊNH, nên không được có hash trong tên file.
    outDir: fileURLToPath(new URL('../web/themes/custom/nidqc/dist', import.meta.url)),
    emptyOutDir: true,
    manifest: false,

    // Không sinh source map ra production: nó lộ đường dẫn máy dev và toàn bộ
    // source. Xem tasks/TASK-005.md §8.
    sourcemap: false,

    rollupOptions: {
      input: fileURLToPath(new URL('./src/main.js', import.meta.url)),
      output: {
        entryFileNames: 'islands.js',
        // Island lazy-load riêng: trang chỉ có FAQ thì không tải code mega menu.
        // Tên chunk cũng phải cố định để không rác thư mục dist qua mỗi lần build.
        chunkFileNames: 'island-[name].js',
        assetFileNames: 'islands.[ext]',
      },
    },
  },
});
