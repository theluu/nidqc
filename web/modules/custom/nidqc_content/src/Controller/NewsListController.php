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
 * Danh sách Tin tức có phân trang, tổng số và danh mục — trong MỘT request.
 *
 * Vì sao có controller này: JSON:API không trả tổng số bản ghi và chặn page[limit]
 * ở 50, nên frontend phải LIỆT KÊ hết rồi cộng (countNews) — 18 request chỉ để đếm
 * 705 tin, đo được 5.5s cho một lần mở /tin-tuc khi cache nguội. Ở đây tổng số là
 * một câu COUNT, và danh mục + danh sách trả kèm luôn.
 */
final class NewsListController implements ContainerInjectionInterface {

  private const DEFAULT_LIMIT = 12;
  private const MAX_LIMIT = 50;
  private const MAX_PAGE = 10000;

  /**
   * Khởi tạo controller danh sách Tin tức.
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
   * Trả danh sách tin của một trang, kèm tổng số và (tuỳ chọn) danh mục.
   */
  public function list(Request $request): CacheableJsonResponse {
    $params = $request->query->all();
    $unknown = array_diff(array_keys($params), ['cat', 'page', 'limit', 'categories']);
    if ($unknown !== []) {
      return $this->errorResponse(
        'INVALID_PARAMETER',
        'Yêu cầu có tham số không hợp lệ.',
        400,
        [['field' => (string) reset($unknown), 'issue' => 'Tham số không được hỗ trợ.']],
      );
    }

    $page = $this->parseInteger($params, 'page', 0, 0, self::MAX_PAGE);
    if ($page === NULL) {
      return $this->invalidField('page', 'Trang phải là số nguyên từ 0 đến 10000.');
    }
    $limit = $this->parseInteger($params, 'limit', self::DEFAULT_LIMIT, 1, self::MAX_LIMIT);
    if ($limit === NULL) {
      return $this->invalidField('limit', 'Số kết quả phải là số nguyên từ 1 đến 50.');
    }

    $catParam = $params['cat'] ?? '';
    if (!is_string($catParam)) {
      return $this->invalidField('cat', 'Chuyên mục phải là chuỗi.');
    }

    try {
      // Chuyên mục nhận UUID (trang /tin-tuc) hoặc tên (trang chủ tách hero/thông
      // báo theo tên chuyên mục), phân tách bằng dấu phẩy.
      $termIds = $this->resolveCategories($catParam);
      if ($termIds === NULL) {
        // Có lọc nhưng không khớp chuyên mục nào -> danh sách rỗng, không phải lỗi.
        return $this->buildResponse([], 0, $page, $limit, $params);
      }

      $storage = $this->entityTypeManager->getStorage('node');
      $query = $storage->getQuery()
        ->accessCheck(TRUE)
        ->condition('type', 'news')
        ->condition('status', 1);
      if ($termIds !== []) {
        $query->condition('field_category.target_id', $termIds, 'IN');
      }

      $total = (int) (clone $query)->count()->execute();
      $ids = $query
        ->sort('created', 'DESC')
        ->range($page * $limit, $limit)
        ->execute();

      $items = [];
      if ($ids !== []) {
        $nodes = $storage->loadMultiple($ids);
        foreach ($ids as $id) {
          if (isset($nodes[$id])) {
            $items[] = $this->presenter->listItem($nodes[$id], NewsPresenter::STYLE_CARD);
          }
        }
      }

      return $this->buildResponse($items, $total, $page, $limit, $params);
    }
    catch (\Throwable $exception) {
      $this->logger->error('Không thể tải danh sách Tin tức: @message', [
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
   * Đổi tham số cat (UUID hoặc tên, CSV) thành danh sách term id.
   *
   * Trả mảng rỗng khi không lọc, NULL khi có lọc nhưng không khớp chuyên mục nào.
   */
  private function resolveCategories(string $catParam): ?array {
    $wanted = array_filter(array_map('trim', explode(',', $catParam)), static fn ($v) => $v !== '' && $v !== 'all');
    if ($wanted === []) {
      return [];
    }

    $storage = $this->entityTypeManager->getStorage('taxonomy_term');
    $terms = $storage->loadByProperties(['vid' => 'news_category']);
    $ids = [];
    foreach ($terms as $term) {
      if (in_array($term->uuid(), $wanted, TRUE) || in_array($term->label(), $wanted, TRUE)) {
        $ids[] = $term->id();
      }
    }
    return $ids === [] ? NULL : $ids;
  }

  /**
   * Danh sách chuyên mục Tin tức theo thứ tự sắp xếp trong Drupal.
   */
  private function categories(): array {
    $terms = $this->entityTypeManager->getStorage('taxonomy_term')
      ->loadByProperties(['vid' => 'news_category']);
    $items = [];
    foreach ($terms as $term) {
      $items[] = [
        'id' => $term->uuid(),
        'label' => $term->label(),
        'weight' => (int) $term->getWeight(),
      ];
    }
    usort($items, static fn (array $a, array $b) => $a['weight'] <=> $b['weight']);
    return array_map(
      static fn (array $item) => ['id' => $item['id'], 'label' => $item['label']],
      $items,
    );
  }

  /**
   * Đóng gói response kèm cache metadata.
   */
  private function buildResponse(
    array $items,
    int $total,
    int $page,
    int $limit,
    array $params,
  ): CacheableJsonResponse {
    $payload = [
      'data' => $items,
      'meta' => [
        'total' => $total,
        'page' => $page,
        'limit' => $limit,
      ],
    ];
    if (($params['categories'] ?? '') === '1') {
      $payload['categories'] = $this->categories();
    }

    $response = new CacheableJsonResponse($payload);
    $response->addCacheableDependency((new CacheableMetadata())
      ->setCacheTags(['node_list:news', 'taxonomy_term_list:news_category'])
      ->setCacheContexts([
        'url.query_args:cat',
        'url.query_args:page',
        'url.query_args:limit',
        'url.query_args:categories',
      ]));
    return $response;
  }

  /**
   * Đọc một số nguyên bị giới hạn.
   */
  private function parseInteger(
    array $params,
    string $field,
    int $default,
    int $minimum,
    int $maximum,
  ): ?int {
    if (!array_key_exists($field, $params)) {
      return $default;
    }
    if (!is_string($params[$field]) || !preg_match('/^\d+$/', $params[$field])) {
      return NULL;
    }
    $value = (int) $params[$field];
    return $value >= $minimum && $value <= $maximum ? $value : NULL;
  }

  /**
   * Trả lỗi validation cho một field.
   */
  private function invalidField(string $field, string $issue): CacheableJsonResponse {
    return $this->errorResponse(
      'INVALID_PARAMETER',
      'Tham số danh sách không hợp lệ.',
      400,
      [['field' => $field, 'issue' => $issue]],
    );
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
