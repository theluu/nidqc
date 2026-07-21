<?php

/**
 * @file
 * Import news from the old Drupal 7 NIDQC site.
 *
 * Usage:
 *   ddev drush php:script scripts/import-old-news.php -- --dry-run --limit=10
 *   ddev drush php:script scripts/import-old-news.php -- --import --with-images
 *
 * Optional environment variables:
 *   OLD_NIDQC_USER, OLD_NIDQC_PASS
 *
 * Credentials are optional because the current old listing is publicly readable.
 * If the old site later requires authentication, the same script can log in
 * without writing credentials to the repository.
 */

declare(strict_types=1);

use Drupal\Component\Utility\Unicode;
use Drupal\Component\Utility\Xss;
use Drupal\Core\File\FileExists;
use Drupal\file\FileInterface;
use Drupal\node\Entity\Node;
use Drupal\node\NodeInterface;
use Drupal\taxonomy\TermInterface;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Cookie\CookieJar;
use GuzzleHttp\Exception\GuzzleException;

const NIDQC_OLD_BASE_URL = 'https://nidqc.gov.vn';
const NIDQC_OLD_ADMIN_PATH = '/admin/tin-tuc';
const NIDQC_OLD_USER_AGENT = 'NIDQC migration script';
const NIDQC_OLD_IMAGE_MAX_BYTES = 8388608;
const NIDQC_OLD_BODY_TAGS = [
  'a',
  'b',
  'blockquote',
  'br',
  'caption',
  'em',
  'h2',
  'h3',
  'h4',
  'i',
  'img',
  'li',
  'ol',
  'p',
  'strong',
  'sub',
  'sup',
  'table',
  'tbody',
  'td',
  'tfoot',
  'th',
  'thead',
  'tr',
  'u',
  'ul',
];
const NIDQC_OLD_ALLOWED_IMAGE_EXTENSIONS = ['gif', 'jpeg', 'jpg', 'png', 'webp'];
const NIDQC_OLD_ALLOWED_IMAGE_MIME = ['image/gif', 'image/jpeg', 'image/png', 'image/webp'];
const NIDQC_OLD_ATTACHMENT_MAX_BYTES = 52428800;
const NIDQC_OLD_ALLOWED_ATTACHMENT_EXTENSIONS = [
  'doc',
  'docx',
  'gif',
  'jpeg',
  'jpg',
  'pdf',
  'png',
  'ppt',
  'pptx',
  'rar',
  'webp',
  'xls',
  'xlsx',
  'zip',
];

$options = nidqc_old_news_parse_args($_SERVER['argv'] ?? []);
$isDryRun = !$options['import'] || $options['dry-run'];
$baseUrl = rtrim($options['base-url'], '/');
$adminPath = $options['admin-path'];

$entityTypeManager = \Drupal::entityTypeManager();
$nodeStorage = $entityTypeManager->getStorage('node');
$termStorage = $entityTypeManager->getStorage('taxonomy_term');
$fields = \Drupal::service('entity_field.manager')->getFieldDefinitions('node', 'news');

foreach (['field_date', 'field_category'] as $requiredField) {
  if (!isset($fields[$requiredField])) {
    throw new RuntimeException("Missing required news field: $requiredField");
  }
}
if (!isset($fields['body'])) {
  throw new RuntimeException('Missing news body field. Import would lose article content.');
}

$termMap = nidqc_old_news_term_map($termStorage);
$missingTerms = array_diff(
  ['Thông báo', 'Tin hoạt động', 'Mua sắm - đấu thầu', 'Đào tạo', 'Hội nghị - Hội thảo', 'Tuyển dụng'],
  array_keys($termMap),
);
if ($missingTerms !== []) {
  throw new RuntimeException('Missing news_category terms: ' . implode(', ', $missingTerms));
}

/** @var \GuzzleHttp\ClientInterface $httpClient */
$httpClient = \Drupal::httpClient();
$cookies = new CookieJar();

nidqc_old_news_try_login($httpClient, $cookies, $baseUrl);

$firstListHtml = nidqc_old_news_get($httpClient, $cookies, $baseUrl . $adminPath);
$lastPage = nidqc_old_news_last_page($firstListHtml);
if ($options['max-pages'] !== NULL) {
  $lastPage = min($lastPage, max(0, $options['max-pages'] - 1));
}

$stats = [
  'pages' => 0,
  'listed' => 0,
  'details' => 0,
  'created' => 0,
  'skipped' => 0,
  'updated' => 0,
  'images' => 0,
  'image_skipped' => 0,
  'attachments' => 0,
  'attachment_skipped' => 0,
  'by_old_category' => [],
  'by_new_category' => [],
  'warnings' => [],
];
$seenUrls = [];

