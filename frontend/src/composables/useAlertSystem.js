// Importación de funciones reactivas de Vue 3:
// - reactive: crea un objeto reactivo que actualiza la vista cuando cambia
// - readonly: crea una versión de solo lectura para evitar modificaciones externas
import { reactive, readonly } from 'vue'

// Estado reactivo global del sistema de alertas.
// Contiene toda la información necesaria para mostrar y controlar una alerta.
const state = reactive({
  visible: false,       // Controla si la alerta está visible en pantalla
  type: 'info',         // Tipo de alerta: 'info', 'success', 'warning', 'error'
  title: '',            // Título de la alerta
  message: '',          // Mensaje descriptivo de la alerta
  actions: [],          // Array de botones/acciones disponibles en la alerta
  countdown: 0,         // Contador regressivo para auto-cierre (en segundos)
  onClose: null,        // Callback ejecutado cuando se cierra la alerta manualmente
})

// Variable para almacenar el identificador del intervalo de tiempo.
// Se usa para poder cancelar el countdown si es necesario.
let countdownInterval = null

// Función que limpia el intervalo de tiempo del countdown.
// Resetea el contador a 0 y cancela cualquier temporizador activo.
// Esto evita múltiples intervalos simultáneos y errores de memoria.
function clearCountdown() {
  if (countdownInterval) {
    clearInterval(countdownInterval)
    countdownInterval = null
  }
  state.countdown = 0
}

// Función para cerrar la alerta actual.
// Parámetro isAutoClose indica si el cierre fue automático (por timeout)
// o manual (por interacción del usuario). Solo ejecuta el callback onClose
// cuando el cierre es manual, para evitar ejecuciones no deseadas.
function closeAlert(isAutoClose = false) {
  clearCountdown()
  state.visible = false

  // Si no es auto-cierre y existe un callback onClose, lo ejecuta
  // y lo limpia para evitar ejecuciones múltiples
  if (!isAutoClose && typeof state.onClose === 'function') {
    const callback = state.onClose
    state.onClose = null
    callback()
  }
}

// Función que normaliza el array de acciones de la alerta.
// Asegura que cada acción tenga las propiedades necesarias con valores por defecto.
// Si no se proporcionan acciones o el array está vacío, retorna una acción por defecto.
function normalizeActions(actions = []) {
  if (!Array.isArray(actions) || actions.length === 0) {
    // Acción por defecto cuando no se especifican acciones
    return [
      {
        text: 'Entendido',
        style: 'primary',
      },
    ]
  }

  // Mapea cada acción asegurando que tenga todas las propiedades requeridas
  return actions.map((action) => ({
    text: action?.text || 'Aceptar',           // Texto del botón
    style: action?.style || 'secondary',        // Estilo visual del botón
    closeOnClick: action?.closeOnClick !== false, // Si cierra la alerta al hacer clic (true por defecto)
    callback: typeof action?.callback === 'function' ? action.callback : null, // Función a ejecutar
  }))
}

// Función principal para mostrar una alerta.
// Configura el estado con las opciones proporcionadas y activa la visibilidad.
// Soporta auto-cierre con countdown regresivo.
function showAlert(options = {}) {
  // Limpia cualquier countdown anterior para evitar conflictos
  clearCountdown()

  // Configura el estado con valores por defecto seguros
  state.type = options?.type || 'info'
  state.title = options?.title || 'Aviso'
  state.message = options?.message || ''
  state.actions = normalizeActions(options?.actions)
  state.onClose = typeof options?.onClose === 'function' ? options.onClose : null
  state.visible = true

  // Configura el auto-cierre si se especifica un tiempo en segundos
  const autoCloseSeconds = Number(options?.autoCloseSeconds || 0)
  if (autoCloseSeconds > 0) {
    state.countdown = autoCloseSeconds
    // Inicia un intervalo que reduce el contador cada segundo
    countdownInterval = setInterval(() => {
      if (state.countdown <= 1) {
        // Cuando el contador llega a 1, cierra la alerta automáticamente
        closeAlert(true)
        return
      }
      state.countdown -= 1
    }, 1000)
  }
}

// Composable exportado que proporciona el sistema de alertas.
// Retorna el estado de solo lectura y las funciones para controlar las alertas.
// El estado es readonly para evitar modificaciones externas directas.
export function useAlertSystem() {
  return {
    alertState: readonly(state),  // Estado reactivo de solo lectura
    showAlert,                     // Función para mostrar alertas
    closeAlert,                    // Función para cerrar alertas
  }
}
