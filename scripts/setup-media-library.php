<?php

/**
 * @file
 * Tạo danh mục + field cho Thư viện Video / Hình ảnh trên content type Tin tức.
 *
 * Chạy: ddev drush php:script scripts/setup-media-library.php
 * Chạy lại được nhiều lần (idempotent).
 */

declare(strict_types=1);

use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\link\LinkItemInterface;
use Drupal\taxonomy\Entity\Term;

$entityTypeManager = \Drupal::entityTypeManager();
$termStorage = $entityTypeManager->getStorage('taxonomy_term');

// --- 1. Danh mục ----------------------------------------------------------
// Tên danh mục là hợp đồng giữa backend và frontend (xem NewsPresenter::MEDIA_*).
$categories = [
  'Videos' => 6,
  'Hình ảnh' => 7,
];
foreach ($categories as $name => $weight) {
  $existing = $termStorage->loadByProperties(['vid' => 'news_category', 'name' => $name]);
  if ($existing !== []) {
    echo "Danh mục đã có: {$name}\n";
    continue;
  }
  Term::create([
    'vid' => 'news_category',
    'name' => $name,
    'weight' => $weight,
  ])->save();
  echo "Đã tạo danh mục: {$name}\n";
}

// --- 2. Field -------------------------------------------------------------
$definitions = [
  'field_youtube_urls' => [
    'type' => 'link',
    'label' => 'Link YouTube',
    'description' => 'Dán link YouTube. Hỗ trợ dạng watch?v=…, youtu.be/… và shorts/…. Thêm nhiều link bằng nút "Add another item"; thứ tự hiển thị đúng như thứ tự ở đây.',
    'settings' => [
      // Chỉ nhận URL ngoài — field này để dán link YouTube, không phải link nội bộ.
      'link_type' => LinkItemInterface::LINK_EXTERNAL,
      'title' => DRUPAL_DISABLED,
    ],
    'storage_settings' => [],
    'widget' => ['type' => 'link_default', 'settings' => ['placeholder_url' => 'https://www.youtube.com/watch?v=…']],
    'weight' => 6,
  ],
  'field_videos' => [
    'type' => 'file',
    'label' => 'Video tải lên',
    'description' => 'Định dạng cho phép: mp4, webm. Phát trực tiếp trên web bằng trình phát HTML5.',
    'settings' => [
      'file_extensions' => 'mp4 webm',
      'file_directory' => 'videos/[date:custom:Y]-[date:custom:m]',
      'max_filesize' => '',
      'description_field' => TRUE,
    ],
    'storage_settings' => ['target_type' => 'file', 'uri_scheme' => 'public'],
    'widget' => ['type' => 'file_generic', 'settings' => ['progress_indicator' => 'throbber']],
    'weight' => 7,
  ],
  'field_gallery_images' => [
    'type' => 'image',
    'label' => 'Ảnh thư viện',
    'description' => 'Định dạng cho phép: jpg, jpeg, png, webp. Kéo thả để đổi thứ tự — frontend hiển thị đúng thứ tự này.',
    'settings' => [
      'file_extensions' => 'jpg jpeg png webp',
      'file_directory' => 'gallery/[date:custom:Y]-[date:custom:m]',
      'max_filesize' => '',
      'alt_field' => TRUE,
      'alt_field_required' => FALSE,
      'title_field' => FALSE,
    ],
    // default_image phải đủ khoá, thiếu là image module cảnh báo "Undefined array key".
    'storage_settings' => [
      'target_type' => 'file',
      'uri_scheme' => 'public',
      'default_image' => [
        'uuid' => NULL,
        'alt' => '',
        'title' => '',
        'width' => NULL,
        'height' => NULL,
      ],
    ],
    'widget' => ['type' => 'image_image', 'settings' => ['progress_indicator' => 'throbber', 'preview_image_style' => 'thumbnail']],
    'weight' => 8,
  ],
];

foreach ($definitions as $fieldName => $definition) {
  $storage = FieldStorageConfig::loadByName('node', $fieldName);
  if (!$storage) {
    $storage = FieldStorageConfig::create([
      'field_name' => $fieldName,
      'entity_type' => 'node',
      'type' => $definition['type'],
      // Multi-value không giới hạn; Drupal giữ nguyên delta nên thứ tự admin nhập
      // chính là thứ tự trả ra API.
      'cardinality' => FieldStorageConfig::CARDINALITY_UNLIMITED,
      'settings' => $definition['storage_settings'],
    ]);
    $storage->save();
    echo "Đã tạo storage: {$fieldName}\n";
  }
  else {
    echo "Storage đã có: {$fieldName}\n";
  }

  $field = FieldConfig::loadByName('node', 'news', $fieldName);
  if (!$field) {
    FieldConfig::create([
      'field_storage' => $storage,
      'bundle' => 'news',
      'label' => $definition['label'],
      'description' => $definition['description'],
      'required' => FALSE,
      'translatable' => FALSE,
      'settings' => $definition['settings'],
    ])->save();
    echo "Đã tạo field: news.{$fieldName}\n";
  }
  else {
    echo "Field đã có: news.{$fieldName}\n";
  }
}

$displayRepository = \Drupal::service('entity_display.repository');
$formDisplay = $displayRepository->getFormDisplay('node', 'news', 'default');
$viewDisplay = $displayRepository->getViewDisplay('node', 'news', 'default');

foreach ($definitions as $fieldName => $definition) {
  $formDisplay->setComponent($fieldName, [
    'type' => $definition['widget']['type'],
    'weight' => $definition['weight'],
    'region' => 'content',
    'settings' => $definition['widget']['settings'],
    'third_party_settings' => [],
  ]);
  // Nuxt tự render thư viện media; Drupal không hiển thị field này ở view mode.
  $viewDisplay->removeComponent($fieldName);
}
$formDisplay->save();
$viewDisplay->save();
echo "Đã cập nhật form display + view display.\n";
