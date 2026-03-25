<template>
  <section
    v-if="banner"
    class="promo-banner"
    :class="{ 'has-image': Boolean(banner.image) }"
    :style="bannerStyle"
  >
    <div v-if="banner.image" class="promo-image-overlay" />
    <div class="promo-content">
      <i v-if="banner.icon" class="fas" :class="banner.icon" />
      <h2>{{ banner.title || 'Promocion especial' }}</h2>
      <p>{{ banner.subtitle || banner.message }}</p>
      <RouterLink class="btn" :to="{ name: 'store' }">
        {{ banner.button_text || 'Ver promociones' }}
      </RouterLink>
    </div>
  </section>
</template>

<script setup>
import { computed } from 'vue'
import { RouterLink } from 'vue-router'
import { resolveMediaUrl } from '../../../utils/media'

const props = defineProps({
  banner: {
    type: Object,
    default: null,
  },
})

const bannerStyle = computed(() => {
  if (!props.banner?.image) return {}
  return {
    backgroundImage: `url('${resolveMediaUrl(props.banner.image, '/logo_principal.png')}')`,
  }
})
</script>
