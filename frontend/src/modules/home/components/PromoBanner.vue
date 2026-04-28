<template>
  <section
    v-if="banner"
    class="promo-banner"
    :class="{ 'has-image': Boolean(banner.image) }"
    :style="bannerStyle"
  >
    <div v-if="banner.image" class="promo-image" />
    <div class="promo-content">
      <i v-if="banner.icon" class="fas fa-3x" :class="banner.icon" />
      <h2>{{ banner.title || '¡Oferta 3x2!' }}</h2>
      <p>{{ banner.subtitle || banner.message || 'Válido por tiempo limitado.' }}</p>
      <a class="btn" :href="buttonLink">
        {{ banner.button_text || 'Aprovechar oferta' }}
      </a>
    </div>
  </section>
</template>

<script setup>
import { computed } from 'vue'
import { getFallbackMediaUrl, resolveMediaUrl } from '../../../utils/media'

const props = defineProps({
  banner: {
    type: Object,
    default: null,
  },
})

const bannerStyle = computed(() => {
  const style = {}

  // Aplica colores dinámicos del anuncio
  if (props.banner?.background_color) style.backgroundColor = props.banner.background_color
  if (props.banner?.text_color) style.color = props.banner.text_color

  if (props.banner?.image) {
    const primaryImage = resolveMediaUrl(props.banner.image, 'banner')
    const fallbackImage = getFallbackMediaUrl('banner')
    style.backgroundImage = `url('${primaryImage}'), url('${fallbackImage}')`
  }

  return style
})

const buttonLink = computed(() => {
  const link = String(props.banner?.button_link || '').trim()
  if (!link) return '/tienda'
  if (link.includes('/tienda/tienda.php')) {
    return link.replace('/tienda/tienda.php', '/tienda')
  }
  return link
})
</script>
