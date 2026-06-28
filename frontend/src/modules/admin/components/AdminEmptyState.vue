<template>
  <div class="admin-empty-state">
    <div class="admin-empty-state__icon">
      <i :class="icon"></i>
    </div>
    <h3 class="admin-empty-state__title">{{ title }}</h3>
    <p v-if="description" class="admin-empty-state__desc">{{ description }}</p>
    <div v-if="$slots.default" class="admin-empty-state__actions">
      <slot />
    </div>
  </div>
</template>

<script setup>
/**
 * Componente de estado vacío reutilizable del admin.
 * Se muestra cuando no hay datos disponibles para una vista o sección.
 * Permite personalizar icono, título, descripción y acciones
 * mediante props y slot por defecto para botones o enlaces.
 */
defineProps({
  /** Icono FontAwesome */
  icon: { type: String, default: 'fas fa-inbox' },
  /** Titulo del estado vacio */
  title: { type: String, default: 'Sin datos' },
  /** Descripcion opcional */
  description: { type: String, default: null },
})
</script>