printf(
  "%s import tin cũ từ %s%s | pages=%d | limit=%s | images=%s\n",
  $isDryRun ? 'DRY-RUN' : 'IMPORT',
  $baseUrl,
  $adminPath,
  $lastPage + 1,
  $options['limit'] === NULL ? 'none' : (string) $options['limit'],
  $options['with-images'] ? 'yes' : 'no',
);
printf("delay: %dms/request\n", $options['delay-ms']);

for ($page = 0; $page <= $lastPage; $page++) {
  $listUrl = nidqc_old_news_list_url($baseUrl, $adminPath, $page);
  $listHtml = $page === 0 ? $firstListHtml : nidqc_old_news_get($httpClient, $cookies, $listUrl);
  $stats['pages']++;
  $rows = nidqc_old_news_rows($listHtml, $baseUrl);
  printf("page %d/%d: %d rows\n", $page + 1, $lastPage + 1, count($rows));

  if ($rows === []) {
    $stats['warnings'][] = "Không thấy dòng dữ liệu ở page=$page.";
    break;
  }

  foreach ($rows as $row) {
    if ($options['limit'] !== NULL && $stats['listed'] >= $options['limit']) {
      break 2;
    }

    if (isset($seenUrls[$row['url']])) {
      continue;
    }
    $seenUrls[$row['url']] = TRUE;
    $stats['listed']++;
    $stats['by_old_category'][$row['old_category']] = ($stats['by_old_category'][$row['old_category']] ?? 0) + 1;

    $targetCategory = nidqc_old_news_target_category($row['old_category'], $row['title']);
    $stats['by_new_category'][$targetCategory] = ($stats['by_new_category'][$targetCategory] ?? 0) + 1;
    if (!isset($termMap[$targetCategory])) {
      $stats['warnings'][] = "Không thấy term mới '$targetCategory' cho '{$row['title']}'.";
      $stats['skipped']++;
      continue;
    }

    $existing = nidqc_old_news_existing_node($nodeStorage, $row['uuid']);
    if ($existing instanceof NodeInterface && !$options['update-existing']) {
      $stats['skipped']++;
      continue;
    }

    $detail = nidqc_old_news_detail($httpClient, $cookies, $row['url'], $baseUrl);
    $stats['details']++;
    if ($options['delay-ms'] > 0) {
      usleep($options['delay-ms'] * 1000);
    }
    $item = $row + $detail + [
      'target_category' => $targetCategory,
      'target_tid' => (int) $termMap[$targetCategory]->id(),
      'attachments' => [],
    ];

    if ($isDryRun) {
      $stats[$existing instanceof NodeInterface ? 'skipped' : 'created']++;
      continue;
    }

    $image = NULL;
    if ($options['with-images']) {
      if ($item['image_url'] !== '') {
        $image = nidqc_old_news_import_image($httpClient, $item['image_url'], $item['title'], $item['date'], $stats);
      }
      if ($item['body'] !== '') {
        $item['body'] = nidqc_old_news_import_body_images($httpClient, $item['body'], $item['title'], $item['date'], $stats);
        [$item['body'], $item['attachments']] = nidqc_old_news_import_attachments($httpClient, $item['body'], $item['title'], $item['date'], $stats);
      }
    }

    if ($existing instanceof NodeInterface) {
      nidqc_old_news_apply_node($existing, $item, $image);
      $existing->save();
      $stats['updated']++;
      continue;
    }

    $node = Node::create([
      'type' => 'news',
      'uuid' => $item['uuid'],
    ]);
    nidqc_old_news_apply_node($node, $item, $image);
    $node->save();
    $stats['created']++;
  }
}

nidqc_old_news_print_stats($stats);

/**
 * Parses command-line options passed after Drush's `--` delimiter.
 */
function nidqc_old_news_parse_args(array $argv): array {
  $options = [
    'admin-path' => NIDQC_OLD_ADMIN_PATH,
    'base-url' => NIDQC_OLD_BASE_URL,
    'delay-ms' => 100,
    'dry-run' => FALSE,
    'import' => FALSE,
    'limit' => NULL,
    'max-pages' => NULL,
    'update-existing' => FALSE,
    'with-images' => FALSE,
  ];

  foreach ($argv as $arg) {
    if ($arg === '--dry-run') {
      $options['dry-run'] = TRUE;
      continue;
    }
    if ($arg === '--import') {
      $options['import'] = TRUE;
      continue;
    }
    if ($arg === '--with-images') {
      $options['with-images'] = TRUE;
      continue;
    }
    if ($arg === '--update-existing') {
      $options['update-existing'] = TRUE;
      continue;
    }
    foreach (['admin-path', 'base-url'] as $key) {
      if (str_starts_with($arg, "--$key=")) {
        $options[$key] = trim(substr($arg, strlen($key) + 3));
      }
    }
    foreach (['delay-ms', 'limit', 'max-pages'] as $key) {
      if (str_starts_with($arg, "--$key=")) {
        $value = (int) substr($arg, strlen($key) + 3);
        $options[$key] = $key === 'delay-ms'
          ? max(0, min(5000, $value))
          : max(1, min(5000, $value));
      }
    }
  }

  if (!str_starts_with($options['admin-path'], '/')) {
    $options['admin-path'] = '/' . $options['admin-path'];
  }

  return $options;
}

