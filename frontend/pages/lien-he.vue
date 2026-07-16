<script setup>
const { data: body } = await useAsyncData('lien-he', async () => {
  const { data } = await fetchJsonApi('/node/page', { 'filter[path.alias]': '/lien-he', 'page[limit]': 1 })
  return data.length ? (data[0].attributes.body?.processed || '') : ''
})
const form = ref({ name: '', email: '', message: '' })
const sent = ref(false)
useSeoMeta({ title: 'Liên hệ & hỗ trợ — NIDQC', description: 'Thông tin liên hệ, địa chỉ hai cơ sở và biểu mẫu hỗ trợ của Viện Kiểm nghiệm thuốc Trung ương.', ogTitle: 'Liên hệ & hỗ trợ — NIDQC', ogDescription: 'Thông tin liên hệ, địa chỉ hai cơ sở và biểu mẫu hỗ trợ của Viện Kiểm nghiệm thuốc Trung ương.' })
</script>
<template>
  <div>
    <PageBand title="Liên hệ & hỗ trợ" description="Mọi ý kiến đóng góp, yêu cầu hỗ trợ hoặc phối hợp công tác, xin liên hệ theo thông tin dưới đây hoặc gửi qua biểu mẫu." />
    <section style="background:#fff;padding:34px 0 60px;"><div class="nidqc-two-col" style="max-width:1280px;margin:0 auto;padding:0 24px;display:grid;grid-template-columns:1fr 1fr;gap:40px;">
      <div v-html="body" style="font-size:15.5px;line-height:25px;color:#212529;"></div>
      <div>
        <h2 style="font-family:'Lexend',sans-serif;font-weight:700;font-size:18px;color:#0F3093;margin:0 0 18px;">Gửi liên hệ</h2>
        <div v-if="sent" style="background:#E8F0F7;border:1px solid #7FA8E0;padding:20px;color:#0F3093;">Cảm ơn bạn đã gửi liên hệ. Viện sẽ phản hồi sớm nhất có thể.</div>
        <form v-else @submit.prevent="sent = true" style="display:flex;flex-direction:column;gap:14px;">
          <input v-model="form.name" required placeholder="Họ và tên" style="border:1px solid #CCCCCC;padding:11px 14px;font-size:14px;">
          <input v-model="form.email" type="email" required placeholder="Email" style="border:1px solid #CCCCCC;padding:11px 14px;font-size:14px;">
          <textarea v-model="form.message" required rows="5" placeholder="Nội dung" style="border:1px solid #CCCCCC;padding:11px 14px;font-size:14px;resize:vertical;"></textarea>
          <button type="submit" style="background:#0F3093;color:#fff;border:none;padding:12px;font-size:14px;font-weight:600;cursor:pointer;">Gửi liên hệ</button>
        </form>
      </div>
    </div></section>
  </div>
</template>
