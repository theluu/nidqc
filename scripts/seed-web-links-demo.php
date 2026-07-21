<?php
/**
 * @file
 * Nhập đầy đủ dữ liệu DEMO cho các node web_link: logo (tự sinh), mô tả, URL.
 * Logo là ảnh placeholder (ô màu + chữ viết tắt) sinh bằng GD — không tải file
 * ngoài, không dùng logo thật (tránh bản quyền). Admin thay bằng logo thật sau.
 *
 * Usage: ddev drush php:script scripts/seed-web-links-demo.php
 */
declare(strict_types=1);

use Drupal\Core\File\FileExists;
use Drupal\Core\File\FileSystemInterface;

$out = static function (string $m): void { print $m . PHP_EOL; };

// Dữ liệu demo theo tiêu đề node đã seed.
$demo = [
  'Bộ Y Tế' => [
    'acr' => 'BYT', 'bg' => [0xC8, 0x10, 0x2E],
    'url' => 'https://moh.gov.vn',
    'desc' => 'Cơ quan quản lý nhà nước về y tế, chăm sóc và bảo vệ sức khỏe nhân dân.',
  ],
  'Cục Quản lý Dược' => [
    'acr' => 'QLD', 'bg' => [0x0F, 0x7B, 0x3E],
    'url' => 'https://dav.gov.vn',
    'desc' => 'Quản lý nhà nước về lĩnh vực dược, mỹ phẩm và nguyên liệu làm thuốc.',
  ],
  'Viện Kiểm nghiệm thuốc TP. Hồ Chí Minh' => [
    'acr' => 'VKN', 'bg' => [0x0F, 0x30, 0x93],
    'url' => 'https://kiemnghiemthuochcm.vn',
    'desc' => 'Đơn vị kiểm nghiệm thuốc khu vực phía Nam, phối hợp giám sát chất lượng thuốc.',
  ],
  'Tổ chức Y tế Thế giới (WHO)' => [
    'acr' => 'WHO', 'bg' => [0x00, 0x9E, 0xDB],
    'url' => 'https://www.who.int',
    'desc' => 'Tổ chức Y tế Thế giới — cơ quan chuyên môn của Liên Hợp Quốc về sức khỏe.',
  ],
];

/**
 * Sinh 1 logo PNG 240x240: nền màu thương hiệu + chữ viết tắt trắng ở giữa.
 * Dùng font bitmap GD (font 5) vẽ nhỏ rồi phóng to -> không cần TTF.
 */
$makeLogo = static function (string $acr, array $rgb): string {
  $size = 240;
  $canvas = imagecreatetruecolor($size, $size);
  $bg = imagecolorallocate($canvas, $rgb[0], $rgb[1], $rgb[2]);
  imagefilledrectangle($canvas, 0, 0, $size, $size, $bg);

  // Lớp chữ nhỏ (font 5) rồi phóng to bằng imagecopyresampled.
  $font = 5;
  $tw = imagefontwidth($font) * strlen($acr);
  $th = imagefontheight($font);
  $small = imagecreatetruecolor($tw, $th);
  $sbg = imagecolorallocate($small, $rgb[0], $rgb[1], $rgb[2]);
  imagefilledrectangle($small, 0, 0, $tw, $th, $sbg);
  $white = imagecolorallocate($small, 0xFF, 0xFF, 0xFF);
  imagestring($small, $font, 0, 0, $acr, $white);

  // Phóng to giữ tỉ lệ, rộng ~150px, đặt giữa canvas.
  $dstW = 150;
  $dstH = (int) round($dstW * $th / $tw);
  $dstX = (int) (($size - $dstW) / 2);
  $dstY = (int) (($size - $dstH) / 2);
  imagecopyresampled($canvas, $small, $dstX, $dstY, 0, 0, $dstW, $dstH, $tw, $th);

  ob_start();
  imagepng($canvas);
  $png = (string) ob_get_clean();
  imagedestroy($small);
  imagedestroy($canvas);
  return $png;
};

// Chuẩn bị thư mục public://web-link-logos.
$fileSystem = \Drupal::service('file_system');
$dir = 'public://web-link-logos';
$fileSystem->prepareDirectory($dir, FileSystemInterface::CREATE_DIRECTORY);

$fileRepo = \Drupal::service('file.repository');
$nodeStorage = \Drupal::entityTypeManager()->getStorage('node');

foreach ($demo as $title => $d) {
  $found = $nodeStorage->loadByProperties(['type' => 'web_link', 'title' => $title]);
  if (!$found) {
    $out("• Không thấy node '$title' — bỏ qua");
    continue;
  }
  /** @var \Drupal\node\NodeInterface $node */
  $node = reset($found);

  // Logo.
  $png = $makeLogo($d['acr'], $d['bg']);
  $slug = strtolower($d['acr']);
  $file = $fileRepo->writeData($png, "$dir/logo-$slug.png", FileExists::Replace);
  $node->set('field_image', ['target_id' => $file->id(), 'alt' => "Logo $title"]);

  // Mô tả + URL.
  $node->set('field_description', ['value' => $d['desc'], 'format' => 'basic_html']);
  $node->set('field_link', ['uri' => $d['url'], 'title' => '']);

  $node->save();
  $out("✔ Đã nhập demo: $title (logo $slug.png, URL {$d['url']})");
}

$out('Hoàn tất nhập dữ liệu demo.');
