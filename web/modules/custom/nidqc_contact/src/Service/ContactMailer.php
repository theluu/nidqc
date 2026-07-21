<?php

declare(strict_types=1);

namespace Drupal\nidqc_contact\Service;

use Drupal\Component\Utility\EmailValidatorInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Mailer\Mailer;
use Symfony\Component\Mailer\Transport;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;

/**
 * Sends contact submission notification emails.
 */
final class ContactMailer {

  public function __construct(
    private readonly ConfigFactoryInterface $configFactory,
    private readonly EmailValidatorInterface $emailValidator,
    private readonly LoggerInterface $logger,
  ) {
  }

  /**
   * Sends admin notification and user confirmation emails.
   *
   * @param array<string, string> $submission
   *   Validated contact submission values.
   *
   * @return array{admin: bool, user: bool}
   *   Result for each outbound email.
   */
  public function send(array $submission, int $submissionId): array {
    $adminEmail = $this->adminEmail();
    $siteEmail = $this->siteEmail();
    $userEmail = $submission['email'];

    if (
      !$this->emailValidator->isValid($adminEmail)
      || !$this->emailValidator->isValid($siteEmail)
      || !$this->emailValidator->isValid($userEmail)
    ) {
      $this->logger->error('Contact email delivery failed because one or more email addresses are invalid.');
      return ['admin' => FALSE, 'user' => FALSE];
    }

    $adminSent = FALSE;
    $userSent = FALSE;

    try {
      $mailer = new Mailer(Transport::fromDsn($this->mailerDsn()));
      $siteName = $this->siteName();
      $from = new Address($siteEmail, $siteName);

      $mailer->send($this->adminEmailMessage($submission, $submissionId, $adminEmail, $from));
      $adminSent = TRUE;

      $mailer->send($this->userEmailMessage($submission, $userEmail, $from));
      $userSent = TRUE;
    }
    catch (\Throwable) {
      $this->logger->error('Contact email delivery failed.');
    }

    return [
      'admin' => $adminSent,
      'user' => $userSent,
    ];
  }

  /**
   * Builds the admin notification email.
   *
   * @param array<string, string> $submission
   *   Validated contact submission values.
   */
  private function adminEmailMessage(array $submission, int $submissionId, string $adminEmail, Address $from): Email {
    return (new Email())
      ->from($from)
      ->to($adminEmail)
      ->replyTo($submission['email'])
      ->subject('[' . $this->siteName() . '] Liên hệ mới: ' . $submission['subject'])
      ->text(implode("\n", [
        'Có liên hệ mới từ website.',
        '',
        'Mã submission: ' . (string) $submissionId,
        'Họ và tên: ' . $submission['name'],
        'Email: ' . $submission['email'],
        'Số điện thoại: ' . ($submission['phone'] !== '' ? $submission['phone'] : 'Không cung cấp'),
        'Chủ đề: ' . $submission['subject'],
        '',
        'Nội dung:',
        $submission['message'],
      ]));
  }

  /**
   * Builds the user confirmation email.
   *
   * @param array<string, string> $submission
   *   Validated contact submission values.
   */
  private function userEmailMessage(array $submission, string $userEmail, Address $from): Email {
    return (new Email())
      ->from($from)
      ->to($userEmail)
      ->subject('[' . $this->siteName() . '] Đã nhận liên hệ của bạn')
      ->text(implode("\n", [
        'Xin chào ' . $submission['name'] . ',',
        '',
        'Viện Kiểm nghiệm thuốc Trung ương đã nhận được liên hệ của bạn.',
        'Viện sẽ phản hồi trong thời gian sớm nhất.',
        '',
        'Thông tin đã gửi:',
        'Chủ đề: ' . $submission['subject'],
        'Nội dung: ' . $submission['message'],
        '',
        'Trân trọng,',
        $this->siteName(),
      ]));
  }

  /**
   * Returns the configured admin recipient with site mail as fallback.
   */
  private function adminEmail(): string {
    $configured = getenv('NIDQC_CONTACT_ADMIN_EMAIL');
    if (is_string($configured) && trim($configured) !== '') {
      return trim($configured);
    }

    return (string) $this->configFactory->get('system.site')->get('mail');
  }

  /**
   * Returns the configured sender email.
   */
  private function siteEmail(): string {
    $siteMail = (string) $this->configFactory->get('system.site')->get('mail');
    if ($siteMail !== '') {
      return $siteMail;
    }

    return 'noreply@nidqc.gov.vn';
  }

  /**
   * Returns the configured site name.
   */
  private function siteName(): string {
    $siteName = (string) $this->configFactory->get('system.site')->get('name');
    if ($siteName !== '') {
      return $siteName;
    }

    return 'Viện Kiểm nghiệm thuốc Trung ương';
  }

  /**
   * Converts Drupal's Symfony mailer config into a DSN string.
   */
  private function mailerDsn(): string {
    $dsn = $this->configFactory->get('system.mail')->get('mailer_dsn');
    if (!is_array($dsn)) {
      return 'smtp://localhost:25';
    }

    $scheme = (string) ($dsn['scheme'] ?? 'smtp');
    $host = (string) ($dsn['host'] ?? 'localhost');
    $port = $dsn['port'] ?? NULL;
    $user = $dsn['user'] ?? NULL;
    $password = $dsn['password'] ?? NULL;
    $options = $dsn['options'] ?? [];

    $auth = '';
    if (is_string($user) && $user !== '') {
      $auth = rawurlencode($user);
      if (is_string($password) && $password !== '') {
        $auth .= ':' . rawurlencode($password);
      }
      $auth .= '@';
    }

    $portPart = '';
    if (is_int($port) || (is_string($port) && $port !== '')) {
      $portPart = ':' . (string) $port;
    }

    $query = '';
    if (is_array($options) && $options !== []) {
      $query = '?' . http_build_query($options, '', '&', PHP_QUERY_RFC3986);
    }

    return $scheme . '://' . $auth . $host . $portPart . $query;
  }

}
