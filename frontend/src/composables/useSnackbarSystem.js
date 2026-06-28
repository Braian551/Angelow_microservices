// Importaciones de Vue: reactive crea un objeto reactivo y readonly lo protege contra modificaciones externas
import { reactive, readonly } from 'vue'

// Duración por defecto en milisegundos que el snackbar permanece visible antes de ocultarse automáticamente
const DEFAULT_DURATION_MS = 2800

// Estado reactivo global que contiene toda la información del snackbar actual
// Se usa un único objeto para centralizar el estado y facilitar la reactividad
const state = reactive({
  visible: false,   // Controla si el snackbar está visible en pantalla
  type: 'info',    // Tipo de snackbar: 'info', 'success', 'warning', 'error', etc.
  title: '',        // Título opcional que se muestra en el snackbar
  message: '',      // Mensaje principal del snackbar (requerido)
  durationMs: DEFAULT_DURATION_MS, // Tiempo que permanece abierto antes de cerrarse solo
})

// Variable fuera de Reactividad para almacenar el identificador del temporizador de autocierre
// Se mantiene fuera del state porque no necesita ser reactiva (solo se usa internamente)
let hideTimer = null

// Función auxiliar que limpia cualquier temporizador pendiente
// Esto previene que múltiples snackbar se programen simultáneamente y causen comportamientos inesperados
function clearHideTimer() {
  if (hideTimer) {
    clearTimeout(hideTimer) // Cancela el temporizador activo si existe
    hideTimer = null        // Reinicia la referencia a null
  }
}

// Función que cierra el snackbar de forma manual e inmediata
// Se utiliza cuando el usuario cierra el snackbar manualmente o al mostrar uno nuevo
function closeSnackbar() {
  clearHideTimer()          // Primero limpia el temporizador para evitar cierres automáticos
  state.visible = false     // Oculta el snackbar estableciendo visible en false
}

// Función principal que muestra el snackbar con opciones personalizadas
// Acepta un string simple (mensaje) o un objeto con opciones detalladas
function showSnackbar(options = {}) {
  clearHideTimer() // Limpia cualquier temporizador anterior al mostrar uno nuevo

  // Si se pasa un string simple, se configura como mensaje de tipo 'info' con duración por defecto
  if (typeof options === 'string') {
    state.type = 'info'            // Tipo por defecto para mensajes simples
    state.title = ''               // Sin título cuando se pasa solo un string
    state.message = options        // El string se usa directamente como mensaje
    state.durationMs = DEFAULT_DURATION_MS
  } else {
    // Si se pasa un objeto, extrae cada propiedad con valores por defecto
    // Se usa optional chaining (?.) para evitar errores si las propiedades no existen
    state.type = options?.type || 'info'      // Tipo con fallback a 'info'
    state.title = options?.title || ''         // Título opcional, vacío por defecto
    state.message = options?.message || ''     // Mensaje, vacío si no se proporciona

    // Valida la duración: debe ser un número finito y mayor que 0
    // Si no cumple, usa el valor por defecto
    const parsedDuration = Number(options?.durationMs)
    state.durationMs = Number.isFinite(parsedDuration) && parsedDuration > 0
      ? parsedDuration
      : DEFAULT_DURATION_MS
  }

  state.visible = true // Muestra el snackbar en la interfaz

  // Programa el autocierre después de la duración especificada
  // Cuando se cumple el tiempo, se ejecuta un callback que oculta el snackbar
  hideTimer = setTimeout(() => {
    state.visible = false
  }, state.durationMs)
}

// Composable que exporta la funcionalidad del sistema de snackbar
// Se usa como función para permitir que múltiples componentes accedan al mismo estado global
// Retorna el estado de solo lectura (protegido) y las funciones para controlar el snackbar
export function useSnackbarSystem() {
  return {
    snackbarState: readonly(state), // Estado protegido: los componentes pueden leer pero no modificar directamente
    showSnackbar,                   // Función para mostrar el snackbar con opciones
    closeSnackbar,                  // Función para cerrar el snackbar manualmente
  }
}
