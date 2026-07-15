---
id: TASK-002
title: Sửa config_sync_directory và đưa settings.php vào quản lý git
status: ready
step: 4                  # Drupal Backend — hạ tầng config
owner: <chưa gán>
reviewer: <chưa gán — KHÔNG được trùng owner>
created: 2026-07-16

schema_change: false     # không đụng content type / field
new_package: false       # không composer require, không npm install
config_change: true      # ⚠️ export toàn bộ config lần đầu vào config/sync/

allowed_files:
  - web/sites/default/settings.php
  - .gitignore
  - config/sync/**              # do `drush cex` sinh ra
  - docs/architecture/BACKEND_ARCHITECTURE.md
  - CHANGELOG.md

read_only:
  - web/sites/default/settings.ddev.php
  - web/core/assets/scaffold/files/default.settings.php
  - docs/standards/GIT_WORKFLOW.md
  - docs/security/SECURITY_POLICY.md
---

# TASK-002 — Sửa `config_sync_directory` và đưa `settings.php` vào git

> 🔴 **Task chặn.** TASK-001 (và mọi task có `config_change: true`) không tái lập được
> cho tới khi task này xong. Làm task này **trước** khi merge TASK-001.

## 1. Mục tiêu

Sau task này, `ddev drush cex` ghi config ra `config/sync/` và config đó **được commit**.
Người mới `git clone` + `ddev start` + `drush cim` sẽ ra **đúng** cấu hình như máy người khác —
kể cả theme mặc định, ngôn ngữ, module bật.

Hiện tại điều đó **không xảy ra**: config export ra một thư mục bị gitignore và biến mất.

## 2. Bối cảnh — nguyên nhân gốc đã truy được

Đã kiểm tra thật ngày 2026-07-16:

**2.1.** `settings.php` **không** đặt `config_sync_directory` — dòng 261 chỉ là comment mẫu:
```php
# $settings['config_sync_directory'] = '/directory/outside/webroot';
```

**2.2.** `settings.ddev.php` (dòng 32–35) đặt **fallback**:
```php
// Set $settings['config_sync_directory'] if not set in settings.php.
if (empty($settings['config_sync_directory'])) {
  $settings['config_sync_directory'] = 'sites/default/files/sync';
}
```
Chính comment của DDEV nói rõ: **nó mong đợi `settings.php` đặt giá trị này.** Fallback chỉ là
lưới an toàn, không phải chỗ để cấu hình.

**2.3.** Kết quả: `config_sync_directory` = `sites/default/files/sync`, nằm trong
`web/sites/*/files/` — **bị `.gitignore` dòng 35 chặn**. `drush cex` ghi ra đó rồi mất hút.

**2.4.** Nhưng `.gitignore` dòng 20 lại chặn luôn `web/sites/*/settings.php` — tức là **chỗ duy nhất
có thể đặt setting này cũng không được commit**. Đây là vòng luẩn quẩn: sửa settings.php cũng vô ích
vì người khác không nhận được.

**2.5.** Mâu thuẫn tài liệu: `BACKEND_ARCHITECTURE.md` §6 nói config export vào `config/sync/`,
`.env.example` có `CONFIG_SYNC_DIRECTORY=../config/sync`. **Tài liệu mô tả ý định, thực tế chưa khớp.**

## 3. Phạm vi

### Trong phạm vi
- Đặt `config_sync_directory` trong `settings.php`
- Bật include `settings.local.php` (chỗ để secret cho staging/production)
- Bỏ `settings.php` khỏi `.gitignore`, **giữ nguyên** việc chặn `settings.local.php` và `settings.ddev.php`
- Tạo `config/sync/`, export config lần đầu, commit
- Cập nhật `BACKEND_ARCHITECTURE.md` §6 cho khớp thực tế

### ⛔ Ngoài phạm vi

| Không làm | Vì sao |
|---|---|
| **Tạo mới `settings.php` từ scaffold D11** | Vấn đề thật (xem §9.2) nhưng là task riêng. Task này chỉ sửa 2 chỗ. |
| **Sửa `settings.ddev.php`** | DDEV ghi đè mỗi lần `ddev start` (có marker `#ddev-generated`). Sửa là mất. |
| **Đổi bất kỳ config nào** | Task này chỉ **export hiện trạng**, không đổi giá trị. |
| **Bật/tắt module, đổi theme** | Đó là TASK-001. |
| **Đụng content type** | `schema_change: false`. |

## 4. Yêu cầu

- [ ] **R1** — Trong `settings.php`, đặt:
      ```php
      $settings['config_sync_directory'] = '../config/sync';
      ```
      **Vị trí bắt buộc: trước dòng 879** (chỗ include `settings.ddev.php`). Đặt cạnh comment mẫu
      ở dòng ~261 là hợp lý.
      > ⚠️ Đặt **sau** dòng 879 sẽ hỏng: DDEV chạy `if (empty(...))` trước, thấy rỗng, gán
      > `sites/default/files/sync`, rồi dòng của ta mới chạy — kết quả đúng nhưng vì lý do sai,
      > và sẽ vỡ nếu DDEV đổi thứ tự. Đặt trước để DDEV thấy non-empty và tự lùi.

      Đường dẫn `../config/sync` giải tương đối theo **Drupal root** (`web/`) → trỏ tới
      `config/sync` ở gốc dự án, **ngoài webroot**. Đây là khuyến nghị của Drupal: config
      không được nằm trong thư mục web truy cập được.

- [ ] **R2** — Bỏ comment block include `settings.local.php` (dòng 897–899):
      ```php
      if (file_exists($app_root . '/' . $site_path . '/settings.local.php')) {
        include $app_root . '/' . $site_path . '/settings.local.php';
      }
      ```
      Giữ nguyên vị trí **cuối file** — đây là chỗ staging/production ghi đè secret
      (`hash_salt`, DB creds). Không có nó thì production không có chỗ đặt secret ngoài git.

- [ ] **R3** — `.gitignore`: **bỏ** dòng chặn `/web/sites/*/settings.php`.
      **Giữ nguyên** chặn: `settings.local.php`, `settings.ddev.php`, `services.local.yml`, `.env`.

- [ ] **R4** — Tạo `config/sync/` với `.gitkeep` (để thư mục tồn tại trước khi `cex`).

- [ ] **R5** — Chạy `ddev drush cex -y`, commit toàn bộ `config/sync/*.yml`.

- [ ] **R6** — `BACKEND_ARCHITECTURE.md` §6: ghi rõ `settings.php` **được commit** (không secret),
      secret nằm ở `settings.local.php`/`settings.ddev.php` (không commit).

## 5. Tiêu chí chấp nhận

- [ ] **AC1** — `config_sync_directory` trả về `../config/sync` (lệnh §6.1).
- [ ] **AC2** — `ddev drush cex -y` ghi file `.yml` vào `config/sync/`, không phải `web/sites/default/files/sync/`.
- [ ] **AC3** — `git check-ignore config/sync/system.theme.yml` → **không** bị ignore.
- [ ] **AC4** — `git check-ignore web/sites/default/settings.php` → **không** bị ignore.
- [ ] **AC5** — `git check-ignore web/sites/default/settings.ddev.php` → **VẪN** bị ignore.
- [ ] **AC6** — 🔴 **`settings.php` không chứa secret nào.** Lệnh §6.4 trả về **rỗng**.
      Đây là AC quan trọng nhất: task này đưa `settings.php` lên **repo public**.
- [ ] **AC7** — `ddev drush cim -y` chạy sạch, báo không có gì để import
      (export rồi import lại phải là no-op — chứng minh config đầy đủ và nhất quán).
- [ ] **AC8** — Site vẫn chạy: `curl -s -o /dev/null -w "%{http_code}" https://nidqc.ddev.site/` → `200`.
- [ ] **AC9** — `ddev drush watchdog:show --severity=3` không có lỗi mới.

## 6. Cách verify

> Chạy **thật**. Dán output vào §10.

### 6.1. Kiểm đường dẫn config
```bash
ddev drush php:eval "print \Drupal\Core\Site\Settings::get('config_sync_directory');"
# phải in: ../config/sync
```

### 6.2. Export config
```bash
mkdir -p config/sync && touch config/sync/.gitkeep
ddev drush cex -y
ls config/sync/ | head
ls config/sync/*.yml | wc -l          # phải > 0
```

### 6.3. Kiểm gitignore
```bash
git check-ignore -v config/sync/system.theme.yml        || echo "OK: config được commit"
git check-ignore -v web/sites/default/settings.php      || echo "OK: settings.php được commit"
git check-ignore -v web/sites/default/settings.ddev.php && echo "OK: settings.ddev.php vẫn bị chặn"
```

### 6.4. ⭐ Kiểm secret trong settings.php — AC6, quan trọng nhất
```bash
# Repo là PUBLIC. Bắt mọi phép gán CHUỖI KHÔNG RỖNG vào key nhạy cảm,
# bỏ qua dòng comment. Output RỖNG = đạt.
scan() {
  grep -nE "\\\$(databases|settings)\[[^]]+\](\[[^]]+\])*[[:space:]]*=[[:space:]]*['\"][^'\"]+['\"]" "$1" \
    | grep -vE "^[0-9]+:[[:space:]]*[#*]" \
    | grep -iE "hash_salt|passw|user|database|host|key|secret|token|salt"
}
scan web/sites/default/settings.php     # phải RỖNG
```

**Đã kiểm chứng lệnh này hoạt động đúng cả hai chiều** (2026-07-16):
```bash
scan web/sites/default/settings.ddev.php   # bắt đúng 4 secret thật:
#   16: $databases[...]['database'] = "db";
#   17: $databases[...]['username'] = "db";
#   18: $databases[...]['password'] = "db";
#   23: $settings['hash_salt'] = '6172fc6b...';
```
Lệnh **không** bắt nhầm `$settings['hash_salt'] = '';` (chuỗi rỗng) và **không** bắt nhầm
dòng comment. Đã test âm tính giả: chèn secret giả vào bản copy → bắt được.

Kiểm thêm bằng mắt: `hash_salt` phải là `''`, **không** có `$databases[...]` giá trị thật.

### 6.5. Import lại phải là no-op — AC7
```bash
ddev drush cim -y
# phải báo: There are no changes to import.
```

### 6.6. Site còn sống
```bash
curl -s -o /dev/null -w "%{http_code}\n" https://nidqc.ddev.site/   # 200
ddev drush watchdog:show --severity=3 --count=10                     # 3 = Error (dùng SỐ, site langcode `vi`)
```

### 6.7. Thử lại từ đầu — chứng minh thật sự tái lập được
```bash
# Đây là điểm mấu chốt của cả task. Không làm bước này thì không biết có đạt mục tiêu không.
ddev drush config:set system.theme default olivero -y   # cố tình làm lệch
ddev drush cim -y                                        # import lại từ config/sync
ddev drush config:get system.theme default --format=string
# phải quay về đúng giá trị đã export -> chứng minh config/sync là nguồn sự thật
```

## 7. Bảo mật — mức chú ý cao

Task này **đưa `settings.php` lên repo PUBLIC** (`github.com/theluu/nidqc`).

Đã kiểm tra trước (2026-07-16): `settings.php` hiện **không có secret** —
`hash_salt = ''`, không có `$databases`. Secret thật nằm ở `settings.ddev.php`
(`hash_salt = 6172fc6b...`, DB creds) và file đó **vẫn bị gitignore**.

- [ ] Đã chạy `docs/security/SECURITY_CHECKLIST.md`, đặc biệt mục **H (Secrets)**
- [ ] Đã chạy §6.4 và output **rỗng**
- [ ] Đã đọc **toàn bộ** `git diff` của `settings.php`, không lướt
- [ ] Xác nhận `settings.ddev.php` và `settings.local.php` vẫn bị chặn (AC5)

> ⚠️ Sau task này, `settings.php` được git theo dõi. Từ nay **ai thêm secret vào đó là lộ ngay
> khi push**. Phải ghi cảnh báo này vào `BACKEND_ARCHITECTURE.md` §6 (R6): secret đi vào
> `settings.local.php`, không bao giờ vào `settings.php`.

## 8. Định nghĩa hoàn thành

Xem `docs/DEFINITION_OF_DONE.md`. Bổ sung riêng task này:
- [ ] Đã chạy §6.7 và config thật sự khôi phục được từ `config/sync/`
- [ ] `git status` sạch sau `cex` + `cim` (không lệch qua lại)

## 9. Câu hỏi mở

### 9.1. 🟡 Xác nhận: commit `settings.php` là đúng hướng?

Task này đi **ngược** `web/example.gitignore` của Drupal (mặc định chặn `sites/*/*settings*.php`),
nhưng đi **thuận** với DDEV (chỉ chặn `settings.ddev.php`, và comment của nó nói settings.php
nên đặt `config_sync_directory`).

Lý do chọn commit: đây là **chỗ duy nhất** đặt được `config_sync_directory`; không commit thì
mỗi người phải sửa tay, và cấu hình không tái lập được — mất luôn ý nghĩa của config management.
Secret vẫn an toàn vì nằm ở `settings.local.php`/`settings.ddev.php`.

Nếu người review không đồng ý → nói **trước khi** code, vì đây là quyết định nền.
Cân nhắc viết thành ADR-002.

### 9.2. 🟡 `settings.php` hiện là bản DDEV sinh từ **Drupal 10**

Dòng 5: `// DDEV-created Drupal 10 settings.php from upstream default.settings.php`.
So với scaffold D11.4.3 (`web/core/assets/scaffold/files/default.settings.php`), file hiện tại **thiếu**:
`aggregate_gc_threshold` · `auto_create_htaccess` · `enable_html5_validation` ·
**`media_oembed_discovery_trusted_host_patterns`** (setting bảo mật)

