# Vue Coding Standard — NIDQC

> Nền tảng: [Vue Style Guide](https://vuejs.org/style-guide/) chính thức.
> File này ghi những gì riêng của dự án — đặc biệt là các ràng buộc của kiến trúc **islands**.

## 1. Nhắc lại nguyên tắc số một

> **Vue nâng cấp HTML có sẵn. Vue không sinh ra nội dung.**

Viết Vue để render nội dung mà Google cần đọc → sai kiến trúc. Xem ADR-001.

## 2. Bắt buộc

- **Vue 3 Composition API + `<script setup>`.** Không dùng Options API.
- Một file `.vue` một island. PascalCase: `FaqAccordion.vue`.
- **Không** Vue Router (điều hướng là việc của Drupal).
- **Không** Pinia/Vuex (island độc lập nhau; cần thì phải có lý do và được duyệt).
- **Không** CSS framework, không Tailwind.

## 3. Cấu trúc component

```vue
<script setup>
import { ref, computed } from 'vue';

const props = defineProps({
  categories: { type: Array, required: true },
});

const selected = ref(null);
const filtered = computed(() => /* ... */);
</script>

<template>
  <!-- ... -->
</template>

<style scoped>
.filter__item--active { border-color: var(--nidqc-primary); }
</style>
```

Thứ tự: `<script setup>` → `<template>` → `<style scoped>`.

## 4. Props

- Luôn khai báo đầy đủ `type`, `required`/`default`.
- Props từ Twig qua `<script type="application/json" data-props>`.
- **Không** parse DOM để lấy dữ liệu.
- Props là **một chiều**. Không sửa props.

## 5. Style

```vue
<!-- ❌ hard-code -->
<style scoped>.tab--active { border-bottom: 2px solid #0F3093; }</style>

<!-- ✅ dùng token -->
<style scoped>.tab--active { border-bottom: 2px solid var(--nidqc-primary); }</style>
```

**Không hex trong file `.vue`.** Token là nguồn duy nhất — `DESIGN_SYSTEM.md` §7.
Đổi màu thương hiệu phải sửa đúng một chỗ.

Class theo BEM: `.doc-filter__item--active`.

## 6. Bảo mật

```vue
<!-- ⛔ TUYỆT ĐỐI KHÔNG với dữ liệu từ API/người dùng -->
<div v-html="item.description" />

<!-- ✅ -->
<div>{{ item.description }}</div>
```

- Không `v-html`. Cần HTML thật → Twig render sẵn, Vue không đụng vào.
- Không đưa dữ liệu nhạy cảm vào `data-props` (xem được bằng View Source).
- Không hard-code URL/token trong component.

## 7. Gọi API

```js
// ✅ qua src/lib/api.js
import { get } from '@/lib/api';
const data = await get('/api/v1/documents', { category, year });
```

- Không `fetch` trực tiếp trong component.
- Xử lý **đủ ba** trạng thái: `loading`, `error`, `empty`.
- Bắt lỗi theo `error.code`, không so khớp `message`.

```vue
<template>
  <p v-if="loading">Đang tải…</p>
  <p v-else-if="error">{{ error.message }}</p>
  <p v-else-if="!items.length">Không tìm thấy văn bản phù hợp.</p>
  <ul v-else><!-- ... --></ul>
</template>
```

Bỏ qua trạng thái `empty` là lỗi hay gặp — người dùng lọc không ra gì và thấy màn hình trắng.

## 8. Progressive enhancement — bắt buộc

Mỗi island phải hoạt động khi **không có JS**. Xem `FRONTEND_ARCHITECTURE.md` §5.

```vue
<script setup>
import { onMounted } from 'vue';
// Chỉ can thiệp SAU khi mount — HTML gốc phải tự dùng được trước đó.
onMounted(() => { /* nâng cấp hành vi */ });
</script>
```

**Cách kiểm:** tắt JS trong DevTools → nội dung và chức năng cốt lõi vẫn dùng được.
Không đạt = task chưa xong.

## 9. Accessibility

```vue
<button
  :aria-expanded="open"
  :aria-controls="`panel-${id}`"
  @click="open = !open"
  @keydown.enter="open = !open"
>
```

- Dùng thẻ ngữ nghĩa: `<button>`, `<nav>`, `<details>`. Không `<div @click>`.
- `aria-expanded`, `aria-controls` cho accordion/menu.
- Bàn phím dùng được. Focus nhìn rõ. **Hover không được là cách duy nhất.**

## 10. Cấm

- ⛔ `v-html` với dữ liệu động
- ⛔ Options API
- ⛔ Hard-code hex
- ⛔ `npm install` không qua duyệt
- ⛔ `console.log()` trong code merge
- ⛔ Sửa `node_modules/`
- ⛔ Import font từ CDN ngoài

## 11. Kiểm tra

```bash
cd frontend
npm run lint
npm run build
```
