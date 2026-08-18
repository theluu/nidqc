<?php

declare(strict_types=1);

namespace Drupal\nidqc_content\Controller;

use Drupal\Core\Cache\CacheableJsonResponse;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\nidqc_content\NewsPresenter;
use Drupal\nidqc_content\Slugger;
use Drupal\node\NodeInterface;
use Drupal\path_alias\AliasManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Gom mọi khối nội dung tĩnh của trang chủ vào MỘT request.
 *
 * Vì sao không dùng JSON:API như trước: trang chủ phải gọi 6 endpoint riêng
 * (service, capability, expertise, banner, web_link, office) cho mỗi lần render, và
 * — quan trọng hơn — JSON:API chỉ trả URL FILE GỐC. Ảnh admin tải lên tới 1.5MB;
 * dựng URL image style bằng tay ở frontend thì thiếu tham số itok nên Drupal trả
 * 404. Ở đây ảnh đi qua NewsPresenter::imageUrl() nên có sẵn derivative đúng kích
 * thước của từng khối.
 */
final class HomeBlocksController implements ContainerInjectionInterface {

  /**
   * Khởi tạo controller khối trang chủ.
   */
  public function __construct(
    private readonly EntityTypeManagerInterface $entityTypeManager,
    private readonly NewsPresenter $presenter,
    private readonly Slugger $slugger,
    private readonly AliasManagerInterface $aliasManager,
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
      $container->get('path_alias.manager'),
      $container->get('logger.factory')->get('nidqc_content'),
    );
  }

  /**
   * Trả dịch vụ, năng lực, hoạt động chuyên môn, banner, liên kết web và cơ sở.
   */
  public function blocks(Request $request): CacheableJsonResponse {
    if ($request->query->count() !== 0) {
      return $this->errorResponse('INVALID_PARAMETER', 'Yêu cầu có tham số không hợp lệ.', 400);
    }

    // Cache theo node_list của từng bundle: sửa nội dung khối nào thì chỉ khối đó
    // hết hiệu lực, không phải dựng lại toàn bộ trang chủ vì một tin mới.
    $cacheability = (new CacheableMetadata())->setCacheTags([
      'node_list:service',
      'node_list:capability',
      'node_list:expertise',
      'node_list:banner',
      'node_list:web_link',
      'node_list:office',
      'node_list:home_block',
      // Đường dẫn ô Dịch vụ sinh từ TÊN term: đổi tên danh mục là khối phải dựng lại.
      'taxonomy_term_list:service_category',
    ]);

    try {
      $banners = ['ads_1' => [], 'ads_2' => [], 'sidebar' => []];
      foreach ($this->load('banner') as $node) {
        $position = (string) $node->get('field_position')->value;
        $image = $this->presenter->imageUrl($node, $position === 'sidebar' ? NewsPresenter::STYLE_CARD : NewsPresenter::STYLE_ARTICLE);
        // Banner không có ảnh thì bỏ: slideshow ảnh mà có slide trắng là hỏng cả dải.
        if ($image === NULL || !isset($banners[$position])) {
          continue;
        }
        $banners[$position][] = [
          'title' => $node->label(),
          'url' => $this->linkUrl($node),
          'image' => $image,
        ];
      }

      $standards = NULL;
      foreach ($this->load('home_block') as $node) {
        $link = $node->get('field_link')->first();
        $standards = [
          'label' => trim((string) $link?->title) !== '' ? (string) $link->title : 'Tra cứu chất chuẩn',
          'url' => $this->linkUrl($node),
          'note' => $this->plainText($node, 'field_description'),
        ];
        break;
      }

      $payload = [
        'services' => array_map(fn (NodeInterface $n): array => [
          'title' => $n->label(),
          'url' => $this->serviceUrl($n),
          'image' => $this->presenter->imageUrl($n, NewsPresenter::STYLE_CARD),
        ], $this->load('service')),

        'capabilities' => array_map(fn (NodeInterface $n): array => [
          'title' => $n->label(),
          'url' => $this->ownPageUrl($n),
          'description' => $this->plainText($n, 'field_description'),
        ], $this->load('capability')),

        'expertise' => array_map(fn (NodeInterface $n): array => [
          'title' => $n->label(),
          'url' => $this->ownPageUrl($n),
          'image' => $this->presenter->imageUrl($n, NewsPresenter::STYLE_CARD),
        ], $this->load('expertise')),

        'banners' => $banners,

        'web_links' => array_map(fn (NodeInterface $n): array => [
          'title' => $n->label(),
          'url' => $this->linkUrl($n),
          'image' => $this->presenter->imageUrl($n, NewsPresenter::STYLE_THUMB),
        ], $this->load('web_link')),

        'offices' => array_map(fn (NodeInterface $n): array => [
          'title' => $n->label(),
          'address' => (string) $n->get('field_address')->value,
          'map' => $this->mapsLink($n),
        ], $this->load('office')),

        'standards' => $standards,
      ];

      $response = new CacheableJsonResponse(['data' => $payload]);
      $response->addCacheableDependency($cacheability);
      return $response;
    }
    catch (\Throwable $exception) {
      $this->logger->error('Không thể tải khối trang chủ: @message', ['@message' => $exception->getMessage()]);
      return $this->errorResponse('INTERNAL_ERROR', 'Đã có lỗi xảy ra. Vui lòng thử lại sau.', 500);
    }
  }

  /**
   * Node đã xuất bản của một bundle, sắp theo field_weight rồi tới tiêu đề.
   *
   * @return \Drupal\node\NodeInterface[]
   */
  private function load(string $bundle): array {
    $storage = $this->entityTypeManager->getStorage('node');
    $query = $storage->getQuery()
      ->accessCheck(TRUE)
      ->condition('type', $bundle)
      ->condition('status', 1)
      ->range(0, 30);
    // home_block không có field_weight; sắp theo tiêu đề cho ổn định thứ tự.
    if ($bundle === 'home_block') {
      $query->sort('nid');
    }
    else {
      $query->sort('field_weight')->sort('title');
    }

    return array_values($storage->loadMultiple($query->execute()));
  }

  /**
   * URL từ field_link, đã bỏ tiền tố 'internal:' để frontend dùng thẳng.
   */
  private function linkUrl(NodeInterface $node): ?string {
    if (!$node->hasField('field_link') || $node->get('field_link')->isEmpty()) {
      return NULL;
    }
    $uri = (string) $node->get('field_link')->uri;
    if ($uri === '') {
      return NULL;
    }

    return str_starts_with($uri, 'internal:') ? substr($uri, strlen('internal:')) : $uri;
  }

  /**
   * Đường dẫn của một ô Dịch vụ trên trang chủ.
   *
   * Sinh từ DANH MỤC đã chọn (/dich-vu/<slug tên danh mục>) thay vì bắt biên tập
   * viên gõ tay URL: gõ lệch một ký tự là ô bấm vào ra 404 mà không có gì báo. Cùng
   * bộ Slugger với ServiceListController nên slug hai bên không thể lệch nhau.
   *
   * field_link vẫn được tôn trọng và ĐỨNG TRƯỚC — vài ô cần trỏ sang hệ thống cũ.
   */
  private function serviceUrl(NodeInterface $node): ?string {
    $manual = $this->linkUrl($node);
    if ($manual !== NULL) {
      return $manual;
    }
    if (!$node->hasField('field_service_category') || $node->get('field_service_category')->isEmpty()) {
      return NULL;
    }
    $term = $node->get('field_service_category')->entity;
    if ($term === NULL) {
      return NULL;
    }

    return '/dich-vu/' . $this->slugger->slug((string) $term->label());
  }

  /**
   * Đường dẫn tới trang riêng của chính node đó (Hoạt động chuyên môn, Năng lực).
   *
   * Bài viết nay nằm ngay trong node — pathauto tự sinh alias
   * /hoat-dong-chuyen-mon/… và /danh-muc-nang-luc/… nên không còn cảnh nhập nội dung
   * ở một node "Trang tĩnh" khác rồi dán đường dẫn ngược lại vào đây.
   *
   * Chưa nhập nội dung -> trả NULL: ô vẫn hiện ảnh + tiêu đề nhưng không bấm được,
   * tốt hơn là dẫn người đọc tới một trang trống.
   */
  private function ownPageUrl(NodeInterface $node): ?string {
    $manual = $this->linkUrl($node);
    if ($manual !== NULL) {
      return $manual;
    }
    if (!$node->hasField('body') || $node->get('body')->isEmpty()) {
      return NULL;
    }
    $alias = $this->aliasManager->getAliasByPath('/node/' . $node->id());

    return $alias !== '/node/' . $node->id() ? $alias : NULL;
  }

  /**
   * Nội dung một field văn bản, đã bỏ thẻ HTML và gộp khoảng trắng.
   */
  private function plainText(NodeInterface $node, string $field): string {
    if (!$node->hasField($field) || $node->get($field)->isEmpty()) {
      return '';
    }
    $raw = (string) $node->get($field)->value;

    return trim(preg_replace('/\s+/u', ' ', strip_tags($raw)) ?? '');
  }

  /**
   * Link mở Google Maps của một cơ sở.
   *
   * field_map lưu URL NHÚNG (iframe). Chân trang chỉ cần một link mở bản đồ, nên rút
   * toạ độ từ tham số pb của URL nhúng (!2d = kinh độ, !3d = vĩ độ) — đúng điểm quản
   * trị viên đã ghim. Không rút được thì lùi về tìm theo địa chỉ.
   */
  private function mapsLink(NodeInterface $node): ?string {
    $embed = $node->hasField('field_map') ? (string) $node->get('field_map')->value : '';
    if (preg_match('/!2d(-?\d+(?:\.\d+)?)!3d(-?\d+(?:\.\d+)?)/', $embed, $m) === 1) {
      return 'https://www.google.com/maps/search/?api=1&query=' . $m[2] . ',' . $m[1];
    }

    $address = trim((string) $node->get('field_address')->value);

    return $address !== ''
      ? 'https://www.google.com/maps/search/?api=1&query=' . rawurlencode($address)
      : NULL;
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
