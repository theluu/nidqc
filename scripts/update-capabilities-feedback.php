<?php

/**
 * @file
 * Cập nhật khối "Danh mục năng lực" ở trang chủ theo feedback khách 21/08/2026.
 *
 * Khách chốt đúng 6 mục, viết hoa toàn bộ. Tiêu đề node vẫn lưu chữ THƯỜNG —
 * phần viết hoa do CSS (.nidqc-capability__label { text-transform: uppercase })
 * lo, giống cách làm ở khối Dịch vụ và Hoạt động chuyên môn. Lưu hoa cứng trong
 * DB thì lỡ sau này dùng lại tiêu đề ở chỗ khác (menu, breadcrumb, thẻ meta) là
 * ra chữ hoa sai chính tả tiếng Việt.
 *
 * Script chạy được NHIỀU LẦN: tra theo field_weight nên chạy lại không sinh ra
 * node trùng. Nội dung sống ở DB từng môi trường (không đi qua git) nên phải chạy
 * riêng trên dev và trên prod.
 *
 * Dùng: drush php:script scripts/update-capabilities-feedback.php
 */

use Drupal\node\Entity\Node;
use Drupal\node\NodeInterface;

// Thứ tự và câu chữ đúng như khách liệt kê trong feedback.docm.
$wanted = [
  0 => ['Năng lực kiểm nghiệm', 'Danh mục các phép thử, chỉ tiêu kiểm nghiệm thuốc, nguyên liệu làm thuốc và mỹ phẩm mà Viện thực hiện.'],
  1 => ['Năng lực hiệu chuẩn', 'Danh mục thiết bị và phạm vi hiệu chuẩn Viện cung cấp cho các đơn vị trong ngành.'],
  2 => ['Danh mục các khóa đào tạo và tư vấn', 'Các khoá đào tạo chuyên môn và dịch vụ tư vấn kỹ thuật do Viện tổ chức.'],
  3 => ['Năng lực đánh giá tương đương sinh học', 'Năng lực và phạm vi đánh giá tương đương sinh học của Viện.'],
  4 => ['Danh mục các chương trình thử nghiệm thành thạo', 'Danh mục các chương trình thử nghiệm thành thạo Viện tổ chức hằng năm.'],
  // Mục MỚI khách yêu cầu bổ sung. Mô tả dưới đây là bản nháp — cần biên tập viên
  // duyệt lại cho đúng phạm vi chứng nhận Viện đang cấp.
  5 => ['Danh mục chứng nhận hệ thống quản lý', 'Danh mục các chứng nhận hệ thống quản lý Viện thực hiện đánh giá và cấp.'],
];

$storage = \Drupal::entityTypeManager()->getStorage('node');
$existing = $storage->loadByProperties(['type' => 'capability']);

// Tra theo field_weight: tiêu đề chính là thứ script này đổi, nên không dùng làm
// khoá tra được.
$byWeight = [];
foreach ($existing as $node) {
  assert($node instanceof NodeInterface);
  $byWeight[(int) $node->get('field_weight')->value] = $node;
}

foreach ($wanted as $weight => [$title, $description]) {
  $node = $byWeight[$weight] ?? NULL;

  if ($node === NULL) {
    $node = Node::create([
      'type' => 'capability',
      'title' => $title,
      'field_weight' => $weight,
      'status' => 1,
    ]);
    $action = 'TẠO MỚI';
  }
  else {
    $action = $node->label() === $title ? 'giữ nguyên' : 'đổi tên';
    $node->setTitle($title);
  }

  $node->set('field_description', ['value' => $description, 'format' => 'plain_text']);
  $node->setPublished();
  $node->save();

  printf("  %-10s | w=%d | %s\n", $action, $weight, $title);
}

// Mục thừa (nếu có) thì báo chứ KHÔNG tự xoá — nội dung của khách, không tự quyết.
foreach ($byWeight as $weight => $node) {
  if (!isset($wanted[$weight])) {
    printf("  ⚠ THỪA     | w=%d | nid %d \"%s\" — kiểm tra rồi gỡ tay nếu không dùng\n", $weight, $node->id(), $node->label());
  }
}

\Drupal::service('cache_tags.invalidator')->invalidateTags(['node_list:capability']);
print "Xong.\n";
