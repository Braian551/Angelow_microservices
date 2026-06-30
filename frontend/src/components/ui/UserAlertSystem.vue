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
// Importación de funciones reactivas de Vue: computed para propiedades derivadas y ref para referencias reactivas
import { computed, ref } from 'vue'
// Importación del composable que gestiona el estado global del sistema de alertas
import { useAlertSystem } from '../../composables/useAlertSystem'
// Importación de estilos CSS específicos para este componente de alerta
import './UserAlertSystem.css'

// Se desestructuran las propiedades reactivas del sistema de alertas: alertState contiene el estado actual y closeAlert es la función para cerrar
const { alertState, closeAlert } = useAlertSystem()
// Referencia reactiva que almacena la clave de la acción actualmente en proceso (para mostrar estado de carga y deshabilitar otras acciones)
const activeActionKey = ref('')

// Función que solicita cerrar la alerta, solo permite cerrar si no hay ninguna acción en proceso
function requestCloseAlert() {
  // Si hay una acción activa en curso, se bloquea el cierre para evitar interrupciones
  if (activeActionKey.value) return
  // Llama a la función del composable para cerrar la alerta y limpiar el estado
  closeAlert()
}

// Propiedad computada que determina la clase CSS del ícono según el tipo de alerta
const iconClass = computed(() => {
  // Mapa de tipos de alerta a clases de iconos de Font Awesome
  const iconByType = {
    success: 'fas fa-check-circle',    // Ícono de éxito (check verde)
    error: 'fas fa-times-circle',      // Ícono de error (X rojo)
    warning: 'fas fa-exclamation-triangle', // Ícono de advertencia (triángulo amarillo)
    question: 'fas fa-question-circle', // Ícono de pregunta (círculo con ?)
    info: 'fas fa-info-circle',        // Ícono informativo (círculo con i)
  }

  // Retorna la clase correspondiente al tipo de alerta actual, o la de información por defecto
  return iconByType[alertState.type] || iconByType.info
})

// Función que traduce el estilo de acción del botón a la clase CSS correspondiente
function actionClass(style) {
  // Si el estilo es 'secondary', usa la clase 'outline' para botón con borde
  if (style === 'secondary') return 'outline'
  // Si el estilo es 'danger', usa la clase 'danger' para botón rojo de peligro
  if (style === 'danger') return 'danger'
  // Por defecto, usa la clase 'primary' para botón principal con color sólido
  return 'primary'
}

// Función que genera una clave única para cada acción, combinando su texto y posición
function actionKey(action, index) {
  // Si la acción no tiene texto, usa 'accion' como valor predeterminado; concatena con el índice para unicidad
  return `${action?.text || 'accion'}-${index}`
}

// Función asíncrona que maneja la ejecución de una acción al hacer clic en un botón
async function handleAction(action, index) {
  // Evita ejecutar múltiples acciones simultáneamente
  if (activeActionKey.value) return

  // Establece la clave de la acción activa para mostrar indicador de carga y deshabilitar otros botones
  activeActionKey.value = actionKey(action, index)

  try {
    // Si la acción tiene una función de callback definida, la ejecuta (operación asíncrona como confirmar, eliminar, etc.)
    if (typeof action?.callback === 'function') {
      await action.callback()
    }

    // Si la acción no tiene la propiedad closeOnClick en false, cierra la alerta automáticamente después de ejecutarse
    if (action?.closeOnClick !== false) {
      closeAlert()
    }
  } finally {
    // Siempre limpia la referencia de acción activa, incluso si ocurre un error, para permitir nuevas interacciones
    activeActionKey.value = ''
  }
}
</script>
