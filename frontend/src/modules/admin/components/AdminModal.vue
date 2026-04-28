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

const props = defineProps({
  show: {
    type: Boolean,
    default: false,
  },
  title: {
    type: String,
    default: '',
  },
  subtitle: {
    type: String,
    default: '',
  },
  icon: {
    type: String,
    default: '',
  },
  maxWidth: {
    type: String,
    default: '860px',
  },
})

const emit = defineEmits(['close'])

const modalStyle = computed(() => ({
  maxWidth: props.maxWidth,
}))
</script>