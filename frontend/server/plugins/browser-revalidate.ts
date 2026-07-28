// Buộc TRÌNH DUYỆT luôn revalidate, trong khi vẫn giữ cache SWR phía server.
//
// Vì sao cần: route rules `swr` làm Nitro gắn `cache-control: s-maxage=600,
// stale-while-revalidate`. `s-maxage` CHỈ áp cho shared cache (CDN/proxy), còn
// trình duyệt thấy response không có `max-age` lẫn `no-cache` nhưng có `ETag` +
// `Last-Modified` nên rơi vào *heuristic caching*: nó tự đặt hạn tươi bằng ~10%
// tuổi tài liệu và phục vụ bản cũ mà KHÔNG hỏi lại server.
//
// Hậu quả thật đã gặp: trong lúc deploy có vài giây web/core bị xoá và server trả
// 404; trình duyệt cache đúng phản hồi đó rồi hiện 404 cho trang chủ, trong khi
// access log không ghi nhận request nào. Purge cũng không cứu được vì cache nằm
// trong máy người dùng.
//
// `max-age=0, must-revalidate` giữ nguyên ETag/304 nên vẫn tiết kiệm băng thông:
// trình duyệt hỏi lại mỗi lần nhưng thường chỉ nhận 304 rỗng.
import { getResponseHeader, setResponseHeader } from 'h3'

export default defineNitroPlugin((nitroApp) => {
  nitroApp.hooks.hook('beforeResponse', (event) => {
    // Response lỗi TUYỆT ĐỐI không được cache ở đâu cả. Đã có sự cố thật: một lần
    // render hỏng trả về trang lỗi, trình duyệt cache lại, rồi những lần sau
    // revalidate vẫn nhận 304 (khớp theo Last-Modified) nên cứ hiện trang lỗi mãi
    // dù server đã lành — access log không hề ghi nhận lỗi nào.
    const status = event.node.res.statusCode
    if (status >= 400) {
      setResponseHeader(event, 'cache-control', 'no-store, must-revalidate')
      return
    }

    const current = String(getResponseHeader(event, 'cache-control') || '')
    // Chỉ đụng vào response do route cache SWR sinh ra. Asset trong /_nuxt/ dùng
    // `max-age=31536000, immutable` và PHẢI giữ nguyên — tên file có hash rồi.
    if (!current.includes('s-maxage')) {
      return
    }

    // THAY hẳn, không nối thêm. Trước đây header giữ nguyên phần Nitro sinh ra và chỉ
    // thêm tiền tố, thành `max-age=0, must-revalidate, s-maxage=600,
    // stale-while-revalidate` — vẫn còn hai thứ gây hại:
    //
    //  - `stale-while-revalidate` KHÔNG có tham số. RFC 5861 bắt buộc delta-seconds;
    //    chỉ thị cụt là không hợp lệ nên mỗi nơi hiểu một kiểu, có nơi coi như cửa sổ
    //    rất dài rồi phục vụ bản cũ mà không hỏi lại server.
    //  - `s-maxage=600` cho phép cache dùng chung giữ bản cũ 10 phút, mà /__purge
    //    không với tới được — sửa bài xong vẫn hiện nội dung cũ.
    //
    // Với payloadExtraction, dữ liệu trang nằm ở /_payload.json chứ không nằm trong
    // HTML, nên một bản payload cũ bị giữ lại là biên tập viên sửa xong không thấy gì
    // đổi, còn "xem nguồn" trang thì chẳng có dữ liệu đó để mà nghi ngờ.
    //
    // Bỏ cả hai: cache SWR nội bộ của Nitro vẫn phục vụ tức thì và vẫn là nơi DUY
    // NHẤT giữ bản dựng sẵn, nên /__purge lúc lưu bài là đủ và có hiệu lực ngay.
    // ETag/304 vẫn còn nên revalidate hầu hết chỉ tốn một response rỗng.
    setResponseHeader(event, 'cache-control', 'public, max-age=0, must-revalidate')
  })
})
