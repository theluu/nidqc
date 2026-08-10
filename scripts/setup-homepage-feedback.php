<?php
/**
 * @file
 * Schema + nội dung cho bản cập nhật trang chủ theo "Feedback NIDQC" (08/2026).
 *
 * Feedback yêu cầu bốn khối dữ liệu mới mà schema cũ không có chỗ chứa:
 *   6.  Dịch vụ hiện dạng "title + ảnh" (service chưa có ảnh) và một cột
 *       "Danh mục năng lực" gồm 5 mục trỏ sang bài viết -> node type `capability`.
 *   7,9 Hai dải banner quảng cáo dạng slideshow -> node type `banner`, phân biệt
 *       bằng field_position (ads_1 = dưới Dịch vụ, ads_2 = dưới Hoạt động chuyên môn).
 *   8.  "Hoạt động chuyên môn" hiện title + ảnh và bấm sang bài viết chi tiết
 *       (expertise chưa có ảnh lẫn link), cột phải là các link nội bộ có ảnh
 *       -> dùng lại `banner` với field_position = sidebar.
 *
 * Mỗi mục dịch vụ / năng lực / hoạt động chuyên môn đều phải có một trang tĩnh để
 * bấm vào, nên script tạo luôn node `page` khung sẵn (alias /dich-vu/…,
 * /danh-muc-nang-luc/…, /hoat-dong-chuyen-mon/…) rồi trỏ field_link vào đó. Biên tập
 * viên chỉ việc mở ra viết nội dung, KHÔNG phải tự dựng đường dẫn.
 *
 * Chạy lại được nhiều lần (idempotent): đã có thì bỏ qua, không tạo trùng, không
 * ghi đè nội dung admin đã sửa.
 *
 * Usage:
 *   ddev drush php:script scripts/setup-homepage-feedback.php
 *   ddev drush cex -y
 */

declare(strict_types=1);

use Drupal\Component\Utility\Unicode;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\link\LinkItemInterface;
use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;
use Drupal\node\NodeInterface;

$out = static function (string $msg): void {
  print $msg . PHP_EOL;
};

$entityTypeManager = \Drupal::entityTypeManager();
$nodeStorage = $entityTypeManager->getStorage('node');
$displays = \Drupal::service('entity_display.repository');
$aliasStorage = $entityTypeManager->getStorage('path_alias');
$transliteration = \Drupal::transliteration();

/**
 * Chuyển tiêu đề tiếng Việt thành slug ASCII cho đường dẫn.
 */
$slugify = static function (string $text) use ($transliteration): string {
  $ascii = $transliteration->transliterate($text, 'vi', '-');
  $ascii = strtolower($ascii);
  $ascii = preg_replace('/[^a-z0-9]+/', '-', $ascii) ?? '';
  return trim($ascii, '-');
};

// ---------------------------------------------------------------------------
// 1. Module bắt buộc.
// ---------------------------------------------------------------------------
$required = array_values(array_filter(
  ['link', 'image', 'options', 'path'],
  static fn (string $m): bool => !\Drupal::moduleHandler()->moduleExists($m),
));
if ($required !== []) {
  \Drupal::service('module_installer')->install($required);
  $out('✔ Đã bật module: ' . implode(', ', $required));
}

// ---------------------------------------------------------------------------
// 2. Node type mới.
// ---------------------------------------------------------------------------
$types = [
  'banner' => [
    'name' => 'Banner & liên kết ảnh',
    'description' => 'Ảnh có đường dẫn dùng cho hai dải banner quảng cáo ở trang chủ và cột liên kết nổi bật bên phải khối Hoạt động chuyên môn. Chọn vị trí ở ô "Vị trí hiển thị".',
  ],
  'capability' => [
    'name' => 'Danh mục năng lực',
    'description' => 'Các mục trong cột "Danh mục năng lực" ở khối Dịch vụ trang chủ (Kiểm nghiệm, Hiệu chuẩn, Đào tạo & tư vấn, Đánh giá TĐSH, Thử nghiệm thành thạo).',
  ],
];
foreach ($types as $type => $info) {
  if (NodeType::load($type)) {
    $out("• Node type $type đã tồn tại");
    continue;
  }
  NodeType::create([
    'type' => $type,
    'name' => $info['name'],
    'description' => $info['description'],
    'new_revision' => TRUE,
    'display_submitted' => FALSE,
  ])->save();
  $out("✔ Đã tạo node type $type");
}

