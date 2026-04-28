<template>
  <section v-if="totalPages > 1" class="store-pagination" aria-label="Paginación de productos">
    <button
      type="button"
      class="store-page-btn store-page-btn--nav"
      :disabled="page <= 1"
      @click="emitPage(page - 1)"
    >
      <i class="fas fa-chevron-left"></i>
      <span>Anterior</span>
    </button>

    <template v-for="item in paginationItems" :key="String(item)">
      <span v-if="item === '...'" class="store-page-dots">...</span>
      <button
        v-else
        type="button"
        class="store-page-btn"
        :class="{ active: item === page }"
        @click="emitPage(item)"
      >
        {{ item }}
      </button>
    </template>

    <button
      type="button"
      class="store-page-btn store-page-btn--nav"
      :disabled="page >= totalPages"
      @click="emitPage(page + 1)"
    >
      <span>Siguiente</span>
      <i class="fas fa-chevron-right"></i>
    </button>
  </section>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  page: {
    type: Number,
    default: 1,
  },
  totalPages: {
    type: Number,
    default: 1,
  },
})

const emit = defineEmits(['change'])

const paginationItems = computed(() => {
  const current = Number(props.page || 1)
  const last = Number(props.totalPages || 1)

  if (last <= 1) {
    return []
  }

  const delta = 2
  const left = current - delta
  const right = current + delta + 1
  const range = []
  const rangeWithDots = []
  let previous

  for (let index = 1; index <= last; index += 1) {
    if (index === 1 || index === last || (index >= left && index < right)) {
      range.push(index)
    }
  }

  // Inserta puntos suspensivos solo cuando hay saltos entre bloques de páginas.
  for (const value of range) {
    if (previous) {
      if (value - previous === 2) {
        rangeWithDots.push(previous + 1)
      } else if (value - previous > 2) {
        rangeWithDots.push('...')
      }
    }

    rangeWithDots.push(value)
    previous = value
  }

  return rangeWithDots
})

function emitPage(nextPage) {
  const targetPage = Number(nextPage)

  if (!Number.isInteger(targetPage)) {
    return
  }

  if (targetPage < 1 || targetPage > props.totalPages || targetPage === props.page) {
    return
  }

  emit('change', targetPage)
}
</script>
