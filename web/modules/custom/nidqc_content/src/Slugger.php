<?php

declare(strict_types=1);

namespace Drupal\nidqc_content;

use Drupal\Component\Transliteration\TransliterationInterface;

/**
 * Đổi tên tiếng Việt thành đoạn slug trên URL.
 *
 * Tách ra thành dịch vụ dùng chung vì ba nơi phải cho ra ĐÚNG một chuỗi: alias
 * pathauto của bài viết dịch vụ (/dich-vu/<danh-muc>/<tieu-de>), đường dẫn mà khối
 * Dịch vụ ở trang chủ sinh ra, và slug mà ServiceListController tra ngược để tìm
 * danh mục. Lệch một quy tắc bỏ dấu là ô trên trang chủ bấm vào ra 404.
 */
final class Slugger {

  /**
   * Khởi tạo bộ sinh slug.
   */
  public function __construct(
    private readonly TransliterationInterface $transliteration,
  ) {
  }

  /**
   * Tên hiển thị -> slug: bỏ dấu, chữ thường, gạch nối.
   */
  public function slug(string $value): string {
    $ascii = $this->transliteration->transliterate($value, 'vi', '-');
    $ascii = strtolower($ascii);
    $ascii = preg_replace('/[^a-z0-9]+/', '-', $ascii) ?? '';

    return trim($ascii, '-');
  }

}
