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

// Nhãn hai mục menu có submenu lấy động từ khối trang chủ — dùng chung cho cả nơi
// khai báo và nơi thay thế, đổi tên mục thì không phải nhớ sửa hai chỗ.
export const EXPERTISE_LABEL = 'Hoạt động chuyên môn'
export const SERVICE_LABEL = 'Dịch vụ'

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
  // qua mainNavWithBlocks(). Chép cứng thì admin thêm/sửa/xoá một hoạt động trong
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
  // BẢN DỰ PHÒNG như trên: lúc chạy, submenu này được thay bằng chính các thẻ trong
  // khối "Dịch vụ" ở trang chủ (/api/v1/home/blocks -> services), cộng nút "Tra cứu
  // chất chuẩn" (-> standards). Admin sửa tiêu đề/link một dịch vụ trong Drupal là
  // menu đổi theo, không phải sửa file này.
  //
  // Mỗi dịch vụ trỏ thẳng sang DANH SÁCH BÀI VIẾT của nó (/dich-vu/<danh-muc>),
  // không còn nhảy về mỏ neo #dich-vu ở trang chủ. Đoạn slug phải khớp tên term
  // trong vocabulary service_category — cùng quy tắc bỏ dấu với ServiceListController
  // và với alias pathauto của bài viết dịch vụ.
  //
  // "Cung ứng chất chuẩn" KHÔNG phải một danh mục dịch vụ: nó dẫn sang trang tra
  // cứu chất chuẩn ở hệ thống cũ, nên giữ link ngoài chứ không có danh sách bài.
  { label: SERVICE_LABEL, to: '/#dich-vu', children: [
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

// Phần payload /api/v1/home/blocks mà menu dùng tới. Khai báo rộng (title + url)
// thay vì import HomeBlocks để composable menu không phụ thuộc ngược vào tầng fetch.
type HomeBlockNavItem = { title: string, url?: string | null }

export type HomeBlockNav = {
  services?: HomeBlockNavItem[] | null
  expertise?: HomeBlockNavItem[] | null
  standards?: { label: string, url: string | null } | null
}

/**
 * Đổi mảng thẻ của một khối trang chủ thành mục menu, bỏ thẻ không có link.
 */
function toNavLinks(items?: HomeBlockNavItem[] | null): NavLink[] {
  return (items ?? [])
    .filter((item): item is { title: string, url: string } => typeof item.url === 'string' && item.url !== '')
    .map((item) => ({ label: item.title, to: item.url }))
}

/**
 * Menu chính với hai submenu "Dịch vụ" và "Hoạt động chuyên môn" dựng từ chính các
 * khối cùng tên ở trang chủ.
 *
 * Nhận thẳng payload của /api/v1/home/blocks (title + url) nên menu và khối trên
 * trang luôn hiện đúng một danh sách, theo đúng thứ tự field_weight mà admin sắp
 * trong Drupal. Không có chỗ nào phải chép tay tiêu đề hay đường dẫn nữa.
 *
 * Bỏ qua mục không có link: khối trang chủ vẫn vẽ được thẻ không bấm được (thẻ chỉ
 * có ảnh + tiêu đề), nhưng một dòng menu không đi đâu cả thì chỉ làm người dùng bấm
 * hụt. Khối nào không còn mục nào có link -> giữ nguyên submenu tĩnh của mục đó.
 */
export function mainNavWithBlocks(blocks?: HomeBlockNav | null): NavItem[] {
  const expertise = toNavLinks(blocks?.expertise)
  const services = toNavLinks(blocks?.services)

  // "Tra cứu chất chuẩn" nằm ở khối home_block chứ không phải bundle service, nên
  // nối thêm vào cuối — đúng vị trí nó vẫn đứng trong menu tĩnh.
  if (services.length > 0 && blocks?.standards?.url) {
    services.push({ label: blocks.standards.label || 'Tra cứu chất chuẩn', to: blocks.standards.url })
  }

  return mainNav.map((item) => {
    if (item.label === EXPERTISE_LABEL && expertise.length > 0) {
      return { ...item, children: expertise }
    }
    if (item.label === SERVICE_LABEL && services.length > 0) {
      return { ...item, children: services }
    }

    return item
  })
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
