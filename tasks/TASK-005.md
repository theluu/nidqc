---
id: TASK-005
title: Dựng frontend/ — Vite + bootstrap island (chưa có island nào)
status: ready
step: 5                  # Vue Frontend — hạ tầng
owner: <chưa gán>
reviewer: <chưa gán — KHÔNG được trùng owner>
created: 2026-07-16

schema_change: false
config_change: false
new_package: true        # 🔴 CẦN DUYỆT — xem §4 R1. Đây là task DUY NHẤT được cài npm.

allowed_files:
  - frontend/package.json
  - frontend/package-lock.json
  - frontend/vite.config.js
  - frontend/.gitignore
  - frontend/README.md
  - frontend/src/main.js
  - frontend/src/lib/api.js
  - web/themes/custom/nidqc/nidqc.libraries.yml
  - .gitignore
  - CHANGELOG.md

read_only:
  - docs/architecture/FRONTEND_ARCHITECTURE.md
  - docs/standards/VUE_CODING_STANDARD.md
  - docs/decisions/ADR-001-frontend-architecture.md
  - docs/api/API_ERROR_STANDARD.md
---

# TASK-005 — Dựng `frontend/` (Vite + bootstrap island)

## 1. Mục tiêu

Có **hạ tầng** để viết Vue island: Vite build ra asset tĩnh, Drupal nạp được, `main.js` tự tìm
và mount island vào `[data-island]`.

Task này **không viết island nào**. Nó chỉ dựng đường ray.

## 2. Bối cảnh

`frontend/` hiện **rỗng hoàn toàn** (kiểm 2026-07-16). `ADR-001` chốt kiến trúc island từ lượt đầu
nhưng chưa ai dựng hạ tầng. Mọi task island sau đều chặn bởi task này.

Đã kiểm: DDEV có sẵn **Node v24.13.1 + npm 11.8.0** (`corepack_enable: true` trong `.ddev/config.yaml`).
Không cần cài Node.

## 3. Nguyên tắc chi phối (đọc trước khi code)

> **Vue nâng cấp HTML có sẵn. Vue không sinh ra nội dung.** (`ADR-001`, `FRONTEND_ARCHITECTURE.md` §1)

97,9% design là nội dung tĩnh → Twig. Chỉ 7 khối tương tác → island (`ROADMAP.md` §5).
Nếu thấy mình định render nội dung bằng Vue → **sai kiến trúc, dừng lại**.

**Production không chạy Node.** Vite chỉ chạy lúc build, xuất ra JS/CSS tĩnh.

## 4. Yêu cầu

- [ ] **R1** — 🔴 **Xin duyệt package trước khi cài.** Task này là ngoại lệ duy nhất của
      `AGENTS.md` §2.3. Danh sách **tối thiểu**, không thêm gì khác:
      | Package | Vì sao |
      |---|---|
      | `vue` ^3 | Bắt buộc |
      | `vite` ^7 | Build |
      | `@vitejs/plugin-vue` | Biên dịch `.vue` |

      ⛔ **KHÔNG** cài: router (điều hướng là việc của Drupal) · Pinia/Vuex (island độc lập) ·
      Tailwind/CSS framework (design là inline style thuần) · UI library · axios (dùng `fetch`).
      Muốn thêm gì → **dừng, hỏi**, đừng tự quyết.

- [ ] **R2** — `vite.config.js`:
      - Build ra `web/themes/custom/nidqc/dist/`
      - `manifest: false`, tên file **cố định** (không hash) — Drupal library cần đường dẫn ổn định
      - Mỗi island **lazy load riêng** (code splitting) — trang chỉ có FAQ thì không tải code mega menu
      - Alias `@` → `src/`

- [ ] **R3** — `src/main.js` — bootstrap:
      ```js
      const registry = {
        // island đăng ký ở đây khi có task tương ứng. Hiện tại RỖNG - đúng.
      };
      document.querySelectorAll('[data-island]').forEach(async (el) => {
        const loader = registry[el.dataset.island];
        if (!loader) return;                    // không có island -> im lặng bỏ qua
        const { default: C } = await loader();
        createApp(C, readProps(el)).mount(el);
      });
      ```
      - `readProps(el)` đọc từ `<script type="application/json" data-props>` bên trong `el`
      - **Không** parse DOM để lấy dữ liệu (`VUE_CODING_STANDARD.md` §4)
      - Không có island khớp → **không lỗi**, chỉ bỏ qua

