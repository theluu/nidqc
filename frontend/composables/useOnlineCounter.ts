type OnlineResponse = {
  data?: {
    count?: number
    window_seconds?: number
  }
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
  let timer: ReturnType<typeof setInterval> | undefined
  let csrfToken = ''

  const { data } = await useAsyncData<number | null>('online-counter', async () => {
    try {
      const response = await $fetch<OnlineResponse>(`${drupalBase()}/api/v1/online`, {
        headers: { Accept: 'application/json' },
      })
      return Number.isInteger(response.data?.count) ? response.data!.count! : null
    }
    catch {
      return null
    }
  })
  onlineCount.value = data.value

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

  return { onlineCount }
}
