// Phân giải alias tin (/<slug>) -> nid nhanh.
//
// Vấn đề: JSON:API không lọc được trên computed field 'path' nên trước đây mỗi lần mở
// 1 node phải LẶP tới ~16 request tuần tự dò alias (cold Drupal ~15s/node). PHP-FPM
// giới hạn worker nên chạy song song cũng không cứu được (16 request cold ~10s).
//
// Cách xử lý: build MAP alias->nid một lần (song song, chỉ lấy path+nid ~26KB/trang)
// rồi cache ở tầng Nitro dùng chung cho MỌI request, SWR (trả bản cũ + build lại nền)
// nên sau lần đầu không ai phải chờ. Pre-warm lúc server khởi động
// (server/plugins/warm-news-alias.ts). Mở node => tra map tức thì + 1 request lấy node.

type AliasMap = Record<string, number>

function drupalBase(): string {
  return process.env.DRUPAL_INTERNAL || 'https://nidqc.ddev.site'
}

async function fetchNewsPage(offset: number): Promise<any[]> {
  try {
    const res: any = await $fetch(`${drupalBase()}/jsonapi/node/news`, {
      params: {
        'filter[status]': 1,
        sort: 'drupal_internal__nid',
        'page[limit]': 50,
        'page[offset]': offset,
        'fields[node--news]': 'path,drupal_internal__nid',
      },
      headers: { Accept: 'application/vnd.api+json' },
    })
    return res.data ?? []
  } catch {
    return []
  }
}

export async function buildNewsAliasMap(): Promise<AliasMap> {
  const map: AliasMap = {}
  const BATCH = 8 // 8 × 50 = 400 tin/vòng, chạy song song
  for (let start = 0; start < 200; start += BATCH) {
    const pages = await Promise.all(
      Array.from({ length: BATCH }, (_, i) => fetchNewsPage((start + i) * 50)),
    )
    let ended = false
    for (const data of pages) {
      for (const n of data) {
        const alias = n.attributes?.path?.alias
        if (alias) map[alias] = n.attributes.drupal_internal__nid
      }
      if (data.length < 50) ended = true
    }
    if (ended) break
  }
  return map
}

// Cache Nitro: giữ 1 giờ + SWR (trả bản cũ ngay, build lại ở nền).
export const getNewsAliasMap = defineCachedFunction(buildNewsAliasMap, {
  maxAge: 3600,
  swr: true,
  name: 'news-alias-map',
  getKey: () => 'v1',
})

// Phân giải 1 alias -> nid. Miss (bài mới chưa vào cache) thì build tươi 1 lần.
export async function resolveNewsNid(alias: string): Promise<number | null> {
  const full = alias.startsWith('/') ? alias : `/${alias}`
  const map = await getNewsAliasMap()
  if (map[full] != null) return map[full]
  const fresh = await buildNewsAliasMap()
  return fresh[full] ?? null
}
