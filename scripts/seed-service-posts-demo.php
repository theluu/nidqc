<?php
/**
 * @file
 * Seed DEMO: 2 bài viết cho mỗi danh mục dịch vụ (6 dịch vụ = 12 bài).
 *
 * Chỉ để UAT nhìn thấy danh sách /dich-vu/<danh-muc> có nội dung. Nội dung là
 * văn bản mẫu, biên tập viên sẽ thay bằng bài thật.
 *
 * Idempotent: nhận diện bài đã seed qua tiêu đề, chạy lại không nhân đôi.
 *
 * Usage:
 *   ddev drush php:script scripts/seed-service-posts-demo.php
 *   ddev drush php:script scripts/seed-service-posts-demo.php -- --delete
 */
declare(strict_types=1);

use Drupal\node\Entity\Node;

$out = static function (string $m): void { print $m . PHP_EOL; };

$args = $extra ?? [];
$delete = in_array('--delete', $args, TRUE);

$entityTypeManager = \Drupal::entityTypeManager();
$nodeStorage = $entityTypeManager->getStorage('node');
$termStorage = $entityTypeManager->getStorage('taxonomy_term');

$terms = $termStorage->loadByProperties(['vid' => 'service_category']);
if ($terms === []) {
  $out('! Chưa có danh mục dịch vụ — chạy scripts/setup-service-posts.php trước.');
  return;
}
usort($terms, static fn ($a, $b) => $a->getWeight() <=> $b->getWeight());

/**
 * Hai bài demo cho mỗi dịch vụ: [tiêu đề, đoạn mở đầu, các ý chính].
 *
 * Viết theo giọng thông báo của Viện chứ không phải lorem ipsum: UAT phải đánh giá
 * được bố cục danh sách với độ dài tiêu đề và mô tả thật.
 */