// ---------------------------------------------------------------------------
// 3. Field storage mới (các storage còn lại đã có sẵn từ những bundle trước).
// ---------------------------------------------------------------------------
if (!FieldStorageConfig::loadByName('node', 'field_position')) {
  FieldStorageConfig::create([
    'field_name' => 'field_position',
    'entity_type' => 'node',
    'type' => 'list_string',
    'cardinality' => 1,
    // allowed_values ở TẦNG RUNTIME là map value => label. Truyền dạng
    // [['value'=>…,'label'=>…]] (dạng đã lưu trong file config) sẽ bị
    // ListItemBase::storageSettingsToConfigData() cấu trúc hoá thêm một lần nữa,
    // nhãn biến thành mảng và save() chết ở "settings.allowed_values.0.label.0".
    'settings' => [
      'allowed_values' => [
        'ads_1' => 'Banner quảng cáo 1 (dưới khối Dịch vụ)',
        'ads_2' => 'Banner quảng cáo 2 (dưới khối Hoạt động chuyên môn)',
        'sidebar' => 'Liên kết nổi bật (cột phải Hoạt động chuyên môn)',
      ],
      'allowed_values_function' => '',
    ],
  ])->save();
  $out('✔ Đã tạo field storage field_position (list_string)');
}
else {
  $out('• Field storage field_position đã tồn tại');
}

// ---------------------------------------------------------------------------
// 4. Field instance.
// ---------------------------------------------------------------------------
$linkSettings = [
  'link_type' => LinkItemInterface::LINK_GENERIC,
  'title' => DRUPAL_DISABLED,
];
$instances = [
  // Bundle => [field_name => [label, required, settings]].
  'banner' => [
    'field_image' => ['Ảnh banner', TRUE, []],
    'field_link' => ['Đường dẫn khi bấm vào', FALSE, $linkSettings],
    'field_position' => ['Vị trí hiển thị', TRUE, []],
    'field_weight' => ['Thứ tự (nhỏ hiện trước)', FALSE, []],
  ],
  'capability' => [
    'field_link' => ['Đường dẫn bài viết', FALSE, $linkSettings],
    'field_description' => ['Mô tả ngắn', FALSE, []],
    'field_weight' => ['Thứ tự (nhỏ hiện trước)', FALSE, []],
  ],
  // Feedback 6: dịch vụ hiện "title + image", bỏ mô tả.
  'service' => [
    'field_image' => ['Ảnh minh hoạ', FALSE, []],
  ],
  // Feedback 8: hoạt động chuyên môn hiện "title + image", bấm ra bài chi tiết.
  'expertise' => [
    'field_image' => ['Ảnh minh hoạ', FALSE, []],
    'field_link' => ['Đường dẫn bài viết chi tiết', FALSE, $linkSettings],
  ],
];
foreach ($instances as $bundle => $fields) {
  foreach ($fields as $name => [$label, $isRequired, $settings]) {
    if (FieldConfig::loadByName('node', $bundle, $name)) {
      $out("• Field $name đã gắn vào $bundle");
      continue;
    }
    FieldConfig::create([
      'field_name' => $name,
      'entity_type' => 'node',
      'bundle' => $bundle,
      'label' => $label,
      'required' => $isRequired,
      'settings' => $settings,
    ])->save();
    $out("✔ Đã gắn field $name vào $bundle");
  }
}