Chưa gây hại (Media oEmbed chưa dùng), nhưng ta sắp **commit** file này — nên đang lưu lại một bản
lỗi thời. **Không sửa trong task này** (ngoài phạm vi, diff 36KB sẽ nuốt mất thay đổi thật).
→ Đề xuất **TASK-003**: dựng lại `settings.php` từ scaffold D11.4.3.

### 9.3. 🟢 Ghi nhận: `settings.ddev.php` có `trusted_host_patterns = ['.*']`

Cho phép mọi host — chỉ an toàn vì file này là local-only và bị gitignore.
**Production phải đặt `trusted_host_patterns` thật** trong `settings.local.php`.
Đã có trong `docs/deployment/DEPLOYMENT.md` §7? → kiểm tra, nếu chưa thì bổ sung ở task deploy.

## 10. Nhật ký

| Ngày | Người/Agent | Việc |
|---|---|---|
| 2026-07-16 | Claude | Soạn task. Truy được nguyên nhân gốc: `settings.ddev.php` dòng 32–35 chỉ đặt fallback và mong `settings.php` đặt giá trị; nhưng `.gitignore` dòng 20 chặn `settings.php` → vòng luẩn quẩn. Đã xác minh `settings.php` không chứa secret (`hash_salt=''`), secret thật nằm ở `settings.ddev.php` (vẫn bị chặn). |
| 2026-07-16 | Claude | **Đã chạy thử lệnh quét secret §6.4 trước khi giao task.** Bản đầu có 2 false positive (`file_scan_ignore_directories`, `entity_update_batch_size`) rồi 2 dòng comment proxy — AC "output rỗng" sẽ fail dù file sạch. Đã thiết kế lại: chỉ bắt gán chuỗi không rỗng vào key nhạy cảm, loại dòng comment. Kiểm chứng 3 chiều: sạch trên `settings.php`, bắt đúng 4 secret trong `settings.ddev.php`, bắt được secret giả cố tình chèn. |
