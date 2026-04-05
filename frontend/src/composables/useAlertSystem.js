import { reactive, readonly } from 'vue'

const state = reactive({
  visible: false,
  type: 'info',
  title: '',
  message: '',
  actions: [],
  countdown: 0,
  onClose: null,
})

let countdownInterval = null

function clearCountdown() {
  if (countdownInterval) {
    clearInterval(countdownInterval)
    countdownInterval = null
  }
  state.countdown = 0
}

function closeAlert(isAutoClose = false) {
  clearCountdown()
  state.visible = false

  if (!isAutoClose && typeof state.onClose === 'function') {
    const callback = state.onClose
    state.onClose = null
    callback()
  }
}

function normalizeActions(actions = []) {
  if (!Array.isArray(actions) || actions.length === 0) {
    return [
      {
        text: 'Entendido',
        style: 'primary',
      },
    ]
  }

  return actions.map((action) => ({
    text: action?.text || 'Aceptar',
    style: action?.style || 'secondary',
    closeOnClick: action?.closeOnClick !== false,
    callback: typeof action?.callback === 'function' ? action.callback : null,
  }))
}

function showAlert(options = {}) {
  clearCountdown()

  state.type = options?.type || 'info'
  state.title = options?.title || 'Aviso'
  state.message = options?.message || ''
  state.actions = normalizeActions(options?.actions)
  state.onClose = typeof options?.onClose === 'function' ? options.onClose : null
  state.visible = true

  const autoCloseSeconds = Number(options?.autoCloseSeconds || 0)
  if (autoCloseSeconds > 0) {
    state.countdown = autoCloseSeconds
    countdownInterval = setInterval(() => {
      if (state.countdown <= 1) {
        closeAlert(true)
        return
      }
      state.countdown -= 1
    }, 1000)
  }
}

export function useAlertSystem() {
  return {
    alertState: readonly(state),
    showAlert,
    closeAlert,
  }
}