// ---------------------------------------------------------------------------
// 5. Form display — admin phải nhập được các field mới, nếu không chúng vô hình.
// ---------------------------------------------------------------------------
$formComponents = [
  'banner' => [
    'field_image' => ['type' => 'image_image', 'weight' => 1],
    'field_link' => ['type' => 'link_default', 'weight' => 2],
    'field_position' => ['type' => 'options_select', 'weight' => 3],
    'field_weight' => ['type' => 'number', 'weight' => 4],
  ],
  'capability' => [
    'field_link' => ['type' => 'link_default', 'weight' => 1],
    'field_description' => ['type' => 'text_textarea', 'weight' => 2],
    'field_weight' => ['type' => 'number', 'weight' => 3],
  ],
  'service' => [
    'field_image' => ['type' => 'image_image', 'weight' => 3],
  ],
  'expertise' => [
    'field_image' => ['type' => 'image_image', 'weight' => 3],
    'field_link' => ['type' => 'link_default', 'weight' => 4],
  ],
];
foreach ($formComponents as $bundle => $components) {
  $form = $displays->getFormDisplay('node', $bundle, 'default');
  foreach ($components as $name => $options) {
    $form->setComponent($name, $options);
  }
  $form->save();

  $view = $displays->getViewDisplay('node', $bundle, 'default');
  foreach (array_keys($components) as $name) {
    $view->setComponent($name, ['label' => 'hidden', 'weight' => 0] + match (TRUE) {
      str_contains($name, 'image') => ['type' => 'image'],
      str_contains($name, 'link') => ['type' => 'link'],
      str_contains($name, 'description') => ['type' => 'text_default'],
      default => ['type' => 'list_default'],
    });
  }
  $view->save();
}
$out('✔ Đã cấu hình form/view display cho banner, capability, service, expertise');

// ---------------------------------------------------------------------------
// 6. Trang tĩnh khung cho các mục sẽ bấm vào được.
// ---------------------------------------------------------------------------

/**
 * Tạo (hoặc tìm lại) một node `page` theo alias và trả về chính alias đó.
 *
 * Alias đặt tay chứ không nhờ pathauto: pattern của bundle `page` sinh alias
 * phẳng ở gốc, còn ở đây cần gom theo nhóm (/dich-vu/…, /danh-muc-nang-luc/…)
 * để đường dẫn tự nói lên nó thuộc khối nào.
 */
$ensurePage = static function (string $title, string $alias, string $intro) use ($nodeStorage, $aliasStorage, $out): string {
  $existing = $aliasStorage->loadByProperties(['alias' => $alias]);
  if ($existing !== []) {
    $out("• Trang $alias đã có");
    return $alias;
  }

  $node = Node::create([
    'type' => 'page',
    'title' => $title,
    'status' => 1,
    'body' => [
      'value' => '<p>' . $intro . '</p>',
      'format' => 'basic_html',
    ],
    // Tắt pathauto để alias đặt tay ở dưới không bị ghi đè.
    'path' => ['pathauto' => 0, 'alias' => $alias],
  ]);
  $node->save();
  $out("✔ Đã tạo trang $alias");

  return $alias;
};

$placeholder = 'Nội dung đang được Viện biên soạn và sẽ cập nhật trong thời gian tới.';

// 6a. Bài viết chi tiết cho từng dịch vụ + trỏ field_link của service vào đó.
$services = $nodeStorage->loadByProperties(['type' => 'service']);
foreach ($services as $service) {
  assert($service instanceof NodeInterface);
  $title = (string) $service->label();
  $alias = $ensurePage(
    $title,
    '/dich-vu/' . $slugify($title),
    'Giới thiệu dịch vụ <strong>' . Unicode::truncate($title, 120, TRUE, TRUE) . '</strong> của Viện Kiểm nghiệm thuốc Trung ương. ' . $placeholder,
  );
  if ($service->get('field_link')->isEmpty()) {
    $service->set('field_link', ['uri' => 'internal:' . $alias, 'title' => '']);
    $service->save();
    $out("✔ Đã trỏ dịch vụ '$title' sang $alias");
  }
}

