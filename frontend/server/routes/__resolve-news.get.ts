// GET /__resolve-news?alias=/<slug> -> { nid } | { nid: null }
//
// Đặt ở /__resolve-news (KHÔNG phải /api/*): reverse proxy đẩy /api,/jsonapi,/user,...
// sang Drupal; chỉ path còn lại mới sang Nuxt. Prefix __ chắc chắn không đụng Drupal
// lẫn slug bài viết. Dùng map alias->nid cache Nitro (server/utils/newsAlias.ts):
// gọi được từ SSR (nội bộ) lẫn điều hướng client-side.
import { defineEventHandler, getQuery } from 'h3'

export default defineEventHandler(async (event) => {
  const alias = String(getQuery(event).alias || '')
  if (!alias) return { nid: null }
  const nid = await resolveNewsNid(alias)
  return { nid }
})
