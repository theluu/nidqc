<script setup>
import { ref, onMounted } from 'vue';
import { RouterLink } from 'vue-router';
import { jsonapi, imageUrl, termLabel, formatDate } from '@/lib/api';

const news = ref([]);
const loading = ref(true);
const error = ref(null);

const services = [
  'Phân tích - Kiểm nghiệm', 'Đánh giá tương đương sinh học (TĐSH)',
  'Đào tạo và tư vấn kỹ thuật', 'Hiệu chuẩn',
  'Nghiên cứu - Chuyển giao', 'Thử nghiệm thành thạo',
];

onMounted(async () => {
  try {
    const { data, included } = await jsonapi('/node/news', {
      'filter[status]': 1,
      'sort': '-created',
      'page[limit]': 7,
      'include': 'field_image,field_category',
    });
    news.value = data.map((n) => ({
      title: n.attributes.title,
      date: formatDate(n.attributes.field_date || n.attributes.created),
      tag: n.attributes.field_tag || termLabel(n, 'field_category', included),
      image: imageUrl(n, included),
      id: n.attributes.drupal_internal__nid,
    }));
  } catch (e) {
    error.value = e.message;
  } finally {
    loading.value = false;
  }
});
</script>

<template>
  <!-- ===== HERO ===== -->
  <section style="background:#F5F5F5;border-bottom:1px solid #ECECEC;">
    <div style="max-width:1280px;margin:0 auto;padding:36px 24px 40px;display:grid;grid-template-columns:1.55fr 1fr;gap:28px;align-items:stretch;">

      <p v-if="loading" style="color:#777;">Đang tải tin tức…</p>
      <p v-else-if="error" style="color:#b00020;">{{ error }}</p>

      <template v-else-if="news.length">
        <!-- Featured -->
        <RouterLink :to="`/tin-tuc/${news[0].id}`" style="display:block;background:#fff;border:1px solid #CCCCCC;box-shadow:0 2px 4px rgba(0,0,0,0.06);text-decoration:none;">
          <div style="position:relative;width:100%;height:340px;overflow:hidden;background:#0D2870;">
            <img v-if="news[0].image" :src="news[0].image" alt="" style="width:100%;height:100%;object-fit:cover;">
            <span style="position:absolute;top:16px;left:16px;background:#0F3093;color:#fff;font-family:'Lexend',sans-serif;font-weight:700;font-size:11.5px;letter-spacing:0.6px;text-transform:uppercase;padding:6px 12px;">Tin tức &amp; sự kiện</span>
          </div>
          <div style="padding:22px 24px 26px;">
            <div style="display:flex;align-items:center;gap:7px;color:#777;font-size:12.5px;margin-bottom:10px;">
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/></svg>
              <span>Cập nhật: {{ news[0].date }}</span>
            </div>
            <h2 style="font-family:'Lexend',sans-serif;font-weight:700;font-size:25px;line-height:31px;color:#212529;margin:0;">{{ news[0].title }}</h2>
          </div>
        </RouterLink>

        <!-- Danh sách tin -->
        <div style="display:flex;flex-direction:column;">
          <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:14px;">
            <h3 style="font-family:'Lexend',sans-serif;font-weight:700;font-size:16px;color:#0F3093;margin:0;padding-left:10px;border-left:4px solid #0F3093;">Tin mới nhất</h3>
            <RouterLink to="/tin-tuc" style="color:#1D6AC5;font-size:13px;text-decoration:none;">Xem tất cả →</RouterLink>
          </div>
          <RouterLink v-for="(item, i) in news.slice(1, 6)" :key="i" :to="`/tin-tuc/${item.id}`"
            style="display:flex;gap:12px;padding:11px 0;border-bottom:1px solid #ECECEC;text-decoration:none;">
            <div style="width:74px;height:56px;flex:0 0 auto;background:#E8F0F7;overflow:hidden;">
              <img v-if="item.image" :src="item.image" alt="" style="width:100%;height:100%;object-fit:cover;">
            </div>
            <div style="min-width:0;">
              <span v-if="item.tag" style="display:inline-block;font-size:10.5px;font-weight:600;color:#1D6AC5;text-transform:uppercase;letter-spacing:0.4px;margin-bottom:3px;">{{ item.tag }}</span>
              <div style="font-size:13.5px;line-height:18px;color:#212529;">{{ item.title }}</div>
              <div style="font-size:12px;color:#777;margin-top:3px;">{{ item.date }}</div>
            </div>
          </RouterLink>
        </div>
      </template>
    </div>
  </section>

  <!-- ===== THÔNG BÁO (grid ảnh) ===== -->
  <section style="background:#F5F5F5;border-bottom:1px solid #ECECEC;padding:44px 0;" id="hoat-dong">
    <div style="max-width:1280px;margin:0 auto;padding:0 24px;">
      <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:24px;">
        <h2 style="font-family:'Lexend',sans-serif;font-weight:700;font-size:22px;color:#0F3093;margin:0;padding-left:12px;border-left:4px solid #0F3093;">Thông báo</h2>
        <RouterLink to="/tin-tuc" style="color:#1D6AC5;font-size:14px;text-decoration:none;">Xem tất cả thông báo →</RouterLink>
      </div>
      <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:18px;">
        <RouterLink v-for="(item, i) in news.slice(0, 4)" :key="i" :to="`/tin-tuc/${item.id}`"
          style="display:block;background:#fff;border:1px solid #ECECEC;text-decoration:none;">
          <div style="width:100%;height:150px;background:#E8F0F7;overflow:hidden;">
            <img v-if="item.image" :src="item.image" alt="" style="width:100%;height:100%;object-fit:cover;">
          </div>
          <div style="padding:16px 18px 20px;">
            <span v-if="item.tag" style="display:inline-block;background:#E8F0F7;color:#0F3093;font-size:10.5px;font-weight:600;text-transform:uppercase;letter-spacing:0.4px;padding:3px 9px;margin-bottom:10px;">{{ item.tag }}</span>
            <div style="font-size:14px;line-height:20px;color:#212529;font-weight:500;margin-bottom:10px;">{{ item.title }}</div>
            <div style="display:flex;align-items:center;gap:6px;color:#777;font-size:12px;">
              <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/></svg>
              {{ item.date }}
            </div>
          </div>
        </RouterLink>
      </div>
    </div>
  </section>

  <!-- ===== DỊCH VỤ ===== -->
  <section style="background:#fff;padding:44px 0;" id="dich-vu">
    <div style="max-width:1280px;margin:0 auto;padding:0 24px;">
      <h2 style="font-family:'Lexend',sans-serif;font-weight:700;font-size:22px;color:#0F3093;margin:0 0 6px;">Dịch vụ &amp; tra cứu</h2>
      <p style="color:#495057;font-size:15px;margin:0 0 24px;">Các dịch vụ khoa học kỹ thuật và công cụ tra cứu của Viện.</p>
      <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:18px;">
        <a v-for="(s, i) in services" :key="i" href="#dich-vu"
           style="display:block;background:#F5F8FC;border:1px solid #CCCCCC;padding:20px 22px;text-decoration:none;">
          <h3 style="font-family:'Lexend',sans-serif;font-weight:600;font-size:16px;color:#0F3093;margin:0;">{{ s }}</h3>
        </a>
      </div>
    </div>
  </section>

  <!-- ===== CTA CHẤT CHUẨN ===== -->
  <section style="background:#0F3093;padding:44px 0;" id="chat-chuan">
    <div style="max-width:1280px;margin:0 auto;padding:0 24px;display:flex;align-items:center;justify-content:space-between;gap:28px;flex-wrap:wrap;">
      <div>
        <h2 style="font-family:'Lexend',sans-serif;font-weight:700;font-size:24px;color:#fff;margin:0 0 6px;">Chất chuẩn – chất đối chiếu</h2>
        <p style="color:rgba(255,255,255,0.85);font-size:15px;margin:0;max-width:620px;">Tra cứu và đăng ký cung ứng chất chuẩn, chất đối chiếu phục vụ kiểm nghiệm.</p>
      </div>
      <a href="https://nidqc.gov.vn/tim-kiem-chat-chuan" style="display:inline-block;background:#fff;color:#0F3093;font-weight:600;font-size:14px;padding:11px 22px;text-decoration:none;">Tra cứu chất chuẩn</a>
    </div>
  </section>
</template>
