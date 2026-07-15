/**
 * @file
 * Mọi request của island đi qua đây. Không fetch rải rác trong component.
 *
 * Lỗi theo chuẩn docs/api/API_ERROR_STANDARD.md:
 *   { "error": { "code": "INVALID_PARAMETER", "message": "...", "details": [...] } }
 */

/** Lỗi API đã chuẩn hoá. Bắt theo `code`, KHÔNG so khớp `message`. */
export class ApiError extends Error {
  constructor(code, message, status) {
    super(message);
    this.name = 'ApiError';
    this.code = code;
    this.status = status;
  }
}

const BASE = '/api/v1';

/** Thông báo cho người dùng cuối. Tiếng Việt, không lộ chi tiết kỹ thuật. */
const FALLBACK_MESSAGE = 'Không kết nối được máy chủ. Vui lòng thử lại.';

export async function get(path, params = {}) {
  const url = new URL(`${BASE}${path}`, window.location.origin);
  for (const [k, v] of Object.entries(params)) {
    // Bỏ tham số rỗng thay vì gửi ?cat=undefined lên server.
    if (v !== null && v !== undefined && v !== '') {
      url.searchParams.set(k, v);
    }
  }

  let res;
  try {
    res = await fetch(url, { headers: { Accept: 'application/json' } });
  } catch (e) {
    // Mất mạng, DNS hỏng, request bị huỷ — chưa từng chạm tới server.
    throw new ApiError('NETWORK_ERROR', FALLBACK_MESSAGE, 0);
  }

  if (!res.ok) {
    // Response lỗi KHÔNG chắc là JSON: nginx 502 trả HTML, gateway trả text.
    // Không bọc catch ở đây thì island nổ bằng SyntaxError khó hiểu thay vì
    // hiện thông báo dùng được.
    const body = await res.json().catch(() => null);
    throw new ApiError(
      body?.error?.code ?? 'INTERNAL_ERROR',
      body?.error?.message ?? FALLBACK_MESSAGE,
      res.status,
    );
  }

  try {
    return await res.json();
  } catch (e) {
    throw new ApiError('INVALID_RESPONSE', FALLBACK_MESSAGE, res.status);
  }
}
