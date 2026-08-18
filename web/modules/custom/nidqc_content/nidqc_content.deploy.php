<?php

/**
 * @file
 * Hook chạy sau `drush deploy` (updatedb -> config:import -> cache:rebuild).
 *
 * Dữ liệu sống ở từng môi trường chứ không đi qua git (xem scripts/deploy.sh), nên
 * việc dọn nội dung phải chạy bằng hook để mọi môi trường tự làm đúng một lần.
 */

declare(strict_types=1);

use Drupal\node\Entity\Node;
use Drupal\pathauto\PathautoState;

/**
 * Gộp bài viết của Hoạt động chuyên môn và Danh mục năng lực vào chính node đó.
 *
 * Trước: một hoạt động = 2 node. Node `expertise` giữ ảnh + tiêu đề, còn bài viết
 * nằm ở một node `page` riêng mà biên tập viên phải TỰ GÕ alias
 * /hoat-dong-chuyen-mon/… rồi dán ngược vào ô Đường dẫn của node expertise. Lệch một
 * ký tự giữa hai bước là ô trên trang chủ bấm vào ra 404, không có gì chặn.
 *
 * Sau: bài viết nằm ngay trong node expertise/capability, alias do pathauto sinh từ
 * tiêu đề. Alias giữ NGUYÊN chuỗi cũ (pathauto bỏ dấu tiêu đề ra đúng slug đang
 * dùng) nên link đã phát ra ngoài không chết — vì vậy phải bỏ node `page` TRƯỚC để
 * nhả alias, rồi mới lưu node đích cho pathauto nhận lại đúng chuỗi đó.
 *
 * Chạy lại nhiều lần vô hại: không còn node `page` trùng thì không làm gì.
 */
function nidqc_content_deploy_merge_detail_pages(): string {
  $aliasManager = \Drupal::service('path_alias.manager');
  $storage = \Drupal::entityTypeManager()->getStorage('node');
  $done = [];

  foreach (['expertise' => '/hoat-dong-chuyen-mon/', 'capability' => '/danh-muc-nang-luc/'] as $bundle => $prefix) {
    $ids = $storage->getQuery()->accessCheck(FALSE)->condition('type', $bundle)->execute();
    foreach ($storage->loadMultiple($ids) as $node) {
      // Bài cũ tra theo chính đường dẫn đang gắn trên ô Đường dẫn; không có thì thôi.
      $uri = $node->get('field_link')->isEmpty() ? '' : (string) $node->get('field_link')->uri;
      $alias = str_starts_with($uri, 'internal:') ? substr($uri, strlen('internal:')) : '';
      if ($alias === '' || !str_starts_with($alias, $prefix)) {
        continue;
      }

      $path = $aliasManager->getPathByAlias($alias);
      $source = NULL;
      if (preg_match('#^/node/(\d+)$#', $path, $m) === 1) {
        $candidate = Node::load((int) $m[1]);
        // Chỉ nuốt node `page` — không đụng vào bài viết loại khác đang giữ alias đó.
        if ($candidate instanceof Node && $candidate->bundle() === 'page' && (int) $candidate->id() !== (int) $node->id()) {
          $source = $candidate;
        }
      }
      if ($source === NULL) {
        continue;
      }

      $body = $source->get('body')->isEmpty() ? NULL : $source->get('body')->first()->getValue();
      $source->delete();

      if ($body !== NULL && $node->hasField('body')) {
        $node->set('body', $body);
      }
      // Ô Đường dẫn nay chỉ dành cho link NGOÀI; alias nội bộ do pathauto lo.
      $node->set('field_link', NULL);
      $node->get('path')->pathauto = PathautoState::CREATE;
      $node->save();

      $done[] = $node->bundle() . ' #' . $node->id() . ' <- page #' . $source->id() . ' (' . $alias . ')';
    }
  }

  return $done === []
    ? 'Không có bài viết nào cần gộp.'
    : "Đã gộp bài viết vào chính node:\n - " . implode("\n - ", $done);
}

