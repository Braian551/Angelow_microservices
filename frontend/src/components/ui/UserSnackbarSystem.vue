<template>
  <Teleport to="body">
    <Transition name="snackbar-slide">
      <div v-if="snackbarState.visible" class="snackbar" :class="`snackbar-${snackbarState.type}`" role="status" aria-live="polite">
        <div class="snackbar-icon">
          <i :class="iconClass" />
        </div>

        <div class="snackbar-content">
          <strong v-if="snackbarState.title" class="snackbar-title">{{ snackbarState.title }}</strong>
          <p class="snackbar-message">{{ snackbarState.message }}</p>
        </div>

        <button type="button" class="snackbar-close" aria-label="Cerrar notificacion" @click="closeSnackbar">
          <i class="fas fa-times" />
        </button>
      </div>
    </Transition>
  </Teleport>
</template>

<script setup>
import { computed } from 'vue'
import { useSnackbarSystem } from '../../composables/useSnackbarSystem'
import './UserSnackbarSystem.css'

const { snackbarState, closeSnackbar } = useSnackbarSystem()

const iconClass = computed(() => {
  const iconByType = {
    success: 'fas fa-check-circle',
    error: 'fas fa-times-circle',
    warning: 'fas fa-exclamation-triangle',
    info: 'fas fa-info-circle',
  }

  return iconByType[snackbarState.type] || iconByType.info
})
</script>
