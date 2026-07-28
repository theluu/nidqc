<?php

declare(strict_types=1);

namespace Drupal\nidqc_content;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\File\FileUrlGeneratorInterface;
use Drupal\Core\Image\ImageFactory;
use Drupal\Core\StreamWrapper\StreamWrapperManagerInterface;
use Drupal\image\ImageStyleInterface;
use Drupal\node\NodeInterface;

/**
 * Chuyển node Tin tức thành mảng JSON cho frontend.
 *
 * Dùng chung cho controller chi tiết và controller danh sách: cả hai đều cần cùng
 * một cách dựng URL ảnh (image style, đường dẫn tương đối) và cùng một hình dạng
 * item để trang Vue map thẳng ra template.
 */
final class NewsPresenter {

  /**
   * Image style cho ảnh đại diện và ảnh trong thân bài.
   */
  public const STYLE_ARTICLE = 'max_1300x1300';

  /**
   * Image style cho ảnh ở danh sách và card.
   */
  public const STYLE_CARD = 'max_650x650';

  /**
   * Image style cho ảnh nhỏ ở sidebar.
   */
  public const STYLE_THUMB = 'max_325x325';

  /**
   * Tên danh mục Tin tức dùng cho Thư viện media.
   *
   * Tra theo TÊN chứ không chôn term id: id khác nhau giữa dev/staging/production.
   */
  public const CATEGORY_VIDEO = 'Videos';
  public const CATEGORY_IMAGE = 'Hình ảnh';

  /**
   * Cache term id của các danh mục media trong một request.
   */
  private ?array $mediaTermIds = NULL;

  /**
   * Khởi tạo presenter.
   */
  public function __construct(
    private readonly EntityTypeManagerInterface $entityTypeManager,
    private readonly FileUrlGeneratorInterface $fileUrlGenerator,
    private readonly StreamWrapperManagerInterface $streamWrapperManager,
    private readonly ImageFactory $imageFactory,
  ) {
  }

  /**
   * Dựng item danh sách (dùng cho tin liên quan, tin mới nhất, trang danh sách).
   */
  public function listItem(NodeInterface $node, string $styleId): array {
    $category = $node->get('field_category')->entity;
    $tag = trim((string) $node->get('field_tag')->value);
    return [
      'id' => (int) $node->id(),
      'title' => $node->label(),
      'created' => gmdate(DATE_ATOM, (int) $node->getCreatedTime()),
      // sitemap.xml dùng cho <lastmod>.
      'changed' => gmdate(DATE_ATOM, (int) $node->getChangedTime()),
      'tag' => $tag !== '' ? $tag : ($category?->label() ?? ''),
      'category' => $category?->label() ?? '',
      'image' => $this->imageUrl($node, $styleId),
      'alias' => $node->toUrl()->toString(),
      'featured' => $this->isFeatured($node),
    ];
  }

  /**
   * Tin có được đánh dấu "Tin nổi bật" hay không.
   *
   * hasField() vì field_featured chỉ gắn trên bundle news; presenter này cũng
   * chạy cho tin cũ được tạo trước khi có field (giá trị NULL = không nổi bật).
   */
  public function isFeatured(NodeInterface $node): bool {
    return $node->hasField('field_featured')
      && (bool) $node->get('field_featured')->value;
  }

  /**
   * Term id của hai danh mục Thư viện media (Videos, Hình ảnh).
   *
   * Các khối Tin tức thường phải LOẠI TRỪ những danh mục này: bài thư viện chỉ có
   * ý nghĩa trong block Thư viện, lọt vào danh sách/tìm kiếm là sai yêu cầu.
   */
  public function mediaCategoryTermIds(): array {
    if ($this->mediaTermIds !== NULL) {
      return $this->mediaTermIds;
    }

    $wanted = [self::CATEGORY_VIDEO, self::CATEGORY_IMAGE];
    $ids = [];
    $terms = $this->entityTypeManager->getStorage('taxonomy_term')
      ->loadByProperties(['vid' => 'news_category']);
    foreach ($terms as $term) {
      if (in_array($term->label(), $wanted, TRUE)) {
        $ids[$term->label()] = (int) $term->id();
      }
    }

    return $this->mediaTermIds = $ids;
  }

  /**
   * Bài này thuộc danh mục thư viện nào: 'video', 'image' hoặc NULL.
   */
  public function mediaKind(NodeInterface $node): ?string {
    $label = $node->get('field_category')->entity?->label();
    return match ($label) {
      self::CATEGORY_VIDEO => 'video',
      self::CATEGORY_IMAGE => 'image',
      default => NULL,
    };
  }