// 6b. Bài viết chi tiết cho từng hoạt động chuyên môn.
$expertises = $nodeStorage->loadByProperties(['type' => 'expertise']);
foreach ($expertises as $expertise) {
  assert($expertise instanceof NodeInterface);
  $title = (string) $expertise->label();
  $alias = $ensurePage(
    $title,
    '/hoat-dong-chuyen-mon/' . $slugify($title),
    'Hoạt động <strong>' . Unicode::truncate($title, 120, TRUE, TRUE) . '</strong> của Viện Kiểm nghiệm thuốc Trung ương. ' . $placeholder,
  );
  if ($expertise->get('field_link')->isEmpty()) {
    $expertise->set('field_link', ['uri' => 'internal:' . $alias, 'title' => '']);
    $expertise->save();
    $out("✔ Đã trỏ hoạt động chuyên môn '$title' sang $alias");
  }
}

// 6c. Danh mục năng lực — 5 mục đúng theo feedback, kèm trang chi tiết.
$capabilities = [
  ['Kiểm nghiệm', 'Danh mục các phép thử, chỉ tiêu kiểm nghiệm thuốc, nguyên liệu làm thuốc và mỹ phẩm mà Viện thực hiện.'],
  ['Hiệu chuẩn', 'Danh mục thiết bị và phạm vi hiệu chuẩn Viện cung cấp cho các đơn vị trong ngành.'],
  ['Danh mục các khoá đào tạo & tư vấn', 'Các khoá đào tạo chuyên môn và dịch vụ tư vấn kỹ thuật do Viện tổ chức.'],
  ['Đánh giá tương đương sinh học (TĐSH)', 'Năng lực và phạm vi đánh giá tương đương sinh học của Viện.'],
  ['Các chương trình thử nghiệm thành thạo (TNTT)', 'Danh mục các chương trình thử nghiệm thành thạo Viện tổ chức hằng năm.'],
];
foreach ($capabilities as $index => [$title, $description]) {
  $alias = $ensurePage(
    $title,
    '/danh-muc-nang-luc/' . $slugify($title),
    $description . ' ' . $placeholder,
  );

  if ($nodeStorage->loadByProperties(['type' => 'capability', 'title' => $title]) !== []) {
    $out("• Danh mục năng lực '$title' đã có");
    continue;
  }
  Node::create([
    'type' => 'capability',
    'title' => $title,
    'status' => 1,
    'field_weight' => $index,
    'field_description' => ['value' => $description, 'format' => 'basic_html'],
    'field_link' => ['uri' => 'internal:' . $alias, 'title' => ''],
  ])->save();
  $out("✔ Đã tạo danh mục năng lực '$title'");
}

// ---------------------------------------------------------------------------
// 7. Giá trị mặc định cho chân trang mới (chỉ điền khi còn trống).
// ---------------------------------------------------------------------------
$settings = \Drupal::configFactory()->getEditable('nidqc_contact.settings');
if ($settings->get('footer.tel') === NULL) {
  $settings
    ->set('footer.tel', '(024) 3825 5075')
    ->set('footer.tel_note', 'giờ hành chính')
    ->set('footer.fax', '')
    ->set('footer.email', 'vienkiemnghiem@nidqc.gov.vn');
  $out('✔ Đã đặt thông tin liên hệ mặc định cho chân trang');
}
if ($settings->get('customer_services') === NULL) {
  // Bốn nhóm đúng theo bảng chân trang trong feedback. Email/hotline riêng của
  // từng nhóm do Viện cung cấp sau — để trống thì chân trang tự ẩn nhóm đó.
  $settings->set('customer_services', array_map(
    static fn (string $label): array => ['label' => $label, 'email' => '', 'hotline' => ''],
    ['Dịch vụ Kiểm nghiệm', 'Đào tạo', 'Hiệu chuẩn thiết bị', 'Cung ứng chất chuẩn'],
  ));
  $out('✔ Đã tạo khung 4 đầu mối dịch vụ ở chân trang (chờ Viện điền email/hotline)');
}
$settings->save();

$out('Hoàn tất.');
