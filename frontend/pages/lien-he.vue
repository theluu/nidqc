<script setup>
const { data: page } = await useCachedData('lien-he', async () => {
  const [pg, off] = await Promise.all([
    fetchPageByAlias('/lien-he'),
    fetchJsonApi('/node/office', { 'filter[status]': 1, sort: 'field_weight', 'page[limit]': 10 }),
  ])
  return {
    body: pg?.body || '',
    offices: off.data.map((n) => ({
      t: n.attributes.title, addr: n.attributes.field_address,
      tel: n.attributes.field_phone, map: n.attributes.field_map,
    })),
  }
})
const body = computed(() => page.value?.body || '')
const offices = computed(() => page.value?.offices || [])

const contactSubjects = [
  'Dịch vụ kiểm nghiệm',
  'Chất chuẩn - chất đối chiếu',
  'Văn bản - tài liệu',
  'Khác',
]

const form = reactive({
  name: '',
  email: '',
  phone: '',
  subject: contactSubjects[0],
  message: '',
})
const sent = ref(false)
const submitting = ref(false)
const errorMessage = ref('')
const successMessage = ref('')
const { executeRecaptcha } = useRecaptchaV3()
const lienHeLinks = [
  { label: 'Liên hệ', to: '/lien-he' },
  { label: 'Câu hỏi thường gặp', to: '/faq' },
]

async function handleSubmit() {
  if (submitting.value) return

  submitting.value = true
  errorMessage.value = ''
  try {
    const recaptchaToken = await executeRecaptcha('contact_submit')
    const response = await submitContact({
      name: form.name,
      email: form.email,
      phone: form.phone,
      subject: form.subject,
      message: form.message,
      recaptchaToken,
    })
    sent.value = true
    form.name = ''
    form.email = ''
    form.phone = ''
    form.subject = contactSubjects[0]
    form.message = ''
    successMessage.value = response?.data?.message || 'Cảm ơn bạn đã gửi liên hệ. Viện sẽ phản hồi sớm nhất có thể.'
  }
  catch (error) {
    errorMessage.value = error instanceof Error
      ? error.message
      : 'Không gửi được liên hệ. Vui lòng thử lại sau.'
  }
  finally {
    submitting.value = false
  }
}

useSeoMeta({ title: 'Liên hệ & hỗ trợ — NIDQC', description: 'Thông tin liên hệ, địa chỉ hai cơ sở và biểu mẫu hỗ trợ của Viện Kiểm nghiệm thuốc Trung ương.', ogTitle: 'Liên hệ & hỗ trợ — NIDQC', ogDescription: 'Thông tin liên hệ, địa chỉ hai cơ sở và biểu mẫu hỗ trợ của Viện Kiểm nghiệm thuốc Trung ương.' })
</script>
<template>
  <div>
    <SectionSubNav :links="lienHeLinks" />
    <PageBand title="Liên hệ & hỗ trợ" description="Mọi ý kiến đóng góp, yêu cầu hỗ trợ hoặc phối hợp công tác, xin liên hệ theo thông tin dưới đây hoặc gửi qua biểu mẫu." />
    <section class="contact-page">
      <div class="contact-page__grid">
        <div class="contact-page__info">
          <div v-html="body" class="contact-page__body"></div>
          <div v-for="(o, i) in offices" :key="i" class="contact-map">
            <div class="contact-map__header">
              <h2>{{ o.t }}</h2>
              <p>{{ o.addr }}</p>
              <p v-if="o.tel">ĐT: {{ o.tel }}</p>
            </div>
            <iframe
              :src="o.map"
              :title="`Bản đồ ${o.t}: ${o.addr}`"
              loading="lazy"
              referrerpolicy="no-referrer-when-downgrade"
              allowfullscreen
            ></iframe>
            <a :href="`https://www.google.com/maps/search/?api=1&query=${encodeURIComponent(o.addr)}`" target="_blank" rel="noopener noreferrer" class="contact-map__link">
              Mở bản đồ
            </a>
          </div>
        </div>

        <div class="contact-form-panel">
          <h2>Gửi liên hệ</h2>
          <p>Viện sẽ phản hồi trong thời gian sớm nhất.</p>

          <div
            v-if="sent"
            class="contact-alert contact-alert--success"
            role="status"
          >
            {{ successMessage }}
          </div>

          <form v-else class="contact-form" @submit.prevent="handleSubmit">
            <div class="contact-form__row">
              <div class="contact-form__field">
                <label for="contact-name">Họ và tên <span aria-hidden="true">*</span></label>
                <input
                  id="contact-name"
                  v-model="form.name"
                  required
                  name="name"
                  autocomplete="name"
                  minlength="2"
                  maxlength="120"
                  placeholder="Nguyễn Văn A"
                >
              </div>
              <div class="contact-form__field">
                <label for="contact-email">Email <span aria-hidden="true">*</span></label>
                <input
                  id="contact-email"
                  v-model="form.email"
                  required
                  name="email"
                  type="email"
                  autocomplete="email"
                  maxlength="254"
                  placeholder="email@example.com"
                >
              </div>
            </div>

            <div class="contact-form__row">
              <div class="contact-form__field">
                <label for="contact-phone">Số điện thoại</label>
                <input
                  id="contact-phone"
                  v-model="form.phone"
                  name="phone"
                  autocomplete="tel"
                  maxlength="40"
                  placeholder="09xx xxx xxx"
                >
              </div>
              <div class="contact-form__field">
                <label for="contact-subject">Chủ đề</label>
                <select id="contact-subject" v-model="form.subject" name="subject">
                  <option v-for="subject in contactSubjects" :key="subject" :value="subject">
                    {{ subject }}
                  </option>
                </select>
              </div>
            </div>

            <div class="contact-form__field">
              <label for="contact-message">Nội dung <span aria-hidden="true">*</span></label>
              <textarea
                id="contact-message"
                v-model="form.message"
                required
                name="message"
                rows="5"
                minlength="10"
                maxlength="4000"
                placeholder="Nội dung yêu cầu..."
              ></textarea>
            </div>

            <div v-if="errorMessage" class="contact-alert contact-alert--error" role="alert">
              {{ errorMessage }}
            </div>

            <noscript>
              <div class="contact-alert contact-alert--error">
                Biểu mẫu cần JavaScript để xác thực reCAPTCHA v3. Vui lòng liên hệ qua email hoặc điện thoại hiển thị trên trang.
              </div>
            </noscript>

            <button type="submit" class="contact-form__submit" :disabled="submitting" :aria-busy="submitting">
              {{ submitting ? 'Đang gửi...' : 'Gửi liên hệ' }}
            </button>
          </form>
        </div>
      </div>
    </section>
  </div>
