<?php

declare(strict_types=1);

namespace Drupal\nidqc_contact\Form;

use Drupal\Component\Utility\EmailValidatorInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\TypedConfigManagerInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\State\StateInterface;
use Drupal\nidqc_contact\Service\ContactMailer;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Central administration form for NIDQC website integrations.
 */
final class NidqcSettingsForm extends ConfigFormBase {

  public function __construct(
    ConfigFactoryInterface $configFactory,
    TypedConfigManagerInterface $typedConfigManager,
    private readonly StateInterface $state,
    private readonly ContactMailer $contactMailer,
    private readonly EmailValidatorInterface $emailValidator,
  ) {
    parent::__construct($configFactory, $typedConfigManager);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): self {
    return new self(
      $container->get('config.factory'),
      $container->get('config.typed'),
      $container->get('state'),
      $container->get('nidqc_contact.contact_mailer'),
      $container->get('email.validator'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'nidqc_contact_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames(): array {
    return [
      'nidqc_contact.settings',
      'system.site',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $settings = $this->config('nidqc_contact.settings');
    $site = $this->config('system.site');

    $form['intro'] = [
      '#type' => 'item',
      '#markup' => $this->t('Toàn bộ chức năng gửi email liên hệ sử dụng duy nhất cấu hình SMTP tại đây. Mật khẩu SMTP và reCAPTCHA secret được lưu trong database State API, không đi vào config export.'),
    ];

    $form['smtp'] = [
      '#type' => 'details',
      '#title' => $this->t('SMTP'),
      '#open' => TRUE,
    ];
    $form['smtp']['smtp_charset'] = [
      '#type' => 'textfield',
      '#title' => $this->t('CharSet'),
      '#default_value' => $settings->get('smtp.charset') ?: 'UTF-8',
      '#required' => TRUE,
      '#description' => $this->t('Website bắt buộc sử dụng UTF-8.'),
    ];
    $form['smtp']['smtp_host'] = [
      '#type' => 'textfield',
      '#title' => $this->t('SMTP host'),
      '#default_value' => $settings->get('smtp.host') ?: 'smtp.nidqc.gov.vn',
      '#maxlength' => 255,
      '#required' => TRUE,
    ];
    $form['smtp']['smtp_auth'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('SMTPAuth'),
      '#default_value' => $settings->get('smtp.auth') ?? TRUE,
    ];
    $form['smtp']['smtp_port'] = [
      '#type' => 'number',
      '#title' => $this->t('Cổng'),
      '#default_value' => $settings->get('smtp.port') ?: 25,
      '#min' => 1,
      '#max' => 65535,
      '#required' => TRUE,
    ];
    $form['smtp']['smtp_username'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Tên đăng nhập'),
      '#default_value' => (string) $this->state->get('nidqc_contact.smtp_username', ''),
      '#maxlength' => 255,
      '#description' => $this->storedSecretDescription($this->state->get('nidqc_contact.smtp_username', '') !== ''),
      '#attributes' => [
        'autocomplete' => 'off',
        'data-lpignore' => 'true',
        'data-1p-ignore' => 'true',
      ],
    ];
    $form['smtp']['smtp_username_clear'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Xoá tên đăng nhập SMTP đang lưu trong database'),
    ];
    $form['smtp']['smtp_password'] = [
      '#type' => 'password',
      '#title' => $this->t('Mật khẩu'),
      '#maxlength' => 512,
      '#description' => $this->storedSecretDescription($this->state->get('nidqc_contact.smtp_password', '') !== ''),
      '#attributes' => [
        'autocomplete' => 'new-password',
        'data-lpignore' => 'true',
        'data-1p-ignore' => 'true',
      ],
    ];
    $form['smtp']['smtp_password_clear'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Xoá mật khẩu SMTP đang lưu trong database'),
    ];
    $form['smtp']['smtp_security'] = [
      '#type' => 'select',
      '#title' => $this->t('SMTPSecure'),
      '#options' => [
        'IMAP' => 'IMAP',
        'TLS' => 'TLS / STARTTLS',
        'SSL' => 'SSL / SMTPS',
        'NONE' => $this->t('Không mã hoá'),
      ],
      '#default_value' => strtoupper((string) ($settings->get('smtp.security') ?: 'IMAP')),
      '#required' => TRUE,
      '#description' => $this->t('IMAP được xử lý thành SMTP không STARTTLS vì IMAP không phải chế độ mã hoá SMTP.'),
    ];
    $form['smtp']['smtp_admin_email'] = [
      '#type' => 'email',
      '#title' => $this->t('Email nhận thông báo liên hệ'),
      '#default_value' => $settings->get('smtp.admin_email') ?: $site->get('mail'),
      '#required' => TRUE,
    ];

    $form['recaptcha'] = [
      '#type' => 'details',
      '#title' => $this->t('Google reCAPTCHA v3'),
      '#open' => TRUE,
    ];
    $form['recaptcha']['recaptcha_site_key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Site key'),
      '#default_value' => $settings->get('recaptcha.site_key') ?: '',
      '#maxlength' => 255,
      '#description' => $this->environmentDescription('NIDQC_RECAPTCHA_SITE_KEY'),
    ];
    $form['recaptcha']['recaptcha_secret'] = [
      '#type' => 'password',
      '#title' => $this->t('Secret key'),
      '#maxlength' => 512,
      '#description' => $this->secretDescription(
        'NIDQC_RECAPTCHA_SECRET',
        $this->state->get('nidqc_contact.recaptcha_secret', '') !== '',
      ),
    ];
    $form['recaptcha']['recaptcha_secret_clear'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Xoá reCAPTCHA secret đang lưu trong database'),
    ];
    $form['recaptcha']['recaptcha_minimum_score'] = [
      '#type' => 'number',
      '#title' => $this->t('Điểm tối thiểu'),
      '#default_value' => $settings->get('recaptcha.minimum_score') ?? 0.5,
      '#min' => 0,
      '#max' => 1,
      '#step' => 0.1,
      '#required' => TRUE,
      '#description' => $this->environmentDescription('NIDQC_RECAPTCHA_MIN_SCORE'),
    ];
    if (getenv('NIDQC_RECAPTCHA_BYPASS') === '1') {
      $form['recaptcha']['bypass_warning'] = [
        '#type' => 'item',
        '#markup' => '<strong>' . $this->t('Môi trường hiện tại đang bật reCAPTCHA bypass. Chỉ dùng cho local/DDEV, tuyệt đối không bật trên production.') . '</strong>',
      ];
    }

    $form['social'] = [
      '#type' => 'details',
      '#title' => $this->t('Mạng xã hội'),
      '#open' => TRUE,
      '#description' => $this->t('Hiện thành các icon trên thanh trên cùng của website. Để trống ô nào thì icon đó không hiện.'),
    ];
    foreach ($this->socialChannels() as $key => $label) {
      $form['social']['social_' . $key] = [
        '#type' => 'url',
        '#title' => $label,
        '#default_value' => $settings->get('social.' . $key) ?: '',
        '#maxlength' => 255,
        '#placeholder' => 'https://',
      ];
    }

    $form['footer'] = [
      '#type' => 'details',
      '#title' => $this->t('Chân trang — thông tin liên hệ chung'),
      '#open' => TRUE,
      '#description' => $this->t('Địa chỉ và bản đồ của từng cơ sở lấy từ nội dung "Cơ sở" (/admin/content). Các ô dưới đây là thông tin dùng chung cho cả Viện.'),
    ];
    $form['footer']['footer_tel'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Điện thoại'),
      '#default_value' => $settings->get('footer.tel') ?: '',
      '#maxlength' => 64,
    ];
    $form['footer']['footer_tel_note'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Ghi chú cạnh số điện thoại'),
      '#default_value' => $settings->get('footer.tel_note') ?: 'giờ hành chính',
      '#maxlength' => 64,
    ];
    $form['footer']['footer_fax'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Fax'),
      '#default_value' => $settings->get('footer.fax') ?: '',
      '#maxlength' => 64,
    ];
    $form['footer']['footer_email'] = [
      '#type' => 'email',
      '#title' => $this->t('Email liên hệ công việc'),
      '#default_value' => $settings->get('footer.email') ?: '',
    ];

    $form['customer_services'] = [
      '#type' => 'details',
      '#title' => $this->t('Chân trang — thông tin dành cho khách hàng'),
      '#open' => TRUE,
      '#description' => $this->t('Đầu mối liên hệ theo từng nhóm dịch vụ. Nhóm nào bỏ trống cả email lẫn hotline thì không hiện ở chân trang.'),
    ];
    $stored = $this->storedCustomerServices();
    foreach ($this->customerServiceKeys() as $key => $label) {
      $form['customer_services'][$key] = [
        '#type' => 'fieldset',
        '#title' => $label,
      ];
      $form['customer_services'][$key]['cs_' . $key . '_email'] = [
        '#type' => 'email',
        '#title' => $this->t('Email'),
        '#default_value' => $stored[$key]['email'] ?? '',
      ];
      $form['customer_services'][$key]['cs_' . $key . '_hotline'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Hotline'),
        '#default_value' => $stored[$key]['hotline'] ?? '',
        '#maxlength' => 64,
      ];
    }

    $form['site'] = [
      '#type' => 'details',
      '#title' => $this->t('Thông tin website'),
      '#open' => TRUE,
    ];
    $form['site']['site_name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Tên website'),
      '#default_value' => $site->get('name'),
      '#maxlength' => 128,
      '#required' => TRUE,
    ];
    $form['site']['site_slogan'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Khẩu hiệu'),
      '#default_value' => $site->get('slogan'),
      '#maxlength' => 255,
    ];
    $form['site']['site_email'] = [
      '#type' => 'email',
      '#title' => $this->t('Email gửi đi của website'),
      '#default_value' => $site->get('mail'),
      '#required' => TRUE,
    ];

    $form = parent::buildForm($form, $form_state);
    $form['actions']['test_smtp'] = [
      '#type' => 'submit',
      '#value' => $this->t('Lưu và gửi email thử'),
      '#submit' => [
        '::submitForm',
        '::sendTestEmail',
      ],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state): void {
    parent::validateForm($form, $form_state);

    $host = trim((string) $form_state->getValue('smtp_host'));
    if (!preg_match('/^[A-Za-z0-9.-]+$/', $host)) {
      $form_state->setErrorByName('smtp_host', $this->t('SMTP host chỉ được chứa chữ cái, chữ số, dấu chấm và dấu gạch ngang.'));
    }

    if (strtoupper(trim((string) $form_state->getValue('smtp_charset'))) !== 'UTF-8') {
      $form_state->setErrorByName('smtp_charset', $this->t('CharSet phải là UTF-8.'));
    }

    if ((bool) $form_state->getValue('smtp_auth')) {
      $hasUsername = trim((string) $form_state->getValue('smtp_username')) !== ''
        || ((string) $this->state->get('nidqc_contact.smtp_username', '') !== ''
          && !(bool) $form_state->getValue('smtp_username_clear'));
      $hasPassword = (string) $form_state->getValue('smtp_password') !== ''
        || ((string) $this->state->get('nidqc_contact.smtp_password', '') !== ''
          && !(bool) $form_state->getValue('smtp_password_clear'));
      if (!$hasUsername) {
        $form_state->setErrorByName('smtp_username', $this->t('Tên đăng nhập là bắt buộc khi SMTPAuth được bật.'));
      }
      if (!$hasPassword) {
        $form_state->setErrorByName('smtp_password', $this->t('Mật khẩu là bắt buộc khi SMTPAuth được bật.'));
      }
    }

    foreach (['smtp_admin_email', 'site_email'] as $field) {
      if (!$this->emailValidator->isValid((string) $form_state->getValue($field))) {
        $form_state->setErrorByName($field, $this->t('Địa chỉ email không hợp lệ.'));
      }
    }

    // #type url đã chặn URL sai cú pháp; ở đây chỉ chặn scheme lạ (javascript:,
    // data:…) vì các URL này được đổ thẳng vào href trên mọi trang.
    foreach (array_keys($this->socialChannels()) as $key) {
      $url = trim((string) $form_state->getValue('social_' . $key));
      if ($url === '') {
        continue;
      }
      $scheme = strtolower((string) parse_url($url, PHP_URL_SCHEME));
      if (!in_array($scheme, ['http', 'https'], TRUE)) {
        $form_state->setErrorByName('social_' . $key, $this->t('Địa chỉ phải bắt đầu bằng http:// hoặc https://.'));
      }
    }

    $siteKey = trim((string) $form_state->getValue('recaptcha_site_key'));
    $newSecret = trim((string) $form_state->getValue('recaptcha_secret'));
    $storedSecret = (string) $this->state->get('nidqc_contact.recaptcha_secret', '');
    $environmentSecret = getenv('NIDQC_RECAPTCHA_SECRET');
    $hasSecret = (
      is_string($environmentSecret) && trim($environmentSecret) !== ''
    ) || $newSecret !== '' || (
      $storedSecret !== '' && !(bool) $form_state->getValue('recaptcha_secret_clear')
    );
    if ($siteKey !== '' && !$hasSecret) {
      $form_state->setErrorByName('recaptcha_secret', $this->t('Phải nhập Secret Key khi đã cấu hình Site Key.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $settings = $this->configFactory->getEditable('nidqc_contact.settings')
      ->set('smtp.charset', 'UTF-8')
      ->set('smtp.host', trim((string) $form_state->getValue('smtp_host')))
      ->set('smtp.auth', (bool) $form_state->getValue('smtp_auth'))
      ->set('smtp.port', (int) $form_state->getValue('smtp_port'))
      ->set('smtp.security', strtoupper((string) $form_state->getValue('smtp_security')))
      ->set('smtp.admin_email', trim((string) $form_state->getValue('smtp_admin_email')))
      ->set('recaptcha.site_key', trim((string) $form_state->getValue('recaptcha_site_key')))
      ->set('recaptcha.minimum_score', (float) $form_state->getValue('recaptcha_minimum_score'));
    foreach (array_keys($this->socialChannels()) as $key) {
      $settings->set('social.' . $key, trim((string) $form_state->getValue('social_' . $key)));
    }

    $settings
      ->set('footer.tel', trim((string) $form_state->getValue('footer_tel')))
      ->set('footer.tel_note', trim((string) $form_state->getValue('footer_tel_note')))
      ->set('footer.fax', trim((string) $form_state->getValue('footer_fax')))
      ->set('footer.email', trim((string) $form_state->getValue('footer_email')));

    // Lưu cả nhóm bỏ trống: thứ tự và nhãn của bốn nhóm là cố định theo yêu cầu
    // nghiệp vụ, frontend mới là nơi quyết định ẩn nhóm rỗng.
    $services = [];
    foreach ($this->customerServiceKeys() as $key => $label) {
      $services[] = [
        'label' => (string) $label,
        'email' => trim((string) $form_state->getValue('cs_' . $key . '_email')),
        'hotline' => trim((string) $form_state->getValue('cs_' . $key . '_hotline')),
      ];
    }
    $settings->set('customer_services', $services);

    $settings->save();

    $this->configFactory->getEditable('system.site')
      ->set('name', trim((string) $form_state->getValue('site_name')))
      ->set('slogan', trim((string) $form_state->getValue('site_slogan')))
      ->set('mail', trim((string) $form_state->getValue('site_email')))
      ->save();

    $this->saveSecret(
      'nidqc_contact.smtp_username',
      trim((string) $form_state->getValue('smtp_username')),
      (bool) $form_state->getValue('smtp_username_clear'),
    );
    $this->saveSecret(
      'nidqc_contact.smtp_password',
      (string) $form_state->getValue('smtp_password'),
      (bool) $form_state->getValue('smtp_password_clear'),
    );
    $this->saveSecret(
      'nidqc_contact.recaptcha_secret',
      (string) $form_state->getValue('recaptcha_secret'),
      (bool) $form_state->getValue('recaptcha_secret_clear'),
    );

    parent::submitForm($form, $form_state);
  }

  /**
   * Sends a test email after settings have been saved.
   */
  public function sendTestEmail(array &$form, FormStateInterface $form_state): void {
    $recipient = trim((string) $form_state->getValue('smtp_admin_email'));
    if ($this->contactMailer->sendTest($recipient)) {
      $this->messenger()->addStatus($this->t('Đã gửi email thử tới @email.', ['@email' => $recipient]));
      return;
    }

    $this->messenger()->addError($this->t('Không gửi được email thử. Kiểm tra SMTP và nhật ký hệ thống.'));
  }

  /**
   * Kênh mạng xã hội hiển thị trên top bar: khoá config => nhãn trên form.
   *
   * Khoá phải khớp với `key` trong danh sách icon ở frontend (layouts/default.vue).
   */
  private function socialChannels(): array {
    return [
      'facebook' => $this->t('Facebook'),
      'youtube' => $this->t('YouTube'),
      'zalo' => $this->t('Zalo'),
      'tiktok' => $this->t('TikTok'),
    ];
  }

  /**
   * Bốn nhóm dịch vụ ở chân trang: khoá máy => nhãn hiển thị.
   *
   * Danh sách cố định theo yêu cầu nghiệp vụ (bảng chân trang trong tài liệu
   * feedback). Thêm nhóm mới thì thêm ở đây, thứ tự trong mảng là thứ tự hiển thị.
   */
  private function customerServiceKeys(): array {
    return [
      'testing' => $this->t('Dịch vụ Kiểm nghiệm'),
      'training' => $this->t('Đào tạo'),
      'calibration' => $this->t('Hiệu chuẩn thiết bị'),
      'standards' => $this->t('Cung ứng chất chuẩn'),
    ];
  }

  /**
   * Giá trị đã lưu của bốn nhóm dịch vụ, tra được theo khoá.
   *
   * Config lưu dạng danh sách có thứ tự (sequence) nên phải ghép lại theo thứ tự
   * khai báo trong customerServiceKeys() thì form mới điền đúng ô.
   */
  private function storedCustomerServices(): array {
    $stored = $this->config('nidqc_contact.settings')->get('customer_services');
    $stored = is_array($stored) ? array_values($stored) : [];
    $keys = array_keys($this->customerServiceKeys());

    $result = [];
    foreach ($keys as $index => $key) {
      $result[$key] = [
        'email' => (string) ($stored[$index]['email'] ?? ''),
        'hotline' => (string) ($stored[$index]['hotline'] ?? ''),
      ];
    }

    return $result;
  }

  /**
   * Saves or clears a non-exported secret.
   */
  private function saveSecret(string $key, string $value, bool $clear): void {
    if ($clear) {
      $this->state->delete($key);
      return;
    }
    if ($value !== '') {
      $this->state->set($key, $value);
    }
  }

  /**
   * Describes an optional environment override.
   */
  private function environmentDescription(string $name): string {
    return getenv($name) !== FALSE
      ? (string) $this->t('Đang bị biến môi trường @name ghi đè.', ['@name' => $name])
      : (string) $this->t('Có thể ghi đè an toàn bằng biến môi trường @name.', ['@name' => $name]);
  }

  /**
   * Describes secret storage without exposing its value.
   */
  private function secretDescription(string $environmentName, bool $stored): string {
    if (getenv($environmentName) !== FALSE) {
      return (string) $this->t('Đang lấy từ biến môi trường @name. Giá trị nhập tại đây sẽ không có hiệu lực khi biến này còn tồn tại.', ['@name' => $environmentName]);
    }

    return $stored
      ? (string) $this->t('Database đang có giá trị. Để trống để giữ nguyên; secret không được export.')
      : (string) $this->t('Chưa có giá trị. Secret được lưu trong database và không được export.');
  }

  /**
   * Describes a State API secret used without environment fallbacks.
   */
  private function storedSecretDescription(bool $stored): string {
    return $stored
      ? (string) $this->t('Database đang có giá trị. Để trống để giữ nguyên; secret không được export.')
      : (string) $this->t('Chưa có giá trị. Secret được lưu trong database và không được export.');
  }

}
