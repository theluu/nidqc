<?php

declare(strict_types=1);

namespace Drupal\nidqc_online\Service;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Config\ConfigFactoryInterface;

/**
 * Nền (baseline) cho khối "Thống kê truy cập".
 *
 * VÌ SAO CÓ FILE NÀY
 * ------------------
 * Bảng nidqc_visit_daily chỉ tính từ ngày module được bật, nên trang chủ của một
 * cơ quan hoạt động hàng chục năm lại hiện "Tổng truy cập: 4". Con số đó làm hỏng
 * độ tin cậy của trang hơn là nói được điều gì thật.
 *
 * Cách xử lý: dựng một đường nền ước lượng cho quãng thời gian TRƯỚC khi có bộ
 * đếm (từ `baseline.start_date`), rồi CỘNG số đếm thật lên trên. Số đếm thật vẫn
 * nguyên vẹn trong DB và vẫn tăng chính xác từng lượt — nền chỉ là phần bù, và
 * tắt được bằng một khoá cấu hình.
 *
 * TÍNH CHẤT PHẢI GIỮ
 * ------------------
 *  - Xác định (deterministic): cùng một ngày luôn cho cùng một số. Không random
 *    theo request, nếu không mỗi lần heartbeat con số lại nhảy lung tung.
 *  - Không giảm (monotonic): nền của tổng ⊇ năm ⊇ tháng ⊇ hôm nay, vì các tổng
 *    được cộng từ cùng một hàm theo ngày.
 *  - Trong ngày thì tăng dần theo giờ thật, không nhảy nguyên cục lúc 00:00.
 */
final class VisitBaseline {

  /**
   * Múi giờ cắt ngày — giống VisitCounter, thống kê để người Việt đọc.
   */
  private const TIMEZONE = 'Asia/Ho_Chi_Minh';

  /**
   * Mặc định dùng khi chưa có config (module vừa bật, chưa import config).
   */
  private const DEFAULTS = [
    'enabled' => TRUE,
    'start_date' => '2016-01-01',
    'daily_average' => 25,
    'weekend_factor' => 0.55,
    'jitter' => 0.18,
    'growth_per_year' => 0.06,
  ];

  /**
   * Phân bố lượt truy cập trong ngày, phần nghìn cho từng giờ 0..23.
   *
   * Tổng đúng 1000. Hình dáng theo giờ làm việc hành chính: hai đỉnh sáng
   * (9-11h) và chiều (14-16h), đáy lúc 2-4h sáng. Dùng để nội suy "hôm nay" nên
   * lúc 8h sáng khối thống kê hiện ~11% chỉ tiêu ngày chứ không phải 33% như khi
   * chia đều theo giờ.
   */
  private const HOURLY_PERMILLE = [
    8, 5, 4, 4, 7, 14, 26, 42, 62, 80, 92, 80,
    55, 66, 86, 82, 74, 54, 41, 35, 31, 27, 17, 8,
  ];

  public function __construct(
    private readonly ConfigFactoryInterface $configFactory,
    private readonly TimeInterface $time,
    private readonly CacheBackendInterface $cache,
  ) {
  }

  /**
   * Nền cho bốn ô hôm nay / tháng / năm / tổng.
   *
   * @return array<string, int>
   */
  public function stats(): array {
    if (!$this->setting('enabled')) {
      return ['today' => 0, 'month' => 0, 'year' => 0, 'total' => 0];
    }

    $now = $this->now();
    $today = $now->format('Y-m-d');
    // Phần đã "trôi qua" của chỉ tiêu hôm nay.
    $partial = (int) round($this->dailyTarget($today) * $this->dayElapsedRatio($now));

    // Tổng của các ngày ĐÃ ĐÓNG (trước hôm nay) chỉ đổi mỗi ngày một lần -> cache
    // theo ngày, khỏi phải cộng lại ~3000 ngày ở từng heartbeat.
    $closed = $this->closedSums($today);

    return [
      'today' => $partial,
      'month' => $closed['month'] + $partial,
      'year' => $closed['year'] + $partial,
      'total' => $closed['total'] + $partial,
    ];
  }

  /**
   * Nền cho ô "Đang trực tuyến".
   *
   * Suy ra từ chính chỉ tiêu ngày: số người cùng lúc ≈ lượt trong một cửa sổ
   * WINDOW_SECONDS, tức lượt-trong-giờ × (cửa sổ / 1 giờ). Nhờ vậy ban đêm nó tự
   * tụt về 1-2 người và giữa giờ hành chính lên hai chữ số, thay vì treo cứng ở
   * một con số đẹp suốt 24 giờ.
   */
  public function onlineFloor(int $windowSeconds): int {
    if (!$this->setting('enabled')) {
      return 0;
    }

    $now = $this->now();
    $hourly = $this->dailyTarget($now->format('Y-m-d'))
      * (self::HOURLY_PERMILLE[(int) $now->format('G')] / 1000);
    $concurrent = $hourly * ($windowSeconds / 3600);

    // Nhiễu đổi theo từng ô 5 phút: con số nhích nhẹ giữa các lần tải trang như
    // đếm thật, nhưng trong cùng một ô thì mọi khách thấy y hệt nhau.
    $bucket = intdiv($this->time->getRequestTime(), max(60, $windowSeconds));
    $concurrent *= 1 + $this->noise('online:' . $bucket, 0.25);

    return max(1, (int) round($concurrent));
  }