- [ ] **R4** — `src/lib/api.js` — mọi request đi qua đây (`VUE_CODING_STANDARD.md` §7):
      - Bắt lỗi theo `error.code`, **không** so khớp `message` (`API_ERROR_STANDARD.md` §4)
      - Fallback khi response không phải JSON (mất mạng, nginx lỗi)
      - Ném `ApiError(code, message, status)`

- [ ] **R5** — `nidqc.libraries.yml`: thêm library `islands` (tách khỏi `global`):
      ```yaml
      islands:
        version: 1.x
        js:
          dist/islands.js: { attributes: { type: module, defer: true } }
      ```
      ⛔ **Không** thêm vào `global`. Island chỉ nạp ở trang cần, qua `#attached`
      (`BACKEND_ARCHITECTURE.md` §4). Task này chỉ **khai báo**, chưa nạp ở đâu.

- [ ] **R6** — `.gitignore` gốc: xác nhận đã chặn `/frontend/node_modules/`, `/frontend/dist/`,
      `/web/themes/custom/nidqc/dist/`. **`package-lock.json` PHẢI commit.**

- [ ] **R7** — `frontend/README.md`: lệnh dev/build, nhắc lại nguyên tắc §3, và
      **cấm** thêm package không qua duyệt.

## 5. Tiêu chí chấp nhận

- [ ] **AC1** — `cd frontend && npm ci` chạy sạch
- [ ] **AC2** — `npm run build` xuất ra `web/themes/custom/nidqc/dist/islands.js`
- [ ] **AC3** — Tên file **không có hash** (Drupal library cần đường dẫn cố định)
- [ ] **AC4** — 🔴 `package.json` **chỉ** có 3 package ở R1 (+ dependency gián tiếp).
      Không router, không store, không CSS framework.
- [ ] **AC5** — `dist/` và `node_modules/` **không** bị commit; `package-lock.json` **có** commit
- [ ] **AC6** — Trang vẫn chạy `200` và **không lỗi console** (island chưa nạp ở đâu — đúng)
- [ ] **AC7** — Không request ra domain ngoài (`SECURITY_POLICY.md` §10)
- [ ] **AC8** — `ddev drush watchdog:show --severity=3` không lỗi mới

## 6. Cách verify

### 6.1. Build — AC1, AC2, AC3
```bash
cd frontend && npm ci && npm run build
ls -la ../web/themes/custom/nidqc/dist/
# phải có islands.js, tên KHÔNG chứa hash kiểu islands.a1b2c3.js
```

### 6.2. ⭐ Package đúng danh sách — AC4
```bash
python3 -c "
import json; p=json.load(open('frontend/package.json'))
d={**p.get('dependencies',{}), **p.get('devDependencies',{})}
print('Đã cài:', ', '.join(sorted(d)))
cam=['router','pinia','vuex','tailwind','axios','bootstrap','element','vuetify','naive']
bad=[k for k in d if any(c in k.lower() for c in cam)]
print('⛔ CẤM mà vẫn có:', bad if bad else '(không) ✅')
print('Số package trực tiếp:', len(d), '(kỳ vọng 3)')
"
```

