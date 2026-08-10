export type ContactPayload = {
  name: string
  email: string
  phone: string
  subject: string
  message: string
  recaptchaToken: string
}

type ContactApiErrorBody = {
  error?: {
    code?: string
    message?: string
    details?: Array<{ field?: string; issue?: string }>
  }
}

export class ContactApiError extends Error {
  code: string
  status: number
  details: Array<{ field?: string; issue?: string }>

  constructor(code: string, message: string, status: number, details: Array<{ field?: string; issue?: string }> = []) {
    super(message)
    this.name = 'ContactApiError'
    this.code = code
    this.status = status
    this.details = details
  }
}

function drupalBase(): string {
  if (import.meta.server) {
    return process.env.DRUPAL_INTERNAL || 'https://nidqc.ddev.site'
  }

  return (import.meta.env.VITE_DRUPAL_BASE as string) || window.location.origin
}

export type PublicConfig = {
  recaptcha: { enabled: boolean, site_key: string }
  social: { key: string, url: string }[]
  // Chân trang (feedback 13): thông tin liên hệ chung + đầu mối theo nhóm dịch vụ.
  footer: { tel: string, tel_note: string, fax: string, email: string }
  customer_services: { label: string, email: string, hotline: string }[]
}

/**
 * Cấu hình công khai của site (quản trị tại /admin/config/nidqc/settings).
 *
 * Khác readPublicConfig() trong useRecaptchaV3.ts: hàm đó chạy phía client (dùng
 * window.location.origin) cho form liên hệ; hàm này gọi được cả trên server để layout
 * render link mạng xã hội ngay trong HTML của SSR.
 */
export async function fetchPublicConfig(): Promise<PublicConfig> {
  const res = await $fetch<{ data: PublicConfig }>(`${drupalBase()}/api/v1/contact/config`, {
    headers: { Accept: 'application/json' },
  })
  return {
    recaptcha: res.data?.recaptcha ?? { enabled: false, site_key: '' },
    social: res.data?.social ?? [],
    footer: res.data?.footer ?? { tel: '', tel_note: '', fax: '', email: '' },
    customer_services: res.data?.customer_services ?? [],
  }
}

async function readError(response: Response): Promise<ContactApiError> {
  const body = await response.json().catch(() => null) as ContactApiErrorBody | null
  return new ContactApiError(
    body?.error?.code ?? 'INTERNAL_ERROR',
    body?.error?.message ?? 'Không kết nối được máy chủ. Vui lòng thử lại.',
    response.status,
    body?.error?.details ?? [],
  )
}

export async function submitContact(payload: ContactPayload) {
  const base = drupalBase()
  const csrfResponse = await fetch(`${base}/api/v1/contact/csrf-token`, {
    credentials: 'include',
    headers: { Accept: 'text/plain' },
  })

  if (!csrfResponse.ok) {
    throw new ContactApiError('CSRF_TOKEN_INVALID', 'Không tạo được phiên gửi biểu mẫu. Vui lòng tải lại trang.', csrfResponse.status)
  }

  const csrfToken = (await csrfResponse.text()).trim()

  const response = await fetch(`${base}/api/v1/contact`, {
    method: 'POST',
    credentials: 'include',
    headers: {
      Accept: 'application/json',
      'Content-Type': 'application/json',
      'X-CSRF-Token': csrfToken,
    },
    body: JSON.stringify(payload),
  })

  if (!response.ok) {
    throw await readError(response)
  }

  return response.json()
}
