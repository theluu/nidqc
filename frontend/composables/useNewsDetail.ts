// Lấy trọn dữ liệu trang chi tiết tin trong MỘT request tới Drupal.
//
// Trước đây trang này phải: gọi /__resolve-news (map alias->nid dựng bằng 16 request
// JSON:API, đo được 13.8s khi cache Drupal nguội) rồi thêm 3 request nữa cho node,
// tin liên quan và tin mới nhất. Nay controller nidqc_content.news_detail tra thẳng
// bảng path_alias và trả cả ba khối — client-side navigation chỉ còn 1 round-trip.
type NewsListItem = {
  id: number
  title: string
  created: string
  category: string
  image: string | null
  alias: string
}

type NewsDetailNode = {
  nid: number
  title: string
  created: string
  tag: string
  category: string
  image: string | null
  body: string
  attachments: { url: string, label: string }[]
}

type NewsDetailResponse = {
  data: {
    node: NewsDetailNode
    related: NewsListItem[]
    latest: NewsListItem[]
  }
}

function drupalBase(): string {
  if (import.meta.server) {
    return process.env.DRUPAL_INTERNAL || 'https://nidqc.ddev.site'
  }
  return (import.meta.env.VITE_DRUPAL_BASE as string) || window.location.origin
}

// Ảnh trả về là đường dẫn tương đối (controller dùng transformRelative) để không
// chôn domain của môi trường vào response; ghép base khi hiển thị.
export function newsImageUrl(path: string | null): string | null {
  if (!path || path.startsWith('http')) {
    return path
  }
  return `${drupalBase()}${path}`
}

type NewsListResponse = {
  data: NewsListItem[]
  meta: { total: number, page: number, limit: number }
  categories?: { id: string, label: string }[]
}

// Danh sách tin có phân trang + TỔNG SỐ trong một request.
//
// JSON:API không trả tổng số và chặn page[limit] ở 50, nên trang /tin-tuc trước đây
// phải liệt kê hết rồi cộng (countNews): 18 request chỉ để đếm 705 tin, đo được 5.5s.
// `cat` nhận UUID chuyên mục (trang danh sách) hoặc tên, phân tách bằng dấu phẩy
// (trang chủ tách hero/thông báo theo tên chuyên mục).
export async function fetchNewsList(options: {
  cat?: string
  page?: number
  limit?: number
  categories?: boolean
} = {}): Promise<NewsListResponse> {
  const query: Record<string, string | number> = {
    page: options.page ?? 0,
    limit: options.limit ?? 12,
  }
  if (options.cat && options.cat !== 'all') query.cat = options.cat
  if (options.categories) query.categories = '1'

  return await $fetch<NewsListResponse>(`${drupalBase()}/api/v1/news/list`, {
    query,
    headers: { Accept: 'application/json' },
  })
}

export async function fetchNewsDetail(alias: string): Promise<NewsDetailResponse['data'] | null> {
  const full = alias.startsWith('/') ? alias : `/${alias}`
  try {
    const res = await $fetch<NewsDetailResponse>(`${drupalBase()}/api/v1/news/detail`, {
      query: { alias: full },
      headers: { Accept: 'application/json' },
    })
    return res.data ?? null
  }
  catch (error: any) {
    // 404 = không có bài với alias này -> để trang tự ném createError(404).
    if (error?.statusCode === 404 || error?.response?.status === 404) {
      return null
    }
    throw error
  }
}
