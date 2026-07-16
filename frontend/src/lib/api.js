/**
 * @file
 * JSON:API client cho Drupal headless backend (ADR-003).
 *
 * Drupal cung cấp nội dung tại /jsonapi. Vue fetch qua đây, không fetch rải rác
 * trong component.
 */

const BASE = '/jsonapi';

export class ApiError extends Error {
  constructor(message, status) {
    super(message);
    this.name = 'ApiError';
    this.status = status;
  }
}

/** Gọi JSON:API, trả { data, included }. Ném ApiError khi lỗi. */
export async function jsonapi(path, params = {}) {
  const url = new URL(`${BASE}${path}`, window.location.origin);
  for (const [k, v] of Object.entries(params)) {
    if (v !== undefined && v !== null && v !== '') {
      url.searchParams.set(k, v);
    }
  }

  let res;
  try {
    res = await fetch(url, { headers: { Accept: 'application/vnd.api+json' } });
  } catch (e) {
    throw new ApiError('Không kết nối được máy chủ.', 0);
  }
  if (!res.ok) {
    throw new ApiError(`Lỗi máy chủ (${res.status}).`, res.status);
  }
  const body = await res.json();
  return { data: body.data ?? [], included: body.included ?? [], meta: body.meta ?? {} };
}

/** Tra một entity trong `included` theo type + id. */
export function findIncluded(included, type, id) {
  return included.find((r) => r.type === type && r.id === id) ?? null;
}

/** URL ảnh từ relationship field_image (nếu có). */
export function imageUrl(node, included) {
  const rel = node.relationships?.field_image?.data;
  if (!rel) return null;
  const file = findIncluded(included, rel.type, rel.id);
  const uri = file?.attributes?.uri?.url;
  return uri ? new URL(uri, window.location.origin).href : null;
}

/** Nhãn taxonomy term từ relationship (đã include). */
export function termLabel(node, field, included) {
  const rel = node.relationships?.[field]?.data;
  if (!rel) return '';
  const term = findIncluded(included, rel.type, rel.id);
  return term?.attributes?.name ?? '';
}

/** Ngày dd/mm/yyyy từ field datetime (Y-m-d) hoặc created. */
export function formatDate(value) {
  if (!value) return '';
  const d = new Date(value);
  if (Number.isNaN(d.getTime())) return '';
  const p = (n) => String(n).padStart(2, '0');
  return `${p(d.getDate())}/${p(d.getMonth() + 1)}/${d.getFullYear()}`;
}
