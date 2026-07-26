<?php

/**
 * @file
 * Nhập nội dung từ design đã duyệt vào Drupal.
 *
 * Dùng:
 *   python3 scripts/extract-content.py -o /tmp/nidqc-content.json
 *   ddev drush php:script scripts/import-content.php
 *
 * IDEMPOTENT: chạy lại nhiều lần không tạo node trùng (khớp theo type + title).
 *
 * VÌ SAO LÀ SCRIPT, KHÔNG PHẢI hook_install():
 * Node là nội dung thật của Viện — biên tập viên sẽ sửa. Nhét vào hook_install()
 * nghĩa là mỗi lần cài lại module sẽ ghi đè công sức biên tập. Đây là bước NHẬP
 * KHỞI TẠO, chạy một lần khi dựng site, không phải một phần của schema.
 *
 * Term và menu thì ngược lại — chúng là cấu trúc, không phải nội dung, nên nằm
 * trong hook_install() (xem nidqc_content.install).
 */

declare(strict_types=1);

use Drupal\node\Entity\Node;

$json = '/tmp/nidqc-content.json';
if (!file_exists($json)) {
  print "⛔ Không thấy $json. Chạy trước:\n";
  print "   python3 scripts/extract-content.py -o /tmp/nidqc-content.json\n";
  return;
}

$data = json_decode(file_get_contents($json), TRUE);
$node_storage = \Drupal::entityTypeManager()->getStorage('node');
$term_storage = \Drupal::entityTypeManager()->getStorage('taxonomy_term');

/** Tìm term theo tên. Trả NULL nếu không có — KHÔNG tự tạo. */
$term = function (string $vid, string $name) use ($term_storage) {
  if ($name === '') {
    return NULL;
  }
  $found = $term_storage->loadByProperties(['vid' => $vid, 'name' => $name]);
  return $found ? reset($found)->id() : NULL;
};

/** Chuyển ngày design dd/mm/yyyy thành timestamp của trường created core. */
$date = function (string $vn): ?int {
  if (!preg_match('#^(\d{2})/(\d{2})/(\d{4})$#', $vn, $m)) {
    return NULL;
  }
  return mktime(12, 0, 0, (int) $m[2], (int) $m[1], (int) $m[3]);
};

$created = 0;
$skipped = 0;
$warnings = [];

$make = function (string $type, string $title, array $values) use ($node_storage, &$created, &$skipped) {
  // Idempotent: đã có node cùng type + title thì bỏ qua.
  if ($node_storage->loadByProperties(['type' => $type, 'title' => $title])) {
    $skipped++;
    return;
  }
  Node::create(['type' => $type, 'title' => $title, 'status' => 1] + $values)->save();
  $created++;
};

// --- Tin tức
foreach ($data['news'] ?? [] as $row) {
  $values = [];
  if ($d = $date($row['date'] ?? '')) {
    $values['created'] = $d;
  }
  if (!empty($row['tag'])) {
    $values['field_tag'] = $row['tag'];
  }
  if ($tid = $term('news_category', $row['category'] ?? '')) {
    $values['field_category'] = $tid;
  }
  elseif (!empty($row['category'])) {
    $warnings[] = "news: không thấy term '{$row['category']}'";
  }
  // field_image: design chỉ có đường dẫn ảnh mẫu (images/*.png), KHÔNG có file
  // thật trong bundle. Bỏ trống — ảnh thật do Viện cung cấp sau.
  $make('news', $row['title'], $values);
}

// --- Văn bản
foreach ($data['document'] ?? [] as $row) {
  $values = [];
  if (!empty($row['meta'])) {
    $values['field_meta'] = $row['meta'];
  }
  if ($tid = $term('document_group', $row['group'] ?? '')) {
    $values['field_group'] = $tid;
  }
  elseif (!empty($row['group'])) {
    $warnings[] = "document: không thấy term '{$row['group']}'";
  }
  $make('document', $row['title'], $values);
}

// --- FAQ
foreach ($data['faq'] ?? [] as $row) {
  $values = [
    'field_answer' => ['value' => $row['answer'] ?? '', 'format' => 'basic_html'],
  ];
  if ($tid = $term('faq_group', $row['group'] ?? '')) {
    $values['field_group'] = $tid;
  }
  elseif (!empty($row['group'])) {
    $warnings[] = "faq: không thấy term '{$row['group']}'";
  }
  $make('faq', $row['title'], $values);
}

// --- {title, description} + project có year
foreach (['department', 'equipment', 'certificate', 'project'] as $type) {
  foreach ($data[$type] ?? [] as $row) {
    $values = [
      'field_description' => ['value' => $row['description'] ?? '', 'format' => 'basic_html'],
    ];
    if ($type === 'project' && !empty($row['year'])) {
      $values['field_year'] = (int) $row['year'];
    }
    $make($type, $row['title'], $values);
  }
}

print "  tạo mới: $created | bỏ qua (đã có): $skipped\n";
foreach (array_unique($warnings) as $w) {
  print "  ⚠️  $w\n";
}
