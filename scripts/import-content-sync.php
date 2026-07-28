<?php
/**
 * @file
 * Import nội dung ngoài tin tức từ scripts/content-export.json (do
 * export-content.php sinh trên site nguồn). Idempotent theo (type,title):
 * node đã tồn tại thì bỏ qua. entity_reference (taxonomy) remap theo TÊN term
 * đã có sẵn ở site đích. Alias được tạo lại nếu chưa có.
 *
 * Usage: drush php:script scripts/import-content-sync.php
 */
declare(strict_types=1);

use Drupal\node\Entity\Node;

$path = DRUPAL_ROOT . '/../scripts/content-export.json';
if (!is_file($path)) {
  print "!! Không thấy $path\n";
  return;
}
$records = json_decode(file_get_contents($path), TRUE);

$nodeStorage = \Drupal::entityTypeManager()->getStorage('node');
$termStorage = \Drupal::entityTypeManager()->getStorage('taxonomy_term');
$aliasStorage = \Drupal::entityTypeManager()->getStorage('path_alias');

$resolveTerm = static function (string $name) use ($termStorage): ?int {
  $t = $termStorage->loadByProperties(['name' => $name]);
  return $t ? (int) reset($t)->id() : NULL;
};

$created = 0;
$skipped = 0;
$missingTerms = [];

foreach ($records as $rec) {
  $exist = $nodeStorage->loadByProperties(['type' => $rec['type'], 'title' => $rec['title']]);
  if ($exist) {
    $skipped++;
    print "• skip (đã có): {$rec['type']} — {$rec['title']}\n";
    continue;
  }

  $values = [
    'type' => $rec['type'],
    'title' => $rec['title'],
    'status' => $rec['status'],
    'langcode' => $rec['langcode'],
    'created' => $rec['created'],
    'uid' => 1,
  ];

  foreach ($rec['fields'] as $name => $f) {
    if (isset($f['__ref__'])) {
      if ($f['__ref__'] !== 'taxonomy_term') {
        // Chỉ hỗ trợ ref taxonomy trong bộ content này.
        continue;
      }
      $ids = [];
      foreach ($f['names'] as $nm) {
        $tid = $resolveTerm($nm);
        if ($tid) {
          $ids[] = ['target_id' => $tid];
        }
        else {
          $missingTerms[$nm] = TRUE;
        }
      }
      if ($ids) {
        $values[$name] = $ids;
      }
    }
    elseif (isset($f['__val__'])) {
      $values[$name] = $f['__val__'];
    }
  }

  $node = Node::create($values);
  $node->save();

  if (!empty($rec['alias'])) {
    $ex = $aliasStorage->loadByProperties(['alias' => $rec['alias']]);
    if (!$ex) {
      $aliasStorage->create([
        'path' => '/node/' . $node->id(),
        'alias' => $rec['alias'],
        'langcode' => $rec['langcode'],
      ])->save();
    }
  }

  $created++;
  print "✔ {$rec['type']} — {$rec['title']} (nid {$node->id()})\n";
}

if ($missingTerms) {
  print "!! Term chưa khớp (bỏ trống ref): " . implode(', ', array_keys($missingTerms)) . "\n";
}
printf("Hoàn tất: tạo %d, bỏ qua %d.\n", $created, $skipped);
