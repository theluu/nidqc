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
 * Tìm kiếm nội dung Tin tức đã xuất bản.
 */
final class NewsSearchController implements ContainerInjectionInterface {

  private const DEFAULT_LIMIT = 12;
  private const MAX_LIMIT = 100;
  private const MAX_PAGE = 10000;
  private const MIN_QUERY_LENGTH = 2;
  private const MAX_QUERY_LENGTH = 200;

  /**
   * Khởi tạo controller tìm kiếm Tin tức.
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
   * Trả danh sách Tin tức khớp tiêu đề hoặc nội dung.
   */
  public function search(Request $request): CacheableJsonResponse {
    $params = $request->query->all();
    $unknown = array_diff(array_keys($params), ['q', 'page', 'limit']);
    if ($unknown !== []) {
      return $this->errorResponse(
        'INVALID_PARAMETER',
        'Yêu cầu có tham số không hợp lệ.',
        400,
        [['field' => (string) reset($unknown), 'issue' => 'Tham số không được hỗ trợ.']],
      );
    }

    if (!array_key_exists('q', $params)) {
      return $this->errorResponse(
        'MISSING_PARAMETER',
        'Vui lòng nhập từ khóa tìm kiếm.',
        400,
        [['field' => 'q', 'issue' => 'Đây là tham số bắt buộc.']],
      );
    }

    if (!is_string($params['q'])) {
      return $this->invalidField('q', 'Từ khóa phải là chuỗi.');
    }
    $keyword = trim($params['q']);
    $length = mb_strlen($keyword);
    if ($length < self::MIN_QUERY_LENGTH || $length > self::MAX_QUERY_LENGTH) {
      return $this->invalidField('q', 'Từ khóa phải có từ 2 đến 200 ký tự.');
    }

    $page = $this->parseInteger($params, 'page', 0, 0, self::MAX_PAGE);
    if ($page === NULL) {
      return $this->invalidField('page', 'Trang phải là số nguyên từ 0 đến 10000.');
    }
    $limit = $this->parseInteger(
      $params,
      'limit',
      self::DEFAULT_LIMIT,
      1,
      self::MAX_LIMIT,
    );
    if ($limit === NULL) {
      return $this->invalidField('limit', 'Số kết quả phải là số nguyên từ 1 đến 100.');
    }

    try {
      $storage = $this->entityTypeManager->getStorage('node');
      $query = $storage->getQuery()
        ->accessCheck(TRUE)
        ->condition('type', 'news')
        ->condition('status', 1);
      $matches = $query->orConditionGroup()
        ->condition('title', $keyword, 'CONTAINS')
        ->condition('body.value', $keyword, 'CONTAINS');
      $query->condition($matches);

      // Bài Thư viện media (Videos, Hình ảnh) không phải tin đọc được nên không
      // đưa vào kết quả tìm kiếm. notExists để bài chưa chọn danh mục không bị
      // loại oan (NOT IN trên LEFT JOIN cho NULL).
      $mediaIds = $this->presenter->mediaCategoryTermIds();
      if ($mediaIds !== []) {
        $query->condition(
          $query->orConditionGroup()
            ->condition('field_category.target_id', array_values($mediaIds), 'NOT IN')
            ->notExists('field_category')
        );
      }

      $total = (int) (clone $query)->count()->execute();
      $ids = $query
        ->sort('created', 'DESC')
        ->range($page * $limit, $limit)
        ->execute();
      $nodes = $storage->loadMultiple($ids);

      $data = [];
      foreach ($ids as $id) {
        if (!isset($nodes[$id])) {
          continue;
        }
        // Dùng chung NewsPresenter với /tin-tuc để card kết quả giống hệt trang
        // danh sách: cùng ảnh qua image style (trước đây trả file gốc ~170KB).
        $item = $this->presenter->listItem($nodes[$id], NewsPresenter::STYLE_CARD);
        $data[] = [
          'id' => $item['id'],
          'title' => $item['title'],
          'created' => $item['created'],
          'tag' => $item['tag'],
          'image' => $item['image'],
          'url' => $item['alias'],
        ];
      }

      $response = new CacheableJsonResponse([
        'data' => $data,
        'meta' => [
          'total' => $total,
          'page' => $page,
          'limit' => $limit,
        ],
      ]);
      $response->addCacheableDependency((new CacheableMetadata())
        ->setCacheTags(['node_list:news'])
        ->setCacheContexts([
          'url.query_args:q',
          'url.query_args:page',
          'url.query_args:limit',
        ]));
      return $response;
    }
    catch (\Throwable $exception) {
      $this->logger->error('Không thể tìm kiếm Tin tức: @message', [
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
      'Tham số tìm kiếm không hợp lệ.',
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
