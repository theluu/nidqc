<?php

declare(strict_types=1);

namespace Drupal\nidqc_contact\Controller;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Component\Utility\EmailValidatorInterface;
use Drupal\Core\Access\CsrfRequestHeaderAccessCheck;
use Drupal\Core\Access\CsrfTokenGenerator;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Flood\FloodInterface;
use Drupal\Core\State\StateInterface;
use Drupal\nidqc_contact\Service\ContactMailer;
use Drupal\nidqc_contact\Service\RecaptchaVerifier;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

/**
 * Handles public contact form submissions.
 */
final class ContactController extends ControllerBase {

  private const RECAPTCHA_ACTION = 'contact_submit';
  private const FLOOD_EVENT = 'nidqc_contact.submit';
  private const FLOOD_LIMIT = 5;
  private const FLOOD_WINDOW = 3600;
  private const ALLOWED_KEYS = [
    'name',
    'email',
    'phone',
    'subject',
    'message',
    'recaptchaToken',
  ];
  private const SUBJECTS = [
    'Dịch vụ kiểm nghiệm',
    'Chất chuẩn - chất đối chiếu',
    'Văn bản - tài liệu',
    'Khác',
  ];

  public function __construct(
    private readonly CsrfTokenGenerator $csrfToken,
    private readonly EmailValidatorInterface $emailValidator,
    private readonly FloodInterface $flood,
    private readonly EntityTypeManagerInterface $entityTypeManagerService,
    private readonly TimeInterface $time,
    private readonly SessionInterface $session,
    private readonly RecaptchaVerifier $recaptchaVerifier,
    private readonly ContactMailer $contactMailer,
    private readonly LoggerInterface $logger,
    private readonly StateInterface $state,
  ) {
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): self {
    return new self(
      $container->get('csrf_token'),
      $container->get('email.validator'),
      $container->get('flood'),
      $container->get('entity_type.manager'),
      $container->get('datetime.time'),
      $container->get('session'),
      $container->get('nidqc_contact.recaptcha_verifier'),
      $container->get('nidqc_contact.contact_mailer'),
      $container->get('logger.channel.nidqc_contact'),
      $container->get('state'),
    );
  }

  /**
   * Returns the browser-visible contact form configuration.
   */
  public function publicConfig(Request $request): JsonResponse {
    if ($request->query->count() !== 0) {
      return $this->errorResponse(
        'INVALID_PARAMETER',
        'Yêu cầu có tham số không hợp lệ.',
        400,
      );
    }

    $environmentSiteKey = getenv('NIDQC_RECAPTCHA_SITE_KEY');
    $siteKey = is_string($environmentSiteKey) && trim($environmentSiteKey) !== ''
      ? trim($environmentSiteKey)
      : (string) $this->config('nidqc_contact.settings')->get('recaptcha.site_key');
    $environmentSecret = getenv('NIDQC_RECAPTCHA_SECRET');
    $secretConfigured = is_string($environmentSecret) && trim($environmentSecret) !== ''
      ? TRUE
      : (string) $this->state->get('nidqc_contact.recaptcha_secret', '') !== '';
    $bypass = getenv('NIDQC_RECAPTCHA_BYPASS') === '1';

    // Mạng xã hội: chỉ trả kênh đã cấu hình, frontend cứ thế render — không phải
    // biết trước site có những kênh nào.
    $settings = $this->config('nidqc_contact.settings');
    $social = [];
    foreach (['facebook', 'youtube', 'zalo', 'tiktok'] as $channel) {
      $url = trim((string) $settings->get('social.' . $channel));
      if ($url !== '') {
        $social[] = ['key' => $channel, 'url' => $url];
      }
    }

    // Chân trang: nhóm nào không có cả email lẫn hotline thì không trả về, để
    // frontend khỏi phải lọc lại.
    $customerServices = [];
    foreach ((array) $settings->get('customer_services') as $service) {
      $email = trim((string) ($service['email'] ?? ''));
      $hotline = trim((string) ($service['hotline'] ?? ''));
      if ($email === '' && $hotline === '') {
        continue;
      }
      $customerServices[] = [
        'label' => (string) ($service['label'] ?? ''),
        'email' => $email,
        'hotline' => $hotline,
      ];
    }

    return $this->jsonResponse([
      'data' => [
        'recaptcha' => [
          'enabled' => !$bypass && $siteKey !== '' && $secretConfigured,
          'site_key' => $siteKey,
        ],
        'social' => $social,
        'footer' => [
          'tel' => trim((string) $settings->get('footer.tel')),
          'tel_note' => trim((string) $settings->get('footer.tel_note')),
          'fax' => trim((string) $settings->get('footer.fax')),
          'email' => trim((string) $settings->get('footer.email')),
        ],
        'customer_services' => $customerServices,
      ],
    ]);
  }

