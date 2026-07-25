type RecaptchaClient = {
  ready: (callback: () => void) => void
  execute: (siteKey: string, options: { action: string }) => Promise<string>
}

declare global {
  interface Window {
    grecaptcha?: RecaptchaClient
  }
}

const RECAPTCHA_SCRIPT_ID = 'nidqc-recaptcha-v3'
let recaptchaScriptPromise: Promise<void> | null = null
let publicConfigPromise: Promise<{ enabled: boolean; siteKey: string }> | null = null

function drupalBase(): string {
  return (import.meta.env.VITE_DRUPAL_BASE as string) || window.location.origin
}

async function readPublicConfig(): Promise<{ enabled: boolean; siteKey: string }> {
  if (publicConfigPromise) {
    return publicConfigPromise
  }

  publicConfigPromise = fetch(`${drupalBase()}/api/v1/contact/config`, {
    credentials: 'include',
    headers: { Accept: 'application/json' },
  })
    .then(async (response) => {
      if (!response.ok) {
        throw new Error('Không tải được cấu hình reCAPTCHA.')
      }
      const body = await response.json()
      return {
        enabled: body?.data?.recaptcha?.enabled === true,
        siteKey: String(body?.data?.recaptcha?.site_key || ''),
      }
    })

  return publicConfigPromise
}

function loadRecaptchaScript(siteKey: string): Promise<void> {
  if (window.grecaptcha) {
    return Promise.resolve()
  }

  if (recaptchaScriptPromise) {
    return recaptchaScriptPromise
  }

  recaptchaScriptPromise = new Promise((resolve, reject) => {
    const existing = document.getElementById(RECAPTCHA_SCRIPT_ID)
    if (existing) {
      existing.addEventListener('load', () => resolve(), { once: true })
      existing.addEventListener('error', () => reject(new Error('Không tải được reCAPTCHA.')), { once: true })
      return
    }

    const script = document.createElement('script')
    script.id = RECAPTCHA_SCRIPT_ID
    script.src = `https://www.google.com/recaptcha/api.js?render=${encodeURIComponent(siteKey)}`
    script.async = true
    script.defer = true
    script.addEventListener('load', () => resolve(), { once: true })
    script.addEventListener('error', () => reject(new Error('Không tải được reCAPTCHA.')), { once: true })
    document.head.appendChild(script)
  })

  return recaptchaScriptPromise
}

export function useRecaptchaV3() {
  const config = useRuntimeConfig()
  const environmentSiteKey = String(config.public.recaptchaSiteKey || '')

  async function initializeRecaptcha(): Promise<string | null> {
    if (import.meta.server) {
      return null
    }

    const publicConfig = environmentSiteKey
      ? { enabled: true, siteKey: environmentSiteKey }
      : await readPublicConfig()
    if (!publicConfig.enabled) {
      return null
    }
    if (!publicConfig.siteKey) {
      throw new Error('reCAPTCHA chưa được cấu hình.')
    }

    const siteKey = publicConfig.siteKey
    await loadRecaptchaScript(siteKey)
    return siteKey
  }

  async function executeRecaptcha(action: string): Promise<string> {
    const siteKey = await initializeRecaptcha()
    if (!siteKey) {
      return 'ddev-bypass'
    }

    if (!window.grecaptcha) {
      throw new Error('Không tải được reCAPTCHA.')
    }

    return new Promise((resolve, reject) => {
      window.grecaptcha?.ready(() => {
        window.grecaptcha
          ?.execute(siteKey, { action })
          .then(resolve)
          .catch(() => reject(new Error('Không xác thực được reCAPTCHA. Vui lòng thử lại.')))
      })
    })
  }

  return { executeRecaptcha, initializeRecaptcha }
}
