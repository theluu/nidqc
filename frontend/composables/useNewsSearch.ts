type NewsSearchItem = {
  id: number
  title: string
  created: string
  tag: string
  image: string | null
  url: string
}

type NewsSearchResponse = {
  data: NewsSearchItem[]
  meta: {
    total: number
    page: number
    limit: number
  }
}

function drupalBase(): string {
  if (import.meta.server) {
    return process.env.DRUPAL_INTERNAL || 'https://nidqc.ddev.site'
  }
  return (import.meta.env.VITE_DRUPAL_BASE as string) || window.location.origin
}

export async function searchNews(
  keyword: string,
  page: number,
  limit = 12,
): Promise<NewsSearchResponse> {
  return await $fetch<NewsSearchResponse>(`${drupalBase()}/api/v1/news/search`, {
    query: {
      q: keyword,
      page,
      limit,
    },
    headers: {
      Accept: 'application/json',
    },
  })
}

export function newsSearchImageUrl(path: string | null): string | null {
  if (!path || path.startsWith('http')) {
    return path
  }
  return `${drupalBase()}${path}`
}
