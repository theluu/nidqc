// Trang tĩnh nhiều cấp: /dich-vu/…, /danh-muc-nang-luc/…, /hoat-dong-chuyen-mon/…
//
// Tra alias qua /api/v1/page (một query trên bảng path_alias). Không dùng
// fetchPageByAlias() của useJsonApi.ts cho những trang này: hàm đó phải liệt kê toàn
// bộ node `page` rồi khớp alias phía JS (JSON:API không lọc được trên computed field
// 'path'), nên sẽ trượt ngay khi số trang vượt page[limit].

export type StaticPage = {
  nid: number
  type: string
  title: string
  created: string
  changed: string
  image: string | null
  body: string
  attachments: { url: string, label: string }[]
}

function drupalBase(): string {
  if (import.meta.server) {
    return process.env.DRUPAL_INTERNAL || 'https://nidqc.ddev.site'
  }
  return (import.meta.env.VITE_DRUPAL_BASE as string) || window.location.origin
}

function absolute(path: string | null): string | null {
  if (!path) return null
  return path.startsWith('http') ? path : `${drupalBase()}${path}`
}

export async function fetchStaticPage(alias: string): Promise<StaticPage | null> {
  const full = alias.startsWith('/') ? alias : `/${alias}`
  try {
    const res = await $fetch<{ data: StaticPage }>(`${drupalBase()}/api/v1/page`, {
      query: { alias: full },
      headers: { Accept: 'application/json' },
    })
    if (!res.data) return null
    return {
      ...res.data,
      image: absolute(res.data.image),
      attachments: (res.data.attachments || []).map((f) => ({ ...f, url: absolute(f.url) as string })),
    }
  }
  catch (error: any) {
    // 404 = không có trang với alias này -> để route tự ném createError(404).
    if (error?.statusCode === 404 || error?.response?.status === 404) {
      return null
    }
    throw error
  }
}
