<template>
  <figure class="admin-table-media" :class="`admin-table-media--${variant}`">
    <img
      class="admin-table-media__image"
      :src="resolvedSrc"
      :alt="alt"
      @error="onImageError"
    >
  </figure>
</template>

<script setup>
import { computed } from 'vue'
import { getFallbackMediaUrl, handleMediaError } from '../../../utils/media'

const props = defineProps({
  src: {
    type: String,
    default: '',
  },
  alt: {
    type: String,
    default: 'Imagen',
  },
  originalPath: {
    type: String,
    default: '',
  },
  fallbackType: {
    type: String,
    default: 'product',
  },
  variant: {
    type: String,
    default: 'landscape',
  },
})

const resolvedSrc = computed(() => props.src || getFallbackMediaUrl(props.fallbackType))

function onImageError(event) {
  handleMediaError(event, props.originalPath || props.src, props.fallbackType)
}
</script>