  /**
   * Danh sách media của một bài, đã chuẩn hoá cho frontend.
   *
   * Trả về mảng phẳng theo ĐÚNG thứ tự admin sắp trong form (delta của field), gồm:
   *   ['type' => 'image',   'thumbnail' => …, 'src' => …, 'alt' => …]
   *   ['type' => 'youtube', 'thumbnail' => …, 'video_id' => …]
   *   ['type' => 'video',   'thumbnail' => …|null, 'src' => …, 'mime' => …]
   *
   * Frontend không phải đụng tới cấu trúc field thô của Drupal.
   */
  public function mediaItems(NodeInterface $node): array {
    $items = [];

    if ($node->hasField('field_gallery_images')) {
      foreach ($node->get('field_gallery_images') as $item) {
        $file = $item->entity;
        if ($file === NULL) {
          continue;
        }
        $uri = $file->getFileUri();
        $items[] = [
          'type' => 'image',
          'thumbnail' => $this->styledUrl($uri, self::STYLE_THUMB) ?? $this->fileUrlGenerator->generateString($uri),
          'src' => $this->styledUrl($uri, self::STYLE_ARTICLE) ?? $this->fileUrlGenerator->generateString($uri),
          'alt' => (string) ($item->alt ?? ''),
        ];
      }
    }

    if ($node->hasField('field_youtube_urls')) {
      foreach ($node->get('field_youtube_urls') as $item) {
        $videoId = $this->youtubeId((string) $item->uri);
        if ($videoId === NULL) {
          continue;
        }
        $items[] = [
          'type' => 'youtube',
          // Thumbnail lấy thẳng từ CDN của YouTube theo video id.
          'thumbnail' => 'https://img.youtube.com/vi/' . $videoId . '/hqdefault.jpg',
          'video_id' => $videoId,
        ];
      }
    }

    if ($node->hasField('field_videos')) {
      // Video tải lên không có thumbnail riêng -> dùng ảnh đại diện của bài; không
      // có nữa thì trả NULL để frontend hiện placeholder (yêu cầu 3.3).
      $fallbackThumb = $this->imageUrl($node, self::STYLE_THUMB);
      foreach ($node->get('field_videos') as $item) {
        $file = $item->entity;
        if ($file === NULL) {
          continue;
        }
        $items[] = [
          'type' => 'video',
          'thumbnail' => $fallbackThumb,
          'src' => $this->fileUrlGenerator->generateString($file->getFileUri()),
          'mime' => $file->getMimeType(),
        ];
      }
    }

    return $items;
  }

  /**
   * Rút video id từ link YouTube, NULL nếu không phải link YouTube hợp lệ.
   *
   * Nhận watch?v=…, youtu.be/…, shorts/… và embed/… — id YouTube luôn 11 ký tự.
   */
  public function youtubeId(string $url): ?string {
    $url = trim($url);
    if ($url === '') {
      return NULL;
    }

    $host = strtolower((string) parse_url($url, PHP_URL_HOST));
    if ($host !== '' && !preg_match('#(^|\.)(youtube\.com|youtu\.be|youtube-nocookie\.com)$#', $host)) {
      return NULL;
    }

    if (preg_match('#(?:v=|/(?:shorts|embed|v)/|youtu\.be/)([A-Za-z0-9_-]{11})(?![A-Za-z0-9_-])#', $url, $m) === 1) {
      return $m[1];
    }

    return NULL;
  }

  /**
   * URL ảnh đại diện đã qua image style, dạng đường dẫn tương đối.
   */
  public function imageUrl(NodeInterface $node, string $styleId): ?string {
    if (!$node->hasField('field_image') || $node->get('field_image')->isEmpty()) {
      return NULL;
    }
    $file = $node->get('field_image')->entity;
    if ($file === NULL) {
      return NULL;
    }
    return $this->styledUrl($file->getFileUri(), $styleId)
      ?? $this->fileUrlGenerator->generateString($file->getFileUri());
  }

  /**
   * Danh sách file đính kèm, nhãn lấy từ description của field.
   */
  public function attachments(NodeInterface $node): array {
    if (!$node->hasField('field_attachments') || $node->get('field_attachments')->isEmpty()) {
      return [];
    }

    $items = [];
    foreach ($node->get('field_attachments') as $item) {
      $file = $item->entity;
      if ($file === NULL) {
        continue;
      }
      $label = ltrim(trim((string) $item->description), "- \t\n\r\0\x0B");
      $items[] = [
        'url' => $this->fileUrlGenerator->generateString($file->getFileUri()),
        'label' => $label !== '' ? $label : $file->getFilename(),
      ];
    }
    return $items;
  }

  /**
   * Đưa ảnh trong thân bài về derivative + lazy-load.
   *
   * Thân bài trỏ thẳng vào file gốc trong thư mục public (trung bình 172KB, cá
   * biệt 5.9MB). Đổi sang image style giảm băng thông rất nhiều; ảnh nào không
   * map được về public:// (ảnh ngoài, data URI) thì giữ nguyên src.
   */
  public function optimiseEmbeddedImages(string $html): string {
    if ($html === '' || stripos($html, '<img') === FALSE) {
      return $html;
    }

    $style = $this->entityTypeManager->getStorage('image_style')->load(self::STYLE_ARTICLE);
    if ($style === NULL) {
      return $html;
    }

    $publicBase = $this->fileUrlGenerator->generateString('public://');
    $replaced = preg_replace_callback(
      '#<img\b[^>]*>#i',
      fn (array $match): string => $this->rewriteImageTag($match[0], $publicBase, $style),
      $html,
    );
    // preg_* trả NULL khi gặp lỗi (backtrack limit trên bài rất dài) — giữ bản gốc.
    return $replaced ?? $html;
  }

