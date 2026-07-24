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
  ) {
  }

  /**
   * Returns sessions active within the configured window.
   */
  public function count(): int {
    $query = $this->database->select('sessions', 's');
    $query->condition('s.timestamp', $this->time->getRequestTime() - self::WINDOW_SECONDS, '>=');
    $query->addExpression('COUNT(DISTINCT s.sid)', 'online_count');

    return (int) $query->execute()->fetchField();
  }

}
