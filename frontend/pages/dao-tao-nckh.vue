<script setup>
const { data: projects } = await useCachedData('projects', async () => {
  const { data } = await fetchJsonApi('/node/project', { 'filter[status]': 1, 'page[limit]': 50 })
  return data.map((n) => ({ title: n.attributes.title, year: n.attributes.field_year || '', desc: n.attributes.field_description?.processed || n.attributes.field_description?.value || '' }))
})
const phdInfo = [
  { label: 'Chuyên ngành', value: 'Kiểm nghiệm thuốc - Độc chất học' },
  { label: 'Thời gian đào tạo', value: '3 năm (tập trung) hoặc 4 năm (không tập trung)' },
  { label: 'Văn bằng', value: 'Tiến sỹ Dược học, do Viện cấp theo quy chế của Bộ Giáo dục & Đào tạo' },
]
const steps = [
  { t: 'Thông báo tuyển sinh', d: 'Viện công bố chỉ tiêu, chuyên ngành và điều kiện dự tuyển hằng năm trên trang Tin tức & Thông báo.' },
  { t: 'Xét tuyển hồ sơ và bảo vệ đề cương', d: 'Hội đồng khoa học xét hồ sơ, tổ chức bảo vệ đề cương nghiên cứu trước hội đồng chuyên môn.' },
  { t: 'Đào tạo và nghiên cứu', d: 'Nghiên cứu sinh thực hiện các học phần chuyên đề, nghiên cứu dưới sự hướng dẫn của giảng viên - cán bộ khoa học của Viện.' },
  { t: 'Bảo vệ luận án', d: 'Bảo vệ luận án cấp Viện và cấp Nhà nước theo quy chế đào tạo trình độ tiến sỹ hiện hành.' },
]
const directions = [
  { icon: 'M9 3h6v2l-1 0v5l4 7a2 2 0 0 1-1.7 3H8.7A2 2 0 0 1 7 17l4-7V5l-1 0z', text: 'Phương pháp phân tích - kiểm nghiệm mới' },
  { icon: 'M3 7V5a2 2 0 0 1 2-2h2 M17 3h2a2 2 0 0 1 2 2v2 M21 17v2a2 2 0 0 1-2 2h-2 M7 21H5a2 2 0 0 1-2-2v-2', text: 'Chất chuẩn - chất đối chiếu quốc gia' },
  { icon: 'M12 2l8 4v6c0 5-3.5 8-8 10-4.5-2-8-5-8-10V6z M9 12l2 2 4-4', text: 'Đánh giá chất lượng dược liệu, thuốc cổ truyền' },
  { icon: 'M14.7 6.3a4 4 0 0 0-5.6 5.6l-6.4 6.4 1.4 1.4 6.4-6.4a4 4 0 0 0 5.6-5.6l-2.1 2.1-1.4-1.4z', text: 'Độc chất học & an toàn dược phẩm' },
]
useSeoMeta({ title: 'Đào tạo & NCKH — NIDQC', description: 'Đào tạo tiến sỹ chuyên ngành Kiểm nghiệm thuốc - Độc chất học và các đề tài nghiên cứu khoa học của Viện Kiểm nghiệm thuốc Trung ương.', ogTitle: 'Đào tạo & NCKH — NIDQC', ogDescription: 'Đào tạo tiến sỹ và các đề tài nghiên cứu khoa học của Viện Kiểm nghiệm thuốc Trung ương.' })
</script>
<template>
  <div>
    <!-- Hero band -->
    <section style="background:#0F3093;color:#fff;">
      <div style="max-width:1280px;margin:0 auto;padding:48px 24px;">
        <nav style="font-size:13px;color:#B9C6DE;margin-bottom:22px;"><NuxtLink to="/" style="color:#B9C6DE;text-decoration:none;">Trang chủ</NuxtLink> / <span style="color:#fff;">Đào tạo & NCKH</span></nav>
        <h1 style="font-family:'Lexend',sans-serif;font-weight:700;font-size:32px;margin:0 0 12px;">Đào tạo &amp; Nghiên cứu khoa học</h1>
        <p style="font-size:15.5px;line-height:25px;color:#DCE5F3;max-width:720px;margin:0;">Viện Kiểm nghiệm thuốc Trung ương là cơ sở đào tạo sau đại học và trung tâm nghiên cứu khoa học đầu ngành trong lĩnh vực kiểm nghiệm dược phẩm, góp phần phát triển nguồn nhân lực và nâng cao năng lực khoa học kỹ thuật của ngành.</p>
      </div>
    </section>

    <!-- Đào tạo tiến sỹ -->
    <section style="background:#fff;padding:44px 0 20px;"><div style="max-width:1280px;margin:0 auto;padding:0 24px;">
      <h2 style="font-family:'Lexend',sans-serif;font-weight:700;font-size:22px;color:#212529;margin:0 0 12px;">Đào tạo tiến sỹ</h2>
      <p style="font-size:15.5px;line-height:25px;color:#334155;max-width:900px;margin:0 0 26px;">Viện được Bộ Y tế giao nhiệm vụ đào tạo trình độ tiến sỹ chuyên ngành Kiểm nghiệm thuốc - Độc chất học, nhằm cung cấp nguồn cán bộ khoa học kỹ thuật trình độ cao cho hệ thống kiểm nghiệm dược phẩm trên cả nước.</p>
      <div class="nidqc-grid-3" style="display:grid;grid-template-columns:repeat(3,1fr);gap:20px;margin-bottom:44px;">
        <div v-for="(c, i) in phdInfo" :key="i" style="background:#F5F8FC;border:1px solid #E4EBF4;border-top:3px solid #0F3093;padding:22px 24px;">
          <div style="font-family:'Lexend',sans-serif;font-weight:700;font-size:12.5px;letter-spacing:0.6px;text-transform:uppercase;color:#0F3093;margin:0 0 10px;">{{ c.label }}</div>
          <div style="font-size:14.5px;line-height:22px;color:#334155;">{{ c.value }}</div>
        </div>
      </div>
    </div></section>

    <!-- Quy trình tuyển sinh -->
    <section style="background:#fff;padding:0 0 30px;"><div style="max-width:1280px;margin:0 auto;padding:0 24px;">
      <h2 style="font-family:'Lexend',sans-serif;font-weight:700;font-size:22px;color:#212529;margin:0 0 22px;">Quy trình tuyển sinh &amp; đào tạo</h2>
      <div style="display:flex;flex-direction:column;">
        <div v-for="(s, i) in steps" :key="i" style="display:flex;gap:18px;align-items:flex-start;padding:18px 0;border-bottom:1px solid #ECECEC;">
          <span style="flex-shrink:0;width:30px;height:30px;border-radius:50%;background:#0F3093;color:#fff;font-family:'Lexend',sans-serif;font-weight:700;font-size:13px;display:flex;align-items:center;justify-content:center;">{{ i + 1 }}</span>
          <div><h3 style="font-family:'Lexend',sans-serif;font-weight:600;font-size:16px;color:#212529;margin:0 0 5px;">{{ s.t }}</h3><p style="font-size:14.5px;line-height:22px;color:#495057;margin:0;">{{ s.d }}</p></div>
        </div>
      </div>
    </div></section>

    <!-- Nghiên cứu khoa học -->
    <section style="background:#fff;padding:34px 0 20px;"><div style="max-width:1280px;margin:0 auto;padding:0 24px;">
      <h2 style="font-family:'Lexend',sans-serif;font-weight:700;font-size:22px;color:#212529;margin:0 0 12px;">Nghiên cứu khoa học</h2>
      <p style="font-size:15.5px;line-height:25px;color:#334155;max-width:900px;margin:0 0 26px;">Hoạt động nghiên cứu khoa học của Viện tập trung xây dựng phương pháp kiểm nghiệm mới, phát triển chất chuẩn quốc gia và nâng cao năng lực kiểm soát chất lượng dược phẩm, mỹ phẩm.</p>
      <h3 style="font-family:'Lexend',sans-serif;font-weight:600;font-size:16.5px;color:#212529;margin:0 0 18px;">Hướng nghiên cứu trọng tâm</h3>
      <div class="nidqc-grid-4" style="display:grid;grid-template-columns:repeat(4,1fr);gap:16px;margin-bottom:44px;">
        <div v-for="(d, i) in directions" :key="i" style="background:#fff;border:1px solid #ECECEC;padding:22px 20px;">
          <div style="width:42px;height:42px;background:#EAF0F8;border-radius:6px;display:flex;align-items:center;justify-content:center;margin-bottom:14px;">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#0F3093" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path :d="d.icon"/></svg>
          </div>
          <div style="font-family:'Lexend',sans-serif;font-weight:600;font-size:14.5px;line-height:20px;color:#212529;">{{ d.text }}</div>
        </div>
      </div>
    </div></section>

    <!-- Đề tài, dự án tiêu biểu (động từ Drupal) -->
    <section style="background:#fff;padding:0 0 60px;"><div style="max-width:1280px;margin:0 auto;padding:0 24px;">
      <h2 style="font-family:'Lexend',sans-serif;font-weight:700;font-size:22px;color:#212529;margin:0 0 22px;">Đề tài, dự án tiêu biểu</h2>
      <p v-if="!projects || !projects.length" style="color:#777;">Đang cập nhật.</p>
      <div v-else style="display:flex;flex-direction:column;">
        <div v-for="(p, i) in projects" :key="i" style="display:flex;gap:20px;align-items:flex-start;justify-content:space-between;padding:18px 0;border-bottom:1px solid #ECECEC;">
          <div><h3 style="font-family:'Lexend',sans-serif;font-weight:600;font-size:16px;color:#212529;margin:0 0 5px;">{{ p.title }}</h3><p style="font-size:14px;line-height:21px;color:#495057;margin:0;" v-html="p.desc"></p></div>
          <span v-if="p.year" style="flex-shrink:0;color:#0F3093;font-weight:600;font-size:13.5px;white-space:nowrap;">{{ p.year }}</span>
        </div>
      </div>
    </div></section>
  </div>
</template>
