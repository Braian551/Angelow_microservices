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

const props = defineProps({
  page: {
    type: Number,
    default: 1,
  },
  pageSize: {
    type: Number,
    default: 10,
  },
  totalItems: {
    type: Number,
    default: 0,
  },
  pageSizeOptions: {
    type: Array,
    default: () => [10, 20, 50],
  },
})

const emit = defineEmits(['update:page', 'update:pageSize'])

const normalizedPageSizeOptions = computed(() => {
  const options = props.pageSizeOptions
    .map((value) => Number(value))
    .filter((value, index, list) => Number.isFinite(value) && value > 0 && list.indexOf(value) === index)

  return options.length > 0 ? options.sort((a, b) => a - b) : [10, 20, 50]
})

const totalPages = computed(() => Math.max(1, Math.ceil(props.totalItems / props.pageSize)))
const startItem = computed(() => (props.totalItems === 0 ? 0 : ((props.page - 1) * props.pageSize) + 1))
const endItem = computed(() => (props.totalItems === 0 ? 0 : Math.min(props.page * props.pageSize, props.totalItems)))
const shouldRender = computed(() => props.totalItems > normalizedPageSizeOptions.value[0] || totalPages.value > 1)

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

function emitPage(page) {
  const nextPage = Math.min(Math.max(1, page), totalPages.value)
  emit('update:page', nextPage)
}

function onPageSizeChange(event) {
  const nextPageSize = Number(event.target.value) || normalizedPageSizeOptions.value[0]
  emit('update:pageSize', nextPageSize)
  emit('update:page', 1)
}
</script>