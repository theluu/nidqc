<?php

/**
 * @file
 * Dựng cấu hình cho mô hình "mỗi mục trên trang chủ = MỘT node duy nhất".
 *
 * Trước đây một hoạt động chuyên môn (hay một mục Danh mục năng lực) phải nhập ở
 * HAI nơi: node expertise/capability giữ ảnh + tiêu đề, còn bài viết nằm ở một node
 * "Trang tĩnh" khác mà biên tập viên phải TỰ GÕ đường dẫn /hoat-dong-chuyen-mon/…
 * rồi dán lại vào ô Đường dẫn của node kia. Gõ lệch một ký tự là ô trên trang chủ
 * bấm vào ra 404, và không có gì chặn.
 *
 * Script này chỉ đổi CẤU HÌNH (chạy một lần trên máy dev rồi `drush cex`):
 *   1. Thêm ô "Nội dung bài viết" vào expertise + capability.
 *   2. Đường dẫn tự sinh cho hai loại đó (pathauto), đúng bằng alias đang dùng.
 *   3. Thêm ô "Danh mục dịch vụ" (chọn từ danh sách) vào loại Dịch vụ, thay cho
 *      việc gõ tay URL danh sách bài viết.
 *   4. Hạ ô "Đường dẫn" xuống thành link ngoài không bắt buộc, có mô tả rõ ràng.
 *
 * Nội dung (body của các node cũ) do nidqc_content_deploy_* chuyển, không nằm ở đây.
 *
 * Dùng: ddev drush php:script scripts/setup-detail-in-place.php
 */

use Drupal\Core\Entity\Entity\EntityFormDisplay;
use Drupal\field\Entity\FieldConfig;

/**
 * Tạo hoặc cập nhật một field trên bundle.
 */
function nidqc_field(string $bundle, string $field, array $values): void {
  $config = FieldConfig::loadByName('node', $bundle, $field);
  if ($config === NULL) {
    $config = FieldConfig::create([
      'field_name' => $field,
      'entity_type' => 'node',
      'bundle' => $bundle,
    ] + $values);
  }
  else {
    foreach ($values as $key => $value) {
      $config->set($key, $value);
    }
  }
  $config->save();
  echo "  field $bundle.$field: ok\n";
}

/**
 * Đặt widget của một field trên form nhập liệu.
 */
function nidqc_widget(string $bundle, string $field, array $options): void {
  $display = EntityFormDisplay::load("node.$bundle.default");
  if ($display === NULL) {
    echo "  BỎ QUA form display node.$bundle.default (chưa có)\n";
    return;
  }
  $display->setComponent($field, $options)->save();
  echo "  widget $bundle.$field: ok\n";
}

$linkDescription = [
  'service' => 'Bỏ trống là đúng trong hầu hết trường hợp: ô trên trang chủ tự trỏ tới danh sách bài viết của danh mục đã chọn ở trên. Chỉ điền khi muốn ô này dẫn sang một trang bên ngoài.',
  'expertise' => 'Bỏ trống là đúng trong hầu hết trường hợp: ô trên trang chủ tự trỏ tới trang riêng của chính hoạt động này (nội dung nhập ở ô "Nội dung bài viết" bên dưới). Chỉ điền khi muốn ô này dẫn sang một trang bên ngoài.',
  'capability' => 'Bỏ trống là đúng trong hầu hết trường hợp: ô trên trang chủ tự trỏ tới trang riêng của chính mục này (nội dung nhập ở ô "Nội dung bài viết" bên dưới). Chỉ điền khi muốn ô này dẫn sang một trang bên ngoài.',
];

echo "==> 1. Ô \"Nội dung bài viết\" cho expertise + capability\n";
foreach (['expertise' => 'hoạt động', 'capability' => 'mục năng lực'] as $bundle => $noun) {
  nidqc_field($bundle, 'body', [
    'label' => 'Nội dung bài viết',
    'description' => "Nội dung đầy đủ của $noun này, hiện ở trang riêng khi người đọc bấm vào ô trên trang chủ. Bỏ trống thì ô chỉ là ảnh, không bấm được.",
    'required' => FALSE,
    'translatable' => TRUE,
    'settings' => [
      'display_summary' => FALSE,
      'required_summary' => FALSE,
      'allowed_formats' => [],
    ],
    'field_type' => 'text_with_summary',
  ]);
  nidqc_widget($bundle, 'body', [
    'type' => 'text_textarea_with_summary',
    'weight' => 5,
    'region' => 'content',
    'settings' => ['rows' => 12, 'summary_rows' => 3, 'placeholder' => '', 'show_summary' => FALSE],
    'third_party_settings' => [],
  ]);
}

