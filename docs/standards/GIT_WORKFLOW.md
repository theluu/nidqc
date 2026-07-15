# Git Workflow — NIDQC

> ⚠️ **Repo hiện chưa được `git init`.** Việc đầu tiên: khởi tạo git và commit baseline
> **trước khi** viết dòng code đầu tiên. Không có git = không có rollback = không có an toàn.

```bash
git init
# Tạo .gitignore TRƯỚC (xem §5) — tránh commit nhầm vendor/ và secrets
git add .
git commit -m "chore: baseline Drupal 11.4.3 + design + docs"
```

## 1. Nhánh

| Nhánh | Vai trò |
|---|---|
| `main` | Code đang chạy production. **Không commit thẳng.** |
| `develop` | Tích hợp |
| `task/TASK-xxx-mo-ta-ngan` | Một task một nhánh |
| `hotfix/mo-ta` | Sửa khẩn production |

**Một task = một nhánh = một PR.** Không gộp nhiều task vào một nhánh.

## 2. Commit message

```
<type>(<scope>): <mô tả ngắn>

<thân — vì sao, không phải cái gì>

Refs: TASK-xxx
```

| Type | Dùng khi |
|---|---|
| `feat` | Tính năng mới |
| `fix` | Sửa lỗi |
| `refactor` | Đổi cấu trúc, không đổi hành vi |
| `style` | Format, không đổi logic |
| `docs` | Tài liệu |
| `test` | Test |
| `chore` | Build, config, dependency |
| `security` | Vá bảo mật |

```
feat(theme): thêm island FAQ accordion

Nội dung FAQ render sẵn bằng <details> trong Twig để giữ SEO và hoạt động
khi tắt JS. Vue chỉ nâng cấp animation và aria-expanded.

Refs: TASK-012
```

Mô tả bằng tiếng Việt có dấu hoặc tiếng Anh — **nhất quán trong toàn dự án**, không trộn.

## 3. Pull request

**Bắt buộc có:**
- [ ] Link tới `tasks/TASK-xxx.md`
- [ ] **Output verify đã dán vào** (không phải "đã test rồi")
- [ ] `docs/security/SECURITY_CHECKLIST.md` đã ký
- [ ] Đối chiếu `docs/DEFINITION_OF_DONE.md`
- [ ] Ảnh chụp màn hình (nếu đụng giao diện)

**Quy tắc review:**
- Ít nhất **một người** duyệt. Người đó **không phải** owner. **AI không được duyệt.**
- PR chỉ chứa thay đổi trong `allowed_files` của task. Có file lạ → trả về.
- PR quá lớn (> ~400 dòng thay đổi thật) → yêu cầu tách.

## 4. Cấm

- ⛔ `git push --force` vào `main` / `develop`
- ⛔ Commit thẳng vào `main`
- ⛔ Commit secrets (`settings.local.php`, `.env`, key)
- ⛔ Commit `vendor/`, `node_modules/`
- ⛔ Merge PR của chính mình
- ⛔ Commit gộp nhiều task không liên quan

## 5. `.gitignore` — tạo trước khi commit lần đầu

```gitignore
# Drupal
/vendor/
/web/core/
/web/modules/contrib/
/web/themes/contrib/
/web/profiles/contrib/
/web/libraries/
/web/sites/*/files/
/web/sites/*/private/

# Secrets — TUYỆT ĐỐI KHÔNG commit
/web/sites/*/settings.local.php
/web/sites/*/services.local.yml
.env
.env.*
!.env.example

# Frontend
/frontend/node_modules/
/frontend/dist/

# Hệ điều hành / IDE
.DS_Store
.idea/
.vscode/
*.swp
```

> `vendor/` và `web/core/` **không** commit — `composer install` dựng lại từ `composer.lock`.
> `composer.lock` **phải** commit.

## 6. Lỡ commit secret

**Coi như đã lộ.** Xoá commit là chưa đủ.

1. **Đổi ngay** secret đó (mật khẩu DB, key, `hash_salt`).
2. Rồi mới dọn lịch sử.
3. Báo quản trị dự án.

## 7. Deploy

Xem `docs/deployment/DEPLOYMENT.md`. Chỉ deploy từ `main` đã qua UAT.
