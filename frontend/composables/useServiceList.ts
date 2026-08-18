// Danh sách "Bài viết dịch vụ" của một dịch vụ: /dich-vu/<danh-muc>.
//
// Feedback 08/2026: bấm vào một dịch vụ ở trang chủ phải ra DANH SÁCH BÀI VIẾT
// (như /tin-hoat-dong của NIFC) chứ không phải một trang tĩnh.
//
// Một request duy nhất trả cả 3 thứ trang cần: bài của trang hiện tại, meta.total
// (để dựng phân trang mà không phải liệt kê hết) và — khi truyền categories —
// danh sách dịch vụ để vẽ thanh chuyển dịch vụ. Xem ServiceListController.php.

export type ServiceCategory = {
  id: string
  label: string
  slug: string
  url: string
  // Chỉ danh mục đang mở mới có phần giới thiệu (HTML).
  description?: string
}

export type ServiceListItem = {
  id: number
  title: string
  created: string
  tag: string
  category: string
  image: string | null
  alias: string
  summary: string
}

export type ServiceListResponse = {
  data: ServiceListItem[]
  meta: { total: number, page: number, limit: number }
  category: ServiceCategory | null
  categories?: ServiceCategory[]
}

function drupalBase(): string {
  if (import.meta.server) {
    return process.env.DRUPAL_INTERNAL || 'https://nidqc.ddev.site'
  }
  return (import.meta.env.VITE_DRUPAL_BASE as string) || window.location.origin
}

export async function fetchServiceList(options: {
  cat?: string
  page?: number
  limit?: number
  categories?: boolean
} = {}): Promise<ServiceListResponse | null> {
  const query: Record<string, string | number> = {}
  if (options.cat) query.cat = options.cat
  if (options.page !== undefined) query.page = options.page
  if (options.limit !== undefined) query.limit = options.limit
  if (options.categories) query.categories = '1'

  try {
    return await $fetch<ServiceListResponse>(`${drupalBase()}/api/v1/service/list`, {
      query,
      headers: { Accept: 'application/json' },
    })
  }
  catch (error: any) {
    // 404 = slug dịch vụ không tồn tại -> để route ném createError(404), đừng dựng
    // một danh sách rỗng trông như "dịch vụ chưa có bài".
    if (error?.statusCode === 404 || error?.response?.status === 404) {
      return null
    }
    throw error
  }
}
