<template>
  <!-- Teleport transporta el snackbar al body para evitar problemas de z-index y posicionamiento -->
  <Teleport to="body">
    <!-- Transition envuelve el snackbar para animar su entrada y salida con la clase 'snackbar-slide' -->
    <Transition name="snackbar-slide">
      <!-- El snackbar solo se muestra cuando snackbarState.visible es true; la clase dinámica aplicará estilos según el tipo (success, error, warning, info) -->
      <div v-if="snackbarState.visible" class="snackbar" :class="`snackbar-${snackbarState.type}`" role="status" aria-live="polite">
        <!-- Contenedor del icono que cambia según el tipo de notificación -->
        <div class="snackbar-icon">
          <i :class="iconClass" />
        </div>

        <!-- Contenido del snackbar: título opcional y mensaje obligatorio -->
        <div class="snackbar-content">
          <!-- El título solo se renderiza si existe en el estado (v-if condicional) -->
          <strong v-if="snackbarState.title" class="snackbar-title">{{ snackbarState.title }}</strong>
          <p class="snackbar-message">{{ snackbarState.message }}</p>
        </div>

        <!-- Botón para cerrar manualmente el snackbar, con aria-label para accesibilidad -->
        <button type="button" class="snackbar-close" aria-label="Cerrar notificacion" @click="closeSnackbar">
          <i class="fas fa-times" />
        </button>
      </div>
    </Transition>
  </Teleport>
</template>

<script setup>
// Importación de computed de Vue para crear propiedades computadas reactivas
import { computed } from 'vue'
// Importación del composable que gestiona el estado global del sistema de snackbar
import { useSnackbarSystem } from '../../composables/useSnackbarSystem'
// Importación de estilos CSS específicos para este componente
import './UserSnackbarSystem.css'

// Se extraen snackbarState (estado reactivo con visible, type, title, message) y closeSnackbar (función para ocultar) del composable
const { snackbarState, closeSnackbar } = useSnackbarSystem()

// Computed que determina la clase CSS del icono según el tipo de notificación
// Se recalcula automáticamente cuando snackbarState.type cambia
const iconClass = computed(() => {
  // Mapa de tipos de notificación a clases de Font Awesome
  const iconByType = {
    success: 'fas fa-check-circle',    // Icono de palomera para éxito
    error: 'fas fa-times-circle',      // Icono de X para errores
    warning: 'fas fa-exclamation-triangle', // Icono de triángulo de advertencia
    info: 'fas fa-info-circle',        // Icono de información para mensajes informativos
  }

  // Retorna el icono correspondiente al tipo actual, o info como valor por defecto si el tipo no existe en el mapa
  return iconByType[snackbarState.type] || iconByType.info
})
</script>
