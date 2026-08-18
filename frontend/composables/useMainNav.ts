// Menu chính — nguồn duy nhất cho cả thanh nav ở layout và các khối trang chủ
// "bê nguyên link trên menu xuống".
//
// Trước đây mảng này nằm trong layouts/default.vue. Tách ra vì trang chủ cũng phải
// dựng đúng danh sách chuyên mục Tin tức: chép sang index.vue thì sửa menu một chỗ,
// trang chủ vẫn hiện link cũ.
export type NavLink = {
  label: string
  to: string
}

export type NavItem = NavLink & {
  children: NavLink[]
}

// Nhãn mục menu có submenu lấy động từ khối trang chủ — dùng chung cho cả nơi khai
// báo và nơi thay thế, đổi tên mục thì không phải nhớ sửa hai chỗ.
export const EXPERTISE_LABEL = 'Hoạt động chuyên môn'

// Menu lấy từ design (const NAV). href trỏ route Vue. 9 mục, submenu 2 tầng.
export const mainNav: NavItem[] = [
  { label: 'Trang chủ', to: '/', children: [] },
  { label: 'Giới thiệu', to: '/gioi-thieu-chung', children: [
    { label: 'Giới thiệu chung', to: '/gioi-thieu-chung' },
    { label: 'Chính sách chất lượng', to: '/chinh-sach-chat-luong' },
    { label: 'Năng lực', to: '/nang-luc' },
    { label: 'Cơ cấu tổ chức', to: '/co-cau-to-chuc' },
  ] },
  // Submenu này chỉ là BẢN DỰ PHÒNG: lúc chạy, layout thay nó bằng chính các thẻ
  // trong khối "Hoạt động chuyên môn" ở trang chủ (/api/v1/home/blocks -> expertise)
  // qua mainNavWithExpertise(). Chép cứng thì admin thêm/sửa/xoá một hoạt động trong
  // Drupal là menu và khối lệch nhau ngay. Giữ lại danh sách này để khi API lỗi menu
  // vẫn có mục con thay vì rỗng.
  { label: EXPERTISE_LABEL, to: '/#hoat-dong-chuyen-mon', children: [
    { label: 'Chỉ đạo tuyến', to: '/hoat-dong-chuyen-mon/chi-dao-tuyen' },
    { label: 'Kiểm nghiệm và giám sát chất lượng thuốc', to: '/hoat-dong-chuyen-mon/kiem-nghiem-va-giam-sat-chat-luong-thuoc' },
    { label: 'Hợp tác quốc tế', to: '/hoat-dong-chuyen-mon/hop-tac-quoc-te' },
    { label: 'Hoạt động NRA', to: '/hoat-dong-chuyen-mon/hoat-dong-nra' },
    { label: 'Tạp chí Kiểm nghiệm Dược và Mỹ phẩm', to: '/hoat-dong-chuyen-mon/tap-chi-kiem-nghiem-duoc-va-my-pham' },
  ] },
  { label: 'Đào tạo & NCKH', to: '/dao-tao-nckh', children: [
    { label: 'Đào tạo tiến sỹ', to: '/dao-tao-nckh' },
    { label: 'Nghiên cứu khoa học', to: '/dao-tao-nckh' },
  ] },
  // Mỗi dịch vụ trỏ thẳng sang DANH SÁCH BÀI VIẾT của nó (/dich-vu/<danh-muc>),
  // không còn nhảy về mỏ neo #dich-vu ở trang chủ. Đoạn slug phải khớp tên term
  // trong vocabulary service_category — cùng quy tắc bỏ dấu với ServiceListController
  // và với alias pathauto của bài viết dịch vụ.
  //
  // "Cung ứng chất chuẩn" KHÔNG phải một danh mục dịch vụ: nó dẫn sang trang tra
  // cứu chất chuẩn ở hệ thống cũ, nên giữ link ngoài chứ không có danh sách bài.
  { label: 'Dịch vụ', to: '/#dich-vu', children: [
    { label: 'Phân tích - Kiểm nghiệm', to: '/dich-vu/phan-tich-kiem-nghiem' },
    { label: 'Đánh giá tương đương sinh học (TĐSH)', to: '/dich-vu/danh-gia-tuong-duong-sinh-hoc-tdsh' },
    { label: 'Đào tạo và tư vấn kỹ thuật', to: '/dich-vu/dao-tao-va-tu-van-ky-thuat' },
    { label: 'Hiệu chuẩn', to: '/dich-vu/hieu-chuan' },
    { label: 'Nghiên cứu - Chuyển giao', to: '/dich-vu/nghien-cuu-chuyen-giao' },
    { label: 'Thử nghiệm thành thạo', to: '/dich-vu/thu-nghiem-thanh-thao' },
    { label: 'Cung ứng chất chuẩn', to: 'https://nidqc.gov.vn/tim-kiem-chat-chuan' },
  ] },
  { label: 'Tin tức & Thông báo', to: '/tin-tuc', children: [
    { label: 'Thông báo', to: '/tin-tuc?cat=thong-bao' },
    { label: 'Tin hoạt động', to: '/tin-tuc?cat=tin-hoat-dong' },
    { label: 'Mua sắm, đấu thầu & công khai minh bạch', to: '/tin-tuc?cat=mua-sam-dau-thau' },
    { label: 'Đào tạo', to: '/tin-tuc?cat=dao-tao' },
    { label: 'Hội nghị - Hội thảo', to: '/tin-tuc?cat=hoi-nghi-hoi-thao' },
    { label: 'Tuyển dụng', to: '/tin-tuc?cat=tuyen-dung' },
  ] },
  { label: 'Văn bản - Tài liệu', to: '/van-ban-tai-lieu', children: [
    { label: 'Văn bản pháp quy', to: '/van-ban-tai-lieu' },
    { label: 'Tài liệu chuyên môn', to: '/van-ban-tai-lieu' },
  ] },
  { label: 'Liên hệ & hỗ trợ', to: '/lien-he', children: [
    { label: 'Liên hệ', to: '/lien-he' },
    { label: 'Câu hỏi thường gặp', to: '/faq' },
  ] },
]

