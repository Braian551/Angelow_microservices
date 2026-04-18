<template>
  <span class="admin-info-tooltip" @mouseenter="showTooltip" @mouseleave="hideTooltip">
    <i class="fas fa-question-circle admin-info-tooltip__icon" aria-hidden="true"></i>
    <!-- Teleport al body para evitar clipping de modales con overflow:auto -->
    <Teleport to="body">
      <span
        v-if="visible"
        class="admin-info-tooltip__text"
        :style="tipStyle"
        role="tooltip"
      >{{ text }}</span>
    </Teleport>
  </span>
</template>

<script setup>
import { ref } from 'vue'

defineProps({
  text: {
    type: String,
    required: true,
  },
})

const visible = ref(false)
const tipStyle = ref({})

const TOOLTIP_WIDTH = 240
const MARGIN = 12

function showTooltip(event) {
  const rect = event.currentTarget.getBoundingClientRect()

  // Posición horizontal: alinear con icono, evitar desborde del viewport
  let left = rect.left
  if (left + TOOLTIP_WIDTH > window.innerWidth - MARGIN) {
    left = window.innerWidth - TOOLTIP_WIDTH - MARGIN
  }
  if (left < MARGIN) {
    left = MARGIN
  }

  tipStyle.value = {
    position: 'fixed',
    bottom: `${window.innerHeight - rect.top + 8}px`,
    left: `${left}px`,
    zIndex: 9999,
  }
  visible.value = true
}

function hideTooltip() {
  visible.value = false
}
</script>
