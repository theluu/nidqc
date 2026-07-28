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

// Menu lấy từ design (const NAV). href trỏ route Vue. 9 mục, submenu 2 tầng.
export const mainNav: NavItem[] = [
  { label: 'Trang chủ', to: '/', children: [] },
  { label: 'Giới thiệu', to: '/gioi-thieu-chung', children: [
    { label: 'Giới thiệu chung', to: '/gioi-thieu-chung' },
    { label: 'Chính sách chất lượng', to: '/chinh-sach-chat-luong' },
    { label: 'Năng lực', to: '/nang-luc' },
    { label: 'Cơ cấu tổ chức', to: '/co-cau-to-chuc' },
  ] },
  { label: 'Hoạt động chuyên môn', to: '/#hoat-dong-chuyen-mon', children: [
    { label: 'Chỉ đạo tuyến', to: '/#hoat-dong-chuyen-mon' },
    { label: 'Kiểm nghiệm và giám sát chất lượng thuốc', to: '/#hoat-dong-chuyen-mon' },
    { label: 'Hợp tác quốc tế', to: '/#hoat-dong-chuyen-mon' },
    { label: 'Hoạt động NRA', to: '/#hoat-dong-chuyen-mon' },
    { label: 'Tạp chí Kiểm nghiệm Dược và Mỹ phẩm', to: '/#hoat-dong-chuyen-mon' },
  ] },
  { label: 'Đào tạo & NCKH', to: '/dao-tao-nckh', children: [
    { label: 'Đào tạo tiến sỹ', to: '/dao-tao-nckh' },
    { label: 'Nghiên cứu khoa học', to: '/dao-tao-nckh' },
  ] },
  { label: 'Dịch vụ', to: '/#dich-vu', children: [
    { label: 'Phân tích - Kiểm nghiệm', to: '/#dich-vu' },
    { label: 'Đánh giá tương đương sinh học (TĐSH)', to: '/#dich-vu' },
    { label: 'Đào tạo và tư vấn kỹ thuật', to: '/#dich-vu' },
    { label: 'Hiệu chuẩn', to: '/#dich-vu' },
    { label: 'Nghiên cứu - Chuyển giao', to: '/#dich-vu' },
    { label: 'Thử nghiệm thành thạo', to: '/#dich-vu' },
    { label: 'Cung ứng chất chuẩn', to: '/#chat-chuan' },
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
 * Submenu của một mục menu chính, tra theo nhãn.
 *
 * Trả mảng rỗng nếu đổi tên mục trên menu mà quên sửa nơi gọi — khối trên trang sẽ
 * trống chứ không làm vỡ trang.
 */
export function navChildren(label: string): NavLink[] {
  return mainNav.find((item) => item.label === label)?.children ?? []
}
