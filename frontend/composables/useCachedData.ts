// Bọc useAsyncData + gắn sẵn getCachedData để KHÔNG fetch lại khi điều hướng
// client-side quay lại một trang đã xem (ADR-004, SSR).
//
// Vì sao cần: Nuxt 3 mặc định chạy lại handler mỗi lần component remount. Rời trang
// chủ sang /tin-tuc rồi quay lại => <NuxtPage> mount lại index.vue => refetch toàn bộ
// request JSON:API. Header nằm ở layout nên hiện ngay, còn thân trang chờ fetch xong
// mới render -> cảm giác "trang chủ hiện trước, nội dung load sau", rõ nhất trên staging.
//
// getCachedData trả dữ liệu đã có trong payload (từ SSR hoặc lần fetch trước) nên quay
// lại trang là tức thì. Reload cả trang vẫn fetch mới (payload rỗng). Đổi tham số ->
// key khác -> vẫn fetch như thường. Trang có thể override option nếu cần.
import type { AsyncDataOptions } from '#app'
import type { MaybeRefOrGetter } from 'vue'

export function useCachedData<T>(
  key: MaybeRefOrGetter<string>,
  handler: () => Promise<T>,
  options: AsyncDataOptions<T> = {},
) {
  return useAsyncData<T>(key, handler, {
    getCachedData: (k, nuxtApp) => nuxtApp.payload.data[k] ?? nuxtApp.static.data[k],
    ...options,
  })
}
