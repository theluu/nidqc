<script setup>
// Cột "Tin tức & Thông báo" bên phải khối hero — dạng TAB (feedback 5).
//
// Nhãn tab lấy thẳng từ submenu "Tin tức & Thông báo" của main menu
// (useMainNav.navChildren) nên menu và trang chủ không bao giờ lệch nhau. Mỗi tab
// hiện 5 tin mới nhất của chuyên mục đó: tiêu đề + ngày đăng, dưới cùng là link
// xem toàn bộ chuyên mục.
//
// Nội dung của MỌI tab đều nằm trong HTML server trả về (chỉ ẩn/hiện bằng CSS
// class), không render theo tab đang chọn: crawler phải đọc được cả 30 tin.
const props = defineProps({
  // [{ label, to, items: [{ id, title, date, alias }] }]
  tabs: { type: Array, required: true },
})

const active = ref(0)

// Bỏ hẳn tab rỗng: chuyên mục chưa có bài nào mà vẫn hiện tab thì bấm vào chỉ thấy
// khoảng trắng.
const visibleTabs = computed(() => props.tabs.filter((tab) => tab.items && tab.items.length))
</script>

<template>
  <div v-if="visibleTabs.length" class="tabs">
    <div class="tabs__strip" role="tablist" aria-label="Chuyên mục tin tức">
      <button
        v-for="(tab, i) in visibleTabs"
        :id="`news-tab-${i}`"
        :key="tab.label"
        type="button"
        role="tab"
        class="tabs__btn"
        :class="{ 'is-active': i === active }"
        :aria-selected="i === active ? 'true' : 'false'"
        :aria-controls="`news-panel-${i}`"
        :tabindex="i === active ? 0 : -1"
        @click="active = i"
      >
        {{ tab.label }}
      </button>
    </div>

    <div
      v-for="(tab, i) in visibleTabs"
      :id="`news-panel-${i}`"
      :key="`p-${tab.label}`"
      role="tabpanel"
      :aria-labelledby="`news-tab-${i}`"
      class="tabs__panel"
      :class="{ 'is-active': i === active }"
    >
      <ul class="tabs__list">
        <li v-for="item in tab.items" :key="item.id">
          <NuxtLink :to="item.alias" class="tabs__item">
            <span class="tabs__title">{{ item.title }}</span>
            <span class="tabs__date">
              <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><rect x="3" y="4" width="18" height="18" rx="2" /><path d="M16 2v4M8 2v4M3 10h18" /></svg>
              {{ item.date }}
            </span>
          </NuxtLink>
        </li>
      </ul>
      <NuxtLink :to="tab.to" class="tabs__all">
        Xem tất cả
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M13 6l6 6-6 6" /></svg>
      </NuxtLink>
    </div>
  </div>
</template>

<style scoped>
.tabs {
  display: flex;
  flex-direction: column;
  background: #fff;
  border: 1px solid #CCCCCC;
  min-height: 0;
}

/* Dải tab XUỐNG NHIỀU HÀNG (feedback 21/08: mockup của khách xếp tab thành hai
   hàng "[Thông báo] [Tin hoạt động]" / "[Mua sắm, đấu thầu] [Đào tạo]").
   Bản trước cuộn ngang một hàng: tab thứ ba trở đi nằm ngoài khung, người dùng
   không thấy là có tab khác nên coi như mất luôn các chuyên mục đó.
   Nút co giãn theo nội dung nhưng có min-width để hai nút cùng hàng không lệch
   nhau quá nhiều. */
.tabs__strip {
  display: flex;
  flex-wrap: wrap;
  gap: 2px;
  background: #0F3093;
  padding: 0 2px;
}
.tabs__btn {
  flex: 1 1 auto;
  min-width: 120px;
  text-align: center;
  padding: 11px 12px;
  border: 0;
  background: none;
  color: rgba(255, 255, 255, 0.78);
  font: inherit;
  font-family: 'Lexend', sans-serif;
  font-size: 12.5px;
  font-weight: 600;
  line-height: 16px;
  cursor: pointer;
  border-bottom: 3px solid transparent;
  transition: color .15s ease, background .15s ease;
}
.tabs__btn:hover { color: #fff; background: rgba(255, 255, 255, 0.09); }
.tabs__btn.is-active {
  color: #fff;
  background: rgba(255, 255, 255, 0.14);
  border-bottom-color: #FFC53D;
}
.tabs__btn:focus-visible { outline: 2px solid #fff; outline-offset: -2px; }

/* Panel ẩn dùng display:none để rơi khỏi thứ tự tab; nội dung vẫn nằm trong HTML
   nên bot đọc được. */
.tabs__panel { display: none; flex-direction: column; flex: 1; min-height: 0; }
.tabs__panel.is-active { display: flex; }

/* Các dòng tin GIÃN ĐỀU lấp hết chiều cao còn lại. Khối hero bên trái cao hơn cột
   này (ảnh 16:9 + khối chữ), align-items:stretch kéo cột tab cho bằng — nếu danh
   sách không nở thì phần dư dồn thành một mảng trắng ngay trên link "Xem tất cả".
   Giãn đều cũng đúng mockup khách gửi: 5 dòng tin chia đều chiều cao khung. */
.tabs__list {
  list-style: none;
  margin: 0;
  padding: 0;
  flex: 1;
  display: flex;
  flex-direction: column;
}
.tabs__list > li { display: flex; flex: 1; }
.tabs__item {
  display: flex;
  flex: 1;
  flex-direction: column;
  justify-content: center;
  padding: 12px 16px;
  border-bottom: 1px solid #F0F0F0;
  text-decoration: none;
  transition: background .15s ease;
}
.tabs__item:hover { background: #F7FAFD; }
.tabs__title {
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
  font-size: 13.5px;
  line-height: 19px;
  color: #212529;
  font-weight: 500;
}
.tabs__item:hover .tabs__title { color: #0F3093; }
.tabs__date {
  display: flex;
  align-self: flex-start;
  align-items: center;
  gap: 5px;
  margin-top: 6px;
  font-size: 11.5px;
  color: #777;
}
.tabs__item:focus-visible { outline: 2px solid #1D6AC5; outline-offset: -2px; }

.tabs__all {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 7px;
  padding: 12px;
  background: #F3F7FC;
  border-top: 1px solid #E4E9F0;
  color: #0F3093;
  font-size: 13px;
  font-weight: 600;
  text-decoration: none;
}
.tabs__all:hover { background: #E4EDF8; }
.tabs__all:focus-visible { outline: 2px solid #1D6AC5; outline-offset: -2px; }
</style>
