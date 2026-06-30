<template>
  <div v-if="shouldRender" class="admin-pagination-shell">
    <div class="admin-pagination-shell__summary">
      <strong>{{ startItem }}-{{ endItem }}</strong>
      <span>de {{ totalItems }} registros</span>
    </div>

    <div class="admin-pagination-shell__controls">
      <label class="admin-pagination-shell__page-size">
        <span>Mostrar</span>
        <select :value="pageSize" @change="onPageSizeChange">
          <option v-for="option in normalizedPageSizeOptions" :key="option" :value="option">{{ option }}</option>
        </select>
      </label>

      <div class="admin-pagination">
        <button class="admin-pagination__item" type="button" :disabled="page <= 1" @click="emitPage(1)">
          <i class="fas fa-angle-double-left"></i>
        </button>
        <button class="admin-pagination__item" type="button" :disabled="page <= 1" @click="emitPage(page - 1)">
          <i class="fas fa-angle-left"></i>
        </button>

        <template v-for="item in paginationItems" :key="String(item)">
          <span v-if="item === '...'" class="admin-pagination__item admin-pagination__item--dots">...</span>
          <button
            v-else
            class="admin-pagination__item"
            :class="{ active: Number(item) === page }"
            type="button"
            @click="emitPage(Number(item))"
          >
            {{ item }}
          </button>
        </template>

        <button class="admin-pagination__item" type="button" :disabled="page >= totalPages" @click="emitPage(page + 1)">
          <i class="fas fa-angle-right"></i>
        </button>
        <button class="admin-pagination__item" type="button" :disabled="page >= totalPages" @click="emitPage(totalPages)">
          <i class="fas fa-angle-double-right"></i>
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'

/**
 * Componente de paginación reutilizable del admin.
 * Muestra resumen de registros, selector de tamaño de página
 * y controles de navegación con elipsis para muchas páginas.
 */
const props = defineProps({
  /** Página actual (1-indexed). */
  page: {
    type: Number,
    default: 1,
  },
  /** Cantidad de registros por página. */
  pageSize: {
    type: Number,
    default: 10,
  },
  /** Total de registros disponibles en el servidor. */
  totalItems: {
    type: Number,
    default: 0,
  },
  /** Opciones disponibles para el selector de tamaño de página. */
  pageSizeOptions: {
    type: Array,
    default: () => [10, 20, 50],
  },
})

const emit = defineEmits(['update:page', 'update:pageSize'])

/** Opciones de tamaño de página normalizadas: valores numéricos únicos y ordenados. */
const normalizedPageSizeOptions = computed(() => {
  const options = props.pageSizeOptions
    .map((value) => Number(value))
    .filter((value, index, list) => Number.isFinite(value) && value > 0 && list.indexOf(value) === index)

  return options.length > 0 ? options.sort((a, b) => a - b) : [10, 20, 50]
})

/** Total de páginas calculado a partir del total de registros y el tamaño de página. */
const totalPages = computed(() => Math.max(1, Math.ceil(props.totalItems / props.pageSize)))
/** Primer registro visible en la página actual. */
const startItem = computed(() => (props.totalItems === 0 ? 0 : ((props.page - 1) * props.pageSize) + 1))
/** Último registro visible en la página actual. */
const endItem = computed(() => (props.totalItems === 0 ? 0 : Math.min(props.page * props.pageSize, props.totalItems)))
/** Determina si la paginación debe renderizarse (más registros que el primer tamaño de página). */
const shouldRender = computed(() => props.totalItems > normalizedPageSizeOptions.value[0] || totalPages.value > 1)

/**
 * Genera la lista de elementos de paginación incluyendo números de página
 * y elipsis (...) cuando hay muchas páginas para mantener la UI compacta.
 */
const paginationItems = computed(() => {
  const pages = totalPages.value
  const current = props.page

  if (pages <= 7) {
    return Array.from({ length: pages }, (_, index) => index + 1)
  }

  const items = [1]
  const start = Math.max(2, current - 1)
  const end = Math.min(pages - 1, current + 1)

  if (start > 2) items.push('...')
  for (let page = start; page <= end; page += 1) items.push(page)
  if (end < pages - 1) items.push('...')
  items.push(pages)

  return items
})

/**
 * Emite el evento de cambio de página, asegurando que el número
 * esté dentro del rango válido (1 a totalPages).
 */
function emitPage(page) {
  const nextPage = Math.min(Math.max(1, page), totalPages.value)
  emit('update:page', nextPage)
}

/**
 * Maneja el cambio de tamaño de página desde el selector.
 * Resetea a la página 1 ya que el contenido cambia completamente.
 */
function onPageSizeChange(event) {
  const nextPageSize = Number(event.target.value) || normalizedPageSizeOptions.value[0]
  emit('update:pageSize', nextPageSize)
  emit('update:page', 1)
}
</script>