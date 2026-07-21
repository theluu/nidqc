<script setup>
// Phân trang số: [Trước] 1 2 … 12 13 14 … 59 [Sau]. Không tự điều hướng — chỉ phát
// sự kiện 'change'; trang cha cập nhật URL (?trang=) để giữ SSR + chia sẻ link được.
const props = defineProps({
  current: { type: Number, required: true },
  total: { type: Number, required: true }, // tổng số trang
})
const emit = defineEmits(['change'])

// Danh sách trang hiển thị: luôn có trang đầu/cuối và cửa sổ ±1 quanh trang hiện tại,
// phần khuyết thay bằng '…'.
const items = computed(() => {
  const { current, total } = props
  if (total <= 1) return [1]
  const keep = new Set([1, total, current, current - 1, current + 1])
  const nums = [...keep].filter((n) => n >= 1 && n <= total).sort((a, b) => a - b)
  const out = []
  let prev = 0
  for (const n of nums) {
    if (n - prev > 1) out.push('…')
    out.push(n)
    prev = n
  }
  return out
})

const go = (n) => {
  if (typeof n !== 'number' || n === props.current || n < 1 || n > props.total) return
  emit('change', n)
}
</script>

<template>
  <nav v-if="total > 1" class="pg" aria-label="Phân trang">
    <button class="pg__btn pg__edge" :disabled="current <= 1" @click="go(current - 1)" aria-label="Trang trước">
      <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="m15 18-6-6 6-6" /></svg>
      <span class="pg__edge-txt">Trước</span>
    </button>

    <template v-for="(it, i) in items" :key="i">
      <span v-if="it === '…'" class="pg__dots">…</span>
      <button
        v-else
        class="pg__btn pg__num"
        :class="{ 'is-active': it === current }"
        :aria-current="it === current ? 'page' : undefined"
        @click="go(it)"
      >{{ it }}</button>
    </template>

    <button class="pg__btn pg__edge" :disabled="current >= total" @click="go(current + 1)" aria-label="Trang sau">
      <span class="pg__edge-txt">Sau</span>
      <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="m9 18 6-6-6-6" /></svg>
    </button>
  </nav>
</template>

<style scoped>
.pg {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  justify-content: center;
  gap: 6px;
  margin-top: 40px;
}
.pg__btn {
  display: inline-flex;
  align-items: center;
  gap: 5px;
  min-width: 38px;
  height: 38px;
  padding: 0 10px;
  border: 1px solid #D8DEE6;
  background: #fff;
  color: #495057;
  font-size: 14px;
  font-weight: 500;
  cursor: pointer;
  border-radius: 6px;
  transition: border-color .15s, background .15s, color .15s;
}
.pg__num {
  justify-content: center;
}
.pg__btn:hover:not(:disabled):not(.is-active) {
  border-color: #0F3093;
  color: #0F3093;
}
.pg__num.is-active {
  background: #0F3093;
  border-color: #0F3093;
  color: #fff;
  cursor: default;
}
.pg__btn:disabled {
  opacity: .45;
  cursor: not-allowed;
}
.pg__dots {
  min-width: 22px;
  text-align: center;
  color: #98A2B3;
  user-select: none;
}
@media (max-width: 520px) {
  .pg__edge-txt { display: none; }
  .pg__edge { min-width: 38px; padding: 0; justify-content: center; }
}
</style>
