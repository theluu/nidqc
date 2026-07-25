<?php

declare(strict_types=1);

namespace Drupal\nidqc_contact\Service;

use Drupal\Component\Utility\EmailValidatorInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\State\StateInterface;
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
    private readonly StateInterface $state,
  ) {
  }

  /**
   * Sends a configuration test email.
   */
  public function sendTest(string $recipient): bool {
    if (!$this->emailValidator->isValid($recipient) || !$this->emailValidator->isValid($this->siteEmail())) {
      return FALSE;
    }

    try {
      $from = new Address($this->siteEmail(), $this->siteName());
      $message = (new Email())
        ->from($from)
        ->to($recipient)
        ->subject('[' . $this->siteName() . '] Kiểm tra cấu hình SMTP')
        ->text('Email kiểm tra SMTP từ trang cấu hình website NIDQC.', $this->charset());
      (new Mailer(Transport::fromDsn($this->mailerDsn())))->send($message);
      return TRUE;
    }
    catch (\Throwable) {
      $this->logger->error('SMTP configuration test failed.');
      return FALSE;
    }
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
      ]), $this->charset());
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
      ]), $this->charset());
  }

  /**
   * Returns the configured admin recipient with site mail as fallback.
   */
  private function adminEmail(): string {
    $stored = (string) $this->configFactory->get('nidqc_contact.settings')->get('smtp.admin_email');
    return $stored !== ''
      ? $stored
      : (string) $this->configFactory->get('system.site')->get('mail');
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
   * Builds the sole SMTP DSN from the NIDQC contact settings.
   */
  private function mailerDsn(): string {
    $settings = $this->configFactory->get('nidqc_contact.settings');
    $host = (string) ($settings->get('smtp.host') ?: 'smtp.nidqc.gov.vn');
    $port = (int) ($settings->get('smtp.port') ?: 25);
    $authEnabled = (bool) $settings->get('smtp.auth');
    $security = strtoupper((string) ($settings->get('smtp.security') ?: 'IMAP'));
    $scheme = $security === 'SSL' ? 'smtps' : 'smtp';
    $user = $authEnabled ? (string) $this->state->get('nidqc_contact.smtp_username', '') : '';
    $password = $authEnabled ? (string) $this->state->get('nidqc_contact.smtp_password', '') : '';
    $options = in_array($security, ['IMAP', 'NONE'], TRUE)
      ? ['auto_tls' => 'false']
      : [];

    $auth = '';
    if (is_string($user) && $user !== '') {
      $auth = rawurlencode($user);
      if (is_string($password) && $password !== '') {
        $auth .= ':' . rawurlencode($password);
      }
      $auth .= '@';
    }

    $portPart = '';
    $portPart = ':' . (string) $port;

    $query = '';
    if (is_array($options) && $options !== []) {
      $query = '?' . http_build_query($options, '', '&', PHP_QUERY_RFC3986);
    }

    return $scheme . '://' . $auth . $host . $portPart . $query;
  }

  /**
   * Returns the UTF-8 character set required by the website.
   */
  private function charset(): string {
    return 'UTF-8';
  }

}
