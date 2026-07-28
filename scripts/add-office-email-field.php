<?php

/**
 * @file
 * Thêm field Email cho content type Cơ sở (office).
 *
 * Chạy: ddev drush php:script scripts/add-office-email-field.php
 */

declare(strict_types=1);

use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;

$storage = FieldStorageConfig::loadByName('node', 'field_email');
if (!$storage) {
  $storage = FieldStorageConfig::create([
    'field_name' => 'field_email',
    'entity_type' => 'node',
    'type' => 'email',
    'cardinality' => 1,
    'translatable' => TRUE,
  ]);
  $storage->save();
  echo "Đã tạo field.storage.node.field_email\n";
}
else {
  echo "field.storage.node.field_email đã có\n";
}

if (!FieldConfig::loadByName('node', 'office', 'field_email')) {
  FieldConfig::create([
    'field_storage' => $storage,
    'bundle' => 'office',
    'label' => 'Email',
    'description' => 'Email liên hệ của cơ sở. Để trống thì trang chủ không hiện dòng email.',
    'required' => FALSE,
    'translatable' => FALSE,
  ])->save();
  echo "Đã tạo field.field.node.office.field_email\n";
}
else {
  echo "field.field.node.office.field_email đã có\n";
}

$displayRepository = \Drupal::service('entity_display.repository');
$displayRepository->getFormDisplay('node', 'office', 'default')
  ->setComponent('field_email', [
    'type' => 'email_default',
    // Ngay dưới Điện thoại để hai thông tin liên hệ đứng cạnh nhau.
    'weight' => 3,
    'region' => 'content',
    'settings' => ['placeholder' => '', 'size' => 60],
    'third_party_settings' => [],
  ])->save();

// Nuxt tự render khối Liên hệ; Drupal không hiển thị field này.
$displayRepository->getViewDisplay('node', 'office', 'default')
  ->removeComponent('field_email')->save();

echo "Đã cập nhật form display + view display của office.\n";
