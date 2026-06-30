<template>
  <div class="admin-shimmer" :class="variantClass" :style="customStyle">
    <slot />
  </div>
</template>

<script setup>
import { computed } from 'vue'

/**
 * Componente shimmer reutilizable del admin para estados de carga.
 * Renderiza placeholders animados que simulan la estructura del contenido
 * mientras se cargan datos reales. Soporta variantes: line, circle, rect,
 * card, thumb, pill y btn, con dimensiones y radios personalizables.
 * Reutilizado por AdminStatsGrid, AdminSidebar, AdminTableShimmer, entre otros.
 */
const props = defineProps({
  /** Tipo: line | circle | rect | card | thumb | pill | btn */
  type: { type: String, default: 'line' },
  /** Ancho personalizado (CSS value) */
  width: { type: String, default: null },
  /** Alto personalizado (CSS value) */
  height: { type: String, default: null },
  /** Radio de borde personalizado (CSS value) */
  radius: { type: String, default: null },
})

const variantClass = computed(() => `shimmer-${props.type}`)

const customStyle = computed(() => {
  const s = {}
  if (props.width) s.width = props.width
  if (props.height) s.height = props.height
  if (props.radius) s.borderRadius = props.radius
  return s
})
</script>
