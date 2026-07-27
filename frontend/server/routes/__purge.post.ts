// POST /__purge — xoá cache HTML (SWR route rules) sau khi Drupal đổi nội dung.
//
// Đặt ở /__purge (KHÔNG phải /api/*): reverse proxy đẩy /api,/jsonapi,/user,... sang
// Drupal, chỉ path còn lại mới tới Nuxt. Gọi từ hook trong nidqc_content.module.
// Không có endpoint này thì biên tập viên sửa bài xong vẫn thấy bản cũ tới hết TTL.
import { timingSafeEqual } from 'node:crypto'
import { createError, defineEventHandler, getHeader } from 'h3'

function tokenMatches(given: string, expected: string): boolean {
  const a = Buffer.from(given)
  const b = Buffer.from(expected)
  // timingSafeEqual ném lỗi khi khác độ dài -> so độ dài trước.
  return a.length === b.length && timingSafeEqual(a, b)
}

export default defineEventHandler(async (event) => {
  const expected = process.env.NUXT_PURGE_TOKEN || ''
  if (!expected) {
    throw createError({ statusCode: 503, statusMessage: 'Purge chưa được cấu hình' })
  }
  if (!tokenMatches(String(getHeader(event, 'x-purge-token') || ''), expected)) {
    throw createError({ statusCode: 401, statusMessage: 'Token purge không hợp lệ' })
  }

  // Route rules cache dùng group "nitro/routes" trong storage "cache"
  // (nitropack/dist/runtime/internal/app.mjs).
  //
  // KHÔNG dùng storage.clear('nitro:routes'): unstorage gọi getMounts(base, false),
  // bỏ qua mount cha, mà 'cache' lại là mount NGOÀI prefix này -> không khớp mount
  // nào và lệnh im lặng không xoá gì. getKeys() thì có includeParent nên chạy đúng.
  const cache = useStorage('cache')
  const keys = await cache.getKeys('nitro:routes')
  await Promise.all(keys.map((key) => cache.removeItem(key, { removeMeta: true })))

  return { purged: keys.length, at: new Date().toISOString() }
})
