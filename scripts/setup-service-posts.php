<?php
/**
 * @file
 * Tạo schema cho "Bài viết dịch vụ" — mỗi dịch vụ ở trang chủ bấm vào sẽ ra một
 * DANH SÁCH BÀI VIẾT thay vì một trang tĩnh (yêu cầu Feedback 08/2026).
 *
 *   - taxonomy vocabulary `service_category` (Danh mục dịch vụ) + 6 term ứng với
 *     6 node `service` đang có. KHÔNG có "Cung ứng chất chuẩn": mục đó là
 *     home_block trỏ sang trang tra cứu chất chuẩn, không phải danh sách bài.
 *   - node type `service_post` (Bài viết dịch vụ): body + ảnh + đính kèm +
 *     field_service_category (bắt buộc — chọn danh mục là bài hiện ở đúng dịch vụ).
 *   - pathauto: /dich-vu/<danh-muc>/<tieu-de>, khớp sẵn alias /dich-vu/<danh-muc>
 *     mà 6 node service đang trỏ tới.
 *
 * Mô tả của term được lấy từ thân 6 node `page` /dich-vu/… đang có, để nội dung
 * giới thiệu dịch vụ không mất khi trang đó bị danh sách bài viết thay chỗ.
 *
 * Idempotent. Usage:
 *   ddev drush php:script scripts/setup-service-posts.php
 *   ddev drush cex -y
 */
declare(strict_types=1);

use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\node\Entity\NodeType;
use Drupal\pathauto\Entity\PathautoPattern;
use Drupal\taxonomy\Entity\Term;
use Drupal\taxonomy\Entity\Vocabulary;

$out = static function (string $m): void { print $m . PHP_EOL; };

$entityTypeManager = \Drupal::entityTypeManager();
$aliasManager = \Drupal::service('path_alias.manager');
$nodeStorage = $entityTypeManager->getStorage('node');
$termStorage = $entityTypeManager->getStorage('taxonomy_term');

// 1) Vocabulary danh mục dịch vụ.
if (!Vocabulary::load('service_category')) {
  Vocabulary::create([
    'vid' => 'service_category',
    'name' => 'Danh mục dịch vụ',
    'description' => 'Mỗi danh mục là một dịch vụ ở trang chủ; bài viết chọn danh mục nào sẽ hiện trong danh sách của dịch vụ đó.',
  ])->save();
  $out('✔ vocabulary service_category');
}
else {
  $out('• vocabulary service_category đã có');
}

// 2) Term — lấy đúng tên và thứ tự của các node `service` đang xuất bản, để danh
//    mục không bao giờ lệch với lưới dịch vụ ở trang chủ.
$serviceIds = $nodeStorage->getQuery()
  ->accessCheck(FALSE)
  ->condition('type', 'service')
  ->condition('status', 1)
  ->sort('field_weight')
  ->sort('title')
  ->execute();
$services = $nodeStorage->loadMultiple($serviceIds);
if ($services === []) {
  $out('! Không tìm thấy node `service` nào — chạy scripts/setup-home-blocks.php trước.');
  return;
}

$existingTerms = [];
foreach ($termStorage->loadByProperties(['vid' => 'service_category']) as $term) {
  $existingTerms[$term->label()] = $term;
}

$weight = 0;
foreach ($services as $service) {
  $label = $service->label();
  $term = $existingTerms[$label] ?? NULL;
  if (!$term) {
    $term = Term::create(['vid' => 'service_category', 'name' => $label, 'weight' => $weight]);
    $out("✔ term «{$label}»");
  }
  else {
    $term->setWeight($weight);
    $out("• term «{$label}» đã có");
  }

  // Mô tả term = thân bài của trang tĩnh /dich-vu/… hiện tại (nếu có và term chưa
  // có mô tả). Trang đó sắp bị danh sách bài viết thay chỗ nên nội dung giới thiệu
  // phải chuyển về đây, không thì mất trắng.
  if (trim((string) $term->getDescription()) === '') {
    $link = $service->hasField('field_link') && !$service->get('field_link')->isEmpty()
      ? (string) $service->get('field_link')->uri
      : '';
    $alias = str_starts_with($link, 'internal:') ? substr($link, strlen('internal:')) : '';
    if ($alias !== '') {
      $path = $aliasManager->getPathByAlias($alias);
      if (preg_match('#^/node/(\d+)$#', $path, $m) === 1) {
        $legacy = $nodeStorage->load((int) $m[1]);
        if ($legacy && $legacy->bundle() === 'page' && $legacy->hasField('body') && !$legacy->get('body')->isEmpty()) {
          $term->setDescription((string) $legacy->get('body')->value);
          $term->get('description')->format = (string) $legacy->get('body')->format;
          $out("  ↳ mô tả lấy từ trang tĩnh $alias (node {$legacy->id()})");
        }
      }
    }
  }

  $term->save();
  $weight++;
}

// 3) Node type.
if (!NodeType::load('service_post')) {
  NodeType::create([
    'type' => 'service_post',
    'name' => 'Bài viết dịch vụ',
    'description' => 'Bài viết hiện trong danh sách của một dịch vụ (/dich-vu/…).',
    'new_revision' => TRUE,
    'display_submitted' => FALSE,
  ])->save();
  $out('✔ node type service_post');
}
else {
  $out('• node type service_post đã có');
}

