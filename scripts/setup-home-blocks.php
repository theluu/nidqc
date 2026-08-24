<?php
/**
 * @file
 * Tạo các node type cho khối trang chủ động + seed dữ liệu hiện tại:
 *   - expertise  (Hoạt động chuyên môn): title + mô tả + thứ tự
 *   - service    (Dịch vụ & tra cứu):    title + link + thứ tự
 *   - office     (Cơ sở/Liên hệ):        title + địa chỉ + SĐT + bản đồ + thứ tự
 *   - home_block (CTA + Video, singleton): title + mô tả + link(nút) + video
 *
 * Idempotent. Usage:
 *   ddev drush php:script scripts/setup-home-blocks.php
 *   ddev drush cex -y
 */
declare(strict_types=1);

use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\link\LinkItemInterface;
use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;

$out = static function (string $m): void { print $m . PHP_EOL; };

// 1) Node types.
$types = [
  'expertise' => 'Hoạt động chuyên môn',
  'service' => 'Dịch vụ & tra cứu',
  'office' => 'Cơ sở / Liên hệ',
  'home_block' => 'Khối trang chủ (CTA + Video)',
];
foreach ($types as $type => $name) {
  if (!NodeType::load($type)) {
    NodeType::create(['type' => $type, 'name' => $name, 'new_revision' => TRUE, 'display_submitted' => FALSE])->save();
    $out("✔ node type $type");
  }
  else { $out("• node type $type đã có"); }
}

// 2) Field storages mới (field_description, field_weight, field_link đã có sẵn — dùng lại).
$storages = [
  'field_address' => 'string_long',
  'field_phone' => 'string',
  'field_map' => 'string_long',
  'field_video' => 'string',
];
foreach ($storages as $name => $ftype) {
  if (!FieldStorageConfig::loadByName('node', $name)) {
    FieldStorageConfig::create(['field_name' => $name, 'entity_type' => 'node', 'type' => $ftype, 'cardinality' => 1])->save();
    $out("✔ field storage $name ($ftype)");
  }
  else { $out("• field storage $name đã có"); }
}

// 3) Field instances theo bundle.
$fields = [
  'expertise' => [
    ['field_description', 'Mô tả', []],
    ['field_weight', 'Thứ tự', []],
  ],
  'service' => [
    ['field_link', 'Đường dẫn', ['link_type' => LinkItemInterface::LINK_GENERIC, 'title' => DRUPAL_DISABLED]],
    ['field_weight', 'Thứ tự', []],
  ],
  'office' => [
    ['field_address', 'Địa chỉ', []],
    ['field_phone', 'Điện thoại', []],
    ['field_map', 'Bản đồ (URL nhúng Google Maps)', []],
    ['field_weight', 'Thứ tự', []],
  ],
  'home_block' => [
    ['field_description', 'Mô tả', []],
    ['field_link', 'Nút CTA (link + nhãn)', ['link_type' => LinkItemInterface::LINK_GENERIC, 'title' => DRUPAL_OPTIONAL]],
    ['field_video', 'Video (YouTube URL hoặc ID)', []],
  ],
];
foreach ($fields as $bundle => $list) {
  foreach ($list as [$fname, $label, $settings]) {
    if (!FieldConfig::loadByName('node', $bundle, $fname)) {
      FieldConfig::create([
        'field_name' => $fname, 'entity_type' => 'node', 'bundle' => $bundle,
        'label' => $label, 'required' => FALSE, 'settings' => $settings,
      ])->save();
      $out("✔ $bundle.$fname");
    }
    else { $out("• $bundle.$fname đã có"); }
  }
}

// 4) Form/view display.
$repo = \Drupal::service('entity_display.repository');
$formWidgets = [
  'field_description' => 'text_textarea',
  'field_link' => 'link_default',
  'field_weight' => 'number',
  'field_address' => 'string_textarea',
  'field_phone' => 'string_textfield',
  'field_map' => 'string_textarea',
  'field_video' => 'string_textfield',
];
foreach ($fields as $bundle => $list) {
  $form = $repo->getFormDisplay('node', $bundle, 'default');
  $view = $repo->getViewDisplay('node', $bundle, 'default');
  $w = 1;
  foreach ($list as [$fname]) {
    $form->setComponent($fname, ['type' => $formWidgets[$fname], 'weight' => $w]);
    $view->setComponent($fname, ['label' => 'hidden', 'weight' => $w]);
    $w++;
  }
  $form->save();
  $view->save();
}
$out('✔ form/view display');

