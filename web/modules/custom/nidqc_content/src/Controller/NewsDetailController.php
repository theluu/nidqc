<?php

declare(strict_types=1);

namespace Drupal\nidqc_content\Controller;

use Drupal\Core\Cache\CacheableJsonResponse;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\nidqc_content\NewsPresenter;
use Drupal\node\NodeInterface;
use Drupal\path_alias\AliasManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Trả trọn gói dữ liệu một trang chi tiết Tin tức trong MỘT request.
 *
 * Vì sao có controller này: frontend trước đây phải dựng map alias->nid bằng 16
 * request JSON:API (JSON:API không lọc được trên computed field 'path'), rồi gọi
 * thêm 3 request nữa cho node + tin liên quan + tin mới nhất. Đo thực tế: dựng map
 * mất 13.8s khi cache Drupal nguội, 1.07s khi ấm — mỗi lần cache Nitro hết hạn là
 * một người dùng phải chờ. Ở đây alias tra thẳng bảng path_alias (1 query có index)
 * và cả ba khối dữ liệu gộp vào một response.
 */
final class NewsDetailController implements ContainerInjectionInterface {

  /**
   * Số tin liên quan (cùng chuyên mục) trả về.
   */
  private const RELATED_LIMIT = 3;

  /**
   * Số tin mới nhất trả về cho sidebar.
   */
  private const LATEST_LIMIT = 5;

  /**
   * Độ dài alias tối đa chấp nhận (chặn request rác).
   */
  private const MAX_ALIAS_LENGTH = 512;

