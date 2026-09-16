<?php

/**
 * @file
 * Gỡ ảnh base64 nhúng trong body tin cũ ra thành file thật.
 *
 * Site cũ (Drupal 7) nhúng ảnh bài viết dưới dạng `data:image/png;base64,...`.
 * `Xss::filter()` ở cuối nidqc_old_news_import_body_images() cắt mất tiền tố
 * `data:` (bộ lọc giao thức của Drupal coi `data:` là không an toàn), nên src
 * còn lại `image/png;base64,...` — trình duyệt không hiểu, ảnh vỡ; body thì vẫn
 * gánh nguyên khối base64 (có bài 15 MB) khiến JSON:API và SSR nặng vô ích.
 *
 * Script này decode từng ảnh, ghi thành file managed trong public://old-news/,
 * rồi trỏ src về file đó. Chạy được nhiều lần: ảnh đã tách thì bỏ qua.
 *
 * Dùng:
 *   drush php:script scripts/fix-old-news-body-images.php -- --nid=817 --dry-run
 *   drush php:script scripts/fix-old-news-body-images.php -- --nid=817
 *   drush php:script scripts/fix-old-news-body-images.php -- --all
 */

declare(strict_types=1);

use Drupal\Core\File\FileExists;
use Drupal\node\NodeInterface;

// Body chứa base64 nhiều MB, decode xong còn giữ cả bản gốc lẫn bản decode.
ini_set('memory_limit', '1024M');

// Drush chạy không có request nên generateAbsoluteString() sinh host "default".
// Mọi ảnh/file body của các lần import trước đều mang tiền tố này.
const BAD_HOST_PREFIX = 'http://default/';
const FIX_IMG_MAX_BYTES = 8388608;
const FIX_IMG_ALLOWED_MIME = ['image/gif', 'image/jpeg', 'image/png', 'image/webp'];

// Drush không truyền $argv vào scope script; đọc thẳng từ $_SERVER như
// import-old-news.php.
$options = fix_old_news_args($_SERVER['argv'] ?? []);
$nodeStorage = \Drupal::entityTypeManager()->getStorage('node');

if ($options['all']) {
  $query = $nodeStorage->getQuery()
    ->accessCheck(FALSE)
    ->condition('type', 'news');
  $nids = array_values($query
    ->condition($query->orConditionGroup()
      ->condition('body.value', '%;base64,%', 'LIKE')
      ->condition('body.value', '%' . BAD_HOST_PREFIX . '%', 'LIKE'))
    ->execute());
}
else {
  $nids = $options['nid'];
}

if ($nids === []) {
  print "Không có node nào để sửa (dùng --nid=… hoặc --all).\n";
  return;
}

printf("%s | %d node\n", $options['dry-run'] ? 'DRY-RUN' : 'SỬA THẬT', count($nids));

$totalImages = 0;
$totalHostFixed = 0;
$totalSavedBytes = 0;

foreach ($nodeStorage->loadMultiple($nids) as $node) {
  if (!$node instanceof NodeInterface || $node->bundle() !== 'news' || $node->get('body')->isEmpty()) {
    continue;
  }

  $body = (string) $node->get('body')->value;
  $before = strlen($body);
  $stats = ['images' => 0, 'skipped' => 0];
  $date = date('Y-m', (int) $node->getCreatedTime());
  $title = (string) $node->label();

  $fixed = preg_replace_callback(
    '#src="(?:data:)?image/(png|jpe?g|gif|webp);base64,([A-Za-z0-9+/=\s]+)"#i',
    static function (array $match) use ($title, $date, $options, &$stats): string {
      $data = base64_decode(preg_replace('/\s+/', '', $match[2]) ?? '', TRUE);
      if ($data === FALSE || $data === '' || strlen($data) > FIX_IMG_MAX_BYTES) {
        $stats['skipped']++;
        return $match[0];
      }

      $info = @getimagesizefromstring($data);
      if (!is_array($info) || empty($info['mime']) || !in_array($info['mime'], FIX_IMG_ALLOWED_MIME, TRUE)) {
        $stats['skipped']++;
        return $match[0];
      }

      $stats['images']++;
      if ($options['dry-run']) {
        return $match[0];
      }

      $uri = fix_old_news_write_file($data, $info['mime'], $title, $date);
      if ($uri === NULL) {
        $stats['skipped']++;
        return $match[0];
      }

      /** @var \Drupal\Core\File\FileUrlGeneratorInterface $urlGenerator */
      $urlGenerator = \Drupal::service('file_url_generator');

      // Tương đối, không tuyệt đối: xem ghi chú cùng vấn đề trong
      // import-old-news.php (drush không có request -> host "default").
      return 'src="' . $urlGenerator->generateString($uri) . '"';
    },
    $body,
  );

  // Nắn URL của những lần import trước: http://default/... -> /...
  $hostFixed = 0;
  if (is_string($fixed)) {
    $fixed = str_replace(
      ['src="' . BAD_HOST_PREFIX, "src='" . BAD_HOST_PREFIX, 'href="' . BAD_HOST_PREFIX, "href='" . BAD_HOST_PREFIX],
      ['src="/', "src='/", 'href="/', "href='/"],
      $fixed,
      $hostFixed,
    );
  }
  $stats['host'] = $hostFixed;

  if ($fixed === NULL || ($stats['images'] === 0 && $hostFixed === 0)) {
    printf("  [%d] %s — không có gì để sửa (bỏ qua %d)\n", $node->id(), mb_substr($title, 0, 40), $stats['skipped']);
    continue;
  }

  $after = strlen($fixed);
  $totalImages += $stats['images'];
  $totalHostFixed += $stats['host'];
  $totalSavedBytes += max(0, $before - $after);

  printf(
    "  [%d] %s — %d ảnh, %d URL nắn lại, body %s → %s%s\n",
    $node->id(),
    mb_substr($title, 0, 40),
    $stats['images'],
    $stats['host'],
    fix_old_news_size($before),
    $options['dry-run'] ? '(ước lượng)' : fix_old_news_size($after),
    $stats['skipped'] > 0 ? ", bỏ qua {$stats['skipped']}" : '',
  );

  if ($options['dry-run']) {
    continue;
  }

  $node->set('body', [
    'value' => $fixed,
    'summary' => (string) $node->get('body')->summary,
    'format' => $node->get('body')->format,
  ]);
  // Bài đang đăng phải ở nguyên trạng thái đăng: đây là sửa kỹ thuật, không
  // phải thao tác biên tập. Không set moderation_state thì revision mới rơi về
  // draft và bài biến mất khỏi site.
  if ($node->hasField('moderation_state')) {
    $node->set('moderation_state', $node->isPublished() ? 'published' : 'draft');
  }
  $node->setNewRevision(TRUE);
  $node->setRevisionLogMessage('Tách ảnh base64 trong body thành file (fix-old-news-body-images.php).');
  $node->setChangedTime($node->getChangedTime());
  $node->save();
}

