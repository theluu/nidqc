<?php

declare(strict_types=1);

namespace Drupal\nidqc_online\Controller;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Access\CsrfRequestHeaderAccessCheck;
use Drupal\Core\Access\CsrfTokenGenerator;
use Drupal\Core\Controller\ControllerBase;
use Drupal\nidqc_online\Service\OnlineCounter;
use Drupal\nidqc_online\Service\VisitCounter;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

/**
 * Serves the aggregate online count and session heartbeat.
 */
final class OnlineController extends ControllerBase {

  public function __construct(
    private readonly OnlineCounter $counter,
    private readonly VisitCounter $visits,
    private readonly CsrfTokenGenerator $csrfToken,
    private readonly SessionInterface $session,
    private readonly TimeInterface $time,
  ) {
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): self {
    return new self(
      $container->get('nidqc_online.counter'),
      $container->get('nidqc_online.visits'),
      $container->get('csrf_token'),
      $container->get('session'),
      $container->get('datetime.time'),
    );
  }

  /**
   * Returns the current aggregate count without creating a session.
   */
  public function count(Request $request): JsonResponse {
    if ($request->query->count() !== 0) {
      return $this->errorResponse('INVALID_PARAMETER', 'Yêu cầu có tham số không hợp lệ.', 400);
    }

    return $this->countResponse();
  }

  /**
   * Starts a session and returns its Drupal CSRF token.
   */
  public function csrfToken(Request $request): Response {
    if ($request->query->count() !== 0) {
      return $this->errorResponse('INVALID_PARAMETER', 'Yêu cầu có tham số không hợp lệ.', 400);
    }

    $this->session->start();
    $this->session->set('nidqc_online_seen', $this->time->getRequestTime());
    // Lượt truy cập tính ở đây: đây là điểm duy nhất mà một trình duyệt chắc chắn
    // đi qua đúng một lần cho mỗi phiên (trang do Nuxt phục vụ từ cache HTML nên
    // không đếm được ở phía Drupal render).
    $this->visits->record($this->session);

    return new Response(
      $this->csrfToken->get(CsrfRequestHeaderAccessCheck::TOKEN_KEY),
      200,
      [
        'Content-Type' => 'text/plain; charset=UTF-8',
        'Cache-Control' => 'no-store',
      ],
    );
  }

  /**
   * Refreshes the current session activity and returns the aggregate count.
   */
  public function heartbeat(Request $request): JsonResponse {
    if ($request->query->count() !== 0 || $request->getContent() !== '') {
      return $this->errorResponse('INVALID_PARAMETER', 'Yêu cầu có dữ liệu không hợp lệ.', 400);
    }

    $token = $request->headers->get('X-CSRF-Token');
    if (!is_string($token) || !$this->csrfToken->validate($token, CsrfRequestHeaderAccessCheck::TOKEN_KEY)) {
      return $this->errorResponse('CSRF_TOKEN_INVALID', 'Phiên truy cập không hợp lệ.', 403);
    }

    $this->session->set('nidqc_online_seen', $this->time->getRequestTime());
    // Phiên mở qua nửa đêm vẫn được tính cho ngày mới — record() tự bỏ qua nếu
    // hôm nay đã tính rồi.
    $this->visits->record($this->session);
    $this->session->save();

    return $this->countResponse();
  }

  /**
   * Builds the no-store success response.
   */
  private function countResponse(): JsonResponse {
    return $this->jsonResponse([
      'data' => [
        'count' => $this->counter->count(),
        'window_seconds' => OnlineCounter::WINDOW_SECONDS,
        // Khối "Thống kê truy cập" ở trang chủ đọc bốn số này.
        'visits' => $this->visits->stats(),
      ],
    ]);
  }

  /**
   * Builds an API-standard error response.
   */
  private function errorResponse(string $code, string $message, int $status): JsonResponse {
    return $this->jsonResponse([
      'error' => [
        'code' => $code,
        'message' => $message,
      ],
    ], $status);
  }

  /**
   * Returns JSON without caching or escaped Vietnamese text.
   */
  private function jsonResponse(array $body, int $status = 200): JsonResponse {
    $response = new JsonResponse($body, $status);
    $response->setEncodingOptions(JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    $response->headers->set('Cache-Control', 'no-store');
    return $response;
  }

}