  /**
   * Khởi tạo controller chi tiết Tin tức.
   */
  public function __construct(
    private readonly EntityTypeManagerInterface $entityTypeManager,
    private readonly AliasManagerInterface $aliasManager,
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
      $container->get('path_alias.manager'),
      $container->get('nidqc_content.news_presenter'),
      $container->get('logger.factory')->get('nidqc_content'),
    );
  }

  /**
   * Trả node + tin liên quan + tin mới nhất theo alias.
   */
  public function detail(Request $request): CacheableJsonResponse {
    $params = $request->query->all();
    $unknown = array_diff(array_keys($params), ['alias']);
    if ($unknown !== []) {
      return $this->errorResponse(
        'INVALID_PARAMETER',
        'Yêu cầu có tham số không hợp lệ.',
        400,
        [['field' => (string) reset($unknown), 'issue' => 'Tham số không được hỗ trợ.']],
      );
    }

    if (!array_key_exists('alias', $params)) {
      return $this->errorResponse(
        'MISSING_PARAMETER',
        'Thiếu đường dẫn bài viết.',
        400,
        [['field' => 'alias', 'issue' => 'Đây là tham số bắt buộc.']],
      );
    }

    if (!is_string($params['alias'])) {
      return $this->errorResponse(
        'INVALID_PARAMETER',
        'Đường dẫn bài viết không hợp lệ.',
        400,
        [['field' => 'alias', 'issue' => 'Đường dẫn phải là chuỗi.']],
      );
    }

    $alias = '/' . ltrim(trim($params['alias']), '/');
    if ($alias === '/' || strlen($alias) > self::MAX_ALIAS_LENGTH) {
      return $this->errorResponse(
        'INVALID_PARAMETER',
        'Đường dẫn bài viết không hợp lệ.',
        400,
        [['field' => 'alias', 'issue' => 'Đường dẫn rỗng hoặc quá dài.']],
      );
    }

    try {
      $node = $this->loadNewsByAlias($alias);
      if ($node === NULL) {
        // 404 vẫn cacheable theo node_list:news — bài mới xuất bản sẽ tự làm mới.
        $response = new CacheableJsonResponse([
          'error' => [
            'code' => 'NOT_FOUND',
            'message' => 'Không tìm thấy bài viết.',
          ],
        ], 404);
        $response->addCacheableDependency((new CacheableMetadata())
          ->setCacheTags(['node_list:news'])
          ->setCacheContexts(['url.query_args:alias']));
        return $response;
      }

      $category = $node->get('field_category')->entity;

      $related = $this->listNews((int) $node->id(), $category?->id(), self::RELATED_LIMIT);
      $latest = $this->listNews((int) $node->id(), NULL, self::LATEST_LIMIT);
      // Chuyên mục chưa đủ bài thì lấp bằng tin mới nhất (giữ nguyên hành vi cũ).
      if ($related === []) {
        $related = array_slice($latest, 0, self::RELATED_LIMIT);
      }

      $tag = trim((string) $node->get('field_tag')->value);
      $body = '';
      if (!$node->get('body')->isEmpty()) {
        $body = $this->presenter->optimiseEmbeddedImages((string) $node->get('body')->processed);
      }

      $payload = [
        'node' => [
          'nid' => (int) $node->id(),
          'title' => $node->label(),
          'created' => gmdate(DATE_ATOM, (int) $node->getCreatedTime()),
          'tag' => $tag !== '' ? $tag : ($category?->label() ?? ''),
          'category' => $category?->label() ?? '',
          'image' => $this->presenter->imageUrl($node, NewsPresenter::STYLE_ARTICLE),
          'body' => $body,
          'attachments' => $this->presenter->attachments($node),
        ],
        'related' => $related,
        'latest' => $latest,
      ];

      $response = new CacheableJsonResponse(['data' => $payload]);
      $cacheability = (new CacheableMetadata())
        ->setCacheTags(['node_list:news'])
        ->setCacheContexts(['url.query_args:alias']);
      $cacheability->addCacheableDependency($node);
      $response->addCacheableDependency($cacheability);
      return $response;
    }
    catch (\Throwable $exception) {
      $this->logger->error('Không thể tải chi tiết Tin tức @alias: @message', [
        '@alias' => $alias,
        '@message' => $exception->getMessage(),
      ]);
      return $this->errorResponse(
        'INTERNAL_ERROR',
        'Đã có lỗi xảy ra. Vui lòng thử lại sau.',
        500,
      );
    }
  }

  /**
   * Tra alias -> node Tin tức đã xuất bản.
   */
  private function loadNewsByAlias(string $alias): ?NodeInterface {
    $path = $this->aliasManager->getPathByAlias($alias);
    // Không khớp alias nào thì getPathByAlias trả lại chính chuỗi đầu vào.
    if ($path === $alias || !preg_match('#^/node/(\d+)$#', $path, $matches)) {
      return NULL;
    }

    $node = $this->entityTypeManager->getStorage('node')->load((int) $matches[1]);
    if (!$node instanceof NodeInterface) {
      return NULL;
    }
    if ($node->bundle() !== 'news' || !$node->isPublished() || !$node->access('view')) {
      return NULL;
    }
    return $node;
  }

  /**
   * Liệt kê tin mới nhất, tuỳ chọn giới hạn theo chuyên mục.
   */
  private function listNews(int $excludeNid, ?string $categoryId, int $limit): array {
    $storage = $this->entityTypeManager->getStorage('node');
    $query = $storage->getQuery()
      ->accessCheck(TRUE)
      ->condition('type', 'news')
      ->condition('status', 1)
      ->condition('nid', $excludeNid, '<>')
      ->sort('created', 'DESC')
      ->range(0, $limit);
    if ($categoryId !== NULL) {
      $query->condition('field_category.target_id', $categoryId);
    }

    $ids = $query->execute();
    if ($ids === []) {
      return [];
    }

    $nodes = $storage->loadMultiple($ids);
    $items = [];
    foreach ($ids as $id) {
      if (isset($nodes[$id])) {
        $items[] = $this->presenter->listItem($nodes[$id], NewsPresenter::STYLE_THUMB);
      }
    }
    return $items;
  }

  /**
   * Tạo response lỗi theo API_ERROR_STANDARD.
   */
  private function errorResponse(
    string $code,
    string $message,
    int $status,
    array $details = [],
  ): CacheableJsonResponse {
    $error = [
      'code' => $code,
      'message' => $message,
    ];
    if ($details !== []) {
      $error['details'] = $details;
    }
    $response = new CacheableJsonResponse(['error' => $error], $status);
    $response->addCacheableDependency((new CacheableMetadata())->setCacheMaxAge(0));
    return $response;
  }

}
