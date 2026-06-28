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

/**
 * Shimmer de tabla reutilizable del admin.
 * Simula la estructura de una tabla con filas y columnas de carga
 * mientras se obtienen los datos reales del servidor.
 * Cada columna puede ser un string (tipo shimmer simple) o un objeto
 * con tipo, ancho y alto personalizados para mayor precisión visual.
 * Reutiliza AdminShimmer como bloque base de cada celda.
 */
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
