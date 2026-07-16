import { fileURLToPath, URL } from 'node:url';
import { defineConfig } from 'vite';
import vue from '@vitejs/plugin-vue';

/**
 * Vite build cho Vue SPA của NIDQC (ADR-003).
 *
 * Frontend tách rời: convert design HTML -> Vue, Drupal làm backend qua JSON:API.
 * SPA build ra web/app/, phục vụ tại /app/, fetch dữ liệu từ /jsonapi (cùng origin).
 */
export default defineConfig({
  base: '/app/',
  plugins: [vue()],
  resolve: {
    alias: { '@': fileURLToPath(new URL('./src', import.meta.url)) },
  },
  build: {
    outDir: fileURLToPath(new URL('../web/app', import.meta.url)),
    emptyOutDir: true,
    sourcemap: false,
  },
  server: {
    // Dev: proxy /jsonapi tới Drupal để cùng origin.
    proxy: {
      '/jsonapi': { target: 'https://nidqc.ddev.site', changeOrigin: true, secure: false },
      '/themes': { target: 'https://nidqc.ddev.site', changeOrigin: true, secure: false },
    },
  },
});
