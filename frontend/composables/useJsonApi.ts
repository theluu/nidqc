// Client gọi JSON:API của Drupal (ADR-004).
//
// KHÔNG dùng useRuntimeConfig() ở đây: composable này được gọi NHIỀU LẦN trong
// một useAsyncData handler, và gọi useRuntimeConfig SAU await sẽ mất Nuxt context
// -> "nuxt instance unavailable". Thay vào đó đọc trực tiếp:
//   - server (SSR): process.env
//   - client: cùng origin qua reverse proxy
function drupalBase(): string {
  if (import.meta.server) {
    return process.env.DRUPAL_INTERNAL || 'https://nidqc.ddev.site'
  }
  // Client: cùng domain với site (reverse proxy gộp Nuxt + Drupal ở production).
  // Dev: Drupal ở origin khác -> dùng env công khai nếu có.
  return (import.meta.env.VITE_DRUPAL_BASE as string) || window.location.origin
}

export async function fetchJsonApi(path: string, params: Record<string, any> = {}) {
  const query = new URLSearchParams()
  for (const [k, v] of Object.entries(params)) {
    if (v !== undefined && v !== null && v !== '') query.set(k, String(v))
  }
  const qs = query.toString()
  const res: any = await $fetch(`${drupalBase()}/jsonapi${path}${qs ? '?' + qs : ''}`, {
    headers: { Accept: 'application/vnd.api+json' },
  })
  return { data: res.data ?? [], included: res.included ?? [], meta: res.meta ?? {} }
}

export function findIncluded(included: any[], type: string, id: string) {
  return included.find((r) => r.type === type && r.id === id) ?? null
}

export function imageUrl(node: any, included: any[]) {
  const rel = node.relationships?.field_image?.data
  if (!rel) return null
  const uri = findIncluded(included, rel.type, rel.id)?.attributes?.uri?.url
  if (!uri) return null
  return uri.startsWith('http') ? uri : drupalBase() + uri
}

// Lấy 1 node "page" theo path alias.
// KHÔNG dùng filter[path.alias]: JSON:API không lọc được trên computed field 'path'
// (trả 500 "'path' not found"). Thay vào đó liệt kê rồi khớp alias phía JS.
export async function fetchPageByAlias(alias: string) {
  const { data } = await fetchJsonApi('/node/page', { 'filter[status]': 1, 'page[limit]': 50 })
  const n = data.find((x: any) => (x.attributes?.path?.alias || '') === alias)
  if (!n) return null
  return {
    title: n.attributes.title,
    body: n.attributes.body?.processed || n.attributes.body?.value || '',
  }
}

// Danh sách file đính kèm (field_attachments). Nhãn lấy từ meta.description
// của quan hệ (VD "-Văn bản gốc"), URL lấy từ file included.
export function attachments(node: any, included: any[]) {
  const rels = node.relationships?.field_attachments?.data
  if (!Array.isArray(rels)) return []
  return rels
    .map((r: any) => {
      const url = findIncluded(included, r.type, r.id)?.attributes?.uri?.url
      if (!url) return null
      const label = (r.meta?.description || '').replace(/^[\s-]+/, '').trim()
      const filename = findIncluded(included, r.type, r.id)?.attributes?.filename || 'Tải xuống'
      return { url: url.startsWith('http') ? url : drupalBase() + url, label: label || filename }
    })
    .filter(Boolean)
}

// Resolve slug (path alias không có prefix /tin-tuc) -> drupal_internal__nid.
// JSON:API không lọc được trên computed field 'path' nên phải phân trang & khớp JS.
export async function fetchNewsNidByAlias(alias: string): Promise<number | null> {
  // Alias tin tức nay ở cấp gốc: /<slug> (khớp trang gốc nidqc.gov.vn, bỏ prefix /tin-tuc).
  const full = alias.startsWith('/') ? alias : `/${alias}`
  for (let offset = 0; offset < 2000; offset += 50) {
    const { data } = await fetchJsonApi('/node/news', {
      'filter[status]': 1, sort: 'drupal_internal__nid', 'page[limit]': 50, 'page[offset]': offset,
    })
    const hit = data.find((x: any) => (x.attributes?.path?.alias || '') === full)
    if (hit) return hit.attributes.drupal_internal__nid
    if (data.length < 50) break
  }
  return null
}

// Đếm tổng số tin (đã xuất bản, tuỳ chọn theo chuyên mục) để tính số trang.
// JSON:API của Drupal KHÔNG trả tổng số (chỉ có link next/prev) và page[limit] bị
// chặn ở 50, nên phải liệt kê rồi cộng. Chỉ lấy id (fields[...]=drupal_internal__nid)
// để payload cực nhẹ, và gọi theo BATCH song song để giảm số vòng round-trip.
export async function countNews(catId?: string | null): Promise<number> {
  const base: Record<string, any> = {
    'filter[status]': 1, 'page[limit]': 50, 'fields[node--news]': 'drupal_internal__nid',
  }
  if (catId && catId !== 'all') base['filter[field_category.id]'] = catId
  const BATCH = 6 // 6 × 50 = 300 mục mỗi vòng song song
  let total = 0
  for (let start = 0; start < 200; start += BATCH) {
    const reqs = Array.from({ length: BATCH }, (_, i) =>
      fetchJsonApi('/node/news', { ...base, 'page[offset]': (start + i) * 50 }))
    const counts = (await Promise.all(reqs)).map((r) => r.data.length)
    total += counts.reduce((a, b) => a + b, 0)
    if (counts.some((c) => c < 50)) break // gặp trang chưa đầy => đã tới mục cuối
  }
  return total
}

export function termLabel(node: any, field: string, included: any[]) {
  const rel = node.relationships?.[field]?.data
  if (!rel) return ''
  return findIncluded(included, rel.type, rel.id)?.attributes?.name ?? ''
}

export function formatDate(value: string) {
  if (!value) return ''
  const d = new Date(value)
  if (Number.isNaN(d.getTime())) return ''
  const p = (n: number) => String(n).padStart(2, '0')
  return `${p(d.getDate())}/${p(d.getMonth() + 1)}/${d.getFullYear()}`
}
