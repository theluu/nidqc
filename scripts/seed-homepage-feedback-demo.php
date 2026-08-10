<?php
/**
 * @file
 * Ảnh demo cho bố cục trang chủ mới (feedback 08/2026).
 *
 * Bố cục mới hiển thị Dịch vụ và Hoạt động chuyên môn dạng "title + ảnh", cộng hai
 * dải banner quảng cáo và cột liên kết nổi bật. Chưa có ảnh thật thì các khối này
 * hoặc trống hoặc toàn ô xám — không kiểm chứng được layout. Script gán tạm ảnh
 * đang có sẵn trong thư viện (ảnh tin cũ) để nhìn thấy bố cục thật.
 *
 * ⚠️ Đây là DỮ LIỆU DEMO. Trên production, biên tập viên thay ảnh tại
 * /admin/content. Script KHÔNG ghi đè node đã có ảnh nên chạy lại vô hại.
 *
 * Usage:
 *   ddev drush php:script scripts/seed-homepage-feedback-demo.php
 */

declare(strict_types=1);

use Drupal\node\Entity\Node;
use Drupal\node\NodeInterface;

$out = static function (string $msg): void {
  print $msg . PHP_EOL;
};

$nodeStorage = \Drupal::entityTypeManager()->getStorage('node');
$fileStorage = \Drupal::entityTypeManager()->getStorage('file');

// Lấy ảnh ĐẠI DIỆN của tin tức (bảng node__field_image có sẵn width/height) thay vì
// quét file_managed: cần ảnh NGANG, đủ lớn để không vỡ khi phủ kín thẻ, và là ảnh
// chụp hoạt động chứ không phải bản scan thông báo — thư viện của Viện có hàng trăm
// file "thông báo mời báo giá", lấy nhầm là cả trang chủ toàn giấy A4 vàng.
$query = \Drupal::database()->select('node__field_image', 'i');
$query->join('file_managed', 'f', 'f.fid = i.field_image_target_id');
$query->fields('i', ['field_image_target_id']);
$query->condition('i.field_image_width', 800, '>=');
$query->where('i.field_image_width > i.field_image_height');
$query->condition('f.status', 1);
$query->condition('f.filesize', 1500000, '<');
foreach (['thong-bao', 'bao-gia', 'cong-khai', 'thu-chuc', 'thu-cam-on', 'quyet-dinh'] as $noise) {
  $query->condition('f.uri', '%' . $query->escapeLike($noise) . '%', 'NOT LIKE');
}
$query->distinct();
$query->orderBy('i.field_image_target_id');
$query->range(0, 40);
$fids = $query->execute()->fetchCol();

if (count($fids) < 10) {
  $out('✖ Không đủ ảnh trong thư viện để seed demo. Bỏ qua.');
  return;
}

$cursor = 0;
/**
 * Trả fid tiếp theo, quay vòng khi hết danh sách.
 */
$nextFid = static function () use ($fids, &$cursor): int {
  $fid = $fids[$cursor % count($fids)];
  $cursor++;
  return (int) $fid;
};

/**
 * Gán ảnh cho node nếu node đó chưa có ảnh.
 */
$setImage = static function (NodeInterface $node, int $fid, string $alt) use ($out): void {
  if (!$node->hasField('field_image') || !$node->get('field_image')->isEmpty()) {
    return;
  }
  $node->set('field_image', ['target_id' => $fid, 'alt' => $alt]);
  $node->save();
  $out('✔ Đã gán ảnh cho ' . $node->bundle() . ' "' . $node->label() . '"');
};

// 1) Ảnh cho Dịch vụ và Hoạt động chuyên môn.
foreach (['service', 'expertise'] as $bundle) {
  foreach ($nodeStorage->loadByProperties(['type' => $bundle]) as $node) {
    assert($node instanceof NodeInterface);
    $setImage($node, $nextFid(), (string) $node->label());
  }
}

// 2) Banner quảng cáo + liên kết nổi bật.
//    Ảnh banner dùng lại ảnh tin: chỉ để nhìn thấy nhịp slideshow, không phải
//    thiết kế thật.
$banners = [
  ['Chương trình thử nghiệm thành thạo 2026', 'ads_1', 0, 'internal:/danh-muc-nang-luc/cac-chuong-trinh-thu-nghiem-thanh-thao-tntt'],
  ['Dịch vụ đánh giá tương đương sinh học', 'ads_1', 1, 'internal:/dich-vu/danh-gia-tuong-duong-sinh-hoc-tdsh'],
  ['Cung ứng chất chuẩn - chất đối chiếu', 'ads_1', 2, 'https://nidqc.gov.vn/tim-kiem-chat-chuan'],
  ['Đào tạo và tư vấn kỹ thuật cho hệ thống kiểm nghiệm', 'ads_2', 0, 'internal:/dich-vu/dao-tao-va-tu-van-ky-thuat'],
  ['Hội nghị khoa học kỹ thuật thường niên', 'ads_2', 1, 'internal:/tin-tuc?cat=hoi-nghi-hoi-thao'],
  ['Tạp chí Kiểm nghiệm Dược và Mỹ phẩm', 'sidebar', 0, 'internal:/hoat-dong-chuyen-mon/tap-chi-kiem-nghiem-duoc-va-my-pham'],
  ['Hoạt động NRA', 'sidebar', 1, 'internal:/hoat-dong-chuyen-mon/hoat-dong-nra'],
  ['Hợp tác quốc tế', 'sidebar', 2, 'internal:/hoat-dong-chuyen-mon/hop-tac-quoc-te'],
  ['Chỉ đạo tuyến', 'sidebar', 3, 'internal:/hoat-dong-chuyen-mon/chi-dao-tuyen'],
];
foreach ($banners as [$title, $position, $weight, $uri]) {
  if ($nodeStorage->loadByProperties(['type' => 'banner', 'title' => $title]) !== []) {
    $out("• Banner '$title' đã có");
    continue;
  }
  Node::create([
    'type' => 'banner',
    'title' => $title,
    'status' => 1,
    'field_position' => $position,
    'field_weight' => $weight,
    'field_image' => ['target_id' => $nextFid(), 'alt' => $title],
    'field_link' => ['uri' => $uri, 'title' => ''],
  ])->save();
  $out("✔ Đã tạo banner '$title' ($position)");
}

// Ảnh vừa gán phải được đánh dấu đang dùng, nếu không cron dọn file sẽ xoá.
$fileStorage->resetCache();

$out('Hoàn tất (dữ liệu demo).');