  /**
   * Tổng nền của các ngày trước hôm nay, cho tháng/năm/tổng.
   *
   * @return array{month: int, year: int, total: int}
   */
  private function closedSums(string $today): array {
    $cid = 'nidqc_online:baseline:' . $today . ':' . $this->settingsFingerprint();
    $cached = $this->cache->get($cid);
    if ($cached && is_array($cached->data)) {
      return $cached->data;
    }

    $start = $this->startDate();
    $end = new \DateTimeImmutable($today, new \DateTimeZone(self::TIMEZONE));
    $sums = ['month' => 0, 'year' => 0, 'total' => 0];

    for ($day = $start; $day < $end; $day = $day->modify('+1 day')) {
      $target = $this->dailyTarget($day->format('Y-m-d'));
      $sums['total'] += $target;
      if ($day->format('Y') === $end->format('Y')) {
        $sums['year'] += $target;
        if ($day->format('m') === $end->format('m')) {
          $sums['month'] += $target;
        }
      }
    }

    // Hết ngày là hỏng: hôm sau cid đổi nên bản cũ cũng không còn ai đọc.
    $this->cache->set($cid, $sums, $this->endOfDayTimestamp($end));

    return $sums;
  }

  /**
   * Chỉ tiêu lượt truy cập của một ngày cụ thể.
   *
   * daily_average là mức trung bình MỘT NGÀY của tuần; hệ số ngày thường được
   * chuẩn hoá lại theo weekend_factor để trung bình tuần vẫn đúng bằng
   * daily_average (nếu chỉ nhân 0.55 cho hai ngày cuối tuần thì cả năm bị hụt).
   */
  private function dailyTarget(string $day): int {
    $date = new \DateTimeImmutable($day, new \DateTimeZone(self::TIMEZONE));
    $weekendFactor = max(0.0, (float) $this->setting('weekend_factor'));
    $isWeekend = (int) $date->format('N') >= 6;

    $weekdayFactor = 7 / (5 + 2 * $weekendFactor);
    $factor = $isWeekend ? $weekendFactor * $weekdayFactor : $weekdayFactor;

    // Lưu lượng lớn dần theo năm: nền của 2016 thấp hơn nền của 2026, nhìn vào
    // dữ liệu theo ngày không thấy một mặt phẳng suốt 10 năm.
    $years = ((int) $date->format('Y')) - ((int) $this->startDate()->format('Y'));
    $factor *= (1 + max(0.0, (float) $this->setting('growth_per_year'))) ** max(0, $years);

    $factor *= 1 + $this->noise('day:' . $day, (float) $this->setting('jitter'));

    return max(0, (int) round(((float) $this->setting('daily_average')) * $factor));
  }

  /**
   * Tỷ lệ chỉ tiêu ngày đã trôi qua tính đến thời điểm hiện tại (0..1).
   */
  private function dayElapsedRatio(\DateTimeImmutable $now): float {
    $hour = (int) $now->format('G');
    $withinHour = ((int) $now->format('i') * 60 + (int) $now->format('s')) / 3600;

    $done = 0;
    for ($h = 0; $h < $hour; $h++) {
      $done += self::HOURLY_PERMILLE[$h];
    }
    $done += self::HOURLY_PERMILLE[$hour] * $withinHour;

    return min(1.0, $done / 1000);
  }

  /**
   * Nhiễu xác định trong khoảng [-$amplitude, +$amplitude].
   *
   * crc32 chứ không phải rand(): cùng một khoá phải cho cùng một giá trị ở mọi
   * request, mọi tiến trình PHP.
   */
  private function noise(string $key, float $amplitude): float {
    if ($amplitude <= 0) {
      return 0.0;
    }
    $unit = (crc32($key) % 2001) / 1000 - 1;

    return $unit * $amplitude;
  }

  /**
   * Ngày bắt đầu tính nền, đã chuẩn hoá về đầu ngày giờ Việt Nam.
   */
  private function startDate(): \DateTimeImmutable {
    $raw = (string) $this->setting('start_date');
    $zone = new \DateTimeZone(self::TIMEZONE);
    try {
      $date = new \DateTimeImmutable($raw, $zone);
    }
    catch (\Exception) {
      $date = new \DateTimeImmutable(self::DEFAULTS['start_date'], $zone);
    }

    return $date->setTime(0, 0);
  }

  /**
   * Thời điểm hiện tại theo giờ Việt Nam.
   */
  private function now(): \DateTimeImmutable {
    return (new \DateTimeImmutable('@' . $this->time->getRequestTime()))
      ->setTimezone(new \DateTimeZone(self::TIMEZONE));
  }

  /**
   * Timestamp hết ngày, dùng làm hạn cache.
   */
  private function endOfDayTimestamp(\DateTimeImmutable $day): int {
    return $day->setTime(23, 59, 59)->getTimestamp();
  }

  /**
   * Một khoá cấu hình, có mặc định trong code nếu config chưa tồn tại.
   */
  private function setting(string $key): mixed {
    $value = $this->configFactory->get('nidqc_online.settings')->get('baseline.' . $key);

    return $value ?? self::DEFAULTS[$key];
  }

  /**
   * Dấu vân tay của cấu hình nền — đổi config là cache nền cũ hết hiệu lực ngay.
   */
  private function settingsFingerprint(): string {
    $values = [];
    foreach (array_keys(self::DEFAULTS) as $key) {
      $values[$key] = $this->setting($key);
    }

    return substr(hash('xxh64', serialize($values)), 0, 8);
  }

}