printf(
  "Xong: %d ảnh tách ra file, %d URL http://default nắn lại, body giảm %s.\n",
  $totalImages,
  $totalHostFixed,
  fix_old_news_size($totalSavedBytes),
);

/**
 * Ghi dữ liệu ảnh thành file managed, trả về URI (hoặc NULL nếu hỏng).
 */
function fix_old_news_write_file(string $data, string $mime, string $title, string $date): ?string {
  $extension = match ($mime) {
    'image/png' => 'png',
    'image/gif' => 'gif',
    'image/webp' => 'webp',
    default => 'jpg',
  };

  $slug = fix_old_news_slug($title);
  // Hash nội dung: ảnh giống nhau trong nhiều bài chỉ ghi một lần.
  $filename = substr(hash('sha256', $data), 0, 12) . '-' . $slug . '.' . $extension;
  $directory = 'public://old-news/' . $date;

  $fileSystem = \Drupal::service('file_system');
  $fileSystem->prepareDirectory($directory, $fileSystem::CREATE_DIRECTORY | $fileSystem::MODIFY_PERMISSIONS);
  $uri = $directory . '/' . $filename;

  $fileStorage = \Drupal::entityTypeManager()->getStorage('file');
  $existing = $fileStorage->loadByProperties(['uri' => $uri]);
  if ($existing) {
    return $uri;
  }

  /** @var \Drupal\file\FileRepositoryInterface $repository */
  $repository = \Drupal::service('file.repository');
  try {
    $file = $repository->writeData($data, $uri, FileExists::Replace);
  }
  catch (Exception) {
    return NULL;
  }
  $file->setPermanent();
  $file->save();

  return $uri;
}

/**
 * Chuyển tiêu đề tiếng Việt thành slug ASCII ngắn cho tên file.
 */
function fix_old_news_slug(string $title): string {
  // Giữ y hệt nidqc_old_news_data_uri_filename() trong import-old-news.php:
  // cùng ảnh thì hai script phải ra cùng tên file, không đẻ bản sao.
  $converted = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $title);
  $ascii = is_string($converted) && $converted !== '' ? $converted : $title;
  $value = trim(preg_replace('/[^a-z0-9]+/', '-', mb_strtolower($ascii)) ?? '', '-');

  return $value === '' ? 'old-news' : mb_substr($value, 0, 80);
}

/**
 * Định dạng dung lượng cho dễ đọc.
 */
function fix_old_news_size(int $bytes): string {
  if ($bytes >= 1048576) {
    return round($bytes / 1048576, 2) . ' MB';
  }

  return round($bytes / 1024, 1) . ' KB';
}

/**
 * Đọc tham số sau dấu `--` của Drush.
 */
function fix_old_news_args(array $argv): array {
  $options = ['all' => FALSE, 'dry-run' => FALSE, 'nid' => []];

  foreach ($argv as $arg) {
    if ($arg === '--all') {
      $options['all'] = TRUE;
      continue;
    }
    if ($arg === '--dry-run') {
      $options['dry-run'] = TRUE;
      continue;
    }
    if (str_starts_with($arg, '--nid=')) {
      foreach (explode(',', substr($arg, 6)) as $nid) {
        $nid = (int) trim($nid);
        if ($nid > 0) {
          $options['nid'][] = $nid;
        }
      }
    }
  }

  return $options;
}
