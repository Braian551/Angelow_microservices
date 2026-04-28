<template>
  <div v-if="displayMessage" class="announcement-bar" :style="barStyle">
    <p>
      <i v-if="announcement.icon" class="fas" :class="announcement.icon" />
      {{ displayMessage }}
    </p>
  </div>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  announcement: {
    type: Object,
    default: null,
  },
})

const displayMessage = computed(() => {
  const rawMessage = props.announcement?.message ?? props.announcement?.title ?? ''
  const text = String(rawMessage || '').trim()
  return text || ''
})

// Aplica colores dinámicos guardados en el anuncio (sobreescribe el color fijo del CSS)
const barStyle = computed(() => {
  const style = {}
  if (props.announcement?.background_color) style.backgroundColor = props.announcement.background_color
  if (props.announcement?.text_color) style.color = props.announcement.text_color
  return style
})
</script>
