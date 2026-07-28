<?php

declare(strict_types=1);

namespace Drupal\nidqc_content\Controller;

use Drupal\Core\Cache\CacheableJsonResponse;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\nidqc_content\NewsPresenter;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Thư viện Video / Hình ảnh cho trang chủ — mỗi bài kèm sẵn toàn bộ media.
 *
 * Trả LUÔN danh sách media của từng bài trong cùng một response: slider ngoài trang
 * chủ chỉ cần thumbnail, nhưng bấm vào là mở lightbox xem cả bộ ảnh/video của bài đó.
 * Nếu tách hai endpoint thì mỗi lần mở lightbox lại là một vòng gọi Drupal nữa, đúng
 * thứ yêu cầu "không tạo query trùng lặp" muốn tránh.
 */
final class NewsMediaController implements ContainerInjectionInterface {

  private const DEFAULT_LIMIT = 12;
  private const MAX_LIMIT = 24;

  /**
   * Khởi tạo controller Thư viện media.
   */
  public function __construct(
    private readonly EntityTypeManagerInterface $entityTypeManager,
    private readonly NewsPresenter $presenter,
    private readonly LoggerInterface $logger,
  ) {
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): self {
    return new self(
      $container->get('entity_type.manager'),
      $container->get('nidqc_content.news_presenter'),
      $container->get('logger.factory')->get('nidqc_content'),
    );
  }

  /**
   * Danh sách bài thuộc danh mục Videos / Hình ảnh, kèm media đã chuẩn hoá.
   */
  public function list(Request $request): CacheableJsonResponse {
    $params = $request->query->all();
    $unknown = array_diff(array_keys($params), ['limit', 'kind']);
    if ($unknown !== []) {
      return $this->errorResponse('INVALID_PARAMETER', 'Yêu cầu có tham số không hợp lệ.', 400);
    }

    $limit = self::DEFAULT_LIMIT;
    if (array_key_exists('limit', $params)) {
      if (!is_string($params['limit']) || !preg_match('/^\d+$/', $params['limit'])) {
        return $this->errorResponse('INVALID_PARAMETER', 'Số kết quả phải là số nguyên.', 400);
      }
      $limit = (int) $params['limit'];
      if ($limit < 1 || $limit > self::MAX_LIMIT) {
        return $this->errorResponse('INVALID_PARAMETER', 'Số kết quả phải từ 1 đến ' . self::MAX_LIMIT . '.', 400);
      }
    }

    $kind = $params['kind'] ?? '';
    if (!is_string($kind) || !in_array($kind, ['', 'video', 'image'], TRUE)) {
      return $this->errorResponse('INVALID_PARAMETER', 'Tham số kind chỉ nhận video hoặc image.', 400);
    }

    try {
      $mediaIds = $this->presenter->mediaCategoryTermIds();
      $wanted = match ($kind) {
        'video' => array_filter([$mediaIds[NewsPresenter::CATEGORY_VIDEO] ?? NULL]),
        'image' => array_filter([$mediaIds[NewsPresenter::CATEGORY_IMAGE] ?? NULL]),
        default => array_values($mediaIds),
      };
      if ($wanted === []) {
        // Chưa tạo danh mục thư viện -> chưa có nội dung, không phải lỗi.
        return $this->buildResponse([]);
      }

      $storage = $this->entityTypeManager->getStorage('node');
      $ids = $storage->getQuery()
        ->accessCheck(TRUE)
        ->condition('type', 'news')
        ->condition('status', 1)
        ->condition('field_category.target_id', $wanted, 'IN')
        ->sort('created', 'DESC')
        ->range(0, $limit)
        ->execute();

      $items = [];
      if ($ids !== []) {
        $nodes = $storage->loadMultiple($ids);
        foreach ($ids as $id) {
          if (!isset($nodes[$id])) {
            continue;
          }
          $entry = $this->buildEntry($nodes[$id]);
          // Bài chưa gắn media nào thì không đẩy ra slider (ô trống vô nghĩa).
          if ($entry !== NULL) {
            $items[] = $entry;
          }
        }
      }

      return $this->buildResponse($items);
    }
    catch (\Throwable $exception) {
      $this->logger->error('Không thể tải Thư viện media: @message', [
        '@message' => $exception->getMessage(),
      ]);
      return $this->errorResponse('INTERNAL_ERROR', 'Đã có lỗi xảy ra. Vui lòng thử lại sau.', 500);
    }
  }

  /**
   * Dựng một mục thư viện, NULL nếu bài không có media nào dùng được.
   */
  private function buildEntry($node): ?array {
    $media = $this->presenter->mediaItems($node);
    if ($media === []) {
      return NULL;
    }

    // Ảnh bìa: ưu tiên ảnh đại diện của bài, không có thì lấy thumbnail của media
    // đầu tiên (ảnh đầu bộ, hoặc thumbnail YouTube).
    $cover = $this->presenter->imageUrl($node, NewsPresenter::STYLE_CARD);
    if ($cover === NULL) {
      foreach ($media as $item) {
        if (!empty($item['thumbnail'])) {
          $cover = $item['thumbnail'];
          break;
        }
      }
    }

    return [
      'id' => (int) $node->id(),
      'title' => $node->label(),
      'created' => gmdate(DATE_ATOM, (int) $node->getCreatedTime()),
      'alias' => $node->toUrl()->toString(),
      'kind' => $this->presenter->mediaKind($node),
      'thumbnail' => $cover,
      'count' => count($media),
      'items' => $media,
    ];
  }

  /**
   * Đóng gói response kèm cache metadata.
   */
  private function buildResponse(array $items): CacheableJsonResponse {
    $response = new CacheableJsonResponse([
      'data' => $items,
      'meta' => ['total' => count($items)],
    ]);
    $response->addCacheableDependency((new CacheableMetadata())
      // file_list: đổi/xoá file media là response phải mới lại.
      ->setCacheTags(['node_list:news', 'taxonomy_term_list:news_category', 'file_list'])
      ->setCacheContexts(['url.query_args:limit', 'url.query_args:kind']));
    return $response;
  }

  /**
   * Tạo response lỗi theo API_ERROR_STANDARD.
   */
  private function errorResponse(string $code, string $message, int $status): CacheableJsonResponse {
    $response = new CacheableJsonResponse([
      'error' => ['code' => $code, 'message' => $message],
    ], $status);
    $response->addCacheableDependency((new CacheableMetadata())->setCacheMaxAge(0));
    return $response;
  }

}