/**
 * Attempts optional Drupal 7 login using environment variables.
 */
function nidqc_old_news_try_login(ClientInterface $client, CookieJar $cookies, string $baseUrl): void {
  $user = getenv('OLD_NIDQC_USER');
  $pass = getenv('OLD_NIDQC_PASS');
  if (!is_string($user) || trim($user) === '' || !is_string($pass) || $pass === '') {
    print "login: skipped (OLD_NIDQC_USER/OLD_NIDQC_PASS not set)\n";
    return;
  }

  try {
    $html = nidqc_old_news_get($client, $cookies, $baseUrl . '/user');
    $dom = nidqc_old_news_dom($html);
    $xpath = new DOMXPath($dom);
    $forms = $xpath->query("//form[contains(@id, 'user-login') or (.//input[@name='name'] and .//input[@name='pass'])]");
    if (!$forms || $forms->length === 0) {
      print "login: skipped (login form not found)\n";
      return;
    }

    /** @var \DOMElement $form */
    $form = $forms->item(0);
    $params = [];
    $inputs = $xpath->query('.//input', $form);
    foreach ($inputs ?? [] as $input) {
      if (!$input instanceof DOMElement) {
        continue;
      }
      $name = $input->getAttribute('name');
      if ($name === '') {
        continue;
      }
      $params[$name] = $input->getAttribute('value');
    }
    $params['name'] = trim($user);
    $params['pass'] = $pass;
    $params['op'] = $params['op'] ?? 'Log in';

    $action = $form->getAttribute('action') ?: '/user';
    $response = $client->request('POST', nidqc_old_news_absolute_url($action, $baseUrl), [
      'allow_redirects' => TRUE,
      'cookies' => $cookies,
      'form_params' => $params,
      'headers' => ['User-Agent' => NIDQC_OLD_USER_AGENT],
      'timeout' => 20,
    ]);
    $body = (string) $response->getBody();
    print str_contains($body, '/user/logout') || str_contains($body, 'admin-menu')
      ? "login: ok\n"
      : "login: attempted\n";
  }
  catch (GuzzleException $e) {
    print "login: failed (" . $e->getCode() . ")\n";
  }
}

/**
 * Fetches a URL as text.
 */
function nidqc_old_news_get(ClientInterface $client, CookieJar $cookies, string $url): string {
  try {
    $response = $client->request('GET', $url, [
      'allow_redirects' => TRUE,
      'cookies' => $cookies,
      'headers' => [
        'Accept' => 'text/html,application/xhtml+xml',
        'User-Agent' => NIDQC_OLD_USER_AGENT,
      ],
      'timeout' => 30,
    ]);
  }
  catch (GuzzleException $e) {
    throw new RuntimeException("Cannot fetch $url: " . $e->getMessage(), 0, $e);
  }

  $status = $response->getStatusCode();
  if ($status >= 400) {
    throw new RuntimeException("Cannot fetch $url: HTTP $status");
  }

  return (string) $response->getBody();
}

/**
 * Builds a listing URL for a zero-based pager index.
 */
function nidqc_old_news_list_url(string $baseUrl, string $adminPath, int $page): string {
  if ($page === 0) {
    return $baseUrl . $adminPath;
  }

  return $baseUrl . $adminPath . '?title=&field_dmtt_tid=All&page=' . $page;
}

/**
 * Extracts the last zero-based pager index.
 */
function nidqc_old_news_last_page(string $html): int {
  $dom = nidqc_old_news_dom($html);
  $xpath = new DOMXPath($dom);
  $lastPage = 0;
  foreach ($xpath->query("//ul[contains(@class, 'pager')]//a[@href]") ?? [] as $link) {
    if (!$link instanceof DOMElement) {
      continue;
    }
    $query = parse_url(html_entity_decode($link->getAttribute('href'), ENT_QUOTES | ENT_HTML5, 'UTF-8'), PHP_URL_QUERY);
    if (!is_string($query)) {
      continue;
    }
    parse_str($query, $params);
    if (isset($params['page']) && is_numeric($params['page'])) {
      $lastPage = max($lastPage, (int) $params['page']);
    }
  }

  return $lastPage;
}

/**
 * Extracts rows from the old admin listing.
 *
 * @return array<int, array{title: string, old_category: string, date: string, url: string, uuid: string}>
 */
