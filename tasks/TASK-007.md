---
id: TASK-007
title: Content type + taxonomy theo đúng design đã duyệt
status: review          # đã thực thi 2026-07-16, chờ NGƯỜI review (AI không được tự duyệt)
step: 4
owner: <chưa gán>
reviewer: <chưa gán — KHÔNG được trùng owner>
created: 2026-07-16

schema_change: true      # 🔴 TẠO content type + field + taxonomy
new_package: false
config_change: true      # export toàn bộ schema vào config/sync/

allowed_files:
  - web/modules/custom/nidqc_content/**
  - config/sync/**
  - docs/database/ENTITY_MAPPING.md
  - docs/PROJECT_CONTEXT.md
  - CHANGELOG.md
  - README.md                          # bước UUID — xem §11
  - docs/deployment/DEPLOYMENT.md      # bước UUID
  - docs/ROADMAP.md
---

# TASK-007 — Content type theo design đã duyệt

## 1. Mục tiêu

Site có đủ content type để dựng 11 trang. **Field lấy thẳng từ design đã duyệt** — không suy diễn.

## 2. Cơ sở: NIDQC ĐÃ DUYỆT design

Design trong `design/` là **spec đã được chủ đầu tư duyệt**. Nó là nguồn chân lý.
Field nào design có → làm. Field nào design không có → **không tự thêm**.

> ⚠️ Bản trước của `ENTITY_MAPPING.md` §4 **bịa** `field_document_number`, `field_issued_date`,
> `field_file` cho `document` — **design không có**. Đã gỡ. Đừng thêm lại.
> Viện muốn thêm → **change request**, không phải suy đoán của dev.

### 2.1. Tra cứu chất chuẩn — NGOÀI phạm vi, design đã trả lời

| Link trong design | Số lần | Nghĩa |
|---|---|---|
| `NIDQC-Homepage.dc.html#chat-chuan`, `#chat-chuan` | 23 | **Anchor trên trang chủ** |
| `https://nidqc.gov.vn/tim-kiem-chat-chuan` | 2 | **URL tuyệt đối → hệ thống có sẵn trên site cũ** |

Design dùng URL **tuyệt đối** chỉ cho thứ **nằm ngoài** nó (`nidqc.gov.vn/` và
`/tim-kiem-chat-chuan`); 10 trang nội bộ đều dùng `.dc.html`.

→ **Tra cứu chất chuẩn là hệ thống đã có, chỉ link ra.** Không dựng content type `standards`.

## 3. Dữ liệu THẬT trong design

Trích 2026-07-16 bằng `scripts/extract-design.py`:

```js
// Tin tức — Tin tuc danh sach
catDefs  = [ {id:'all',label:'Tất cả'}, {id:'thongbao',label:'Thông báo'},
             {id:'hoatdong',label:'Tin hoạt động'}, {id:'dauthau',label:'Mua sắm - đấu thầu'},
             {id:'daotao',label:'Đào tạo'}, {id:'hoithao',label:'Hội nghị - Hội thảo'},
             {id:'tuyendung',label:'Tuyển dụng'} ]
allItems = [ { tag:'Báo giá', cat:'dauthau',
               title:'Thông báo mời báo giá vật tư tiêu hao...', date:'18/06/2026',
               img:'images/thongbao-baogia.png' } ]

// Văn bản — Van ban tai lieu
tabDefs = [ {id:'phapquy',label:'Văn bản pháp quy'}, {id:'chuyenmon',label:'Tài liệu chuyên môn'} ]
docs    = [ { title:'Thông tư quy định về kiểm nghiệm thuốc...', meta:'Bộ Y tế · Thông tư' } ]

// FAQ — có NHÓM
rawGroups = [ { title:'Dịch vụ kiểm nghiệm', items:[ {question:'...', answer:'...'} ] } ]

// Khác
depts     = [ {name, desc} ]          // Cơ cấu tổ chức
equipment = [ {name, desc} ]          // Năng lực
certs     = [ {name, desc} ]          // Năng lực
projects  = [ {title, desc, year} ]   // Đào tạo NCKH
```

## 4. Yêu cầu

- [ ] **R1** — Module `nidqc_content`. Content type + field khai báo bằng **config YAML**
      (`config/install/`), không tạo tay qua UI. Config phải vào `config/sync/` được.

- [ ] **R2** — Taxonomy, term lấy **nguyên văn** từ design:
      | Vocabulary | Term |
      |---|---|
      | `news_category` | Thông báo · Tin hoạt động · Mua sắm - đấu thầu · Đào tạo · Hội nghị - Hội thảo · Tuyển dụng |
      | `document_group` | Văn bản pháp quy · Tài liệu chuyên môn |
      | `faq_group` | Dịch vụ kiểm nghiệm · (các nhóm khác trong design) |
      > `all`/`Tất cả` **không phải term** — nó là bộ lọc trên UI.

- [ ] **R3** — Content type, **chỉ field design có**:
      | Type | Field | Kiểu | Từ design |
      |---|---|---|---|
      | `news` | `title` | string | `title` |
      | | `field_date` | datetime (date) | `date` — design dạng `18/06/2026` |
      | | `field_image` | image | `img` |
      | | `field_tag` | string | `tag` — vd "Báo giá", "Bảo trì - Sửa chữa" |
      | | `field_category` | ER → `news_category` | `cat` |
      | `document` | `title` | string | `title` |
      | | `field_meta` | string | `meta` — vd "Bộ Y tế · Thông tư" |
      | | `field_group` | ER → `document_group` | tab |
      | `faq` | `title` | string | `question` |
      | | `field_answer` | text_long | `answer` |
      | | `field_group` | ER → `faq_group` | nhóm |
      | `department` | `title` | string | `name` |
      | | `field_description` | text_long | `desc` |
      | `equipment` | `title` / `field_description` | string / text_long | `name` / `desc` |
      | `certificate` | `title` / `field_description` | string / text_long | `name` / `desc` |
      | `project` | `title` / `field_description` / `field_year` | string / text_long / integer | `title` / `desc` / `year` |
      | `page` | `title` / `body` | | trang tĩnh |

      ⛔ **KHÔNG thêm** `field_document_number`, `field_issued_date`, `field_file` —
      **design không có**. Xem §2.

- [ ] **R4** — `field_meta` là **một chuỗi**, không tách thành 2 field.
      Design lưu `"Bộ Y tế · Thông tư"` nguyên khối. Tách ra là **suy diễn**.
      Ghi §9.1 để Viện xác nhận sau.

- [ ] **R5** — Pathauto pattern theo `PAGE_MAPPING.md` §3.

- [ ] **R6** — `drush cex`, commit. `drush cim` phải tái lập được toàn bộ schema.

## 5. Tiêu chí chấp nhận

- [ ] **AC1** — 8 content type tồn tại
- [ ] **AC2** — 3 vocabulary + term đúng nguyên văn design
- [ ] **AC3** — 🔴 **Không field nào ngoài §3** — lệnh §6.2
- [ ] **AC4** — `cex` → schema vào `config/sync/`; `cim` no-op
- [ ] **AC5** — 🔴 **Tái lập được**: `drush cim` trên DB sạch dựng lại đủ 8 type
- [ ] **AC6** — Tạo thử 1 node mỗi type, tiếng Việt đúng dấu
- [ ] **AC7** — Alias sinh đúng pattern
- [ ] **AC8** — `watchdog:show --severity=3` sạch

## 6. Cách verify

### 6.1. Content type + taxonomy — AC1, AC2
```bash
ddev drush php:eval "
print implode(', ', array_keys(\Drupal::entityTypeManager()->getStorage('node_type')->loadMultiple()));"
ddev drush php:eval "
foreach (\Drupal::entityTypeManager()->getStorage('taxonomy_vocabulary')->loadMultiple() as \$v) {
  \$t = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->loadByProperties(['vid' => \$v->id()]);
  print \$v->id() . ': ' . implode(' · ', array_map(fn(\$x) => \$x->label(), \$t)) . PHP_EOL;
}"
```

### 6.2. ⭐ Không field thừa — AC3
```bash
# Field nào KHÔNG có trong design là suy diễn -> fail.
ddev drush php:eval "
foreach (['news','document','faq','department','equipment','certificate','project','page'] as \$b) {
  \$f = array_keys(\Drupal::service('entity_field.manager')->getFieldDefinitions('node', \$b));
  \$f = array_filter(\$f, fn(\$x) => str_starts_with(\$x, 'field_') || \$x === 'body');
  print \$b . ': ' . implode(', ', \$f) . PHP_EOL;
}"
# Đối chiếu §3. CẤM: field_document_number, field_issued_date, field_file
```

### 6.3. 🔴 Tái lập được — AC5 (bài kiểm quyết định)
```bash
ddev drush sql:drop -y && ddev drush si standard -y --account-pass=admin
ddev drush cim -y
ddev drush php:eval "print count(\Drupal::entityTypeManager()->getStorage('node_type')->loadMultiple());"
# phải: 8
```
> Đây mới là bằng chứng schema là code, không phải trạng thái máy ai đó.

### 6.4. AC4, AC8
```bash
ddev drush cex -y && ddev drush cim -y      # There are no changes to import.
ddev drush watchdog:show --severity=3
```

## 7. Kỳ vọng đúng

Xong task này site **vẫn chưa có nội dung** — chỉ có **cấu trúc** để nhập nội dung.
Trang vẫn trống. Template là task sau (008+).

## 8. Bảo mật

- [ ] `docs/security/SECURITY_CHECKLIST.md`
- [ ] **Không** `field_file` → **không có bề mặt upload** ở task này. Nếu Viện yêu cầu thêm sau
      thì đó là task riêng, và là **điểm rủi ro cao nhất** của schema — xem `SECURITY_POLICY.md` §5.
- [ ] `field_answer`, `field_description`, `body` dùng text format **hạn chế**, không `full_html`
      cho biên tập viên thường

## 9. Câu hỏi mở

### 9.1. 🟡 `field_meta` là chuỗi ghép — xác nhận với Viện sau

Design lưu `"Bộ Y tế · Thông tư"` **nguyên khối**. Rõ ràng đó là *cơ quan ban hành* + *loại văn
bản*, nhưng design **không tách**. R4 làm đúng design: một chuỗi.

Hệ quả: **không lọc theo cơ quan hoặc loại văn bản được**. Nếu Viện cần lọc → tách 2 field,
là **change request**. Đừng tự tách.

### 9.2. 🟡 Văn bản không có file tải về

Design **không có link tải** trong thẻ văn bản — chỉ icon trang trí. Task làm đúng design.
Gần như chắc chắn Viện sẽ cần → nhưng đó là **change request**, không phải suy đoán.

### 9.3. 🟡 `date` dạng `18/06/2026`

Design hiển thị `dd/mm/yyyy`. Lưu dạng `datetime` chuẩn, **format khi hiển thị** — không lưu chuỗi.

## 11. Output verify (chạy thật 2026-07-16)

```
$ # AC1
certificate, department, document, equipment, faq, news, page, project     ✅ 8

$ # AC2 — term nguyên văn design
document_group (2): Văn bản pháp quy · Tài liệu chuyên môn
faq_group (1):      Dịch vụ kiểm nghiệm
news_category (6):  Thông báo · Tin hoạt động · Mua sắm - đấu thầu · Đào tạo ·
                    Hội nghị - Hội thảo · Tuyển dụng                        ✅

$ # AC3 — field, đối chiếu §3
page         body
news         field_category, field_date, field_image, field_tag
document     field_group, field_meta
faq          field_answer, field_group
department   field_description
equipment    field_description
certificate  field_description
project      field_description, field_year
-> field bịa (field_document_number/field_issued_date/field_file): KHÔNG CÓ     ✅

$ # AC7 — alias
/tin-tuc/thong-bao-moi-bao-gia-vat-tu-tieu-hao
/van-ban-tai-lieu/thong-tu-quy-dinh-ve-kiem-nghiem-thuoc-nguyen-lieu-lam-thuoc
/gioi-thieu-chung                                                            ✅

$ ddev drush cim -y
There are no changes to import.                                              ✅ AC4
```

### ⭐ AC5 — DROP DB, cài lại, cim (mô phỏng dev mới clone repo)

```
$ ddev drush sql:drop -y && ddev drush si standard -y
$ ddev drush config:set system.site uuid <uuid từ config/sync> -y
$ ddev drush cim -y
The configuration was imported successfully.

content type:     8  ✅
pathauto pattern: 8  ✅
taxonomy term:    9  ✅
  document_group: Văn bản pháp quy · Tài liệu chuyên môn
  faq_group:      Dịch vụ kiểm nghiệm
  news_category:  Thông báo · Tin hoạt động · Mua sắm - đấu thầu · Đào tạo ·
                  Hội nghị - Hội thảo · Tuyển dụng
theme:            nidqc  ✅
```

## Ba lỗi thật, chỉ lộ ra khi chạy AC5

### 1. 🔴 `cim` THẤT BẠI trên site mới cài — UUID không khớp

`drush si` luôn sinh UUID site mới; `config/sync/system.site.yml` giữ UUID cũ. Drupal **từ chối**
import config từ "site khác".

**TASK-002 đã bỏ sót lỗi này** vì chỉ kiểm `cim` trên **cùng** site — nó pass. Kịch bản thật
(dev mới clone repo) thì fail. Phải chạy trước:
```bash
drush config:set system.site uuid $(grep '^uuid:' config/sync/system.site.yml | cut -d' ' -f2) -y
```
Đã ghi vào `README.md` §Bắt đầu và `DEPLOYMENT.md` §4.

### 2. 🔴 Pathauto cắt mất từ TIẾNG VIỆT

`pathauto.settings.ignore_words` mặc định là stopword **tiếng Anh** — trùng từ tiếng Việt thật:

| Tiêu đề | Alias hỏng | Mất chữ |
|---|---|---|
| Phòng **Tổ** chức - Hành chính | `/phong-chuc-hanh-chinh` | `Tổ` → `to` |
| Thuốc và **an** toàn | `/thuoc-va-toan` | `an` |
| Viện **in ấn** tài liệu | `/vien-tai-lieu` | `in` **và** `ấn` |

`/vien-tai-lieu` mất nghĩa và sẽ **đụng alias** với trang "Viện tài liệu" thật.
Đã sửa `ignore_words` → rỗng. Kiểm lại: `/phong-to-chuc-hanh-chinh`, `/thuoc-va-an-toan`,
`/vien-in-an-tai-lieu`. ✅

### 3. 🔴 Config không bị dọn khi gỡ module — thiếu `enforced` dependency

Config YAML bản đầu ghi `dependencies: {  }`, nên Drupal không biết chúng thuộc module nào →
gỡ module xong **8 content type vẫn còn**, cài lại thì `PreExistingConfigException`.

Đã thêm `dependencies.enforced.module: nidqc_content` vào **35 file**. Kiểm chứng:
gỡ module → **0 content type**; cài lại → **8**. ✅

### Taxonomy term không tái lập được → `hook_install()`

`cim` dựng lại đủ config nhưng **term = 0** — term là entity **content**, `cex` không export.
Đúng lỗ hổng nợ #10 (menu link), nay chặn cả TASK-007.

Đã giải bằng `nidqc_content.install` → `hook_install()` tạo term từ design. Term thành **code**,
vào git, chạy được trên mọi môi trường. Idempotent, và `hook_uninstall()` dọn sạch.
**Cách này áp dụng được cho menu link khi dựng menu thật.**

## 10. Nhật ký

| Ngày | Người/Agent | Việc |
|---|---|---|
| 2026-07-16 | Claude | **Thực thi task.** R1–R6 xong, AC1–AC8 đạt, output §11. 8 content type + 3 vocabulary + 9 term + 8 pathauto pattern, tất cả tái lập được từ DB trống. |
| 2026-07-16 | Claude | **Ba lỗi thật lộ ra khi chạy AC5** (xem §11): UUID site làm `cim` fail trên máy mới — **TASK-002 bỏ sót vì chỉ kiểm trên cùng site**; pathauto cắt mất từ tiếng Việt (`Tổ`, `an`, `in`) vì dùng stopword tiếng Anh; config YAML thiếu `enforced` dependency nên không bị dọn khi gỡ module. |
| 2026-07-16 | Claude | Soạn task sau khi biết **NIDQC đã duyệt design** → design là spec, không cần hỏi thêm. Trích toàn bộ cấu trúc dữ liệu từ 11 trang. **Blocker "tra cứu chất chuẩn" tự gỡ bằng chính design**: nó dùng URL **tuyệt đối** (`https://nidqc.gov.vn/tim-kiem-chat-chuan`) trong khi 10 trang nội bộ dùng `.dc.html` — tức là hệ thống có sẵn, chỉ link ra, ngoài phạm vi. |
