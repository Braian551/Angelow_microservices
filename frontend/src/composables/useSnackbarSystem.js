import { reactive, readonly } from 'vue'

const DEFAULT_DURATION_MS = 2800

const state = reactive({
  visible: false,
  type: 'info',
  title: '',
  message: '',
  durationMs: DEFAULT_DURATION_MS,
})

let hideTimer = null

function clearHideTimer() {
  if (hideTimer) {
    clearTimeout(hideTimer)
    hideTimer = null
  }
}

function closeSnackbar() {
  clearHideTimer()
  state.visible = false
}

function showSnackbar(options = {}) {
  clearHideTimer()

  if (typeof options === 'string') {
    state.type = 'info'
    state.title = ''
    state.message = options
    state.durationMs = DEFAULT_DURATION_MS
  } else {
    state.type = options?.type || 'info'
    state.title = options?.title || ''
    state.message = options?.message || ''

    const parsedDuration = Number(options?.durationMs)
    state.durationMs = Number.isFinite(parsedDuration) && parsedDuration > 0
      ? parsedDuration
      : DEFAULT_DURATION_MS
  }

  state.visible = true

  hideTimer = setTimeout(() => {
    state.visible = false
  }, state.durationMs)
}

export function useSnackbarSystem() {
  return {
    snackbarState: readonly(state),
    showSnackbar,
    closeSnackbar,
  }
}
