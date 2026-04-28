<!-- AdminResultsBar.vue
     Barra informativa de resultados con conteo y acciones.
     Patrón: Decorator — envuelve información con slot de acciones. -->
<template>
  <div class="admin-results-bar">
    <div class="admin-results-bar__summary">
      <span class="admin-results-bar__icon">
        <i class="fas fa-clipboard-list"></i>
      </span>
      <p>{{ text }}</p>
    </div>
    <div v-if="$slots.actions" class="admin-results-bar__actions">
      <slot name="actions"></slot>
    </div>
  </div>
</template>

<script setup>
defineProps({
  /** Texto descriptivo, ej: "Mostrando 14 clientes" */
  text: { type: String, required: true },
})
</script>
