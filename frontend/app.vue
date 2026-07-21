<script setup>
const config = useRuntimeConfig()
const site = config.public.drupalBase || 'https://nidqc.gov.vn'
const route = useRoute()

// SEO/GEO mặc định toàn site — mỗi trang có thể ghi đè bằng useSeoMeta.
useSeoMeta({
  ogSiteName: 'Viện Kiểm nghiệm thuốc Trung ương',
  ogType: 'website',
  ogLocale: 'vi_VN',
  description: 'Viện Kiểm nghiệm thuốc Trung ương (NIDQC) — cơ quan khoa học kỹ thuật đầu ngành về kiểm tra, giám sát chất lượng thuốc, nguyên liệu làm thuốc và mỹ phẩm trên phạm vi cả nước.',
  twitterCard: 'summary_large_image',
})

// JSON-LD structured data — quan trọng cho GEO (crawler AI/LLM) và rich results.
useHead({
  htmlAttrs: { lang: 'vi' },
  link: [{ rel: 'canonical', href: () => site + route.path }],
  script: [
    {
      type: 'application/ld+json',
      innerHTML: JSON.stringify({
        '@context': 'https://schema.org',
        '@type': 'GovernmentOrganization',
        name: 'Viện Kiểm nghiệm thuốc Trung ương',
        alternateName: 'National Institute of Drug Quality Control (NIDQC)',
        url: site,
        description: 'Cơ quan khoa học kỹ thuật đầu ngành về kiểm tra, giám sát chất lượng thuốc, nguyên liệu làm thuốc và mỹ phẩm.',
        address: {
          '@type': 'PostalAddress',
          streetAddress: '48 Hai Bà Trưng, Tràng Tiền, Hoàn Kiếm',
          addressLocality: 'Hà Nội',
          addressCountry: 'VN',
        },
        telephone: '(024) 3825 5075',
        parentOrganization: { '@type': 'GovernmentOrganization', name: 'Bộ Y tế' },
      }),
    },
    {
      type: 'application/ld+json',
      innerHTML: JSON.stringify({
        '@context': 'https://schema.org',
        '@type': 'WebSite',
        name: 'Viện Kiểm nghiệm thuốc Trung ương',
        url: site,
        inLanguage: 'vi',
      }),
    },
  ],
})
</script>

<template>
  <NuxtLayout>
    <NuxtPage />
  </NuxtLayout>
  <AppLoading />
</template>