</template>

<style scoped>
.contact-page {
  background: var(--nidqc-white);
  padding: 34px 0 60px;
}

.contact-page__grid {
  display: grid;
  grid-template-columns: minmax(0, 1fr) minmax(360px, 0.82fr);
  gap: 40px;
  max-width: var(--nidqc-container);
  margin: 0 auto;
  padding: 0 var(--nidqc-gutter);
}

.contact-page__body {
  color: var(--nidqc-text);
  font-size: 15.5px;
  line-height: 25px;
}

.contact-map {
  margin-top: 28px;
  border: 1px solid var(--nidqc-border-strong);
  background: var(--nidqc-white);
}

.contact-map__header {
  padding: 18px 20px;
  border-bottom: 1px solid var(--nidqc-border);
}

.contact-map__header h2,
.contact-form-panel h2 {
  margin: 0;
  color: var(--nidqc-primary);
  font-family: var(--nidqc-font-heading);
  font-size: 18px;
  font-weight: 700;
}

.contact-map__header p,
.contact-form-panel p {
  margin: 7px 0 0;
  color: var(--nidqc-text-muted);
  font-size: 13.5px;
  line-height: 21px;
}

.contact-map iframe {
  display: block;
  width: 100%;
  min-height: 260px;
  border: 0;
}

.contact-map__link {
  display: inline-flex;
  margin: 14px 20px 18px;
  color: var(--nidqc-primary);
  font-size: 14px;
  font-weight: 600;
  text-decoration: none;
}

.contact-map__link:focus-visible,
.contact-map__link:hover {
  text-decoration: underline;
}

.contact-form-panel {
  border: 1px solid var(--nidqc-border-strong);
  background: var(--nidqc-white);
  padding: 30px 34px;
}

.contact-form {
  display: flex;
  flex-direction: column;
  gap: 18px;
  margin-top: 24px;
}

.contact-form__row {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 16px;
}

.contact-form__field {
  display: flex;
  flex-direction: column;
  gap: 7px;
}

.contact-form__field label {
  color: var(--nidqc-text);
  font-size: 13px;
  font-weight: 600;
}

.contact-form__field span {
  color: var(--nidqc-danger);
}

.contact-form__field input,
.contact-form__field select,
.contact-form__field textarea {
  width: 100%;
  border: 1px solid var(--nidqc-border-strong);
  color: var(--nidqc-text-body);
  font: 14px var(--nidqc-font);
  padding: 10px 12px;
  outline: none;
}

.contact-form__field textarea {
  resize: vertical;
}

.contact-form__field input:focus,
.contact-form__field select:focus,
.contact-form__field textarea:focus {
  border-color: var(--nidqc-primary);
  box-shadow: 0 0 0 2px var(--nidqc-primary-pale);
}

.contact-form__submit {
  align-self: flex-start;
  border: none;
  border-radius: 18px;
  background: var(--nidqc-primary);
  color: var(--nidqc-white);
  cursor: pointer;
  font-family: var(--nidqc-font);
  font-size: 14px;
  font-weight: 700;
  padding: 12px 24px;
}

.contact-form__submit:disabled {
  cursor: wait;
  opacity: 0.72;
}

.contact-form__submit:focus-visible,
.contact-form__submit:hover {
  background: var(--nidqc-primary-dark);
}

.contact-alert {
  padding: 16px 20px;
  font-size: 14px;
  line-height: 22px;
}

.contact-alert--success {
  border: 1px solid var(--nidqc-primary-light);
  background: var(--nidqc-primary-pale);
  color: var(--nidqc-primary);
}

.contact-alert--error {
  border: 1px solid var(--nidqc-danger);
  background: var(--nidqc-white);
  color: var(--nidqc-text);
}

@media (max-width: 900px) {
  .contact-page__grid,
  .contact-form__row {
    grid-template-columns: 1fr;
  }

  .contact-form-panel {
    padding: 24px 20px;
  }
}
</style>
