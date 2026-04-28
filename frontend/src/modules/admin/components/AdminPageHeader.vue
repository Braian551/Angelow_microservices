<template>
  <header class="admin-page-header">
    <div class="admin-page-header__surface">
      <div class="admin-page-header__top">
        <div class="admin-page-header__info">
          <div class="admin-page-header__heading">
            <span v-if="icon" class="admin-page-header__icon">
              <i :class="icon"></i>
            </span>
            <div class="admin-page-header__copy">
              <h1>{{ title }}</h1>
              <p v-if="subtitle" class="admin-page-header__subtitle">{{ subtitle }}</p>
            </div>
          </div>
        </div>
        <div v-if="$slots.actions" class="admin-page-header__actions">
          <slot name="actions" />
        </div>
      </div>
      <nav class="admin-page-header__breadcrumb">
        <RouterLink to="/admin">Dashboard</RouterLink>
        <template v-for="(crumb, idx) in filteredBreadcrumbs" :key="idx">
          <span class="admin-page-header__separator">/</span>
          <RouterLink v-if="crumb.to" :to="crumb.to">{{ crumb.label }}</RouterLink>
          <span v-else>{{ crumb.label }}</span>
        </template>
      </nav>
    </div>
  </header>
</template>

<script setup>
import { computed } from 'vue'
import { RouterLink } from 'vue-router'

const props = defineProps({
  /** Icono FontAwesome, ej: 'fas fa-chart-line' */
  icon: { type: String, default: null },
  /** Titulo principal de la pagina */
  title: { type: String, required: true },
  /** Subtitulo descriptivo */
  subtitle: { type: String, default: null },
  /**
   * Breadcrumbs adicionales despues de "Dashboard".
   * Array de { label, to? }. El ultimo suele ser sin `to`.
   * Si el primer elemento ya tiene label 'Dashboard', se omite para evitar duplicado.
   */
  breadcrumbs: {
    type: Array,
    default: () => [],
  },
})

// Evita mostrar "Dashboard" dos veces si la pagina ya lo incluye como primer item
const filteredBreadcrumbs = computed(() => {
  if (props.breadcrumbs.length && props.breadcrumbs[0].label === 'Dashboard') {
    return props.breadcrumbs.slice(1)
  }
  return props.breadcrumbs
})
</script>