### 6.3. Gitignore — AC5
```bash
# ⚠️ BẪY: rule kết thúc bằng '/' (vd /frontend/node_modules/) CHỈ khớp thư mục ĐÃ TỒN TẠI.
# Chạy check-ignore lên đường dẫn chưa tồn tại sẽ báo "không bị chặn" — ĐÓ LÀ BÁO ĐỘNG GIẢ,
# không phải gitignore hỏng. Vì vậy chỉ chạy lệnh này SAU khi npm ci + npm run build.
# Và kiểm lên FILE BÊN TRONG, không phải bản thân thư mục.
git check-ignore -v frontend/node_modules/.package-lock.json   && echo "OK: node_modules bị chặn"
git check-ignore -v web/themes/custom/nidqc/dist/islands.js    && echo "OK: dist bị chặn"
git check-ignore -q frontend/package-lock.json && echo "⛔ SAI: lock PHẢI commit" || echo "OK: lock được commit"

# Chốt hạ: sau build, git status KHÔNG được thấy dist/ hay node_modules/
git status --porcelain | grep -E "node_modules|dist/" && echo "⛔ LỘ" || echo "OK: sạch"
```

### 6.4. Site còn sống — AC6, AC7
```bash
ddev drush cr
curl -s -o /dev/null -w "%{http_code}\n" https://nidqc.ddev.site/          # 200
curl -s https://nidqc.ddev.site/ | grep -oE 'https?://[^"'"'"' ]+' | grep -v nidqc.ddev.site | sort -u
echo "(rỗng = không gọi ra ngoài)"
```

### 6.5. Bootstrap không nổ khi không có island
```bash
# Chèn thử một phần tử island KHÔNG có trong registry -> phải im lặng, không lỗi console.
# Kiểm bằng DevTools Console sau khi nạp library islands ở một trang thử.
```

### 6.6. Lỗi — AC8
```bash
ddev drush watchdog:show --severity=3 --count=10
```

## 7. Kỳ vọng đúng về kết quả

**Nhìn vào trang sẽ KHÔNG thấy gì thay đổi.** Đó là đúng.

Task này chỉ dựng đường ray: chưa có island nào, `registry` rỗng, library `islands` khai báo
nhưng chưa nạp ở trang nào. Người review đừng tìm "cái gì chạy được" — thứ cần kiểm là
**build ra file, đúng chỗ, đúng tên, đúng danh sách package**.

Island đầu tiên (`mega-menu`) là **TASK-006**.

## 8. Bảo mật

- [ ] Đã chạy `docs/security/SECURITY_CHECKLIST.md`, đặc biệt mục **I (Dependency)**
- [ ] 🔴 `npm ci` (không `npm install`) — bám `package-lock.json`, không tự nâng phiên bản
- [ ] Đã xem `npm audit` — có lỗ hổng nghiêm trọng thì **báo, đừng tự ý `audit fix`**
      (`audit fix` có thể nâng major version = đổi package ngoài duyệt)
- [ ] Không import font/CSS/JS từ CDN ngoài
- [ ] `dist/` không chứa source map lộ đường dẫn máy dev

> ⚠️ `node_modules` là bề mặt tấn công supply chain lớn nhất của dự án. Mỗi package thêm vào là
> thêm rủi ro. Đó là lý do R1 giới hạn **3 package** và mọi bổ sung phải qua người duyệt.

## 9. Câu hỏi mở

### 9.1. 🟡 Commit `dist/` hay build trong CI?

`DEPLOYMENT.md` §3 để ngỏ. R6 chọn **không commit `dist/`** → nghĩa là **production phải build**,
tức là cần Node ở tầng build (CI hoặc máy deploy).

Nếu deploy tay không có Node → phải commit `dist/`. **Quyết định này chưa chốt** và ảnh hưởng
deploy. Cần trả lời trước lần deploy đầu, không phải bây giờ.

### 9.2. 🟡 Vite 7 cần Node ≥ 20.19

DDEV có Node 24.13.1 → OK. Nhưng **máy CI/production phải cùng phiên bản**. Nên ghim
`engines` trong `package.json` và `.nvmrc`. Chưa có hạ tầng CI để ghim vào — ghi nhận.

## 10. Nhật ký

| Ngày | Người/Agent | Việc |
|---|---|---|
| 2026-07-16 | Claude | Soạn task. Kiểm thật: `frontend/` rỗng hoàn toàn; DDEV có sẵn Node v24.13.1 + npm 11.8.0 nên không cần cài Node. Giới hạn 3 package có chủ đích — `node_modules` là bề mặt supply chain lớn nhất, và `ADR-001` không cần router/store/CSS framework. |