$templates = [
  'Phân tích - Kiểm nghiệm' => [
    [
      'Quy trình tiếp nhận mẫu phân tích - kiểm nghiệm',
      'Viện Kiểm nghiệm thuốc Trung ương tiếp nhận mẫu kiểm nghiệm thuốc, nguyên liệu làm thuốc và mỹ phẩm từ các đơn vị trong và ngoài ngành y tế.',
      [
        'Hồ sơ tiếp nhận gồm phiếu yêu cầu kiểm nghiệm, mẫu lưu và tài liệu kỹ thuật kèm theo.',
        'Mẫu được mã hoá ngay khi tiếp nhận để bảo đảm tính khách quan của kết quả.',
        'Thời gian trả kết quả phụ thuộc chỉ tiêu đăng ký, thông báo cụ thể trên phiếu tiếp nhận.',
      ],
    ],
    [
      'Danh mục chỉ tiêu phân tích được công nhận ISO/IEC 17025',
      'Phòng thí nghiệm của Viện duy trì hệ thống quản lý chất lượng theo ISO/IEC 17025 cho các phép thử hoá lý, vi sinh và sinh học.',
      [
        'Định lượng hoạt chất bằng HPLC, UPLC, GC và quang phổ.',
        'Thử độ hoà tan, độ đồng đều hàm lượng, tạp chất liên quan.',
        'Thử vô khuẩn, giới hạn nhiễm khuẩn và nội độc tố vi khuẩn.',
      ],
    ],
  ],
  'Đánh giá tương đương sinh học (TĐSH)' => [
    [
      'Hướng dẫn nộp hồ sơ đánh giá tương đương sinh học',
      'Trung tâm Đánh giá tương đương sinh học tiếp nhận hồ sơ nghiên cứu TĐSH của thuốc generic theo quy định hiện hành của Bộ Y tế.',
      [
        'Hồ sơ gồm đề cương nghiên cứu, hồ sơ sản phẩm và tài liệu về thuốc đối chứng.',
        'Đề cương được Hội đồng đạo đức thẩm định trước khi triển khai trên người tình nguyện.',
        'Kết quả nghiên cứu được báo cáo theo mẫu thống nhất, kèm dữ liệu gốc.',
      ],
    ],
    [
      'Năng lực nghiên cứu tương đương sinh học của Viện',
      'Viện đã thực hiện nhiều nghiên cứu TĐSH cho các nhóm thuốc tim mạch, tiểu đường, kháng sinh và thần kinh.',
      [
        'Khu vực lưu trú người tình nguyện đạt chuẩn thực hành lâm sàng tốt (GCP).',
        'Phòng phân tích dịch sinh học trang bị LC-MS/MS độ nhạy cao.',
        'Xử lý số liệu dược động học bằng phần mềm chuyên dụng đã được thẩm định.',
      ],
    ],
  ],
  'Đào tạo và tư vấn kỹ thuật' => [
    [
      'Kế hoạch các khoá đào tạo kiểm nghiệm năm 2026',
      'Viện tổ chức các khoá đào tạo ngắn hạn về kỹ thuật kiểm nghiệm thuốc và quản lý chất lượng phòng thí nghiệm cho cán bộ tuyến tỉnh và doanh nghiệp.',
      [
        'Đào tạo kỹ thuật sắc ký lỏng hiệu năng cao ứng dụng trong kiểm nghiệm thuốc.',
        'Đào tạo thẩm định quy trình phân tích theo hướng dẫn ICH.',
        'Đào tạo xây dựng hệ thống quản lý chất lượng theo ISO/IEC 17025.',
      ],
    ],
    [
      'Tư vấn xây dựng phòng kiểm nghiệm đạt GLP',
      'Viện nhận tư vấn thiết lập và nâng cấp phòng kiểm nghiệm cho các cơ sở sản xuất, kinh doanh dược phẩm.',
      [
        'Khảo sát hiện trạng và tư vấn bố trí mặt bằng, trang thiết bị.',
        'Xây dựng hệ thống hồ sơ, quy trình thao tác chuẩn (SOP).',
        'Đào tạo nhân sự và hỗ trợ chuẩn bị đánh giá của cơ quan quản lý.',
      ],
    ],
  ],
  'Hiệu chuẩn' => [
    [
      'Dịch vụ hiệu chuẩn thiết bị đo lường phòng thí nghiệm',
      'Phòng Hiệu chuẩn của Viện cung cấp dịch vụ hiệu chuẩn thiết bị đo lường cho các phòng kiểm nghiệm dược phẩm trên toàn quốc.',
      [
        'Hiệu chuẩn cân phân tích, cân kỹ thuật và quả cân chuẩn.',
        'Hiệu chuẩn thiết bị đo nhiệt độ, độ ẩm, tủ sấy, tủ ấm, nồi hấp.',
        'Hiệu chuẩn pipet, buret và các dụng cụ đo thể tích.',
      ],
    ],
    [
      'Thủ tục đăng ký hiệu chuẩn và thời gian trả kết quả',
      'Đơn vị có nhu cầu hiệu chuẩn gửi phiếu đăng ký kèm danh mục thiết bị về Viện để được xếp lịch.',
      [
        'Hiệu chuẩn tại phòng thí nghiệm của Viện hoặc tại cơ sở theo yêu cầu.',
        'Giấy chứng nhận hiệu chuẩn cấp trong vòng 07 ngày làm việc sau khi hoàn thành.',
        'Viện thông báo trước thời điểm hiệu chuẩn định kỳ tiếp theo của từng thiết bị.',
      ],
    ],
  ],
  'Nghiên cứu - Chuyển giao' => [
    [
      'Nghiên cứu thiết lập chất chuẩn phục vụ kiểm nghiệm thuốc',
      'Viện triển khai các đề tài thiết lập chất chuẩn, chất đối chiếu dùng trong kiểm nghiệm thuốc và mỹ phẩm.',
      [
        'Xây dựng quy trình thiết lập và đánh giá độ đồng nhất, độ ổn định.',
        'Chứng nhận giá trị sử dụng theo hướng dẫn của Dược điển Việt Nam.',
        'Chất chuẩn sau nghiên cứu được cung ứng cho hệ thống kiểm nghiệm toàn quốc.',
      ],
    ],
    [
      'Chuyển giao phương pháp phân tích cho đơn vị tuyến dưới',
      'Viện chuyển giao các phương pháp phân tích đã được thẩm định cho trung tâm kiểm nghiệm tỉnh, thành phố.',
      [
        'Chuyển giao trọn gói: quy trình, điều kiện thiết bị và tài liệu thẩm định.',
        'Đào tạo trực tiếp tại phòng thí nghiệm của đơn vị nhận chuyển giao.',
        'Đánh giá kết quả chuyển giao bằng mẫu kiểm tra chéo.',
      ],
    ],
  ],
  'Thử nghiệm thành thạo' => [
    [
      'Chương trình thử nghiệm thành thạo năm 2026',
      'Viện tổ chức các chương trình thử nghiệm thành thạo giúp phòng kiểm nghiệm tự đánh giá năng lực và duy trì công nhận.',
      [
        'Chương trình định lượng hoạt chất trong chế phẩm bằng HPLC.',
        'Chương trình xác định giới hạn nhiễm khuẩn trong dược phẩm.',
        'Chương trình thử độ hoà tan của viên nén.',
      ],
    ],
    [
      'Hướng dẫn đăng ký tham gia thử nghiệm thành thạo',
      'Các đơn vị đăng ký tham gia theo thông báo chương trình được Viện phát hành đầu mỗi năm.',
      [
        'Gửi phiếu đăng ký trước thời hạn ghi trong thông báo chương trình.',
        'Mẫu thử nghiệm được gửi tới đơn vị kèm hướng dẫn xử lý và báo cáo kết quả.',
        'Báo cáo đánh giá theo điểm z-score được gửi lại sau khi tổng hợp toàn quốc.',
      ],
    ],
  ],
];

