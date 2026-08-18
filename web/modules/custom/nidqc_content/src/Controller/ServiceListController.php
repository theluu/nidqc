<?php

declare(strict_types=1);

namespace Drupal\nidqc_content\Controller;

use Drupal\Core\Cache\CacheableJsonResponse;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\nidqc_content\NewsPresenter;
use Drupal\nidqc_content\Slugger;
use Drupal\taxonomy\TermInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Danh sách "Bài viết dịch vụ" của một danh mục, có phân trang.
 *
 * Feedback 08/2026: bấm vào một dịch vụ ở trang chủ phải ra DANH SÁCH BÀI VIẾT
 * (như /tin-hoat-dong của NIFC) chứ không phải một trang tĩnh.
 *
 * Danh mục nhận SLUG (/dich-vu/hieu-chuan) chứ không phải UUID: URL của khối dịch vụ
 * đã tồn tại từ trước dưới dạng slug, và pathauto cũng sinh alias bài viết bằng chính
 * tên term đã transliterate. Một nguồn slug duy nhất -> không có chỗ nào lệch nhau.
 */
final class ServiceListController implements ContainerInjectionInterface {

  private const DEFAULT_LIMIT = 12;
  private const MAX_LIMIT = 50;
  private const MAX_PAGE = 10000;

  /**
   * Khởi tạo controller danh sách bài viết dịch vụ.
   */
  public function __construct(
    private readonly EntityTypeManagerInterface $entityTypeManager,
    private readonly NewsPresenter $presenter,
    private readonly Slugger $slugger,
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
      $container->get('nidqc_content.slugger'),
      $container->get('logger.factory')->get('nidqc_content'),
    );
  }

  /**
   * Trả bài viết của một danh mục dịch vụ, kèm tổng số và danh sách danh mục.
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
      return $this->invalidField('cat', 'Danh mục dịch vụ phải là chuỗi.');
    }
    $catParam = trim($catParam);

    try {
      $terms = $this->terms();

      $category = NULL;
      if ($catParam !== '') {
        $category = $terms[$this->slug($catParam)] ?? NULL;
        if ($category === NULL) {
          // Slug lạ = dịch vụ không tồn tại. Trả 404 để Nuxt dựng trang 404 thật,
          // chứ không phải một danh sách rỗng trông như "dịch vụ chưa có bài".
          $response = new CacheableJsonResponse([
            'error' => ['code' => 'NOT_FOUND', 'message' => 'Không tìm thấy dịch vụ.'],
          ], 404);
          $response->addCacheableDependency($this->cacheability());
          return $response;
        }
      }

      $storage = $this->entityTypeManager->getStorage('node');
      $query = $storage->getQuery()
        ->accessCheck(TRUE)
        ->condition('type', 'service_post')
        ->condition('status', 1);
      if ($category !== NULL) {
        $query->condition('field_service_category.target_id', $category->id());
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
            $items[] = $this->listItem($nodes[$id]);
          }
        }
      }

      $payload = [
        'data' => $items,
        'meta' => ['total' => $total, 'page' => $page, 'limit' => $limit],
        'category' => $category !== NULL ? $this->categoryPayload($category, TRUE) : NULL,
      ];
      if (($params['categories'] ?? '') === '1') {
        $payload['categories'] = array_values(array_map(
          fn (TermInterface $term): array => $this->categoryPayload($term, FALSE),
          $terms,
        ));
      }

      $response = new CacheableJsonResponse($payload);
      $response->addCacheableDependency($this->cacheability());
      return $response;
    }
    catch (\Throwable $exception) {
      $this->logger->error('Không thể tải danh sách bài viết dịch vụ: @message', [
        '@message' => $exception->getMessage(),
      ]);
      return $this->errorResponse('INTERNAL_ERROR', 'Đã có lỗi xảy ra. Vui lòng thử lại sau.', 500);
    }
  }

  /**
   * Item danh sách: dùng lại đúng hình dạng của Tin tức để frontend chung component.
   */
  private function listItem($node): array {
    $item = $this->presenter->listItem($node, NewsPresenter::STYLE_CARD);
    // listItem() đọc field_category/field_tag của Tin tức — service_post không có,
    // nên nhãn trên thẻ lấy từ danh mục dịch vụ.
    $term = $node->get('field_service_category')->entity;
    $item['tag'] = $term?->label() ?? '';
    $item['category'] = $item['tag'];

    return $item;
  }

  /**
   * Danh mục dịch vụ theo slug, đã sắp theo thứ tự quản trị viên đặt.
   *
   * @return \Drupal\taxonomy\TermInterface[]
   *   Khoá là slug của tên term.
   */
  private function terms(): array {
    $terms = $this->entityTypeManager->getStorage('taxonomy_term')
      ->loadByProperties(['vid' => 'service_category']);
    usort($terms, static fn (TermInterface $a, TermInterface $b) => $a->getWeight() <=> $b->getWeight() ?: strcmp($a->label(), $b->label()));

    $bySlug = [];
    foreach ($terms as $term) {
      $bySlug[$this->slug($term->label())] = $term;
    }

    return $bySlug;
  }

  /**
   * Mô tả một danh mục cho frontend.
   *
   * $withDescription: chỉ trang đang mở mới cần phần giới thiệu (HTML đầy đủ); danh
   * sách tab thì không, gửi kèm chỉ tổ phình response.
   */
  private function categoryPayload(TermInterface $term, bool $withDescription): array {
    $payload = [
      'id' => $term->uuid(),
      'label' => $term->label(),
      'slug' => $this->slug($term->label()),
      'url' => '/dich-vu/' . $this->slug($term->label()),
    ];
    if ($withDescription) {
      $description = trim((string) $term->getDescription());
      $payload['description'] = $description !== ''
        ? $this->presenter->optimiseEmbeddedImages($description)
        : '';
    }

    return $payload;
  }

  /**
   * Tên term -> slug trên URL.
   *
   * Phải khớp với alias pathauto sinh ra (/dich-vu/<danh-muc>/<tieu-de>), với đường
   * dẫn mà HomeBlocksController sinh cho ô Dịch vụ ở trang chủ, và với categorySlug()
   * phía Nuxt — nên quy tắc bỏ dấu nằm ở Slugger dùng chung, không chép lại ở đây.
   */
  private function slug(string $value): string {
    return $this->slugger->slug($value);
  }

  /**
   * Cache metadata dùng chung cho mọi response thành công.
   */
  private function cacheability(): CacheableMetadata {
    return (new CacheableMetadata())
      ->setCacheTags(['node_list:service_post', 'taxonomy_term_list:service_category'])
      ->setCacheContexts([
        'url.query_args:cat',
        'url.query_args:page',
        'url.query_args:limit',
        'url.query_args:categories',
      ]);
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
    $error = ['code' => $code, 'message' => $message];
    if ($details !== []) {
      $error['details'] = $details;
    }
    $response = new CacheableJsonResponse(['error' => $error], $status);
    $response->addCacheableDependency((new CacheableMetadata())->setCacheMaxAge(0));

    return $response;
  }

}
