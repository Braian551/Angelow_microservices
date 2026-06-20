<template>
  <Teleport to="body">
    <div v-if="alertState.visible" class="alert-overlay active" @click.self="requestCloseAlert">
      <div class="alert-box" :class="alertState.type">
        <button type="button" class="alert-close" aria-label="Cerrar alerta" :disabled="Boolean(activeActionKey)" @click="requestCloseAlert">
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
            :class="[actionClass(action.style), { 'is-loading': activeActionKey === actionKey(action, index) }]"
            :disabled="Boolean(activeActionKey)"
            @click="handleAction(action, index)"
          >
            <i v-if="activeActionKey === actionKey(action, index)" class="fas fa-spinner fa-spin" aria-hidden="true"></i>
            <span>{{ action.text }}</span>
          </button>
        </div>
      </div>
    </div>
  </Teleport>
</template>

<script setup>
import { computed, ref } from 'vue'
import { useAlertSystem } from '../../composables/useAlertSystem'
import './UserAlertSystem.css'

const { alertState, closeAlert } = useAlertSystem()
const activeActionKey = ref('')

function requestCloseAlert() {
  if (activeActionKey.value) return
  closeAlert()
}

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

function actionKey(action, index) {
  return `${action?.text || 'accion'}-${index}`
}

async function handleAction(action, index) {
  if (activeActionKey.value) return

  activeActionKey.value = actionKey(action, index)

  try {
    if (typeof action?.callback === 'function') {
      await action.callback()
    }

    if (action?.closeOnClick !== false) {
      closeAlert()
    }
  } finally {
    activeActionKey.value = ''
  }
}
</script>