  /**
   * Starts an anonymous session and returns a contact CSRF token.
   */
  public function csrfToken(Request $request): Response {
    $this->session->start();
    $this->session->set('nidqc_contact_csrf', $this->time->getRequestTime());

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
   * Accepts and stores a contact submission.
   */
  public function submit(Request $request): JsonResponse {
    if (!$this->validCsrf($request)) {
      return $this->errorResponse(
        'CSRF_TOKEN_INVALID',
        'Phiên gửi biểu mẫu không hợp lệ. Vui lòng tải lại trang và thử lại.',
        403,
      );
    }

    if (!str_starts_with((string) $request->headers->get('Content-Type'), 'application/json')) {
      return $this->errorResponse(
        'INVALID_PARAMETER',
        'Dữ liệu gửi lên không hợp lệ.',
        400,
        [['field' => 'Content-Type', 'issue' => 'Phải là application/json.']],
      );
    }

    try {
      $payload = json_decode($request->getContent(), TRUE, flags: JSON_THROW_ON_ERROR);
    }
    catch (\JsonException) {
      return $this->errorResponse(
        'INVALID_PARAMETER',
        'Dữ liệu gửi lên không hợp lệ.',
        400,
        [['field' => 'body', 'issue' => 'JSON không hợp lệ.']],
      );
    }

    [$submission, $error] = $this->validatePayload($payload);
    if ($error instanceof JsonResponse) {
      return $error;
    }

    $identifier = $request->getClientIp() ?: 'unknown';
    if (!$this->flood->isAllowed(self::FLOOD_EVENT, self::FLOOD_LIMIT, self::FLOOD_WINDOW, $identifier)) {
      $response = $this->errorResponse(
        'RATE_LIMITED',
        'Bạn đã gửi quá nhiều yêu cầu. Vui lòng thử lại sau.',
        429,
      );
      $response->headers->set('Retry-After', (string) self::FLOOD_WINDOW);
      return $response;
    }
    $this->flood->register(self::FLOOD_EVENT, self::FLOOD_WINDOW, $identifier);

    $recaptcha = $this->recaptchaVerifier->verify($submission['recaptchaToken'], self::RECAPTCHA_ACTION);
    if (!$recaptcha['ok']) {
      if ($recaptcha['reason'] === 'not_configured') {
        return $this->errorResponse(
          'INTERNAL_ERROR',
          'Chức năng gửi liên hệ chưa được cấu hình. Vui lòng liên hệ qua email hoặc điện thoại.',
          500,
        );
      }

      return $this->errorResponse(
        'ACCESS_DENIED',
        'Không xác thực được yêu cầu. Vui lòng thử lại.',
        403,
      );
    }

    try {
      $node = $this->entityTypeManagerService->getStorage('node')->create([
        'type' => 'contact_submission',
        'title' => $this->submissionTitle($submission['name']),
        'status' => 0,
        'uid' => 0,
        'field_contact_name' => $submission['name'],
        'field_contact_email' => $submission['email'],
        'field_contact_phone' => $submission['phone'],
        'field_contact_subject' => $submission['subject'],
        'field_contact_message' => [
          'value' => $submission['message'],
          'format' => 'plain_text',
        ],
      ]);
      $node->save();

      $mailResult = $this->contactMailer->send($submission, (int) $node->id());
      if (!$mailResult['admin'] || !$mailResult['user']) {
        $this->logger->error('Contact submission email delivery failed for node @nid.', [
          '@nid' => $node->id(),
        ]);
        return $this->errorResponse(
          'INTERNAL_ERROR',
          'Đã có lỗi xảy ra. Vui lòng thử lại sau.',
          500,
        );
      }
    }
    catch (\Throwable) {
      $this->logger->error('Contact submission failed.');
      return $this->errorResponse(
        'INTERNAL_ERROR',
        'Đã có lỗi xảy ra. Vui lòng thử lại sau.',
        500,
      );
    }

    return $this->jsonResponse([
      'data' => [
        'id' => (int) $node->id(),
        'message' => 'Cảm ơn bạn đã gửi liên hệ. Viện sẽ phản hồi sớm nhất có thể.',
      ],
    ]);
  }

  /**
   * Validates JSON payload and returns normalized values.
   *
   * @return array{0: array<string, string>|null, 1: \Symfony\Component\HttpFoundation\JsonResponse|null}
   *   Valid submission values or an API error response.
   */
  private function validatePayload(mixed $payload): array {
    if (!is_array($payload)) {
      return [
        NULL,
        $this->errorResponse(
          'INVALID_PARAMETER',
          'Dữ liệu gửi lên không hợp lệ.',
          400,
          [['field' => 'body', 'issue' => 'JSON body phải là object.']],
        ),
      ];
    }

    $unknown = array_values(array_diff(array_keys($payload), self::ALLOWED_KEYS));
    if ($unknown !== []) {
      return [
        NULL,
        $this->errorResponse(
          'INVALID_PARAMETER',
          'Dữ liệu gửi lên có tham số không hợp lệ.',
          400,
          array_map(
            static fn ($field): array => [
              'field' => (string) $field,
              'issue' => 'Không có trong API contract.',
            ],
            $unknown,
          ),
        ),
      ];
    }

    $missing = [];
    foreach (['name', 'email', 'message', 'recaptchaToken'] as $field) {
      if (!isset($payload[$field]) || !is_string($payload[$field]) || trim($payload[$field]) === '') {
        $missing[] = [
          'field' => $field,
          'issue' => 'Bắt buộc.',
        ];
      }
    }
    if ($missing !== []) {
      return [
        NULL,
        $this->errorResponse(
          'MISSING_PARAMETER',
          'Vui lòng điền đầy đủ thông tin bắt buộc.',
          400,
          $missing,
        ),
      ];
    }

    $submission = [
      'name' => $this->normalizeLine((string) $payload['name']),
      'email' => $this->normalizeLine((string) $payload['email']),
      'phone' => isset($payload['phone']) && is_string($payload['phone']) ? $this->normalizeLine($payload['phone']) : '',
      'subject' => isset($payload['subject']) && is_string($payload['subject']) ? $this->normalizeLine($payload['subject']) : 'Khác',
      'message' => $this->normalizeMessage((string) $payload['message']),
      'recaptchaToken' => $this->normalizeLine((string) $payload['recaptchaToken']),
    ];

    $errors = [];
    if (!$this->validTextLength($submission['name'], 2, 120) || $this->hasControlChars($submission['name'])) {
      $errors[] = ['field' => 'name', 'issue' => 'Phải có từ 2 đến 120 ký tự.'];
    }
    if (!$this->validTextLength($submission['email'], 5, 254) || !$this->emailValidator->isValid($submission['email'])) {
      $errors[] = ['field' => 'email', 'issue' => 'Email không hợp lệ.'];
    }
    if ($submission['phone'] !== '' && (!$this->validTextLength($submission['phone'], 0, 40) || !preg_match('/^[0-9+\s().-]+$/u', $submission['phone']))) {
      $errors[] = ['field' => 'phone', 'issue' => 'Số điện thoại không hợp lệ.'];
    }
    if (!in_array($submission['subject'], self::SUBJECTS, TRUE)) {
      $errors[] = ['field' => 'subject', 'issue' => 'Chủ đề không hợp lệ.'];
    }
    if (!$this->validTextLength($submission['message'], 10, 4000) || $this->hasControlChars($submission['message'], TRUE)) {
      $errors[] = ['field' => 'message', 'issue' => 'Phải có từ 10 đến 4000 ký tự.'];
    }
    if (!$this->validTextLength($submission['recaptchaToken'], 1, 4096) || $this->hasControlChars($submission['recaptchaToken'])) {
      $errors[] = ['field' => 'recaptchaToken', 'issue' => 'Token reCAPTCHA không hợp lệ.'];
    }

    if ($errors !== []) {
      return [
        NULL,
        $this->errorResponse(
          'INVALID_PARAMETER',
          'Một số thông tin chưa hợp lệ.',
          400,
          $errors,
        ),
      ];
    }

    return [$submission, NULL];
  }

  /**
   * Checks the Drupal CSRF token header.
   */
  private function validCsrf(Request $request): bool {
    $token = $request->headers->get('X-CSRF-Token');
    return is_string($token)
      && $this->csrfToken->validate($token, CsrfRequestHeaderAccessCheck::TOKEN_KEY);
  }

  /**
   * Returns a single-line trimmed string.
   */
  private function normalizeLine(string $value): string {
    return trim(str_replace(["\r", "\n"], ' ', $value));
  }

  /**
   * Returns a trimmed message with normalized line endings.
   */
  private function normalizeMessage(string $value): string {
    return trim(str_replace(["\r\n", "\r"], "\n", $value));
  }

  /**
   * Checks UTF-8 text length boundaries.
   */
  private function validTextLength(string $value, int $min, int $max): bool {
    if (!mb_check_encoding($value, 'UTF-8')) {
      return FALSE;
    }

    $length = mb_strlen($value);
    return $length >= $min && $length <= $max;
  }

  /**
   * Checks disallowed control characters.
   */
  private function hasControlChars(string $value, bool $allowNewlines = FALSE): bool {
    $pattern = $allowNewlines ? '/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u' : '/[\x00-\x1F\x7F]/u';
    return preg_match($pattern, $value) !== 0;
  }

  /**
   * Builds the node title shown in admin lists.
   */
  private function submissionTitle(string $name): string {
    return sprintf('Liên hệ: %s - %s', $name, date('Y-m-d H:i', $this->time->getRequestTime()));
  }

  /**
   * Returns a JSON API error response.
   *
   * @param array<int, array<string, string>> $details
   *   Optional field-level validation details.
   */
  private function errorResponse(string $code, string $message, int $status, array $details = []): JsonResponse {
    $body = [
      'error' => [
        'code' => $code,
        'message' => $message,
      ],
    ];
    if ($details !== []) {
      $body['error']['details'] = $details;
    }

    return $this->jsonResponse($body, $status);
  }

  /**
   * Returns a no-store JSON response with Vietnamese text preserved.
   */
  private function jsonResponse(array $body, int $status = 200): JsonResponse {
    $response = new JsonResponse($body, $status);
    $response->setEncodingOptions(JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    $response->headers->set('Cache-Control', 'no-store');
    return $response;
  }

}
