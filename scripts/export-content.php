<?php
/**
 * @file
 * Export nội dung NGOÀI tin tức (certificate, department, document, equipment,
 * faq, page, project) ra JSON để import sang site khác (dev). Ảnh inline trong
 * text field được đổi host tuyệt đối (ddev.site / http://default) -> tương đối.
 * entity_reference (taxonomy) export theo TÊN term để remap phía import.
 *
 * Usage: ddev drush php:script scripts/export-content.php
 * Output: scripts/content-export.json
 */
declare(strict_types=1);

use Drupal\node\Entity\Node;

$types = ['certificate', 'department', 'document', 'equipment', 'faq', 'page', 'project'];

$rewrite = static function ($html): string {
  return str_replace(
    ['https://nidqc.ddev.site/', 'http://nidqc.ddev.site/', 'http://default/'],
    '/',
    (string) $html
  );
};

$out = [];
$aliasStorage = \Drupal::entityTypeManager()->getStorage('path_alias');

foreach ($types as $type) {
  $ids = \Drupal::entityQuery('node')->condition('type', $type)->accessCheck(FALSE)->sort('nid')->execute();
  foreach (Node::loadMultiple($ids) as $node) {
    $rec = [
      'type' => $type,
      'title' => $node->label(),
      'status' => (int) $node->isPublished(),
      'langcode' => $node->language()->getId(),
      'created' => (int) $node->getCreatedTime(),
      'alias' => NULL,
      'fields' => [],
    ];
    $aliases = $aliasStorage->loadByProperties(['path' => '/node/' . $node->id()]);
    if ($aliases) {
      $rec['alias'] = reset($aliases)->getAlias();
    }
    foreach ($node->getFields() as $name => $items) {
      if (strpos($name, 'field_') !== 0 && $name !== 'body') {
        continue;
      }
      if ($items->isEmpty()) {
        continue;
      }
      $fdef = $items->getFieldDefinition();
      $ftype = $fdef->getType();
      $val = $items->getValue();

      if ($ftype === 'entity_reference') {
        $tt = $fdef->getSettings()['target_type'] ?? 'node';
        $names = [];
        foreach ($node->get($name)->referencedEntities() as $ref) {
          $names[] = $ref->label();
        }
        $rec['fields'][$name] = ['__ref__' => $tt, 'names' => $names];
      }
      elseif (in_array($ftype, ['text_long', 'text_with_summary', 'text'], TRUE)) {
        foreach ($val as &$item) {
          if (isset($item['value'])) {
            $item['value'] = $rewrite($item['value']);
          }
          if (isset($item['summary'])) {
            $item['summary'] = $rewrite($item['summary']);
          }
        }
        unset($item);
        $rec['fields'][$name] = ['__val__' => $val];
      }
      else {
        $rec['fields'][$name] = ['__val__' => $val];
      }
    }
    $out[] = $rec;
  }
}

$path = DRUPAL_ROOT . '/../scripts/content-export.json';
file_put_contents($path, json_encode($out, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
printf("Exported %d nodes -> %s\n", count($out), $path);
