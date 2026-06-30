<template>
  <Teleport to="body">
    <div v-if="show" class="admin-modal-overlay" @click.self="emit('close')">
      <div class="admin-modal" :style="modalStyle" role="dialog" aria-modal="true">
        <div class="admin-modal-header">
          <slot name="header">
            <div v-if="icon" class="admin-modal-header__icon">
              <i :class="icon"></i>
            </div>
            <div class="admin-modal-header__text">
              <h3>{{ title }}</h3>
              <p v-if="subtitle">{{ subtitle }}</p>
            </div>
          </slot>

          <button type="button" class="admin-modal-close" aria-label="Cerrar modal" @click="emit('close')">
            &times;
          </button>
        </div>

        <div class="admin-modal-body">
          <slot />
        </div>

        <div v-if="$slots.footer" class="admin-modal-footer">
          <slot name="footer" />
        </div>
      </div>
    </div>
  </Teleport>
</template>

<script setup>
import { computed } from 'vue'

/**
 * Componente modal reutilizable del admin.
 * Se teletransporta al body para evitar problemas de overflow
 * y emite 'close' al hacer clic fuera del contenido o en el botón de cerrar.
 */
const props = defineProps({
  /** Controla la visibilidad del modal. */
  show: {
    type: Boolean,
    default: false,
  },
  /** Título principal que se muestra en el encabezado del modal. */
  title: {
    type: String,
    default: '',
  },
  /** Subtítulo descriptivo debajo del título. */
  subtitle: {
    type: String,
    default: '',
  },
  /** Clase de icono FontAwesome para el encabezado. */
  icon: {
    type: String,
    default: '',
  },
  /** Ancho máximo del modal, acepta cualquier valor CSS válido. */
  maxWidth: {
    type: String,
    default: '860px',
  },
})

const emit = defineEmits(['close'])

/** Estilo dinámico que aplica el ancho máximo configurado. */
const modalStyle = computed(() => ({
  maxWidth: props.maxWidth,
}))
</script>