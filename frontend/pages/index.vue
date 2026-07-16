<script setup>
// Trang chủ — SSR fetch tin tức từ Drupal JSON:API (ADR-004).
const { data: split } = await useAsyncData('home-news', async () => {
  const { data, included } = await fetchJsonApi('/node/news', {
    'filter[status]': 1, sort: '-field_date', 'page[limit]': 50,
    include: 'field_image,field_category',
  })
  const items = data.map((n) => ({
    id: n.attributes.drupal_internal__nid,
    title: n.attributes.title,
    date: formatDate(n.attributes.field_date || n.attributes.created),
    tag: n.attributes.field_tag || termLabel(n, 'field_category', included),
    category: termLabel(n, 'field_category', included),
    image: imageUrl(n, included),
  }))
  // Design tách 2 khối: hero = tin SỰ KIỆN, section "Thông báo" = thông báo hành chính.
  // Lọc theo CHUYÊN MỤC (category), không phải nhãn tự do (field_tag).
  const notice = ['Thông báo', 'Mua sắm - đấu thầu', 'Tuyển dụng', 'Đào tạo']
  return {
    events: items.filter((i) => !notice.includes(i.category)),
    announcements: items.filter((i) => notice.includes(i.category)),
  }
})
const news = computed(() => split.value.events)
const announcements = computed(() => split.value.announcements)

const services = [
  'Phân tích - Kiểm nghiệm', 'Đánh giá tương đương sinh học (TĐSH)',
  'Đào tạo và tư vấn kỹ thuật', 'Hiệu chuẩn',
  'Nghiên cứu - Chuyển giao', 'Thử nghiệm thành thạo',
]

// Liên kết ngoài — design/NIDQC Trang chu.html -> webLinks
const webLinks = [
  { label: 'Bộ Y Tế', href: 'https://moh.gov.vn' },
  { label: 'Cục Quản lý Dược', href: 'https://dav.gov.vn' },
  { label: 'Viện Kiểm nghiệm thuốc TP. Hồ Chí Minh', href: '#' },
  { label: 'Tổ chức Y tế Thế giới (WHO)', href: 'https://www.who.int' },
]

useHead({ title: 'Trang chủ — Viện Kiểm nghiệm thuốc Trung ương' })
</script>

