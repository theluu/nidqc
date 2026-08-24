<?php

declare(strict_types=1);

namespace Drupal\nidqc_online\Service;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Database\Connection;

/**
 * Đếm lượt truy cập theo ngày cho khối "Thống kê truy cập" ở trang chủ.
 *
 * Một "lượt" = một phiên trình duyệt trong một ngày. Ghi nhận ở lần
 * /api/v1/online/csrf-token đầu tiên của phiên, và ghi lại khi phiên bước sang
 * ngày mới (người mở tab qua đêm vẫn được tính cho ngày hôm sau).
 *
 * Không lưu IP, user agent hay bất kỳ thứ gì định danh: bảng chỉ có (ngày, số đếm).
 */
final class VisitCounter {

  /**
   * Khoá trong session ghi ngày đã tính lượt gần nhất.
   */
  private const SESSION_KEY = 'nidqc_online_visit_day';

  /**
   * Múi giờ dùng để cắt ngày/tháng/năm — thống kê là để người Việt đọc.
   */
  private const TIMEZONE = 'Asia/Ho_Chi_Minh';

  public function __construct(
    private readonly Connection $database,
    private readonly TimeInterface $time,
    private readonly VisitBaseline $baseline,
  ) {
  }

  /**
   * Ghi nhận một lượt cho phiên hiện tại nếu hôm nay chưa tính.
   *
   * @param \Symfony\Component\HttpFoundation\Session\SessionInterface|\ArrayAccess|null $session
   *   Session của request. Truyền NULL thì bỏ qua (không có phiên = không đếm).
   *
   * @return bool
   *   TRUE nếu vừa cộng thêm một lượt.
   */
  public function record(mixed $session): bool {
    if ($session === NULL) {
      return FALSE;
    }

    $today = $this->today();
    if ($session->get(self::SESSION_KEY) === $today) {
      return FALSE;
    }
    $session->set(self::SESSION_KEY, $today);

    // upsert: hai request cùng lúc trong ngày mới không được ném lỗi khoá trùng.
    $this->database->merge('nidqc_visit_daily')
      ->key('day', $today)
      ->expression('visits', '[visits] + 1')
      // 'day' phải có mặt ở đây: insertFields() GHI ĐÈ danh sách cột chèn, nên
      // khoá do key() thêm vào trước đó sẽ biến mất nếu chỉ liệt kê 'visits'.
      ->insertFields(['day' => $today, 'visits' => 1])
      ->execute();

    return TRUE;
  }

  /**
   * Số lượt hôm nay / tháng này / năm nay / tổng cộng để HIỆN RA TRANG.
   *
   * = số đếm thật + nền ước lượng cho quãng trước khi có bộ đếm (VisitBaseline).
   * Phần thật luôn được cộng nguyên vào, nên mỗi lượt mới vẫn làm con số tăng
   * đúng 1; tắt nền (baseline.enabled: false) là về đúng số thật.
   *
   * @return array<string, int>
   *   Mảng có các khoá today, month, year, total.
   */
  public function stats(): array {
    $real = $this->realStats();
    $baseline = $this->baseline->stats();

    $stats = [];
    foreach ($real as $key => $value) {
      $stats[$key] = $value + ($baseline[$key] ?? 0);
    }

    return $stats;
  }

  /**
   * Số lượt THẬT trong bảng, không cộng nền — dùng để kiểm tra/đối chiếu.
   *
   * @return array<string, int>
   *   Mảng có các khoá today, month, year, total.
   */
  public function realStats(): array {
    $today = $this->today();

    return [
      'today' => $this->sum($today),
      'month' => $this->sum(substr($today, 0, 7)),
      'year' => $this->sum(substr($today, 0, 4)),
      'total' => $this->sum(''),
    ];
  }

  /**
   * Tổng lượt của các ngày bắt đầu bằng $prefix ('' = toàn bộ).
   */
  private function sum(string $prefix): int {
    $query = $this->database->select('nidqc_visit_daily', 'v');
    if ($prefix !== '') {
      // LIKE với ký tự thoát mặc định: prefix chỉ gồm chữ số và dấu gạch nối nên
      // không có ký tự đại diện nào lọt vào.
      $query->condition('v.day', $this->database->escapeLike($prefix) . '%', 'LIKE');
    }
    $query->addExpression('SUM(v.visits)', 'total');

    return (int) $query->execute()->fetchField();
  }

  /**
   * Ngày hôm nay dạng YYYY-MM-DD theo giờ Việt Nam.
   */
  private function today(): string {
    return (new \DateTimeImmutable('@' . $this->time->getRequestTime()))
      ->setTimezone(new \DateTimeZone(self::TIMEZONE))
      ->format('Y-m-d');
  }

}
