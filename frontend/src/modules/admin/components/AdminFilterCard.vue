<!-- AdminFilterCard.vue
     Componente reutilizable de filtros para secciones de admin.
     Encapsula: barra unificada (icono + título + búsqueda + toggle), slot de contenido
     siempre visible (#default) y filtros avanzados colapsables (#advanced).
     Patrón: Template Method — estructura fija con slots para contenido variable. -->
<template>
  <div class="admin-filters admin-filters--animated">
    <!-- Cabecera superior: título + toggle -->
    <div class="admin-filters__header">
      <div class="admin-filters__identity">
        <i :class="icon"></i>
        <h3>{{ title }}</h3>
      </div>

      <button
        v-if="$slots.advanced || $slots.default"
        class="admin-filters__toggle"
        :class="{ collapsed: !expanded }"
        :title="expanded ? 'Ocultar filtros' : 'Mostrar filtros'"
        @click="expanded = !expanded"
      >
        <i class="fas fa-chevron-down"></i>
      </button>
    </div>

    <div class="admin-filters__search-row">
      <div class="admin-filters__search-shell">
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
      </div>

      <button class="admin-filters__submit" @click="$emit('search')">
        <i class="fas fa-search"></i>
        <span>Buscar</span>
      </button>
    </div>

    <!-- Slot siempre visible (ej: pestañas de sección), sólo cuando hay contenido -->
    <Transition name="admin-filters-reveal" appear>
      <div v-if="$slots.default && expanded" class="admin-filters__prefix">
        <slot></slot>
      </div>
    </Transition>

    <!-- Filtros avanzados colapsables -->
    <Transition name="admin-filters-reveal" appear>
      <div v-if="$slots.advanced && expanded" class="admin-filters__advanced">
        <slot name="advanced"></slot>
      </div>
    </Transition>
  </div>
</template>

<script setup>
import { ref } from 'vue'

defineProps({
  /** Clase de icono FontAwesome para la cabecera */
  icon: { type: String, default: 'fas fa-filter' },
  /** Título visible de la sección de filtros */
  title: { type: String, default: 'Búsqueda y segmentación' },
  /** Placeholder del input de búsqueda */
  placeholder: { type: String, default: 'Buscar...' },
  /** Valor del input (v-model) */
  modelValue: { type: String, default: '' },
})

defineEmits(['update:modelValue', 'search'])

// Estado interno de colapso
const expanded = ref(false)
</script>
