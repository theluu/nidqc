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
