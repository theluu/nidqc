// Pre-warm map alias->nid ngay khi Nitro khởi động (chạy nền, không chặn startup)
// -> user đầu tiên mở node cũng không phải chờ build map. Sau lần này SWR tự làm mới.
export default defineNitroPlugin(() => {
  // Không await: build ở nền. Lỗi (Drupal chưa sẵn sàng) thì bỏ qua, request sẽ tự build.
  getNewsAliasMap().catch(() => {})
})
