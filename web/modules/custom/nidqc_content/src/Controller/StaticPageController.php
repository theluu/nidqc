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
 * Trả nội dung một TRANG TĨNH theo alias.
 *
 * Feedback 08/2026 yêu cầu mỗi dịch vụ, mỗi mục "Danh mục năng lực" và mỗi hoạt
 * động chuyên môn đều bấm được sang một bài viết riêng. Những bài đó là node
 * `page` với alias nhiều cấp (/dich-vu/…, /danh-muc-nang-luc/…). JSON:API không
 * lọc được trên computed field 'path' nên đường cũ (fetchPageByAlias) phải liệt kê
 * toàn bộ node page rồi khớp alias phía JS — sẽ hỏng ngay khi số trang vượt
 * page[limit]. Ở đây alias tra thẳng bảng path_alias, đúng một query có index.
 *
 * KHÔNG phục vụ bundle 'news': tin tức đã có NewsDetailController với tin liên
 * quan và tin mới nhất đi kèm.
 */
final class StaticPageController implements ContainerInjectionInterface {

  /**
   * Độ dài alias tối đa chấp nhận (chặn request rác).
   */
  private const MAX_ALIAS_LENGTH = 512;

  /**
   * Các bundle được phép trả qua endpoint này.
   *
   * Danh sách trắng chứ không phải danh sách đen: thêm content type mới không
   * được vô tình phơi nội dung nội bộ (VD contact_submission) ra API công khai.
   */
  private const ALLOWED_BUNDLES = ['page', 'document', 'faq', 'department', 'equipment', 'certificate', 'project', 'service_post'];

  /**
   * Khởi tạo controller trang tĩnh.
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
   * Trả tiêu đề + thân bài của trang tĩnh khớp alias.
   */
  public function detail(Request $request): CacheableJsonResponse {
    $params = $request->query->all();
    $unknown = array_diff(array_keys($params), ['alias']);
    if ($unknown !== []) {
      return $this->errorResponse('INVALID_PARAMETER', 'Yêu cầu có tham số không hợp lệ.', 400);
    }
    if (!array_key_exists('alias', $params) || !is_string($params['alias'])) {
      return $this->errorResponse('MISSING_PARAMETER', 'Thiếu đường dẫn trang.', 400);
    }

    $alias = '/' . ltrim(trim($params['alias']), '/');
    if ($alias === '/' || strlen($alias) > self::MAX_ALIAS_LENGTH) {
      return $this->errorResponse('INVALID_PARAMETER', 'Đường dẫn trang không hợp lệ.', 400);
    }

    // Cache theo node_list: trang mới xuất bản là 404 cũ tự hết hiệu lực.
    $cacheability = (new CacheableMetadata())
      ->setCacheTags(['node_list'])
      ->setCacheContexts(['url.query_args:alias']);

    try {
      $node = $this->loadByAlias($alias);
      if ($node === NULL) {
        $response = new CacheableJsonResponse([
          'error' => ['code' => 'NOT_FOUND', 'message' => 'Không tìm thấy trang.'],
        ], 404);
        $response->addCacheableDependency($cacheability);
        return $response;
      }

      $body = '';
      foreach (['body', 'field_description', 'field_answer'] as $field) {
        if ($node->hasField($field) && !$node->get($field)->isEmpty()) {
          $body = $this->presenter->optimiseEmbeddedImages((string) $node->get($field)->processed);
          break;
        }
      }

      $cacheability->addCacheableDependency($node);
      $response = new CacheableJsonResponse([
        'data' => [
          'nid' => (int) $node->id(),
          'type' => $node->bundle(),
          'title' => $node->label(),
          'created' => gmdate(DATE_ATOM, (int) $node->getCreatedTime()),
          'changed' => gmdate(DATE_ATOM, (int) $node->getChangedTime()),
          'image' => $this->presenter->imageUrl($node, NewsPresenter::STYLE_ARTICLE),
          'body' => $body,
          'attachments' => $this->presenter->attachments($node),
        ],
      ]);
      $response->addCacheableDependency($cacheability);
      return $response;
    }
    catch (\Throwable $exception) {
      $this->logger->error('Không thể tải trang tĩnh @alias: @message', [
        '@alias' => $alias,
        '@message' => $exception->getMessage(),
      ]);
      return $this->errorResponse('INTERNAL_ERROR', 'Đã có lỗi xảy ra. Vui lòng thử lại sau.', 500);
    }
  }

  /**
   * Tra alias -> node đã xuất bản thuộc bundle được phép.
   */
  private function loadByAlias(string $alias): ?NodeInterface {
    $path = $this->aliasManager->getPathByAlias($alias);
    // Không khớp alias nào thì getPathByAlias trả lại chính chuỗi đầu vào.
    if ($path === $alias || !preg_match('#^/node/(\d+)$#', $path, $matches)) {
      return NULL;
    }

    $node = $this->entityTypeManager->getStorage('node')->load((int) $matches[1]);
    if (!$node instanceof NodeInterface) {
      return NULL;
    }
    if (!in_array($node->bundle(), self::ALLOWED_BUNDLES, TRUE)) {
      return NULL;
    }
    if (!$node->isPublished() || !$node->access('view')) {
      return NULL;
    }

    return $node;
  }

  /**
   * Trả lỗi theo đúng khuôn dạng chung của API.
   */
  private function errorResponse(string $code, string $message, int $status): CacheableJsonResponse {
    $response = new CacheableJsonResponse([
      'error' => ['code' => $code, 'message' => $message],
    ], $status);
    $response->addCacheableDependency((new CacheableMetadata())->setCacheMaxAge(0));

    return $response;
  }

}
