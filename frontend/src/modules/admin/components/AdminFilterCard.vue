<!-- AdminFilterCard.vue
     Componente reutilizable de filtros para secciones de admin.
     Encapsula: cabecera con toggle, barra de búsqueda y filtros avanzados colapsables.
     Patrón: Template Method — estructura fija con slots para contenido variable. -->
<template>
  <div class="admin-filters">
    <!-- Cabecera: icono + título + botón colapsar -->
    <div class="admin-filters__header">
      <div class="admin-filters__title">
        <i :class="icon"></i>
        <h3>{{ title }}</h3>
      </div>
      <button
        class="admin-filters__toggle"
        :class="{ collapsed: !expanded }"
        :title="expanded ? 'Colapsar filtros' : 'Expandir filtros'"
        @click="expanded = !expanded"
      >
        <i class="fas fa-chevron-up"></i>
      </button>
    </div>

    <!-- Barra de búsqueda -->
    <div v-show="expanded" class="admin-filters__search">
      <div class="admin-filters__search-wrapper">
        <i class="fas fa-search admin-filters__search-icon"></i>
        <input
          class="admin-filters__input"
          type="text"
          :placeholder="placeholder"
          :value="modelValue"
          @input="$emit('update:modelValue', $event.target.value)"
          @keydown.enter="$emit('search')"
        />
        <button
          v-if="modelValue"
          class="admin-filters__search-clear"
          title="Limpiar búsqueda"
          @click="$emit('update:modelValue', ''); $emit('search')"
        >
          <i class="fas fa-times"></i>
        </button>
      </div>
      <button class="admin-filters__submit" @click="$emit('search')">
        <i class="fas fa-search"></i> Buscar
      </button>
    </div>

    <!-- Filtros avanzados (slot) -->
    <div v-if="$slots.advanced && expanded" class="admin-filters__advanced">
      <slot name="advanced"></slot>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'

defineProps({
  /** Clase de icono FontAwesome para la cabecera */
  icon: { type: String, default: 'fas fa-filter' },
  /** Título visible de la sección de filtros */
  title: { type: String, default: 'Busqueda y segmentacion' },
  /** Placeholder del input de búsqueda */
  placeholder: { type: String, default: 'Buscar...' },
  /** Valor del input (v-model) */
  modelValue: { type: String, default: '' },
})

defineEmits(['update:modelValue', 'search'])

// Estado interno de colapso
const expanded = ref(true)
</script>
