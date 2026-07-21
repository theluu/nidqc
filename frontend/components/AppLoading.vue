<script setup>
// Chỉ hiện spinner khi điều hướng client-side chậm HƠN 2s (yêu cầu: <2s thì không
// hiện gì để khỏi nhấp nháy). Màu/style theo hệ thống: xanh #0F3093, bo tròn, sạch.
const show = ref(false)
let timer = null

const { isLoading } = useLoadingIndicator()

watch(isLoading, (loading) => {
  clearTimeout(timer)
  if (loading) {
    timer = setTimeout(() => { show.value = true }, 2000)
  } else {
    show.value = false
  }
})

onBeforeUnmount(() => clearTimeout(timer))
</script>

<template>
  <Transition name="apl-fade">
    <div v-if="show" class="apl" role="status" aria-live="polite" aria-label="Đang tải">
      <div class="apl__box">
        <span class="apl__spinner"></span>
        <span class="apl__text">Đang tải…</span>
      </div>
    </div>
  </Transition>
</template>

<style scoped>
.apl {
  position: fixed;
  inset: 0;
  z-index: 200;
  display: flex;
  align-items: center;
  justify-content: center;
  background: rgba(255, 255, 255, 0.55);
  backdrop-filter: blur(1.5px);
}
.apl__box {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 14px;
  padding: 26px 34px;
  background: #fff;
  border: 1px solid #ECECEC;
  border-radius: 12px;
  box-shadow: 0 10px 30px rgba(15, 48, 147, 0.14);
}
.apl__spinner {
  width: 38px;
  height: 38px;
  border: 3.5px solid #E1E8F5;
  border-top-color: #0F3093;
  border-radius: 50%;
  animation: apl-spin 0.7s linear infinite;
}
.apl__text {
  font-family: 'Be Vietnam Pro', sans-serif;
  font-size: 13.5px;
  font-weight: 500;
  color: #0F3093;
  letter-spacing: 0.2px;
}
@keyframes apl-spin {
  to { transform: rotate(360deg); }
}
.apl-fade-enter-active,
.apl-fade-leave-active { transition: opacity 0.2s; }
.apl-fade-enter-from,
.apl-fade-leave-to { opacity: 0; }

@media (prefers-reduced-motion: reduce) {
  .apl__spinner { animation-duration: 1.4s; }
}
</style>