<template>
  <div>
    <!-- HERO -->
    <section style="background:#F5F5F5;border-bottom:1px solid #ECECEC;">
      <div class="nidqc-hero-grid" data-container style="max-width:1280px;margin:0 auto;padding:36px 24px 40px;display:grid;grid-template-columns:1.55fr 1fr;gap:28px;align-items:stretch;">
        <template v-if="news && news.length">
          <NuxtLink :to="`/tin-tuc/${news[0].id}`" style="display:block;background:#fff;border:1px solid #CCCCCC;box-shadow:0 2px 4px rgba(0,0,0,0.06);text-decoration:none;">
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
          </NuxtLink>
          <div style="background:#fff;border:1px solid #CCCCCC;display:flex;flex-direction:column;">
            <div style="background:#0F3093;color:#fff;padding:14px 20px;display:flex;align-items:center;justify-content:space-between;">
              <h3 style="font-family:'Lexend',sans-serif;font-weight:700;font-size:16px;letter-spacing:0.3px;text-transform:uppercase;margin:0;">Tin mới nhất</h3>
              <NuxtLink to="/tin-tuc" style="color:rgba(255,255,255,0.85);font-size:12.5px;text-decoration:none;display:flex;align-items:center;gap:4px;">Xem tất cả
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="m9 18 6-6-6-6"/></svg>
              </NuxtLink>
            </div>
            <div style="flex:1;display:flex;flex-direction:column;">
              <NuxtLink v-for="item in news.slice(1, 6)" :key="item.id" :to="`/tin-tuc/${item.id}`" style="display:flex;gap:12px;padding:12px 20px;border-bottom:1px solid #ECECEC;align-items:center;flex:1;text-decoration:none;">
                <div style="width:66px;height:52px;flex:0 0 auto;background:#E8F0F7;overflow:hidden;">
                  <img v-if="item.image" :src="item.image" alt="" style="width:100%;height:100%;object-fit:cover;">
                </div>
                <span style="flex:1;min-width:0;">
                  <span style="display:block;font-size:13.5px;line-height:19px;color:#212529;font-weight:500;">{{ item.title }}</span>
                  <span style="display:block;font-size:12px;color:#777;margin-top:4px;">{{ item.date }}</span>
                </span>
              </NuxtLink>
            </div>
          </div>
        </template>
      </div>
    </section>

    <!-- HOẠT ĐỘNG CHUYÊN MÔN -->
    <section style="background:#F3F7FC;border-bottom:1px solid #ECECEC;padding:44px 0;" id="hoat-dong-chuyen-mon">
      <div data-container style="max-width:1280px;margin:0 auto;padding:0 24px;">
        <h2 style="font-family:'Lexend',sans-serif;font-weight:700;font-size:22px;color:#0F3093;margin:0 0 24px;padding-left:12px;border-left:4px solid #0F3093;">Hoạt động chuyên môn</h2>
        <div class="nidqc-grid-3" style="display:grid;grid-template-columns:repeat(3,1fr);gap:18px;">
          <a v-for="(a, i) in [
            {t:'Chỉ đạo tuyến', d:'Hướng dẫn, giám sát chuyên môn hệ thống kiểm nghiệm địa phương.'},
            {t:'Kiểm nghiệm và giám sát chất lượng thuốc', d:'Kiểm tra, giám sát chất lượng thuốc lưu hành trên toàn quốc.'},
            {t:'Hợp tác quốc tế', d:'Hợp tác với các tổ chức kiểm nghiệm và y tế quốc tế.'},
            {t:'Hoạt động NRA', d:'Cơ quan quản lý quốc gia về vắc xin theo chuẩn WHO.'},
            {t:'Tạp chí Kiểm nghiệm Dược và Mỹ phẩm', d:'Ấn phẩm khoa học công bố nghiên cứu, kết quả kiểm nghiệm.'},
          ]" :key="i" href="#hoat-dong-chuyen-mon" style="display:block;background:#fff;border:1px solid #CCCCCC;padding:22px 24px;text-decoration:none;">
            <h3 style="font-family:'Lexend',sans-serif;font-weight:600;font-size:16px;line-height:22px;color:#0F3093;margin:0 0 10px;">{{ a.t }}</h3>
            <p style="font-size:14px;line-height:21px;color:#495057;margin:0;">{{ a.d }}</p>
          </a>
        </div>
      </div>
    </section>

    <!-- THÔNG BÁO -->
    <section style="background:#F5F5F5;border-bottom:1px solid #ECECEC;padding:44px 0;" id="thong-bao">
      <div data-container style="max-width:1280px;margin:0 auto;padding:0 24px;">
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:24px;">
          <h2 style="font-family:'Lexend',sans-serif;font-weight:700;font-size:22px;color:#0F3093;margin:0;padding-left:12px;border-left:4px solid #0F3093;">Thông báo</h2>
          <NuxtLink to="/tin-tuc" style="color:#1D6AC5;font-size:14px;text-decoration:none;">Xem tất cả thông báo →</NuxtLink>
        </div>
        <div class="nidqc-grid-4" style="display:grid;grid-template-columns:repeat(4,1fr);gap:18px;">
          <NuxtLink v-for="item in announcements.slice(0, 4)" :key="item.id" :to="`/tin-tuc/${item.id}`" style="display:block;background:#fff;border:1px solid #ECECEC;text-decoration:none;">
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
          </NuxtLink>
        </div>
      </div>
    </section>

    <!-- DỊCH VỤ -->
    <section style="background:#fff;padding:44px 0;" id="dich-vu">
      <div style="max-width:1280px;margin:0 auto;padding:0 24px;">
        <h2 style="font-family:'Lexend',sans-serif;font-weight:700;font-size:22px;color:#0F3093;margin:0 0 6px;">Dịch vụ &amp; tra cứu</h2>
        <p style="color:#495057;font-size:15px;margin:0 0 24px;">Các dịch vụ khoa học kỹ thuật và công cụ tra cứu của Viện.</p>
        <div class="nidqc-grid-3" style="display:grid;grid-template-columns:repeat(3,1fr);gap:18px;">
          <a v-for="(s, i) in services" :key="i" href="#dich-vu" style="display:block;background:#F5F8FC;border:1px solid #CCCCCC;padding:20px 22px;text-decoration:none;">
            <h3 style="font-family:'Lexend',sans-serif;font-weight:600;font-size:16px;color:#0F3093;margin:0;">{{ s }}</h3>
          </a>
        </div>
      </div>
    </section>

    <!-- CTA CHẤT CHUẨN -->
    <section style="background:#0F3093;padding:44px 0;" id="chat-chuan">
      <div style="max-width:1280px;margin:0 auto;padding:0 24px;display:flex;align-items:center;justify-content:space-between;gap:28px;flex-wrap:wrap;">
        <div>
          <h2 style="font-family:'Lexend',sans-serif;font-weight:700;font-size:24px;color:#fff;margin:0 0 6px;">Chất chuẩn – chất đối chiếu</h2>
          <p style="color:rgba(255,255,255,0.85);font-size:15px;margin:0;max-width:620px;">Tra cứu và đăng ký cung ứng chất chuẩn, chất đối chiếu phục vụ kiểm nghiệm.</p>
        </div>
        <a href="https://nidqc.gov.vn/tim-kiem-chat-chuan" style="display:inline-block;background:#fff;color:#0F3093;font-weight:600;font-size:14px;padding:11px 22px;text-decoration:none;">Tra cứu chất chuẩn</a>
      </div>
    </section>

    <!-- LIÊN KẾT & LIÊN HỆ -->
    <section style="background:#F5F5F5;padding:44px 0;" id="lien-he">
      <div class="nidqc-two-col" data-container style="max-width:1280px;margin:0 auto;padding:0 24px;display:grid;grid-template-columns:1.5fr 1fr;gap:40px;">
        <div>
          <h2 style="font-family:'Lexend',sans-serif;font-weight:700;font-size:22px;color:#0F3093;margin:0 0 18px;padding-left:12px;border-left:4px solid #0F3093;">Liên kết</h2>
          <div style="display:flex;flex-direction:column;gap:9px;">
            <a v-for="(l, i) in webLinks" :key="i" :href="l.href" target="_blank" rel="noopener" style="color:#1D6AC5;font-size:14.5px;text-decoration:none;">{{ l.label }}</a>
          </div>
        </div>
        <div>
          <h2 style="font-family:'Lexend',sans-serif;font-weight:700;font-size:22px;color:#0F3093;margin:0 0 18px;padding-left:12px;border-left:4px solid #0F3093;">Liên hệ</h2>
          <p style="font-size:14.5px;line-height:24px;color:#495057;margin:0 0 16px;">48 Hai Bà Trưng, Hoàn Kiếm, Hà Nội<br>ĐT: (024) 3825 5075</p>
          <NuxtLink to="/lien-he" style="display:inline-block;background:#0F3093;color:#fff;font-weight:600;font-size:14px;padding:11px 22px;text-decoration:none;">Trang liên hệ &amp; hỗ trợ</NuxtLink>
        </div>
      </div>
    </section>
  </div>
</template>
