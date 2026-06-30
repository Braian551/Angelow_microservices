<template>
  <div class="admin-chart-panel" :style="panelStyle">
    <canvas v-if="hasData" ref="chartCanvasRef"></canvas>
    <AdminEmptyState
      v-else
      :icon="emptyIcon"
      :title="emptyTitle"
      :description="emptyDescription"
      class="admin-chart-panel__empty"
    />
  </div>
</template>

<script setup>
import {
  ArcElement,
  BarController,
  BarElement,
  CategoryScale,
  Chart,
  DoughnutController,
  Legend,
  LinearScale,
  Tooltip,
} from 'chart.js'
import { computed, nextTick, onBeforeUnmount, onMounted, ref, watch } from 'vue'
import AdminEmptyState from './AdminEmptyState.vue'

/**
 * Panel de gráfico reutilizable del admin basado en Chart.js.
 * Soporta gráficos de barras y doughnut con opciones personalizables.
 * Muestra AdminEmptyState cuando no hay datos suficientes para graficar.
 * Destruye y recrea la instancia de Chart.js ante cambios de datos
 * para evitar fugas de memoria y mantener el canvas sincronizado.
 * Patrón: Observer — reacciona a cambios profundos de datasets y opciones.
 */

/** Registra los controladores y elementos de Chart.js necesarios. */
Chart.register(
  ArcElement,
  BarController,
  BarElement,
  CategoryScale,
  DoughnutController,
  LinearScale,
  Tooltip,
  Legend,
)

const props = defineProps({
  /** Tipo de gráfico reutilizable del admin. */
  type: { type: String, default: 'bar' },
  /** Etiquetas visibles del gráfico. */
  labels: { type: Array, default: () => [] },
  /** Datasets completos compatibles con Chart.js. */
  datasets: { type: Array, default: () => [] },
  /** Opciones adicionales del gráfico. */
  options: { type: Object, default: () => ({}) },
  /** Indica si hay datos suficientes para renderizar el canvas. */
  hasData: { type: Boolean, default: false },
  /** Icono del estado vacío reutilizable. */
  emptyIcon: { type: String, default: 'fas fa-chart-column' },
  /** Título del estado vacío reutilizable. */
  emptyTitle: { type: String, default: 'Sin datos para graficar' },
  /** Descripción del estado vacío reutilizable. */
  emptyDescription: { type: String, default: 'Todavía no hay datos suficientes para este gráfico.' },
  /** Altura mínima del panel para estabilizar la UI en todos los breakpoints. */
  height: { type: Number, default: 260 },
  /** Limita el ancho del canvas cuando un gráfico circular necesita una lectura más compacta. */
  maxWidth: { type: Number, default: 0 },
  /** Centra el gráfico dentro de la tarjeta cuando se aplica un ancho máximo. */
  centered: { type: Boolean, default: false },
})

const chartCanvasRef = ref(null)
const panelStyle = computed(() => ({
  minHeight: `${props.height}px`,
  maxWidth: props.maxWidth > 0 ? `${props.maxWidth}px` : undefined,
  marginInline: props.centered ? 'auto' : undefined,
}))

let chartInstance = null

// Destruye la instancia activa para reciclar el canvas sin fugas cuando cambian datos o tipo.
function destroyChart() {
  if (chartInstance) {
    chartInstance.destroy()
    chartInstance = null
  }
}

// Reutiliza el lifecycle del dashboard/reportes para repintar el gráfico solo cuando existe data operativa.
async function renderChart() {
  if (!props.hasData) {
    destroyChart()
    return
  }

  await nextTick()
  if (!chartCanvasRef.value) {
    return
  }

  destroyChart()

  chartInstance = new Chart(chartCanvasRef.value, {
    type: props.type,
    data: {
      labels: props.labels,
      datasets: props.datasets,
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      animation: {
        duration: 320,
      },
      ...props.options,
    },
  })
}

onMounted(renderChart)

// Reacciona a cambios profundos de datasets/opciones para mantener el canvas sincronizado sin duplicar lógica por vista.
watch(
  () => ({
    type: props.type,
    hasData: props.hasData,
    labels: props.labels,
    datasets: props.datasets,
    options: props.options,
  }),
  renderChart,
  { deep: true },
)

onBeforeUnmount(destroyChart)
</script>

<style scoped>
.admin-chart-panel {
  position: relative;
  width: 100%;
}

.admin-chart-panel :deep(canvas) {
  width: 100% !important;
  height: 100% !important;
}

.admin-chart-panel__empty {
  min-height: inherit;
}
</style>