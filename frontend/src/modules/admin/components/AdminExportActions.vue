<template>
  <div class="admin-export-actions" :class="`admin-export-actions--${tone}`">
    <button
      v-if="showExcel"
      type="button"
      :class="buttonClass"
      :disabled="disabled || excelDisabled || excelLoading"
      @click="$emit('excel')"
    >
      <template v-if="usesResultsTone">
        <span class="results-action-btn__icon"><i :class="excelIconClass"></i></span>
        <span>{{ excelLabel }}</span>
      </template>
      <template v-else>
        <i :class="excelIconClass"></i>
        {{ excelLabel }}
      </template>
    </button>

    <button
      v-if="showPdf"
      type="button"
      :class="buttonClass"
      :disabled="disabled || pdfDisabled || pdfLoading"
      @click="$emit('pdf')"
    >
      <template v-if="usesResultsTone">
        <span class="results-action-btn__icon"><i :class="pdfIconClass"></i></span>
        <span>{{ pdfLabel }}</span>
      </template>
      <template v-else>
        <i :class="pdfIconClass"></i>
        {{ pdfLabel }}
      </template>
    </button>
  </div>
</template>

<script setup>
import { computed } from 'vue'

/**
 * Botones de exportación reutilizables del admin (Excel y PDF).
 * Soporta dos tonos visuales: 'header' (estilo btn-secondary)
 * y 'results' (estilo results-action-btn de la barra de resultados).
 * Incluye estados de carga individuales con spinner por botón
 * y bloqueo de doble envío mientras la exportación está en curso.
 * Patrón: Strategy — cambia la presentación según el tono configurado.
 */
const props = defineProps({
  tone: {
    type: String,
    default: 'results',
  },
  disabled: {
    type: Boolean,
    default: false,
  },
  showExcel: {
    type: Boolean,
    default: true,
  },
  showPdf: {
    type: Boolean,
    default: true,
  },
  excelDisabled: {
    type: Boolean,
    default: false,
  },
  pdfDisabled: {
    type: Boolean,
    default: false,
  },
  excelLoading: {
    type: Boolean,
    default: false,
  },
  pdfLoading: {
    type: Boolean,
    default: false,
  },
  excelLabel: {
    type: String,
    default: 'Exportar Excel',
  },
  pdfLabel: {
    type: String,
    default: 'Exportar PDF',
  },
})

defineEmits(['excel', 'pdf'])

// Reutiliza los dos sistemas de botones ya existentes: header (`btn`) y barra de resultados (`results-action-btn`).
const usesResultsTone = computed(() => props.tone !== 'header')

// Mantiene la identidad visual existente y evita crear otro estilo ad hoc de exportación.
const buttonClass = computed(() => (
  usesResultsTone.value
    ? 'results-action-btn results-action-btn--neutral'
    : 'btn btn-secondary'
))

// Muestra spinner cuando la exportación en Excel está en progreso.
const excelIconClass = computed(() => (
  props.excelLoading ? 'fas fa-spinner fa-spin' : 'fas fa-file-excel'
))

// Muestra spinner cuando la exportación en PDF está en progreso.
const pdfIconClass = computed(() => (
  props.pdfLoading ? 'fas fa-spinner fa-spin' : 'fas fa-file-pdf'
))
</script>

<style scoped>
.admin-export-actions {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  flex-wrap: wrap;
}

.admin-export-actions--results {
  justify-content: flex-end;
}

.admin-export-actions--header {
  justify-content: flex-start;
}

.admin-export-actions .btn,
.admin-export-actions .results-action-btn {
  white-space: nowrap;
}

@media (max-width: 640px) {
  .admin-export-actions {
    width: 100%;
  }
}
</style>