echo "==> 2. Ô \"Danh mục dịch vụ\" cho loại Dịch vụ\n";
nidqc_field('service', 'field_service_category', [
  'label' => 'Danh mục dịch vụ',
  'description' => 'Ô vuông trên trang chủ sẽ tự dẫn tới danh sách bài viết của danh mục này. Không phải gõ đường dẫn.',
  'required' => TRUE,
  'translatable' => TRUE,
  'settings' => [
    'handler' => 'default:taxonomy_term',
    'handler_settings' => [
      'target_bundles' => ['service_category' => 'service_category'],
      'sort' => ['field' => 'weight', 'direction' => 'asc'],
      'auto_create' => FALSE,
      'auto_create_bundle' => '',
    ],
  ],
  'field_type' => 'entity_reference',
]);
nidqc_widget('service', 'field_service_category', [
  'type' => 'options_select',
  'weight' => 1,
  'region' => 'content',
  'settings' => [],
  'third_party_settings' => [],
]);

echo "==> 3. Hạ ô Đường dẫn thành link ngoài không bắt buộc\n";
foreach ($linkDescription as $bundle => $description) {
  nidqc_field($bundle, 'field_link', [
    'label' => 'Link ngoài (không bắt buộc)',
    'description' => $description,
    'required' => FALSE,
  ]);
}

echo "==> 4. Đường dẫn tự sinh (pathauto) cho expertise + capability\n";
$patterns = [
  'expertise' => ['/hoat-dong-chuyen-mon/[node:title]', 'Hoạt động chuyên môn'],
  'capability' => ['/danh-muc-nang-luc/[node:title]', 'Danh mục năng lực'],
];
$storage = \Drupal::entityTypeManager()->getStorage('pathauto_pattern');
foreach ($patterns as $bundle => [$pattern, $label]) {
  $entity = $storage->load($bundle);
  if ($entity === NULL) {
    $entity = $storage->create([
      'id' => $bundle,
      'label' => $label,
      'type' => 'canonical_entities:node',
    ]);
  }
  $entity->set('pattern', $pattern);
  $entity->set('weight', -5);
  $entity->set('selection_logic', 'and');
  // Đặt thẳng selection_criteria (thay vì addSelectionCondition sinh uuid ngẫu
  // nhiên) để chạy lại script không tạo diff mới trong config/sync.
  $entity->set('selection_criteria', [
    "$bundle-bundle-check" => [
      'id' => 'entity_bundle:node',
      'negate' => FALSE,
      'context_mapping' => ['node' => 'node'],
      'bundles' => [$bundle => $bundle],
      'uuid' => "$bundle-bundle-check",
    ],
  ]);
  $entity->save();
  echo "  pathauto $bundle: $pattern\n";
}

echo "==> 5. Sắp lại thứ tự ô trên form nhập liệu\n";
// Đọc từ trên xuống theo đúng trình tự người nhập nghĩ: tên -> ảnh/nội dung ->
// thứ tự hiển thị. Ô "Link ngoài" đẩy xuống cuối vì hầu như không bao giờ phải điền.
$order = [
  'service' => ['field_service_category' => 0, 'field_image' => 1, 'field_weight' => 2, 'field_link' => 8],
  'expertise' => ['field_image' => 1, 'field_description' => 2, 'body' => 3, 'field_weight' => 4, 'field_link' => 8],
  'capability' => ['field_description' => 2, 'body' => 3, 'field_weight' => 4, 'field_link' => 8],
];
foreach ($order as $bundle => $weights) {
  $display = EntityFormDisplay::load("node.$bundle.default");
  foreach ($weights as $field => $weight) {
    $component = $display->getComponent($field);
    if ($component === NULL) {
      continue;
    }
    $component['weight'] = $weight;
    $display->setComponent($field, $component);
  }
  $display->save();
  echo "  form $bundle: " . implode(', ', array_keys($weights)) . "\n";
}

echo "==> Xong. Chạy `drush cex -y` để xuất cấu hình ra config/sync.\n";