// 4) Field storage riêng của content type này (body/field_image/field_attachments
//    đã có storage dùng chung với Tin tức).
if (!FieldStorageConfig::loadByName('node', 'field_service_category')) {
  FieldStorageConfig::create([
    'field_name' => 'field_service_category',
    'entity_type' => 'node',
    'type' => 'entity_reference',
    'cardinality' => 1,
    'settings' => ['target_type' => 'taxonomy_term'],
  ])->save();
  $out('✔ field storage field_service_category');
}
else {
  $out('• field storage field_service_category đã có');
}

foreach (['body', 'field_image', 'field_attachments'] as $required) {
  if (!FieldStorageConfig::loadByName('node', $required)) {
    $out("! Thiếu field storage $required — chạy các script setup content type trước.");
    return;
  }
}

// 5) Field instance trên service_post.
$fields = [
  [
    'field_service_category',
    'Danh mục dịch vụ',
    'Bài viết sẽ hiện trong danh sách của dịch vụ được chọn.',
    TRUE,
    [
      'handler' => 'default:taxonomy_term',
      'handler_settings' => [
        'target_bundles' => ['service_category' => 'service_category'],
        'auto_create' => FALSE,
        'sort' => ['field' => 'weight', 'direction' => 'ASC'],
      ],
    ],
  ],
  ['body', 'Nội dung', '', FALSE, []],
  ['field_image', 'Ảnh đại diện', '', FALSE, []],
  [
    'field_attachments',
    'Tài liệu đính kèm',
    'File đính kèm của bài viết (biểu mẫu, báo giá, quy trình…).',
    FALSE,
    [
      'handler' => 'default:file',
      'handler_settings' => [],
      'file_directory' => 'service/files/[date:custom:Y-m]',
      'file_extensions' => 'pdf doc docx xls xlsx ppt pptx rar zip jpg jpeg png gif webp',
      'max_filesize' => '',
      'description_field' => TRUE,
    ],
  ],
];
foreach ($fields as [$name, $label, $description, $isRequired, $settings]) {
  if (FieldConfig::loadByName('node', 'service_post', $name)) {
    $out("• field $name đã có");
    continue;
  }
  FieldConfig::create([
    'field_name' => $name,
    'entity_type' => 'node',
    'bundle' => 'service_post',
    'label' => $label,
    'description' => $description,
    'required' => $isRequired,
    'settings' => $settings,
  ])->save();
  $out("✔ field $name");
}

// 6) Form display + view display — không có thì Drupal dựng mặc định nhưng KHÔNG
//    lưu thành config, `drush cex` sẽ không xuất ra và prod mất bố cục form.
$formDisplay = \Drupal::service('entity_display.repository')
  ->getFormDisplay('node', 'service_post', 'default');
$formDisplay
  ->setComponent('title', ['type' => 'string_textfield', 'weight' => 0])
  ->setComponent('field_service_category', ['type' => 'options_select', 'weight' => 1])
  ->setComponent('field_image', ['type' => 'image_image', 'weight' => 2])
  ->setComponent('body', ['type' => 'text_textarea_with_summary', 'weight' => 3])
  ->setComponent('field_attachments', ['type' => 'file_generic', 'weight' => 4])
  ->save();
$out('✔ form display service_post');

$viewDisplay = \Drupal::service('entity_display.repository')
  ->getViewDisplay('node', 'service_post', 'default');
$viewDisplay
  ->setComponent('field_image', ['type' => 'image', 'label' => 'hidden', 'weight' => 0])
  ->setComponent('body', ['type' => 'text_default', 'label' => 'hidden', 'weight' => 1])
  ->setComponent('field_attachments', ['type' => 'file_default', 'label' => 'above', 'weight' => 2])
  ->removeComponent('field_service_category')
  ->save();
$out('✔ view display service_post');

// 7) Pathauto: /dich-vu/<danh-muc>/<tieu-de>.
//    Đoạn giữa là TÊN TERM đã transliterate, nên trùng khít alias /dich-vu/<danh-muc>
//    mà 6 node `service` đang trỏ tới — không phải chôn slug ở hai nơi.
if (!PathautoPattern::load('service_post')) {
  PathautoPattern::create([
    'id' => 'service_post',
    'label' => 'Bài viết dịch vụ',
    'type' => 'canonical_entities:node',
    'pattern' => '/dich-vu/[node:field_service_category:entity:name]/[node:title]',
    'selection_criteria' => [
      'service-post-bundle-check' => [
        'id' => 'entity_bundle:node',
        'negate' => FALSE,
        'uuid' => 'service-post-bundle-check',
        'context_mapping' => ['node' => 'node'],
        'bundles' => ['service_post' => 'service_post'],
      ],
    ],
    'selection_logic' => 'and',
    'weight' => -5,
  ])->save();
  $out('✔ pathauto pattern service_post');
}
else {
  $out('• pathauto pattern service_post đã có');
}

$out('');
$out('Xong. Nhớ: ddev drush cex -y');
