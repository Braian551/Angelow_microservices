<template>
  <Teleport to="body">
    <div v-if="alertState.visible" class="alert-overlay active" @click.self="closeAlert()">
      <div class="alert-box" :class="alertState.type">
        <button type="button" class="alert-close" aria-label="Cerrar alerta" @click="closeAlert()">
          <i class="fas fa-times" />
        </button>

        <div class="alert-icon-container">
          <i class="alert-icon" :class="iconClass" />
        </div>

        <h3 class="alert-title">{{ alertState.title }}</h3>
        <p class="alert-message">{{ alertState.message }}</p>

        <div v-if="alertState.countdown > 0" class="alert-countdown">
          Cierre automático en <strong>{{ alertState.countdown }}s</strong>
        </div>

        <div class="alert-buttons">
          <button
            v-for="(action, index) in alertState.actions"
            :key="`${action.text}-${index}`"
            type="button"
            class="alert-button"
            :class="actionClass(action.style)"
            @click="handleAction(action)"
          >
            {{ action.text }}
          </button>
        </div>
      </div>
    </div>
  </Teleport>
</template>

<script setup>
import { computed } from 'vue'
import { useAlertSystem } from '../../composables/useAlertSystem'
import './UserAlertSystem.css'

const { alertState, closeAlert } = useAlertSystem()

const iconClass = computed(() => {
  const iconByType = {
    success: 'fas fa-check-circle',
    error: 'fas fa-times-circle',
    warning: 'fas fa-exclamation-triangle',
    question: 'fas fa-question-circle',
    info: 'fas fa-info-circle',
  }

  return iconByType[alertState.type] || iconByType.info
})

function actionClass(style) {
  if (style === 'secondary') return 'outline'
  if (style === 'danger') return 'danger'
  return 'primary'
}

function handleAction(action) {
  if (typeof action?.callback === 'function') {
    action.callback()
  }

  if (action?.closeOnClick !== false) {
    closeAlert()
  }
}
</script>
