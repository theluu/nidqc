// Khối nội dung tĩnh của trang chủ: dịch vụ, danh mục năng lực, hoạt động chuyên
// môn, banner, liên kết web, cơ sở và nút tra cứu chất chuẩn.
//
// MỘT request tới /api/v1/home/blocks thay cho 6 request JSON:API. Lý do chính không
// phải số request mà là ẢNH: JSON:API chỉ trả URL file gốc (ảnh admin tải lên tới
// 1.5MB), còn URL image style thì không dựng được ở frontend vì thiếu tham số itok.
// Controller phía Drupal trả sẵn URL đã qua image style đúng cỡ của từng khối.

export type HomeBlockItem = {
  title: string
  url: string | null
  image?: string | null
  description?: string
}

export type HomeBlocks = {
  services: HomeBlockItem[]
  capabilities: HomeBlockItem[]
  expertise: HomeBlockItem[]
  banners: { ads_1: HomeBlockItem[], ads_2: HomeBlockItem[], sidebar: HomeBlockItem[] }
  web_links: HomeBlockItem[]
  offices: { title: string, address: string, map: string | null }[]
  standards: { label: string, url: string | null, note: string } | null
}

const EMPTY: HomeBlocks = {
  services: [],
  capabilities: [],
  expertise: [],
  banners: { ads_1: [], ads_2: [], sidebar: [] },
  web_links: [],
  offices: [],
  standards: null,
}

function drupalBase(): string {
  if (import.meta.server) {
    return process.env.DRUPAL_INTERNAL || 'https://nidqc.ddev.site'
  }
  return (import.meta.env.VITE_DRUPAL_BASE as string) || window.location.origin
}

/**
 * Ảnh trả về là đường dẫn tương đối (không chôn domain của môi trường vào response);
 * ghép base khi hiển thị.
 */
function absolute(path: string | null | undefined): string | null {
  if (!path) return null
  return path.startsWith('http') ? path : `${drupalBase()}${path}`
}

export async function fetchHomeBlocks(): Promise<HomeBlocks> {
  const res = await $fetch<{ data: HomeBlocks }>(`${drupalBase()}/api/v1/home/blocks`, {
    headers: { Accept: 'application/json' },
  })
  const data = res.data
  if (!data) return EMPTY

  const withImage = (items: HomeBlockItem[] = []) =>
    items.map((item) => ({ ...item, image: absolute(item.image) }))

  return {
    services: withImage(data.services),
    capabilities: data.capabilities ?? [],
    expertise: withImage(data.expertise),
    banners: {
      ads_1: withImage(data.banners?.ads_1),
      ads_2: withImage(data.banners?.ads_2),
      sidebar: withImage(data.banners?.sidebar),
    },
    web_links: withImage(data.web_links),
    offices: data.offices ?? [],
    standards: data.standards ?? null,
  }
}
