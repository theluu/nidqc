<?php

/**
 * @file
 * Tick "Tin nổi bật" cho 5 tin mới nhất thuộc chuyên mục sự kiện VÀ có ảnh đại diện.
 *
 * Khối hero trang chủ hiển thị ảnh lớn + 5 thumbnail, nên tin không có ảnh sẽ để
 * lại ô xám. Vì vậy chỉ chọn tin có field_image.
 *
 * Chạy: ddev drush php:script scripts/seed-featured-news.php
 */

declare(strict_types=1);

const FEATURED_LIMIT = 5;
const EVENT_CATEGORIES = ['Tin hoạt động', 'Hội nghị - Hội thảo'];

$entityTypeManager = \Drupal::entityTypeManager();
$nodeStorage = $entityTypeManager->getStorage('node');

$termIds = [];
foreach ($entityTypeManager->getStorage('taxonomy_term')->loadByProperties(['vid' => 'news_category']) as $term) {
  if (in_array($term->label(), EVENT_CATEGORIES, TRUE)) {
    $termIds[] = $term->id();
  }
}
if ($termIds === []) {
  echo "Không tìm thấy chuyên mục sự kiện nào — dừng.\n";
  return;
}

$ids = $nodeStorage->getQuery()
  ->accessCheck(FALSE)
  ->condition('type', 'news')
  ->condition('status', 1)
  ->condition('field_category.target_id', $termIds, 'IN')
  ->condition('field_image', NULL, 'IS NOT NULL')
  ->sort('created', 'DESC')
  ->range(0, FEATURED_LIMIT)
  ->execute();

if ($ids === []) {
  echo "Không có tin sự kiện nào kèm ảnh — dừng.\n";
  return;
}

// Bỏ tick các tin nổi bật cũ để trang chủ luôn đúng 5 tin của lần seed này.
$stale = $nodeStorage->getQuery()
  ->accessCheck(FALSE)
  ->condition('type', 'news')
  ->condition('field_featured', 1)
  ->condition('nid', $ids, 'NOT IN')
  ->execute();
foreach ($nodeStorage->loadMultiple($stale) as $node) {
  $node->set('field_featured', 0);
  $node->setNewRevision(FALSE);
  $node->save();
  echo "Bỏ nổi bật: {$node->label()}\n";
}

foreach ($nodeStorage->loadMultiple($ids) as $node) {
  $node->set('field_featured', 1);
  // Không tạo revision mới: đây là seed dữ liệu demo, không phải thao tác biên tập.
  $node->setNewRevision(FALSE);
  $node->save();
  echo "Nổi bật: [{$node->id()}] {$node->label()}\n";
}

echo 'Xong — ' . count($ids) . " tin được đánh dấu nổi bật.\n";