/**
 * Menu chính với submenu "Hoạt động chuyên môn" dựng từ khối cùng tên ở trang chủ.
 *
 * Nhận thẳng mảng expertise của /api/v1/home/blocks (title + url) nên hai chỗ luôn
 * hiện đúng một danh sách, theo đúng thứ tự field_weight mà admin sắp trong Drupal.
 *
 * Bỏ qua mục không có link: khối trang chủ vẫn vẽ được thẻ không bấm được (thẻ chỉ
 * có ảnh + tiêu đề), nhưng một dòng menu không đi đâu cả thì chỉ làm người dùng bấm
 * hụt. Không còn mục nào có link -> trả nguyên menu tĩnh.
 */
export function mainNavWithExpertise(items?: { title: string, url?: string | null }[] | null): NavItem[] {
  const children = (items ?? [])
    .filter((item): item is { title: string, url: string } => typeof item.url === 'string' && item.url !== '')
    .map((item) => ({ label: item.title, to: item.url }))

  if (children.length === 0) {
    return mainNav
  }

  return mainNav.map((item) => (item.label === EXPERTISE_LABEL ? { ...item, children } : item))
}

/**
 * Submenu của một mục menu chính, tra theo nhãn.
 *
 * Trả mảng rỗng nếu đổi tên mục trên menu mà quên sửa nơi gọi — khối trên trang sẽ
 * trống chứ không làm vỡ trang.
 */
export function navChildren(label: string): NavLink[] {
  return mainNav.find((item) => item.label === label)?.children ?? []
}

/**
 * Link ra ngoài site hay không.
 *
 * Menu có vài mục trỏ sang hệ thống cũ (VD tra cứu chất chuẩn). NuxtLink tự nhận
 * ra URL tuyệt đối và render <a href>, nhưng KHÔNG tự thêm target/rel — mà mở
 * trang ngoài đè lên tab hiện tại thì người dùng mất chỗ đang đọc.
 */
export function isExternalLink(to: string): boolean {
  return /^https?:\/\//.test(to)
}