  /**
   * Dựng URL derivative cho một URI, trả NULL nếu style không dùng được.
   */
  private function styledUrl(string $uri, string $styleId): ?string {
    $style = $this->entityTypeManager->getStorage('image_style')->load($styleId);
    if ($style === NULL || !$style->supportsUri($uri)) {
      return NULL;
    }
    // buildUrl() trả URL tuyệt đối; đưa về tương đối để không chôn domain của
    // môi trường hiện tại vào response (dev, staging và prod khác domain).
    return $this->fileUrlGenerator->transformRelative($style->buildUrl($uri));
  }

  /**
   * Viết lại một thẻ <img>: đổi src sang derivative và thêm lazy-load.
   */
  private function rewriteImageTag(string $tag, string $publicBase, ImageStyleInterface $style): string {
    if (preg_match('#\bsrc\s*=\s*"([^"]*)"#i', $tag, $src) === 1) {
      $uri = $this->publicUriForPath($src[1], $publicBase);
      $styled = $uri === NULL ? NULL : $this->styledUrl($uri, self::STYLE_ARTICLE);
      if ($styled !== NULL) {
        $tag = str_replace($src[0], 'src="' . htmlspecialchars($styled, ENT_QUOTES) . '"', $tag);
        $tag = $this->rewriteDimensions($tag, $uri, $style);
      }
    }
    if (preg_match('#\bloading\s*=#i', $tag) !== 1) {
      $tag = preg_replace('#<img\b#i', '<img loading="lazy" decoding="async"', $tag, 1) ?? $tag;
    }
    return $tag;
  }

  /**
   * Quy đổi width/height của thẻ img sang kích thước sau khi qua image style.
   *
   * Ảnh thân bài lazy-load mà không có width/height thì trình duyệt coi chiều cao
   * bằng 0 tới lúc tải xong rồi mới đẩy nội dung xuống (layout shift). HTML do
   * CKEditor sinh ra thường không có hai thuộc tính này, nên đo thẳng từ file gốc
   * rồi quy đổi qua transformDimensions() để ra đúng tỉ lệ của derivative.
   */
  private function rewriteDimensions(string $tag, string $uri, ImageStyleInterface $style): string {
    $dimensions = $this->sourceDimensions($tag, $uri);
    if ($dimensions === NULL) {
      // Không đo được tỉ lệ; bỏ luôn số cũ (là kích thước ảnh GỐC, không còn đúng
      // với derivative) để CSS max-width:100%/height:auto tự lo, tránh ảnh méo.
      return preg_replace('#\s(width|height)\s*=\s*"[^"]*"#i', '', $tag) ?? $tag;
    }

    $style->transformDimensions($dimensions, $uri);
    if (empty($dimensions['width']) || empty($dimensions['height'])) {
      return preg_replace('#\s(width|height)\s*=\s*"[^"]*"#i', '', $tag) ?? $tag;
    }

    $width = ' width="' . (int) $dimensions['width'] . '"';
    $height = ' height="' . (int) $dimensions['height'] . '"';
    $tag = preg_replace('#\s(width|height)\s*=\s*"[^"]*"#i', '', $tag) ?? $tag;
    return preg_replace('#<img\b#i', '<img' . $width . $height, $tag, 1) ?? $tag;
  }

  /**
   * Kích thước ảnh gốc: ưu tiên thuộc tính trong HTML, không có thì đọc từ file.
   */
  private function sourceDimensions(string $tag, string $uri): ?array {
    if (
      preg_match('#\bwidth\s*=\s*"(\d+)"#i', $tag, $w) === 1
      && preg_match('#\bheight\s*=\s*"(\d+)"#i', $tag, $h) === 1
      && (int) $w[1] > 0 && (int) $h[1] > 0
    ) {
      return ['width' => (int) $w[1], 'height' => (int) $h[1]];
    }

    $image = $this->imageFactory->get($uri);
    if (!$image->isValid()) {
      return NULL;
    }
    return ['width' => $image->getWidth(), 'height' => $image->getHeight()];
  }

  /**
   * Đổi một đường dẫn file public thành URI public://, NULL nếu không map được.
   */
  private function publicUriForPath(string $src, string $publicBase): ?string {
    if ($src === '' || $publicBase === '') {
      return NULL;
    }
    // Bỏ scheme/host để so khớp cả src tuyệt đối lẫn tương đối.
    $path = (string) parse_url($src, PHP_URL_PATH);
    if ($path === '' || !str_starts_with($path, $publicBase)) {
      return NULL;
    }

    $relative = rawurldecode(substr($path, strlen($publicBase)));
    // Derivative đã có sẵn trong src thì bỏ qua, tránh lồng styles/ vào styles/.
    if ($relative === '' || str_starts_with($relative, 'styles/')) {
      return NULL;
    }

    $uri = 'public://' . $relative;
    return $this->streamWrapperManager->isValidUri($uri) ? $uri : NULL;
  }

}
