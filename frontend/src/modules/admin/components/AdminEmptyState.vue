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
defineProps({
  /** Icono FontAwesome */
  icon: { type: String, default: 'fas fa-inbox' },
  /** Titulo del estado vacio */
  title: { type: String, default: 'Sin datos' },
  /** Descripcion opcional */
  description: { type: String, default: null },
})
</script>
