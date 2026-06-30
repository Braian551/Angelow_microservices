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

/**
 * Componente de imagen para tablas del admin.
 * Resuelve la URL de la imagen o usa un fallback por tipo (producto, avatar, etc.)
 * cuando la imagen original no está disponible. Reutiliza las utilidades compartidas
 * de media (resolveMediaUrl, handleMediaError) para mantener consistencia.
 */
const props = defineProps({
  /** URL de la imagen a mostrar. */
  src: {
    type: String,
    default: '',
  },
  /** Texto alternativo de la imagen para accesibilidad. */
  alt: {
    type: String,
    default: 'Imagen',
  },
  /** Ruta original del archivo en el servidor para resolver fallbacks. */
  originalPath: {
    type: String,
    default: '',
  },
  /** Tipo de imagen para seleccionar el fallback adecuado (product, avatar, brand). */
  fallbackType: {
    type: String,
    default: 'product',
  },
  /** Variante visual del contenedor (landscape, square, etc.). */
  variant: {
    type: String,
    default: 'landscape',
  },
})

/** URL resuelta: usa src si existe, de lo contrario genera la URL del fallback. */
const resolvedSrc = computed(() => props.src || getFallbackMediaUrl(props.fallbackType))

/**
 * Maneja el error de carga de imagen reemplazándola por el fallback
 * correspondiente al tipo, usando la utilidad compartida handleMediaError.
 */
function onImageError(event) {
  handleMediaError(event, props.originalPath || props.src, props.fallbackType)
}
</script>