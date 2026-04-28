import { computed, reactive, ref, unref, watch } from 'vue'

function normalizePageSizeOptions(options, fallback) {
  const normalized = Array.isArray(options)
    ? [...new Set(options.map((value) => Number(value)).filter((value) => Number.isFinite(value) && value > 0))]
    : fallback

  return normalized.length > 0 ? normalized.sort((a, b) => a - b) : fallback
}

export function useAdminPagination(itemsSource, options = {}) {
  const pageSizeOptions = normalizePageSizeOptions(options.pageSizeOptions, [10, 20, 50])
  const configuredPageSize = Number(options.initialPageSize || pageSizeOptions[0])
  const initialPageSize = pageSizeOptions.includes(configuredPageSize) ? configuredPageSize : pageSizeOptions[0]

  const currentPage = ref(1)
  const pageSize = ref(initialPageSize)

  const sourceItems = computed(() => {
    const items = unref(itemsSource)
    return Array.isArray(items) ? items : []
  })

  const totalItems = computed(() => sourceItems.value.length)
  const totalPages = computed(() => Math.max(1, Math.ceil(totalItems.value / pageSize.value)))
  const startIndex = computed(() => (currentPage.value - 1) * pageSize.value)

  const paginatedItems = computed(() => sourceItems.value.slice(startIndex.value, startIndex.value + pageSize.value))
  const visibleCount = computed(() => paginatedItems.value.length)
  const startItem = computed(() => (totalItems.value === 0 ? 0 : startIndex.value + 1))
  const endItem = computed(() => (totalItems.value === 0 ? 0 : Math.min(startIndex.value + visibleCount.value, totalItems.value)))

  const paginationItems = computed(() => {
    const pages = totalPages.value
    const current = currentPage.value

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

  function clampCurrentPage() {
    currentPage.value = Math.min(Math.max(1, currentPage.value), totalPages.value)
  }

  watch(sourceItems, () => {
    if (options.resetOnItemsChange !== false) {
      currentPage.value = 1
      return
    }

    clampCurrentPage()
  })

  watch(pageSize, () => {
    currentPage.value = 1
  })

  watch(totalPages, () => {
    clampCurrentPage()
  })

  return reactive({
    currentPage,
    pageSize,
    pageSizeOptions,
    totalItems,
    totalPages,
    visibleCount,
    startItem,
    endItem,
    paginatedItems,
    paginationItems,
  })
}