/**
 * Gắn danh mục cho từng ô Dịch vụ, thay cho đường dẫn gõ tay.
 *
 * Ô Dịch vụ trên trang chủ nay tự sinh /dich-vu/<slug danh mục> từ ô "Danh mục dịch
 * vụ". Hook chuyển đường dẫn đang gõ tay thành tham chiếu danh mục tương ứng rồi
 * xoá đường dẫn đó đi — giữ nguyên link người dùng thấy, bớt một chỗ gõ sai.
 *
 * Không khớp được danh mục nào thì GIỮ NGUYÊN ô Đường dẫn: thà để cấu hình cũ chạy
 * tiếp còn hơn làm ô trên trang chủ mất link.
 */
function nidqc_content_deploy_link_service_categories(): string {
  $slugger = \Drupal::service('nidqc_content.slugger');
  $storage = \Drupal::entityTypeManager()->getStorage('node');

  $terms = [];
  foreach (\Drupal::entityTypeManager()->getStorage('taxonomy_term')->loadByProperties(['vid' => 'service_category']) as $term) {
    $terms[$slugger->slug((string) $term->label())] = $term;
  }

  $done = [];
  $skipped = [];
  $ids = $storage->getQuery()->accessCheck(FALSE)->condition('type', 'service')->execute();
  foreach ($storage->loadMultiple($ids) as $node) {
    if (!$node->get('field_service_category')->isEmpty()) {
      continue;
    }

    $uri = $node->get('field_link')->isEmpty() ? '' : (string) $node->get('field_link')->uri;
    $alias = str_starts_with($uri, 'internal:') ? substr($uri, strlen('internal:')) : '';
    // Ưu tiên slug trên đường dẫn cũ; không có thì thử khớp theo chính tiêu đề ô.
    $slug = str_starts_with($alias, '/dich-vu/') ? substr($alias, strlen('/dich-vu/')) : $slugger->slug((string) $node->label());
    $term = $terms[trim($slug, '/')] ?? NULL;
    if ($term === NULL) {
      $skipped[] = $node->label() . ' (#' . $node->id() . ')';
      continue;
    }

    $node->set('field_service_category', $term->id());
    $node->set('field_link', NULL);
    $node->save();
    $done[] = $node->label() . ' -> ' . $term->label();
  }

  $report = $done === [] ? 'Không có ô Dịch vụ nào cần gắn danh mục.' : "Đã gắn danh mục:\n - " . implode("\n - ", $done);
  if ($skipped !== []) {
    $report .= "\nGIỮ NGUYÊN đường dẫn cũ (không khớp danh mục nào): " . implode(', ', $skipped);
  }

  return $report;
}

/**
 * Dọn các trang tĩnh /dich-vu/* đã chết.
 *
 * Từ khi mỗi dịch vụ trỏ sang DANH SÁCH bài viết (/dich-vu/<danh-muc>), 6 node `page`
 * mang alias /dich-vu/… không còn đường nào dẫn tới: route danh sách của Nuxt phủ
 * đúng các đường dẫn đó. Để lại chỉ làm mục "Nội dung" trong trang quản trị rối thêm.
 *
 * Chỉ đụng node có alias đúng MỘT cấp dưới /dich-vu/ — bài viết dịch vụ thật nằm ở
 * hai cấp (/dich-vu/<danh-muc>/<tieu-de>) và là bundle service_post, không dính.
 */
function nidqc_content_deploy_remove_dead_service_pages(): string {
  $aliasManager = \Drupal::service('path_alias.manager');
  $storage = \Drupal::entityTypeManager()->getStorage('node');
  $ids = $storage->getQuery()->accessCheck(FALSE)->condition('type', 'page')->execute();

  $done = [];
  foreach ($storage->loadMultiple($ids) as $node) {
    $alias = $aliasManager->getAliasByPath('/node/' . $node->id());
    if (preg_match('#^/dich-vu/[^/]+$#', $alias) !== 1) {
      continue;
    }
    $done[] = $node->label() . ' (' . $alias . ')';
    $node->delete();
  }

  return $done === []
    ? 'Không còn trang tĩnh /dich-vu/* nào.'
    : "Đã dọn trang tĩnh không còn đường dẫn tới:\n - " . implode("\n - ", $done);
}