function nidqc_old_news_rows(string $html, string $baseUrl): array {
  $dom = nidqc_old_news_dom($html);
  $xpath = new DOMXPath($dom);
  $rows = [];

  foreach ($xpath->query("//table[contains(concat(' ', normalize-space(@class), ' '), ' table ')]//tbody/tr") ?? [] as $tr) {
    if (!$tr instanceof DOMElement) {
      continue;
    }

    $link = nidqc_old_news_first($xpath, ".//td[contains(@class, 'views-field-title')]//a[@href]", $tr);
    $categoryCell = nidqc_old_news_first($xpath, ".//td[contains(@class, 'views-field-field-dmtt')]", $tr);
    $dateCell = nidqc_old_news_first($xpath, ".//td[contains(@class, 'views-field-created')]", $tr);
    if (!$link instanceof DOMElement) {
      continue;
    }

    $title = nidqc_old_news_text($link->textContent);
    $url = nidqc_old_news_absolute_url($link->getAttribute('href'), $baseUrl);
    $oldCategory = $categoryCell instanceof DOMNode ? nidqc_old_news_text($categoryCell->textContent) : '';
    $date = $dateCell instanceof DOMNode ? nidqc_old_news_parse_date(nidqc_old_news_text($dateCell->textContent)) : NULL;

    if ($title === '' || $url === '' || $date === NULL) {
      continue;
    }

    $rows[] = [
      'title' => Unicode::truncate($title, 255, TRUE, TRUE),
      'old_category' => $oldCategory !== '' ? $oldCategory : 'Tin tức sự kiện',
      'date' => $date,
      'url' => $url,
      'uuid' => nidqc_old_news_uuid($url),
    ];
  }

  return $rows;
}

/**
 * Extracts sanitized body, summary and image URL from a detail page.
 *
 * @return array{body: string, summary: string, image_url: string}
 */
