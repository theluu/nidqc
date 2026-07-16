<?php
// Gắn ảnh design vào node theo tiêu đề. Chạy sau extract-images.py.
//   python3 scripts/extract-images.py --out web/sites/default/files/design-images --map web/nidqc-images.json
//   ddev drush php:script scripts/import-images.php
// (Idempotent: node đã có ảnh thì bỏ qua.)
use Drupal\file\Entity\File;
$map = json_decode(file_get_contents(DRUPAL_ROOT . '/nidqc-images.json'), TRUE);
if (!$map) { print "Không đọc được web/nidqc-images.json\n"; return; }
$ns = \Drupal::entityTypeManager()->getStorage('node');
$fs = \Drupal::entityTypeManager()->getStorage('file');
$done = 0; $skip = 0;
foreach ($map as $title => $fn) {
  $nodes = $ns->loadByProperties(['title' => $title]);
  if (!$nodes) { $skip++; continue; }
  $node = reset($nodes);
  if (!$node->hasField('field_image') || !$node->get('field_image')->isEmpty()) continue;
  $uri = "public://design-images/$fn";
  $ex = $fs->loadByProperties(['uri' => $uri]);
  $file = $ex ? reset($ex) : File::create(['uri' => $uri, 'status' => 1]);
  if (!$ex) $file->save();
  $node->set('field_image', ['target_id' => $file->id(), 'alt' => $title])->save();
  $done++;
}
print "gắn ảnh: $done | không thấy node: $skip\n";
