// sitemap.xml động — crawler (Google/Bing) và AI (GEO) khám phá được TẤT CẢ trang.
// Drupal sitemap gốc chỉ liệt kê '/', nhưng nội dung do Nuxt render, nên phải tự sinh.
// URL tuyệt đối dựng từ host của request -> chạy đúng trên ddev.site lẫn nidqc.gov.vn.
import { defineEventHandler, getRequestHost } from 'h3'

// Các trang tĩnh (khớp pages/*.vue) + độ ưu tiên.
const STATIC_ROUTES: Array<[string, string, string]> = [
  ['/', '1.0', 'daily'],
  ['/gioi-thieu-chung', '0.7', 'monthly'],
  ['/co-cau-to-chuc', '0.6', 'monthly'],
  ['/nang-luc', '0.6', 'monthly'],
  ['/dao-tao-nckh', '0.7', 'monthly'],
  ['/chinh-sach-chat-luong', '0.6', 'monthly'],
  ['/van-ban-tai-lieu', '0.7', 'weekly'],
  ['/tin-tuc', '0.9', 'daily'],
  ['/lien-he', '0.5', 'yearly'],
  ['/faq', '0.4', 'monthly'],
]

function xmlEscape(s: string): string {
  return s.replace(/[<>&'"]/g, (c) => ({ '<': '&lt;', '>': '&gt;', '&': '&amp;', "'": '&apos;', '"': '&quot;' }[c] as string))
}

export default defineEventHandler(async (event) => {
  const drupal = process.env.DRUPAL_INTERNAL || 'https://nidqc.ddev.site'
  const host = getRequestHost(event, { xForwardedHost: true }) || 'nidqc.gov.vn'
  const origin = `https://${host}`

  // Lấy tin đã xuất bản để đưa vào sitemap (id + ngày sửa cho <lastmod>).
  const news: Array<{ id: number; changed: string }> = []
  try {
    const res: any = await $fetch(`${drupal}/jsonapi/node/news`, {
      params: {
        'filter[status]': 1,
        sort: '-changed',
        'fields[node--news]': 'drupal_internal__nid,changed',
        'page[limit]': 200,
      },
      headers: { Accept: 'application/vnd.api+json' },
    })
    for (const n of res.data ?? []) {
      news.push({ id: n.attributes.drupal_internal__nid, changed: n.attributes.changed })
    }
  } catch {
    // Drupal không phản hồi -> vẫn trả sitemap các trang tĩnh, không để 500.
  }

  const today = new Date().toISOString().slice(0, 10)
  const urls: string[] = []

  for (const [path, priority, freq] of STATIC_ROUTES) {
    urls.push(
      `  <url><loc>${xmlEscape(origin + path)}</loc><changefreq>${freq}</changefreq><priority>${priority}</priority><lastmod>${today}</lastmod></url>`,
    )
  }
  for (const n of news) {
    const lastmod = (n.changed || '').slice(0, 10) || today
    urls.push(
      `  <url><loc>${xmlEscape(`${origin}/tin-tuc/${n.id}`)}</loc><changefreq>monthly</changefreq><priority>0.6</priority><lastmod>${lastmod}</lastmod></url>`,
    )
  }

  const xml = `<?xml version="1.0" encoding="UTF-8"?>\n<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">\n${urls.join('\n')}\n</urlset>\n`

  setHeader(event, 'Content-Type', 'application/xml; charset=utf-8')
  setHeader(event, 'Cache-Control', 'max-age=3600, public')
  return xml
})