// Xoá bài demo đã seed (theo tiêu đề trong $templates) — dọn trước khi bàn giao.
if ($delete) {
  $titles = [];
  foreach ($templates as $rows) {
    foreach ($rows as [$title]) {
      $titles[] = $title;
    }
  }
  $ids = $nodeStorage->getQuery()
    ->accessCheck(FALSE)
    ->condition('type', 'service_post')
    ->condition('title', $titles, 'IN')
    ->execute();
  if ($ids !== []) {
    $nodeStorage->delete($nodeStorage->loadMultiple($ids));
  }
  $out('✔ đã xoá ' . count($ids) . ' bài demo');
  return;
}

$created = 0;
$skipped = 0;
// Ngày tạo giãn ra để danh sách sắp theo created DESC trông tự nhiên, không phải
// 12 bài cùng một mốc thời gian.
$stamp = \Drupal::time()->getRequestTime() - 86400;

foreach ($terms as $term) {
  $rows = $templates[$term->label()] ?? NULL;
  if ($rows === NULL) {
    $out("• bỏ qua «{$term->label()}» — chưa có nội dung demo");
    continue;
  }

  foreach ($rows as [$title, $lead, $points]) {
    $existing = $nodeStorage->getQuery()
      ->accessCheck(FALSE)
      ->condition('type', 'service_post')
      ->condition('title', $title)
      ->range(0, 1)
      ->execute();
    if ($existing !== []) {
      $out("• «{$title}» đã có");
      $skipped++;
      continue;
    }

    $body = '<p>' . htmlspecialchars($lead, ENT_QUOTES, 'UTF-8') . '</p><ul>';
    foreach ($points as $point) {
      $body .= '<li>' . htmlspecialchars($point, ENT_QUOTES, 'UTF-8') . '</li>';
    }
    $body .= '</ul><p>Chi tiết xin liên hệ Phòng Kế hoạch tổng hợp — Viện Kiểm nghiệm thuốc Trung ương.</p>';

    Node::create([
      'type' => 'service_post',
      'title' => $title,
      'status' => 1,
      'created' => $stamp,
      'field_service_category' => ['target_id' => $term->id()],
      'body' => ['value' => $body, 'format' => 'basic_html', 'summary' => $lead],
    ])->save();

    $out("✔ «{$title}»");
    $created++;
    $stamp -= 43200;
  }
}

$out('');
$out("Xong: tạo mới $created bài, bỏ qua $skipped bài đã có.");