function nidqc_old_news_detail(ClientInterface $client, CookieJar $cookies, string $url, string $baseUrl): array {
  $html = nidqc_old_news_get($client, $cookies, $url);
  $dom = nidqc_old_news_dom($html);
  $xpath = new DOMXPath($dom);

  $description = nidqc_old_news_meta($xpath, 'og:description');
  if ($description === '') {
    $description = nidqc_old_news_meta_name($xpath, 'description');
  }
  $imageUrl = nidqc_old_news_meta($xpath, 'og:image');
  $imageUrl = $imageUrl !== '' ? nidqc_old_news_absolute_url($imageUrl, $baseUrl) : '';

  $content = nidqc_old_news_first($xpath, "//*[contains(concat(' ', normalize-space(@class), ' '), ' entry-content ')]");
  $body = $content instanceof DOMNode ? nidqc_old_news_inner_html($content) : '';
  $body = nidqc_old_news_clean_body($body, $baseUrl);

  if ($body === '' && $description !== '') {
    $body = '<p>' . htmlspecialchars($description, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '</p>';
  }

  return [
    'body' => $body,
    'summary' => Unicode::truncate($description !== '' ? $description : nidqc_old_news_plain_text($body), 300, TRUE, TRUE),
    'image_url' => $imageUrl,
  ];
}

/**
 * Applies normalized old item data to a node.
 */
function nidqc_old_news_apply_node(NodeInterface $node, array $item, ?FileInterface $image): void {
  $timestamp = nidqc_old_news_timestamp($item['date']);
  $node->set('type', 'news');
  $node->setTitle($item['title']);
  $node->setOwnerId(1);
  $node->setPublished(TRUE);
  $node->setCreatedTime($timestamp);
  $node->setChangedTime($timestamp);
  $node->set('field_date', $item['date']);
  $node->set('field_category', ['target_id' => $item['target_tid']]);
  $node->set('body', [
    'value' => $item['body'],
    'summary' => $item['summary'],
    'format' => 'basic_html',
  ]);

  if ($node->hasField('field_tag')) {
    $node->set('field_tag', '');
  }

  if ($node->hasField('field_attachments') && !empty($item['attachments'])) {
    $node->set('field_attachments', array_map(
      static fn(array $attachment): array => [
        'target_id' => $attachment['fid'],
        'description' => $attachment['label'],
      ],
      $item['attachments'],
    ));
  }

  if ($image instanceof FileInterface && $node->hasField('field_image') && $node->get('field_image')->isEmpty()) {
    $node->set('field_image', [
      'target_id' => $image->id(),
      'alt' => $item['title'],
    ]);
  }
}

/**
 * Imports a remote image into public files.
 */
function nidqc_old_news_import_image(ClientInterface $client, string $url, string $title, string $date, array &$stats): ?FileInterface {
  if (!nidqc_old_news_valid_image_url($url)) {
    $stats['image_skipped']++;
    return NULL;
  }

  try {
    $response = $client->request('GET', $url, [
      'headers' => [
        'Accept' => 'image/avif,image/webp,image/apng,image/*,*/*;q=0.8',
        'User-Agent' => NIDQC_OLD_USER_AGENT,
      ],
      'timeout' => 30,
    ]);
  }
  catch (GuzzleException) {
    $stats['image_skipped']++;
    return NULL;
  }

  if ($response->getStatusCode() >= 400) {
    $stats['image_skipped']++;
    return NULL;
  }

  $data = (string) $response->getBody();
  if ($data === '' || strlen($data) > NIDQC_OLD_IMAGE_MAX_BYTES) {
    $stats['image_skipped']++;
    return NULL;
  }

  $imageInfo = @getimagesizefromstring($data);
  if (!is_array($imageInfo) || empty($imageInfo['mime']) || !in_array($imageInfo['mime'], NIDQC_OLD_ALLOWED_IMAGE_MIME, TRUE)) {
    $stats['image_skipped']++;
    return NULL;
  }

  $extension = strtolower(pathinfo((string) parse_url($url, PHP_URL_PATH), PATHINFO_EXTENSION));
  if ($extension === 'jpg') {
    $extension = 'jpeg';
  }
  $pathExtension = $extension === 'jpeg' ? 'jpg' : $extension;
  $filename = nidqc_old_news_image_filename($url, $title, $pathExtension);
  $directory = 'public://old-news/' . substr($date, 0, 7);
  $fileSystem = \Drupal::service('file_system');
  $fileSystem->prepareDirectory($directory, $fileSystem::CREATE_DIRECTORY | $fileSystem::MODIFY_PERMISSIONS);
  $uri = $directory . '/' . $filename;

  $fileStorage = \Drupal::entityTypeManager()->getStorage('file');
  $existing = $fileStorage->loadByProperties(['uri' => $uri]);
  if ($existing) {
    return reset($existing);
  }

  /** @var \Drupal\file\FileRepositoryInterface $repository */
  $repository = \Drupal::service('file.repository');
  $file = $repository->writeData($data, $uri, FileExists::Error);
  $file->setPermanent();
  $file->save();
  $stats['images']++;

  return $file;
}

/**
 * Imports inline body images and rewrites their src attributes to local files.
 */
function nidqc_old_news_import_body_images(ClientInterface $client, string $body, string $title, string $date, array &$stats): string {
  $dom = nidqc_old_news_dom('<div id="nidqc-old-news-body">' . $body . '</div>');
  $xpath = new DOMXPath($dom);
  /** @var \Drupal\Core\File\FileUrlGeneratorInterface $fileUrlGenerator */
  $fileUrlGenerator = \Drupal::service('file_url_generator');

  foreach ($xpath->query("//*[@id='nidqc-old-news-body']//img[@src]") ?? [] as $node) {
    if (!$node instanceof DOMElement) {
      continue;
    }

    $file = nidqc_old_news_import_image($client, $node->getAttribute('src'), $title, $date, $stats);
    if (!$file instanceof FileInterface) {
      continue;
    }

    $node->setAttribute('src', $fileUrlGenerator->generateAbsoluteString($file->getFileUri()));
    if ($node->getAttribute('alt') === '') {
      $node->setAttribute('alt', $title);
    }
  }

  $wrapper = nidqc_old_news_first($xpath, "//*[@id='nidqc-old-news-body']");
  $html = $wrapper instanceof DOMNode ? nidqc_old_news_inner_html($wrapper) : $body;

  return trim(Xss::filter($html, NIDQC_OLD_BODY_TAGS));
}

/**
 * Downloads a remote attachment (document or image) into public files.
 */
function nidqc_old_news_import_file(ClientInterface $client, string $url, string $title, string $date, array &$stats): ?FileInterface {
  if (!nidqc_old_news_valid_attachment_url($url)) {
    $stats['attachment_skipped']++;
    return NULL;
  }

  try {
    $response = $client->request('GET', $url, [
      'headers' => ['User-Agent' => NIDQC_OLD_USER_AGENT],
      'timeout' => 60,
    ]);
  }
  catch (GuzzleException) {
    $stats['attachment_skipped']++;
    return NULL;
  }

  if ($response->getStatusCode() >= 400) {
    $stats['attachment_skipped']++;
    return NULL;
  }

  $data = (string) $response->getBody();
  if ($data === '' || strlen($data) > NIDQC_OLD_ATTACHMENT_MAX_BYTES) {
    $stats['attachment_skipped']++;
    return NULL;
  }

  $extension = strtolower(pathinfo((string) parse_url($url, PHP_URL_PATH), PATHINFO_EXTENSION));
  if (!in_array($extension, NIDQC_OLD_ALLOWED_ATTACHMENT_EXTENSIONS, TRUE)) {
    $stats['attachment_skipped']++;
    return NULL;
  }

  $filename = nidqc_old_news_image_filename($url, $title, $extension);
  $directory = 'public://old-news/files/' . substr($date, 0, 7);
  $fileSystem = \Drupal::service('file_system');
  $fileSystem->prepareDirectory($directory, $fileSystem::CREATE_DIRECTORY | $fileSystem::MODIFY_PERMISSIONS);
  $uri = $directory . '/' . $filename;

  $fileStorage = \Drupal::entityTypeManager()->getStorage('file');
  $existing = $fileStorage->loadByProperties(['uri' => $uri]);
  if ($existing) {
    return reset($existing);
  }

  /** @var \Drupal\file\FileRepositoryInterface $repository */
  $repository = \Drupal::service('file.repository');
  $file = $repository->writeData($data, $uri, FileExists::Error);
  $file->setPermanent();
  $file->save();
  $stats['attachments']++;

  return $file;
}

/**
 * Extracts and localizes file attachments linked from the body.
 *
 * Downloads each linked file, rewrites its href to the local copy and returns
 * the rewritten body plus a structured attachment list for field_attachments.
 *
 * @return array{0: string, 1: array<int, array{fid: int, label: string}>}
 */
function nidqc_old_news_import_attachments(ClientInterface $client, string $body, string $title, string $date, array &$stats): array {
  $dom = nidqc_old_news_dom('<div id="nidqc-old-news-body">' . $body . '</div>');
  $xpath = new DOMXPath($dom);
  /** @var \Drupal\Core\File\FileUrlGeneratorInterface $fileUrlGenerator */
  $fileUrlGenerator = \Drupal::service('file_url_generator');

  $attachments = [];
  $seen = [];
  foreach ($xpath->query("//*[@id='nidqc-old-news-body']//a[@href]") ?? [] as $node) {
    if (!$node instanceof DOMElement) {
      continue;
    }
    $href = $node->getAttribute('href');
    if (!nidqc_old_news_valid_attachment_url($href)) {
      continue;
    }

    if (!array_key_exists($href, $seen)) {
      $file = nidqc_old_news_import_file($client, $href, $title, $date, $stats);
      $seen[$href] = $file instanceof FileInterface ? $file : NULL;
    }
    $file = $seen[$href];
    if (!$file instanceof FileInterface) {
      continue;
    }

    $node->setAttribute('href', $fileUrlGenerator->generateAbsoluteString($file->getFileUri()));

    $label = nidqc_old_news_text($node->textContent);
    if (!isset($attachments[$file->id()])) {
      $attachments[$file->id()] = [
        'fid' => (int) $file->id(),
        'label' => Unicode::truncate($label !== '' ? $label : $file->getFilename(), 128, TRUE, TRUE),
      ];
    }
  }

  $wrapper = nidqc_old_news_first($xpath, "//*[@id='nidqc-old-news-body']");
  $html = $wrapper instanceof DOMNode ? nidqc_old_news_inner_html($wrapper) : $body;

  return [trim(Xss::filter($html, NIDQC_OLD_BODY_TAGS)), array_values($attachments)];
}

/**
 * Validates a remote attachment URL before downloading it.
 */
function nidqc_old_news_valid_attachment_url(string $url): bool {
  $parts = parse_url($url);
  if (($parts['scheme'] ?? '') !== 'https' || ($parts['host'] ?? '') !== 'nidqc.gov.vn') {
    return FALSE;
  }
  $extension = strtolower(pathinfo((string) ($parts['path'] ?? ''), PATHINFO_EXTENSION));
  return in_array($extension, NIDQC_OLD_ALLOWED_ATTACHMENT_EXTENSIONS, TRUE);
}

/**
 * Maps old categories/titles to the new approved news_category terms.
 */
function nidqc_old_news_target_category(string $oldCategory, string $title): string {
  $haystack = mb_strtolower($oldCategory . ' ' . $title);

  if (preg_match('/tuyển dụng|xét tuyển|viên chức|nhân sự/u', $haystack)) {
    return 'Tuyển dụng';
  }
  if (preg_match('/hội nghị|hội thảo|tọa đàm|toạ đàm/u', $haystack)) {
    return 'Hội nghị - Hội thảo';
  }
  if (preg_match('/đào tạo|tuyển sinh|nghiên cứu sinh|luận án|nckh|tập huấn/u', $haystack)) {
    return 'Đào tạo';
  }
  if (preg_match('/mua sắm|đấu thầu|mời thầu|báo giá|chào giá|công khai|dự toán|ngân sách|gói thầu|nhà thầu|vật tư|hóa chất|hoá chất|linh kiện|thiết bị|cải tạo|sửa chữa|hiệu chuẩn|cung cấp|lắp đặt/u', $haystack)) {
    return 'Mua sắm - đấu thầu';
  }
  if ($oldCategory === 'Thông báo') {
    return 'Thông báo';
  }

  return 'Tin hoạt động';
}

/**
 * Loads news_category terms keyed by name.
 *
 * @return array<string, \Drupal\taxonomy\TermInterface>
 */
function nidqc_old_news_term_map($termStorage): array {
  $terms = $termStorage->loadByProperties(['vid' => 'news_category']);
  $map = [];
  foreach ($terms as $term) {
    if ($term instanceof TermInterface) {
      $map[$term->label()] = $term;
    }
  }

  return $map;
}

/**
 * Finds an existing imported node by deterministic UUID.
 */
function nidqc_old_news_existing_node($nodeStorage, string $uuid): ?NodeInterface {
  $existing = $nodeStorage->loadByProperties(['uuid' => $uuid]);
  $node = $existing ? reset($existing) : NULL;
  return $node instanceof NodeInterface ? $node : NULL;
}

/**
 * Parses a Vietnamese short date from the old Drupal 7 view.
 */
function nidqc_old_news_parse_date(string $value): ?string {
  if (!preg_match('#^(\d{1,2})/(\d{1,2})/(\d{2}|\d{4})$#', $value, $matches)) {
    return NULL;
  }

  $year = (int) $matches[3];
  if ($year < 100) {
    $year += 2000;
  }

  return sprintf('%04d-%02d-%02d', $year, (int) $matches[2], (int) $matches[1]);
}

/**
 * Converts a Y-m-d date to a stable morning timestamp.
 */
function nidqc_old_news_timestamp(string $date): int {
  return (new DateTimeImmutable($date . ' 09:00:00', new DateTimeZone('Asia/Ho_Chi_Minh')))->getTimestamp();
}

/**
 * Loads an HTML document without fetching external resources.
 */
function nidqc_old_news_dom(string $html): DOMDocument {
  $previous = libxml_use_internal_errors(TRUE);
  $dom = new DOMDocument('1.0', 'UTF-8');
  $dom->loadHTML('<?xml encoding="UTF-8">' . $html, LIBXML_NONET | LIBXML_NOERROR | LIBXML_NOWARNING);
  libxml_clear_errors();
  libxml_use_internal_errors($previous);

  return $dom;
}

/**
 * Returns the first XPath match.
 */
function nidqc_old_news_first(DOMXPath $xpath, string $query, ?DOMNode $context = NULL): ?DOMNode {
  $result = $context instanceof DOMNode ? $xpath->query($query, $context) : $xpath->query($query);
  if (!$result || $result->length === 0) {
    return NULL;
  }

  return $result->item(0);
}

/**
 * Extracts an Open Graph meta value.
 */
function nidqc_old_news_meta(DOMXPath $xpath, string $property): string {
  $node = nidqc_old_news_first($xpath, "//meta[@property='$property']/@content");
  return $node instanceof DOMAttr ? nidqc_old_news_text($node->value) : '';
}

/**
 * Extracts a named meta value.
 */
function nidqc_old_news_meta_name(DOMXPath $xpath, string $name): string {
  $node = nidqc_old_news_first($xpath, "//meta[@name='$name']/@content");
  return $node instanceof DOMAttr ? nidqc_old_news_text($node->value) : '';
}

/**
 * Returns a node's inner HTML.
 */
function nidqc_old_news_inner_html(DOMNode $node): string {
  $html = '';
  foreach ($node->childNodes as $child) {
    $html .= $node->ownerDocument?->saveHTML($child) ?: '';
  }

  return $html;
}

/**
 * Sanitizes old detail HTML before storing it in Drupal.
 */
function nidqc_old_news_clean_body(string $html, string $baseUrl): string {
  if (trim($html) === '') {
    return '';
  }

  $dom = nidqc_old_news_dom('<div id="nidqc-old-news-body">' . $html . '</div>');
  $xpath = new DOMXPath($dom);

  foreach ($xpath->query('//script|//style|//iframe|//form|//input|//button|//object|//embed') ?? [] as $node) {
    $node->parentNode?->removeChild($node);
  }

  foreach ($xpath->query('//*[@href]') ?? [] as $node) {
    if ($node instanceof DOMElement) {
      $node->setAttribute('href', nidqc_old_news_absolute_url($node->getAttribute('href'), $baseUrl));
    }
  }
  foreach ($xpath->query('//*[@src]') ?? [] as $node) {
    if ($node instanceof DOMElement) {
      $node->setAttribute('src', nidqc_old_news_absolute_url($node->getAttribute('src'), $baseUrl));
    }
  }

  foreach ($xpath->query('//*') ?? [] as $node) {
    if (!$node instanceof DOMElement || !$node->hasAttributes()) {
      continue;
    }
    if ($node->getAttribute('id') === 'nidqc-old-news-body') {
      continue;
    }
    foreach (iterator_to_array($node->attributes) as $attribute) {
      if (!$attribute instanceof DOMAttr) {
        continue;
      }
      $name = strtolower($attribute->name);
      $tag = strtolower($node->tagName);
      $allowed = match ($tag) {
        'a' => in_array($name, ['href', 'rel', 'title'], TRUE),
        'img' => in_array($name, ['alt', 'src'], TRUE),
        'td', 'th' => in_array($name, ['colspan', 'rowspan'], TRUE),
        default => FALSE,
      };
      if (!$allowed || str_starts_with($name, 'on')) {
        $node->removeAttributeNode($attribute);
      }
    }
    if ($tag === 'a') {
      $node->setAttribute('rel', 'noopener noreferrer');
    }
  }

  $wrapper = nidqc_old_news_first($xpath, "//*[@id='nidqc-old-news-body']");
  $clean = $wrapper instanceof DOMNode ? nidqc_old_news_inner_html($wrapper) : '';

  return trim(Xss::filter($clean, NIDQC_OLD_BODY_TAGS));
}

/**
 * Converts text to one clean line.
 */
function nidqc_old_news_text(string $value): string {
  $value = html_entity_decode($value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
  $value = str_replace("\xC2\xA0", ' ', $value);
  $value = preg_replace('/[[:space:]]+/u', ' ', $value) ?? $value;

  return trim($value);
}

/**
 * Plain text from a body fragment.
 */
function nidqc_old_news_plain_text(string $html): string {
  return nidqc_old_news_text(strip_tags($html));
}

/**
 * Converts old relative paths to absolute URLs.
 */
function nidqc_old_news_absolute_url(string $href, string $baseUrl): string {
  $href = html_entity_decode(trim($href), ENT_QUOTES | ENT_HTML5, 'UTF-8');
  if ($href === '') {
    return '';
  }
  // Leave non-navigational URIs (data:, mailto:, tel:, #anchors) untouched.
  if (str_starts_with($href, 'data:') || str_starts_with($href, 'mailto:') || str_starts_with($href, 'tel:') || str_starts_with($href, '#')) {
    return $href;
  }
  if (str_starts_with($href, 'https://') || str_starts_with($href, 'http://')) {
    return $href;
  }
  if (str_starts_with($href, '//')) {
    return 'https:' . $href;
  }
  if (str_starts_with($href, '/')) {
    return rtrim($baseUrl, '/') . $href;
  }

  return rtrim($baseUrl, '/') . '/' . ltrim($href, '/');
}

/**
 * Creates a deterministic UUID from the old canonical URL.
 */
function nidqc_old_news_uuid(string $url): string {
  $hash = sha1('nidqc-old-news:' . $url);
  $timeHi = (hexdec(substr($hash, 12, 4)) & 0x0fff) | 0x5000;
  $clockSeq = (hexdec(substr($hash, 16, 4)) & 0x3fff) | 0x8000;

  return sprintf(
    '%s-%s-%04x-%04x-%s',
    substr($hash, 0, 8),
    substr($hash, 8, 4),
    $timeHi,
    $clockSeq,
    substr($hash, 20, 12),
  );
}

/**
 * Validates a remote image URL before downloading it.
 */
function nidqc_old_news_valid_image_url(string $url): bool {
  $parts = parse_url($url);
  if (($parts['scheme'] ?? '') !== 'https' || ($parts['host'] ?? '') !== 'nidqc.gov.vn') {
    return FALSE;
  }

  $extension = strtolower(pathinfo((string) ($parts['path'] ?? ''), PATHINFO_EXTENSION));
  return in_array($extension, NIDQC_OLD_ALLOWED_IMAGE_EXTENSIONS, TRUE);
}

/**
 * Builds a stable local image filename.
 */
function nidqc_old_news_image_filename(string $url, string $title, string $extension): string {
  $slug = trim(preg_replace('/[^a-z0-9]+/', '-', mb_strtolower(nidqc_old_news_ascii($title))) ?? '', '-');
  if ($slug === '') {
    $slug = 'old-news';
  }
  $slug = Unicode::truncate($slug, 80, FALSE, FALSE);

  return substr(sha1($url), 0, 12) . '-' . $slug . '.' . $extension;
}

/**
 * Best-effort ASCII transliteration for stable filenames.
 */
function nidqc_old_news_ascii(string $value): string {
  $converted = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value);
  return is_string($converted) && $converted !== '' ? $converted : $value;
}

/**
 * Prints a compact report.
 */
function nidqc_old_news_print_stats(array $stats): void {
  print "pages: {$stats['pages']}\n";
  print "listed: {$stats['listed']} | details: {$stats['details']} | created: {$stats['created']} | updated: {$stats['updated']} | skipped: {$stats['skipped']}\n";
  print "images: {$stats['images']} | image_skipped: {$stats['image_skipped']}\n";
  print "attachments: {$stats['attachments']} | attachment_skipped: {$stats['attachment_skipped']}\n";
  nidqc_old_news_print_bucket('old categories', $stats['by_old_category']);
  nidqc_old_news_print_bucket('new categories', $stats['by_new_category']);

  $warnings = array_values(array_unique($stats['warnings']));
  if ($warnings !== []) {
    print "warnings:\n";
    foreach (array_slice($warnings, 0, 25) as $warning) {
      print "  - $warning\n";
    }
    if (count($warnings) > 25) {
      print '  - ... ' . (count($warnings) - 25) . " more\n";
    }
  }
}

/**
 * Prints a sorted stats bucket.
 */
function nidqc_old_news_print_bucket(string $label, array $bucket): void {
  ksort($bucket);
  print "$label:\n";
  foreach ($bucket as $key => $count) {
    print "  - $key: $count\n";
  }
}
