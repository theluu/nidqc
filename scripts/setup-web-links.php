<?php
/**
 * @file
 * Tạo node type "web_link" (Liên kết web) + các field, form/view display,
 * và seed 4 liên kết ngoài. Chạy idempotent (chạy lại không tạo trùng).
 *
 * Usage:
 *   ddev drush php:script scripts/setup-web-links.php
 *   ddev drush cex -y   # export config mới vào config/sync
 */
declare(strict_types=1);

use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\link\LinkItemInterface;
use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;

$out = static function (string $msg): void { print $msg . PHP_EOL; };

// 1) Bật module core 'link' (cần cho field URL).
if (!\Drupal::moduleHandler()->moduleExists('link')) {
  \Drupal::service('module_installer')->install(['link']);
  $out('✔ Đã bật module link');
}
else {
  $out('• Module link đã bật');
}

// 2) Node type web_link.
if (!NodeType::load('web_link')) {
  NodeType::create([
    'type' => 'web_link',
    'name' => 'Liên kết web',
    'description' => 'Liên kết ngoài hiển thị ở trang chủ (logo + mô tả + URL).',
    'new_revision' => TRUE,
    'display_submitted' => FALSE,
  ])->save();
  $out('✔ Đã tạo node type web_link');
}
else {
  $out('• Node type web_link đã tồn tại');
}

// 3) Field storages mới (field_image, field_description dùng lại storage sẵn có).
$storages = [
  'field_link' => ['type' => 'link'],
  'field_weight' => ['type' => 'integer'],
];
foreach ($storages as $name => $def) {
  if (!FieldStorageConfig::loadByName('node', $name)) {
    FieldStorageConfig::create([
      'field_name' => $name,
      'entity_type' => 'node',
      'type' => $def['type'],
      'cardinality' => 1,
    ])->save();
    $out("✔ Đã tạo field storage $name ({$def['type']})");
  }
  else {
    $out("• Field storage $name đã tồn tại");
  }
}

// 4) Field instances trên bundle web_link.
$fields = [
  'field_link' => [
    'label' => 'Đường dẫn (URL)',
    'required' => FALSE,
    'settings' => ['link_type' => LinkItemInterface::LINK_GENERIC, 'title' => DRUPAL_DISABLED],
  ],
  'field_image' => [
    'label' => 'Logo / Ảnh',
    'required' => FALSE,
    'settings' => [],
  ],
  'field_description' => [
    'label' => 'Mô tả',
    'required' => FALSE,
    'settings' => [],
  ],
  'field_weight' => [
    'label' => 'Thứ tự (nhỏ hiện trước)',
    'required' => FALSE,
    'settings' => [],
  ],
];
foreach ($fields as $name => $def) {
  if (!FieldConfig::loadByName('node', 'web_link', $name)) {
    FieldConfig::create([
      'field_name' => $name,
      'entity_type' => 'node',
      'bundle' => 'web_link',
      'label' => $def['label'],
      'required' => $def['required'],
      'settings' => $def['settings'],
    ])->save();
    $out("✔ Đã gắn field $name vào web_link");
  }
  else {
    $out("• Field $name đã gắn vào web_link");
  }
}

// 5) Form display (để admin nhập liệu) + view display.
$repo = \Drupal::service('entity_display.repository');
$form = $repo->getFormDisplay('node', 'web_link', 'default');
$form->setComponent('field_link', ['type' => 'link_default', 'weight' => 1]);
$form->setComponent('field_image', ['type' => 'image_image', 'weight' => 2]);
$form->setComponent('field_description', ['type' => 'text_textarea', 'weight' => 3]);
$form->setComponent('field_weight', ['type' => 'number', 'weight' => 4]);
$form->save();

$view = $repo->getViewDisplay('node', 'web_link', 'default');
$view->setComponent('field_image', ['type' => 'image', 'label' => 'hidden', 'weight' => 0]);
$view->setComponent('field_description', ['type' => 'text_default', 'label' => 'hidden', 'weight' => 1]);
$view->setComponent('field_link', ['type' => 'link', 'label' => 'hidden', 'weight' => 2]);
$view->save();
$out('✔ Đã cấu hình form/view display');

// 6) Seed 4 liên kết (idempotent theo title). VKN TP.HCM chưa có URL -> để trống.
$seed = [
  ['Bộ Y Tế', 'https://moh.gov.vn', 0],
  ['Cục Quản lý Dược', 'https://dav.gov.vn', 1],
  ['Viện Kiểm nghiệm thuốc TP. Hồ Chí Minh', NULL, 2],
  ['Tổ chức Y tế Thế giới (WHO)', 'https://www.who.int', 3],
];
$nodeStorage = \Drupal::entityTypeManager()->getStorage('node');
foreach ($seed as [$title, $url, $weight]) {
  $found = $nodeStorage->loadByProperties(['type' => 'web_link', 'title' => $title]);
  if ($found) {
    $out("• Seed '$title' đã có");
    continue;
  }
  $values = ['type' => 'web_link', 'title' => $title, 'status' => 1, 'field_weight' => $weight];
  if ($url !== NULL) {
    $values['field_link'] = ['uri' => $url, 'title' => ''];
  }
  Node::create($values)->save();
  $out("✔ Đã seed '$title'");
}

$out('Hoàn tất.');
