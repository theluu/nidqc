// Thư viện Video / Hình ảnh trên trang chủ.
//
// Drupal đã chuẩn hoá sẵn media (controller NewsMediaController): mỗi bài trả kèm
// luôn danh sách item {type, thumbnail, src|video_id} theo đúng thứ tự admin sắp.
// Frontend KHÔNG tự suy diễn từ field thô, cũng không gọi thêm request nào nữa khi
// mở lightbox — cả bộ media của bài đã nằm trong response này.
export type MediaItem =
  | { type: 'image', thumbnail: string | null, src: string, alt: string }
  | { type: 'youtube', thumbnail: string | null, video_id: string }
  | { type: 'video', thumbnail: string | null, src: string, mime: string }

export type MediaPost = {
  id: number
  title: string
  created: string
  alias: string
  kind: 'video' | 'image' | null
  thumbnail: string | null
  // Bìa là khung hình của video tải lên (khi bài không có ảnh tĩnh nào dùng được).
  cover_video: string | null
  count: number
  items: MediaItem[]
}

function drupalBase(): string {
  if (import.meta.server) {
    return process.env.DRUPAL_INTERNAL || 'https://nidqc.ddev.site'
  }
  return (import.meta.env.VITE_DRUPAL_BASE as string) || window.location.origin
}

// Ảnh/video là đường dẫn tương đối (controller dùng transformRelative) để không chôn
// domain của môi trường vào response; thumbnail YouTube là URL tuyệt đối nên giữ nguyên.
export function mediaUrl(path: string | null): string | null {
  if (!path || path.startsWith('http')) {
    return path
  }
  return `${drupalBase()}${path}`
}

export async function fetchMediaLibrary(limit = 12): Promise<MediaPost[]> {
  const res = await $fetch<{ data: MediaPost[] }>(`${drupalBase()}/api/v1/news/media`, {
    query: { limit },
    headers: { Accept: 'application/json' },
  })
  return (res.data || []).map((post) => ({
    ...post,
    thumbnail: mediaUrl(post.thumbnail),
    cover_video: mediaUrl(post.cover_video),
    items: post.items.map((item) => ({
      ...item,
      thumbnail: mediaUrl(item.thumbnail),
      ...('src' in item ? { src: mediaUrl(item.src) as string } : {}),
    })) as MediaItem[],
  }))
}