// 5) Seed dữ liệu hiện tại (idempotent theo title trong từng bundle).
$nodeStorage = \Drupal::entityTypeManager()->getStorage('node');
$seedNode = static function (array $values) use ($nodeStorage, $out): void {
  $found = $nodeStorage->loadByProperties(['type' => $values['type'], 'title' => $values['title']]);
  if ($found) { $out("• seed '{$values['title']}' đã có"); return; }
  Node::create($values + ['status' => 1])->save();
  $out("✔ seed '{$values['title']}'");
};

$expertise = [
  ['Chỉ đạo tuyến', 'Hướng dẫn, giám sát chuyên môn hệ thống kiểm nghiệm địa phương.'],
  ['Kiểm nghiệm và giám sát chất lượng thuốc', 'Kiểm tra, giám sát chất lượng thuốc lưu hành trên toàn quốc.'],
  ['Hợp tác quốc tế', 'Hợp tác với các tổ chức kiểm nghiệm và y tế quốc tế.'],
  ['Hoạt động NRA', 'Cơ quan quản lý quốc gia về vắc xin theo chuẩn WHO.'],
  ['Tạp chí Kiểm nghiệm Dược và Mỹ phẩm', 'Ấn phẩm khoa học công bố nghiên cứu, kết quả kiểm nghiệm.'],
];
foreach ($expertise as $i => [$t, $d]) {
  $seedNode(['type' => 'expertise', 'title' => $t, 'field_description' => ['value' => $d, 'format' => 'basic_html'], 'field_weight' => $i]);
}

$services = [
  'Phân tích - Kiểm nghiệm', 'Đánh giá tương đương sinh học (TĐSH)',
  'Đào tạo và tư vấn kỹ thuật', 'Hiệu chuẩn',
  'Nghiên cứu - Chuyển giao', 'Thử nghiệm thành thạo',
];
foreach ($services as $i => $t) {
  $seedNode(['type' => 'service', 'title' => $t, 'field_weight' => $i]);
}

$offices = [
  ['Cơ sở 1', '48 Hai Bà Trưng, phường Cửa Nam, Hà Nội', '(024) 3825 5075', 'https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3724.185366346867!2d105.84769501476318!3d21.025267786000292!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x3135ab949db87b29%3A0xeab602afd22b8090!2zNDggSGFpIELDoCBUcsawbmcsIFRyw6BuZyBUaeG7gW4sIEhvw6BuIEtp4bq_bSwgSMOgIE7hu5lpLCBWaeG7h3QgTmFt!5e0!3m2!1svi!2s!4v1577957514119!5m2!1svi!2s'],
  ['Cơ sở 2', 'Phường Hoàng Liệt, Tp. Hà Nội', '(024) 3736 4738', 'https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3725.941679701607!2d105.8298021147619!3d20.954857086038405!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x3135ada9a6796f99%3A0xebd36f00bc31e2f4!2zVmnhu4duIEtp4buDbSBuZ2hp4buHbSB0aHXhu5FjIFRydW5nIMawxqFuZyAoQ8ahIHPhu58gSUkp!5e0!3m2!1svi!2s!4v1577958314927!5m2!1svi!2s'],
];
foreach ($offices as $i => [$t, $addr, $tel, $map]) {
  $seedNode(['type' => 'office', 'title' => $t, 'field_address' => $addr, 'field_phone' => $tel, 'field_map' => $map, 'field_weight' => $i]);
}

$seedNode([
  'type' => 'home_block',
  'title' => 'Chất chuẩn – chất đối chiếu',
  'field_description' => ['value' => 'Tra cứu và đăng ký cung ứng chất chuẩn, chất đối chiếu phục vụ kiểm nghiệm.', 'format' => 'basic_html'],
  'field_link' => ['uri' => 'https://nidqc.gov.vn/tim-kiem-chat-chuan', 'title' => 'Cung ứng chất chuẩn'],
  'field_video' => '7k9OhYB8Q5A',
]);

$out('Hoàn tất.');
