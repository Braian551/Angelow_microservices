<template>
  <div class="shimmer-table-wrap">
    <div v-for="r in rows" :key="r" class="shimmer-table-row">
      <div v-for="c in columns" :key="c.key || c" class="shimmer-table-cell">
        <AdminShimmer v-if="typeof c === 'string'" :type="c" />
        <AdminShimmer v-else :type="c.type || 'line'" :width="c.width" :height="c.height" />
      </div>
    </div>
  </div>
</template>

<script setup>
import AdminShimmer from './AdminShimmer.vue'

defineProps({
  /** Numero de filas simuladas */
  rows: { type: Number, default: 5 },
  /**
   * Columnas: array de strings (tipo shimmer) u objetos { type, width, height }.
   * Ejemplo: ['thumb', 'line', 'line', 'pill', 'btn']
   */
  columns: {
    type: Array,
    default: () => ['line', 'line', 'line', 'pill'],
  },
})
</script>
