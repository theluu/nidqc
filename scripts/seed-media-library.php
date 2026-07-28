<?php

/**
 * @file
 * Tạo 3 bài mẫu cho Thư viện media: nhiều ảnh, nhiều link YouTube, nhiều video upload.
 *
 * Ảnh dùng lại file đã có trong site; video upload sinh file mp4 nhỏ tại chỗ để
 * không phải tải gì từ mạng.
 *
 * Chạy: ddev drush php:script scripts/seed-media-library.php
 */

declare(strict_types=1);

use Drupal\file\Entity\File;
use Drupal\node\Entity\Node;

$entityTypeManager = \Drupal::entityTypeManager();
$termStorage = $entityTypeManager->getStorage('taxonomy_term');
$nodeStorage = $entityTypeManager->getStorage('node');
$fileSystem = \Drupal::service('file_system');

$termId = static function (string $name) use ($termStorage): ?int {
  $found = $termStorage->loadByProperties(['vid' => 'news_category', 'name' => $name]);
  $term = reset($found);
  return $term ? (int) $term->id() : NULL;
};

$videoTerm = $termId('Videos');
$imageTerm = $termId('Hình ảnh');
if ($videoTerm === NULL || $imageTerm === NULL) {
  echo "Chưa có danh mục Videos / Hình ảnh — chạy setup-media-library.php trước.\n";
  return;
}

/** Xoá bài mẫu cũ để chạy lại không sinh trùng. */
$old = $nodeStorage->getQuery()
  ->accessCheck(FALSE)
  ->condition('type', 'news')
  ->condition('field_category.target_id', [$videoTerm, $imageTerm], 'IN')
  ->condition('title', 'DEMO —', 'STARTS_WITH')
  ->execute();
if ($old !== []) {
  $nodeStorage->delete($nodeStorage->loadMultiple($old));
  echo 'Đã xoá ' . count($old) . " bài demo cũ.\n";
}

// --- Ảnh: lấy lại file ảnh đang có trong site -----------------------------
$imageFiles = $entityTypeManager->getStorage('file')->getQuery()
  ->accessCheck(FALSE)
  ->condition('filemime', 'image/%', 'LIKE')
  ->condition('status', 1)
  ->range(0, 6)
  ->sort('fid', 'DESC')
  ->execute();
$imageFiles = array_values($imageFiles);
if (count($imageFiles) < 3) {
  echo "Không đủ file ảnh trong site để tạo gallery mẫu.\n";
  return;
}

$gallery = [];
foreach ($imageFiles as $i => $fid) {
  $gallery[] = ['target_id' => $fid, 'alt' => 'Ảnh thư viện demo ' . ($i + 1)];
}

$imageNode = Node::create([
  'type' => 'news',
  'title' => 'DEMO — Bộ ảnh hoạt động của Viện',
  'status' => 1,
  // Site bật content_moderation: chỉ đặt status=1 sẽ bị ép về draft.
  'moderation_state' => 'published',
  'field_category' => ['target_id' => $imageTerm],
  'field_image' => ['target_id' => $imageFiles[0]],
  'field_gallery_images' => $gallery,
  'body' => ['value' => '<p>Bộ ảnh demo cho Thư viện hình ảnh.</p>', 'format' => 'basic_html'],
]);
$imageNode->save();
echo "Đã tạo bài ảnh [{$imageNode->id()}] với " . count($gallery) . " ảnh.\n";

// --- YouTube --------------------------------------------------------------
// Ba dạng URL mà yêu cầu 3.1 bắt buộc hỗ trợ.
$youtubeUrls = [
  'https://www.youtube.com/watch?v=aqz-KE-bpKQ',
  'https://youtu.be/jNQXAC9IVRw',
  'https://www.youtube.com/shorts/tPEE9ZwTmy0',
  'https://www.youtube.com/watch?v=dQw4w9WgXcQ&t=30s',
];
$youtubeNode = Node::create([
  'type' => 'news',
  'title' => 'DEMO — Video giới thiệu trên YouTube',
  'status' => 1,
  // Site bật content_moderation: chỉ đặt status=1 sẽ bị ép về draft.
  'moderation_state' => 'published',
  'field_category' => ['target_id' => $videoTerm],
  'field_image' => ['target_id' => $imageFiles[1]],
  'field_youtube_urls' => array_map(static fn (string $u) => ['uri' => $u, 'title' => ''], $youtubeUrls),
  'body' => ['value' => '<p>Danh sách video YouTube demo.</p>', 'format' => 'basic_html'],
]);
$youtubeNode->save();
echo "Đã tạo bài YouTube [{$youtubeNode->id()}] với " . count($youtubeUrls) . " link.\n";

// --- Video upload ---------------------------------------------------------
// Dùng file mp4 THẬT trong scripts/fixtures. Trước đây script này sinh mp4 rỗng
// bằng base64 cho gọn, nhưng trình duyệt không giải mã được (media error 4) nên
// "video demo" luôn hỏng — sai lệch hẳn với thứ cần kiểm thử.
$directory = 'public://videos/demo';
$fileSystem->prepareDirectory($directory, \Drupal\Core\File\FileSystemInterface::CREATE_DIRECTORY);
$fixture = DRUPAL_ROOT . '/../scripts/fixtures/demo-video.mp4';
if (!is_file($fixture)) {
  echo "Thiếu scripts/fixtures/demo-video.mp4 — bỏ qua phần video tải lên.\n";
  return;
}

$videoFiles = [];
foreach ([1, 2, 3] as $n) {
  $destination = $directory . "/nidqc-demo-$n.mp4";
  $uri = $fileSystem->saveData(file_get_contents($fixture), $destination, \Drupal\Core\File\FileExists::Replace);
  $file = File::create(['uri' => $uri, 'status' => 1]);
  $file->save();
  $videoFiles[] = ['target_id' => $file->id(), 'description' => "Video demo $n"];
}

$videoNode = Node::create([
  'type' => 'news',
  'title' => 'DEMO — Video tải lên từ máy',
  'status' => 1,
  // Site bật content_moderation: chỉ đặt status=1 sẽ bị ép về draft.
  'moderation_state' => 'published',
  'field_category' => ['target_id' => $videoTerm],
  'field_image' => ['target_id' => $imageFiles[2]],
  'field_videos' => $videoFiles,
  'body' => ['value' => '<p>Video mp4 tải trực tiếp lên site.</p>', 'format' => 'basic_html'],
]);
$videoNode->save();
echo "Đã tạo bài video upload [{$videoNode->id()}] với " . count($videoFiles) . " video.\n";

echo "Xong.\n";
