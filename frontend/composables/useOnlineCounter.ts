export type VisitStats = {
  today: number
  month: number
  year: number
  total: number
}

type OnlineResponse = {
  data?: {
    count?: number
    window_seconds?: number
    visits?: Partial<VisitStats>
  }
}

/**
 * Chuẩn hoá khối thống kê: thiếu khoá nào (Drupal cũ, lỗi mạng) thì coi như 0 chứ
 * không để undefined lọt vào template.
 */
function normaliseVisits(value: Partial<VisitStats> | undefined): VisitStats | null {
  if (!value) return null
  const num = (v: unknown) => (Number.isFinite(Number(v)) ? Number(v) : 0)
  return { today: num(value.today), month: num(value.month), year: num(value.year), total: num(value.total) }
}

function drupalBase(): string {
  if (import.meta.server) {
    return process.env.DRUPAL_INTERNAL || 'https://nidqc.ddev.site'
  }

  return (import.meta.env.VITE_DRUPAL_BASE as string) || window.location.origin
}

/**
 * Provides an SSR count and a CSRF-protected client heartbeat.
 */
export async function useOnlineCounter() {
  const onlineCount = ref<number | null>(null)
  const visits = ref<VisitStats | null>(null)
  let timer: ReturnType<typeof setInterval> | undefined
  let csrfToken = ''

  const { data } = await useAsyncData<{ count: number | null, visits: VisitStats | null }>('online-counter', async () => {
    try {
      const response = await $fetch<OnlineResponse>(`${drupalBase()}/api/v1/online`, {
        headers: { Accept: 'application/json' },
      })
      return {
        count: Number.isInteger(response.data?.count) ? response.data!.count! : null,
        visits: normaliseVisits(response.data?.visits),
      }
    }
    catch {
      return { count: null, visits: null }
    }
  })
  onlineCount.value = data.value?.count ?? null
  visits.value = data.value?.visits ?? null

  async function heartbeat() {
    if (document.hidden) {
      return
    }

    try {
      if (!csrfToken) {
        const tokenResponse = await fetch(`${drupalBase()}/api/v1/online/csrf-token`, {
          credentials: 'include',
          headers: { Accept: 'text/plain' },
        })
        if (!tokenResponse.ok) {
          return
        }
        csrfToken = (await tokenResponse.text()).trim()
      }

      const response = await fetch(`${drupalBase()}/api/v1/online/heartbeat`, {
        method: 'POST',
        credentials: 'include',
        headers: {
          Accept: 'application/json',
          'X-CSRF-Token': csrfToken,
        },
      })
      if (!response.ok) {
        return
      }

      const body = await response.json() as OnlineResponse
      if (Number.isInteger(body.data?.count) && body.data!.count! >= 0) {
        onlineCount.value = body.data!.count!
      }
      // Heartbeat của chính phiên này vừa được tính vào lượt truy cập hôm nay ->
      // cập nhật luôn khối thống kê, không để nó đứng ở số cũ của bản HTML cache.
      const fresh = normaliseVisits(body.data?.visits)
      if (fresh) visits.value = fresh
    }
    catch {
      // Keep the SSR value when a background refresh cannot reach Drupal.
    }
  }

  function onVisibilityChange() {
    if (!document.hidden) {
      heartbeat()
    }
  }

  onMounted(() => {
    heartbeat()
    document.addEventListener('visibilitychange', onVisibilityChange)
    timer = setInterval(heartbeat, 60000)
  })

  onUnmounted(() => {
    document.removeEventListener('visibilitychange', onVisibilityChange)
    if (timer) {
      clearInterval(timer)
    }
  })

  return { onlineCount, visits }
}
