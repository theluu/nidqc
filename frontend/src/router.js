import { createRouter, createWebHashHistory } from 'vue-router';

// Trang chủ convert xong. Các trang khác: placeholder tới khi convert.
const Placeholder = () => import('./pages/PlaceholderPage.vue');

const routes = [
  { path: '/', component: () => import('./pages/HomePage.vue') },
  { path: '/tin-tuc', component: Placeholder, meta: { title: 'Tin tức & Thông báo' } },
  { path: '/tin-tuc/:id', component: Placeholder, meta: { title: 'Chi tiết tin' } },
  { path: '/van-ban-tai-lieu', component: Placeholder, meta: { title: 'Văn bản - Tài liệu' } },
  { path: '/faq', component: Placeholder, meta: { title: 'Câu hỏi thường gặp' } },
  { path: '/gioi-thieu-chung', component: Placeholder, meta: { title: 'Giới thiệu chung' } },
  { path: '/chinh-sach-chat-luong', component: Placeholder, meta: { title: 'Chính sách chất lượng' } },
  { path: '/nang-luc', component: Placeholder, meta: { title: 'Năng lực' } },
  { path: '/co-cau-to-chuc', component: Placeholder, meta: { title: 'Cơ cấu tổ chức' } },
  { path: '/dao-tao-nckh', component: Placeholder, meta: { title: 'Đào tạo & NCKH' } },
  { path: '/lien-he', component: Placeholder, meta: { title: 'Liên hệ & hỗ trợ' } },
];

export default createRouter({
  history: createWebHashHistory('/app/'),
  routes,
  scrollBehavior(to) {
    if (to.hash) return { el: to.hash, behavior: 'smooth' };
    return { top: 0 };
  },
});
