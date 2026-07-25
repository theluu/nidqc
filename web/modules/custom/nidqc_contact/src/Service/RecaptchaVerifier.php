<?php

declare(strict_types=1);

namespace Drupal\nidqc_contact\Service;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\State\StateInterface;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\GuzzleException;
use Psr\Log\LoggerInterface;

/**
 * Verifies Google reCAPTCHA v3 tokens server-side.
 */
final class RecaptchaVerifier {

  private const VERIFY_URL = 'https://www.google.com/recaptcha/api/siteverify';
  private const DEFAULT_MIN_SCORE = 0.5;

  public function __construct(
    private readonly ClientInterface $httpClient,
    private readonly LoggerInterface $logger,
    private readonly ConfigFactoryInterface $configFactory,
    private readonly StateInterface $state,
  ) {
  }

  /**
   * Verifies a token for an expected reCAPTCHA v3 action.
   *
   * @return array{ok: bool, reason: string, score: float|null}
   *   Verification result. Reason is for server-side branching only.
   */
  public function verify(string $token, string $expectedAction): array {
    if (getenv('NIDQC_RECAPTCHA_BYPASS') === '1') {
      return [
        'ok' => $token === 'ddev-bypass',
        'reason' => $token === 'ddev-bypass' ? 'bypass' : 'bypass_token_invalid',
        'score' => $token === 'ddev-bypass' ? 1.0 : NULL,
      ];
    }

    $secret = getenv('NIDQC_RECAPTCHA_SECRET');
    if (!is_string($secret) || trim($secret) === '') {
      $secret = (string) $this->state->get('nidqc_contact.recaptcha_secret', '');
    }
    if (trim($secret) === '') {
      $this->logger->error('reCAPTCHA secret is not configured.');
      return ['ok' => FALSE, 'reason' => 'not_configured', 'score' => NULL];
    }

    try {
      $response = $this->httpClient->request('POST', self::VERIFY_URL, [
        'form_params' => [
          'secret' => $secret,
          'response' => $token,
        ],
        'timeout' => 4,
      ]);
      $body = json_decode((string) $response->getBody(), TRUE, flags: JSON_THROW_ON_ERROR);
    }
    catch (GuzzleException | \JsonException $e) {
      $this->logger->warning('reCAPTCHA verification request failed: @reason', [
        '@reason' => $e->getMessage(),
      ]);
      return ['ok' => FALSE, 'reason' => 'verify_request_failed', 'score' => NULL];
    }

    if (!is_array($body) || empty($body['success'])) {
      return ['ok' => FALSE, 'reason' => 'verify_failed', 'score' => NULL];
    }

    $action = isset($body['action']) && is_string($body['action']) ? $body['action'] : '';
    $score = isset($body['score']) && is_numeric($body['score']) ? (float) $body['score'] : NULL;

    if ($action !== $expectedAction) {
      return ['ok' => FALSE, 'reason' => 'action_mismatch', 'score' => $score];
    }

    if ($score === NULL || $score < $this->minimumScore()) {
      return ['ok' => FALSE, 'reason' => 'score_too_low', 'score' => $score];
    }

    return ['ok' => TRUE, 'reason' => 'verified', 'score' => $score];
  }

  /**
   * Returns the configured minimum score, clamped to the v3 score range.
   */
  private function minimumScore(): float {
    $configured = getenv('NIDQC_RECAPTCHA_MIN_SCORE');
    if (!is_string($configured) || trim($configured) === '') {
      $stored = $this->configFactory->get('nidqc_contact.settings')->get('recaptcha.minimum_score');
      return is_numeric($stored) ? (float) $stored : self::DEFAULT_MIN_SCORE;
    }

    $score = (float) $configured;
    if ($score < 0 || $score > 1) {
      $this->logger->warning('Invalid NIDQC_RECAPTCHA_MIN_SCORE; using default.');
      return self::DEFAULT_MIN_SCORE;
    }

    return $score;
  }

}
