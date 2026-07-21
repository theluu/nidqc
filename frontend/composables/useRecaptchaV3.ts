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
  const siteKey = String(config.public.recaptchaSiteKey || '')

  async function executeRecaptcha(action: string): Promise<string> {
    if (!siteKey) {
      return 'ddev-bypass'
    }

    if (import.meta.server) {
      throw new Error('reCAPTCHA chỉ chạy trên trình duyệt.')
    }

    await loadRecaptchaScript(siteKey)

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

  return { executeRecaptcha }
}
