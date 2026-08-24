<?php

declare(strict_types=1);

namespace Drupal\nidqc_online\Service;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Database\Connection;

/**
 * Counts active Drupal sessions without exposing session data.
 */
final class OnlineCounter {

  public const WINDOW_SECONDS = 300;

  public function __construct(
    private readonly Connection $database,
    private readonly TimeInterface $time,
    private readonly VisitBaseline $baseline,
  ) {
  }

  /**
   * Số người đang trực tuyến để hiện ra trang, gồm cả nền ước lượng.
   *
   * Xem VisitBaseline: phiên THẬT vẫn được đếm chính xác, nền chỉ là phần bù để
   * ô này không đứng ở "1" (chính người đang xem) giữa giờ hành chính.
   */
  public function count(): int {
    return $this->realCount() + $this->baseline->onlineFloor(self::WINDOW_SECONDS);
  }

  /**
   * Số phiên thật còn hoạt động trong cửa sổ — KHÔNG cộng nền.
   */
  public function realCount(): int {
    $query = $this->database->select('sessions', 's');
    $query->condition('s.timestamp', $this->time->getRequestTime() - self::WINDOW_SECONDS, '>=');
    $query->addExpression('COUNT(DISTINCT s.sid)', 'online_count');

    return (int) $query->execute()->fetchField();
  }

}
