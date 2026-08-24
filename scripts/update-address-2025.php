<?php
/**
 * Cập nhật địa chỉ sau sắp xếp đơn vị hành chính từ 01/7/2025
 * (theo thông báo chính thức của Viện, node 336):
 *   Cơ sở 1: 48 Hai Bà Trưng, phường Tràng Tiền, Hoàn Kiếm -> phường Cửa Nam
 *   Cơ sở 2: xã Tam Hiệp, huyện Thanh Trì            -> phường Hoàng Liệt
 * Chạy: ddev drush php:script scripts/update-address-2025.php
 */

use Drupal\node\Entity\Node;

$storage = \Drupal::entityTypeManager()->getStorage('node');

// 1. Content type "Cơ sở" -> footer + trang /lien-he lấy địa chỉ từ đây.
$addresses = [
  'Cơ sở 1' => '48 Hai Bà Trưng, phường Cửa Nam, Hà Nội',
  'Cơ sở 2' => 'Phường Hoàng Liệt, Tp. Hà Nội',
];
foreach ($storage->loadByProperties(['type' => 'office']) as $node) {
  $title = $node->label();
  if (!isset($addresses[$title])) {
    printf("BỎ QUA office: %s (nid %d)\n", $title, $node->id());
    continue;
  }
  $old = $node->get('field_address')->value;
  $new = $addresses[$title];
  if ($old === $new) {
    printf("GIỮ NGUYÊN %s: %s\n", $title, $old);
    continue;
  }
  $node->set('field_address', $new);
  $node->setNewRevision(TRUE);
  $node->setRevisionLogMessage('Cập nhật địa chỉ sau sắp xếp đơn vị hành chính 01/7/2025');
  $node->save();
  printf("CẬP NHẬT %s (nid %d):\n  cũ: %s\n  mới: %s\n", $title, $node->id(), $old, $new);
}

// 2. Trang tĩnh "Liên hệ" (nid 53) có địa chỉ viết cứng trong body.
$replacements = [
  '48 Hai Bà Trưng, Hoàn Kiếm, Hà Nội' => '48 Hai Bà Trưng, phường Cửa Nam, Hà Nội',
];
foreach ($storage->loadByProperties(['type' => 'page']) as $node) {
  if (!$node->hasField('body') || $node->get('body')->isEmpty()) {
    continue;
  }
  $body = $node->get('body')->value;
  $new_body = strtr($body, $replacements);
  if ($new_body === $body) {
    continue;
  }
  $node->set('body', [
    'value' => $new_body,
    'format' => $node->get('body')->format,
    'summary' => $node->get('body')->summary,
  ]);
  $node->setNewRevision(TRUE);
  $node->setRevisionLogMessage('Cập nhật địa chỉ sau sắp xếp đơn vị hành chính 01/7/2025');
  $node->save();
  printf("CẬP NHẬT trang \"%s\" (nid %d)\n", $node->label(), $node->id());
}
