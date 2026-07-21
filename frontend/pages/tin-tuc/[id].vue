<script setup>
// Tương thích ngược: URL cũ /tin-tuc/<nid> hoặc /tin-tuc/<slug> -> 301 sang alias gốc /<slug>.
const route = useRoute()
const raw = String(route.params.id)

let target = null
if (/^\d+$/.test(raw)) {
  // nid -> tra alias gốc của node.
  const { data } = await useAsyncData(`redir-news-${raw}`, async () => {
    const { data } = await fetchJsonApi('/node/news', {
      'filter[drupal_internal__nid]': Number(raw), 'page[limit]': 1,
    })
    return data[0]?.attributes?.path?.alias || null
  })
  target = data.value
}
else {
  // /tin-tuc/<slug> (cũ) -> /<slug> (mới, cấp gốc).
  target = `/${raw}`
}

await navigateTo(target || '/tin-tuc', { redirectCode: 301, replace: true })
</script>

<template>
  <div />
</template>
