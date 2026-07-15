# Drupal Coding Standard — NIDQC

> Nền tảng: [Drupal coding standards](https://www.drupal.org/docs/develop/standards) chính thức.
> File này chỉ ghi những gì **riêng của dự án** hoặc **hay bị làm sai**.

## 1. Kiểm tra

```bash
ddev composer phpcs        # kiểm tra
ddev composer phpcbf       # tự sửa lỗi format
```

Chuẩn: `Drupal` + `DrupalPractice`. Phải sạch trước khi merge.

## 2. Đặt tên

| Loại | Quy tắc | Ví dụ |
|---|---|---|
| Module | `nidqc_` + snake_case | `nidqc_island` |
| Class | PascalCase | `DocumentListController` |
| Method/biến | camelCase | `$issuedDate` |
| Hằng | SCREAMING_SNAKE | `MAX_LIMIT` |
| Field | `field_` + snake_case | `field_issued_date` |
| Twig | kebab-case | `node--news--full.html.twig` |

Tên bằng **tiếng Anh**. Comment có thể tiếng Việt.

## 3. PHP

```php
declare(strict_types=1);   // bắt buộc ở mọi file PHP mới
```

- Type hint đầy đủ cho tham số và giá trị trả về.
- Dùng dependency injection, **không** `\Drupal::service()` trong class có thể inject.
- Một class một trách nhiệm. Không có class `Helper`/`Utils` chứa mọi thứ.

```php
// ❌ service locator trong class
public function build() {
  $node = \Drupal::entityTypeManager()->getStorage('node')->load(1);
}

// ✅ inject
public function __construct(
  private readonly EntityTypeManagerInterface $entityTypeManager,
) {}
```

## 4. Bắt buộc về bảo mật

Không thương lượng — xem `docs/security/SECURITY_POLICY.md`:

- `->accessCheck(TRUE)` ở **mọi** Entity Query (nếu `FALSE` phải có comment giải thích)
- Không nối chuỗi SQL — luôn placeholder
- Không tắt Twig autoescape
- `|raw` phải có comment giải thích vì sao an toàn
- Form dùng Form API (CSRF sẵn có)

## 5. Render array & cache

```php
$build = [
  '#theme' => 'nidqc_document_list',
  '#items' => $items,
  '#cache' => [
    'tags' => ['node_list:document'],   // sửa document → tự xoá cache
    'contexts' => ['url.query_args'],   // lọc khác nhau → cache khác nhau
  ],
];
```

Sai cache tag = người dùng thấy nội dung cũ sau khi biên tập viên sửa. Lỗi này khó phát hiện
lúc dev (cache thường tắt) và chỉ lộ ra trên production.

## 6. Twig

```twig
{# ✅ #}
{{ node.label }}

{# ❌ trừ khi đã json_encode/sanitize + có comment #}
{{ content|raw }}
```

- Logic nghiệp vụ ở PHP (preprocess), **không** ở Twig.
- Không gọi service từ Twig.
- Không hard-code màu — dùng `var(--nidqc-*)`.

## 7. Comment

```php
/**
 * Lọc danh sách văn bản theo loại và năm ban hành.
 */
```

- Docblock cho mọi class và method public.
- Comment giải thích **vì sao**, không phải **cái gì**. Code đã nói cái gì rồi.
- Comment tiếng Việt được, nhưng phải có dấu.

```php
// ❌ vô nghĩa
$limit = 100; // đặt limit bằng 100

// ✅ nói điều code không nói được
$limit = 100; // Trên 100 bản ghi thì query field_file join chậm rõ rệt.
```

## 8. Cấm

- ⛔ Sửa `web/core/`, `vendor/`, `web/modules/contrib/`
- ⛔ Patch contrib trực tiếp (dùng `composer-patches` + task được duyệt)
- ⛔ `composer require` không qua duyệt
- ⛔ `eval()`, `extract()`, `$$biến`
- ⛔ Tắt error reporting để giấu lỗi
- ⛔ `dpm()`, `var_dump()`, `kint()` trong code merge

## 9. Git

Xem `docs/standards/GIT_WORKFLOW.md`.
