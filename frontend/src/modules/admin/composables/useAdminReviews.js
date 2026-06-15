import { computed, onMounted, ref } from 'vue'
import { catalogHttp } from '../../../services/http'
import { useAlertSystem } from '../../../composables/useAlertSystem'
import { useSnackbarSystem } from '../../../composables/useSnackbarSystem'
import { loadAdminCustomerProfiles, resolveAdminCustomerProfile } from './useAdminCustomerProfiles'
import { useAdminDataExport } from './useAdminDataExport'
import { useAdminPagination } from './useAdminPagination'

export function useAdminReviews() {
  const { showAlert } = useAlertSystem()
  const { showSnackbar } = useSnackbarSystem()
  const { exportData, exportingFormat } = useAdminDataExport()

  const loading = ref(true)
  const showDetailModal = ref(false)
  const reviews = ref([])
  const customerProfiles = ref({})
  const selectedReviewId = ref(null)
  const filters = ref({
    search: '',
    status: 'all',
    rating: '',
    verified: 'all',
  })

  const selectedReview = computed(() => reviews.value.find((review) => review.id === selectedReviewId.value) || null)

  const pagination = useAdminPagination(reviews, {
    initialPageSize: 8,
    pageSizeOptions: [8, 16, 24],
  })

  const activeFilterCount = computed(() => {
    let count = 0
    if (filters.value.search.trim()) count += 1
    if (filters.value.status !== 'all') count += 1
    if (filters.value.rating) count += 1
    if (filters.value.verified !== 'all') count += 1
    return count
  })

  const ratingDistribution = computed(() => {
    const total = reviews.value.length || 1

    return [5, 4, 3, 2, 1].map((rating) => {
      const count = reviews.value.filter((review) => review.rating === rating).length

      return {
        rating,
        count,
        share: reviews.value.length ? (count / total) * 100 : 0,
      }
    })
  })

  // Determina si el gráfico horizontal debe renderizarse o mostrar el estado vacío compartido.
  const hasRatingChartData = computed(() => ratingDistribution.value.some((bucket) => bucket.count > 0))

  // Mantiene una escala vertical estable para que la gráfica siga siendo legible incluso con pocas reseñas.
  const ratingChartMaxValue = computed(() => Math.max(2, ...ratingDistribution.value.map((bucket) => bucket.count + 1)))

  // Construye etiquetas humanas para reutilizar Chart.js sin duplicar formato dentro del template.
  const ratingChartLabels = computed(() => ratingDistribution.value.map((bucket) => `${bucket.rating} estrella${bucket.rating === 1 ? '' : 's'}`))

  // Arma el dataset del gráfico de rating con una sola fuente de verdad basada en la distribución calculada.
  const ratingChartDatasets = computed(() => ([
    {
      label: 'Reseñas',
      data: ratingDistribution.value.map((bucket) => bucket.count),
      backgroundColor: ['#0f88c2', '#2d9fd5', '#58b7e4', '#86ccee', '#b3e1f7'],
      borderRadius: 999,
      borderSkipped: false,
      maxBarThickness: 22,
    },
  ]))

  // Reutiliza la lectura de porcentajes de la distribución para enriquecer el tooltip del gráfico.
  const ratingChartOptions = computed(() => ({
    layout: {
      padding: {
        top: 8,
        right: 10,
        left: 6,
        bottom: 0,
      },
    },
    plugins: {
      legend: {
        display: false,
      },
      tooltip: {
        callbacks: {
          label: (context) => {
            const bucket = ratingDistribution.value[context.dataIndex]
            return `${context.raw} reseñas (${bucket.share.toFixed(0)}%)`
          },
        },
      },
    },
    scales: {
      x: {
        ticks: {
          color: '#23314d',
          font: {
            size: 12,
            weight: '600',
          },
        },
        grid: {
          display: false,
          drawBorder: false,
        },
      },
      y: {
        beginAtZero: true,
        suggestedMax: ratingChartMaxValue.value,
        ticks: {
          precision: 0,
          color: '#6b7280',
        },
        grid: {
          color: 'rgba(148, 184, 216, 0.18)',
          drawBorder: false,
        },
      },
    },
  }))

  const highlightReviews = computed(() => reviews.value.slice(0, 6))

  const reviewStats = computed(() => {
    const total = reviews.value.length
    const approved = reviews.value.filter((review) => review.status === 'approved').length
    const pending = total - approved
    const verified = reviews.value.filter((review) => review.is_verified).length
    const average = total > 0 ? (reviews.value.reduce((sum, review) => sum + review.rating, 0) / total).toFixed(1) : '0.0'

    return [
      { key: 'pending', label: 'Pendientes', value: String(pending), icon: 'fas fa-clock', color: 'warning' },
      { key: 'approved', label: 'Publicadas', value: String(approved), icon: 'fas fa-check-circle', color: 'success' },
      { key: 'average', label: 'Rating promedio', value: average, icon: 'fas fa-star', color: 'info' },
      { key: 'verified', label: 'Verificadas', value: String(verified), icon: 'fas fa-circle-check', color: 'primary' },
      { key: 'total', label: 'Total reseñas', value: String(total), icon: 'fas fa-comments', color: 'primary' },
    ]
  })

  function normalizeReviewStatus(review) {
    if (review.status) {
      return review.status
    }

    return review.is_approved ? 'approved' : 'pending'
  }

  function normalizeReview(review) {
    const profile = resolveAdminCustomerProfile(customerProfiles.value, review.user_id)

    return {
      ...review,
      id: Number(review.id),
      user_id: String(review.user_id || ''),
      rating: Number(review.rating || 0),
      title: review.title || '',
      comment: review.comment || review.body || '',
      is_verified: Boolean(review.is_verified),
      is_approved: Boolean(review.is_approved),
      status: normalizeReviewStatus(review),
      product_name: review.product_name || review.product?.name || 'Producto',
      created_at: review.created_at || null,
      customer: {
        id: String(review.user_id || ''),
        name: profile?.name || `Cliente ${review.user_id || ''}`,
        email: profile?.email || '',
        image: profile?.image || '',
      },
    }
  }

  function reviewStatusLabel(status) {
    return status === 'approved' ? 'Publicada' : 'Pendiente'
  }

  function formatDate(value) {
    if (!value) return 'Sin fecha'

    const date = new Date(value)
    return Number.isNaN(date.getTime()) ? 'Sin fecha' : date.toLocaleDateString('es-CO')
  }

  function formatDateTime(value) {
    if (!value) return 'Sin fecha'

    const date = new Date(value)
    return Number.isNaN(date.getTime()) ? 'Sin fecha' : date.toLocaleString('es-CO')
  }

  function renderStars(rating) {
    return Array.from({ length: 5 }, (_, index) => {
      const filled = index < rating
      return `<i class="${filled ? 'fas' : 'far'} fa-star"></i>`
    }).join('')
  }

  function clearAllFilters() {
    filters.value = {
      search: '',
      status: 'all',
      rating: '',
      verified: 'all',
    }
    loadReviews()
  }

  let debounceTimer = null

  function debouncedLoadReviews() {
    clearTimeout(debounceTimer)
    debounceTimer = setTimeout(() => {
      loadReviews()
    }, 350)
  }

  async function loadReviews() {
    loading.value = true

    try {
      const params = {}
      if (filters.value.search.trim()) params.search = filters.value.search.trim()
      if (filters.value.status !== 'all') params.status = filters.value.status
      if (filters.value.rating) params.rating = filters.value.rating
      if (filters.value.verified !== 'all') params.verified = String(filters.value.verified === 'verified')

      const response = await catalogHttp.get('/admin/reviews', { params })
      const payload = response.data?.data || response.data || []
      const rows = Array.isArray(payload) ? payload : (payload.data || [])

      customerProfiles.value = await loadAdminCustomerProfiles(rows.map((row) => row.user_id).filter(Boolean))
      reviews.value = rows.map(normalizeReview)
    } catch {
      showSnackbar({ type: 'error', message: 'Error cargando reseñas' })
    } finally {
      loading.value = false
    }
  }

  function openReviewModal(review) {
    selectedReviewId.value = review.id
    showDetailModal.value = true
  }

  function closeReviewModal() {
    showDetailModal.value = false
  }

  function confirmReviewStatus(review, status) {
    showAlert({
      type: 'warning',
      title: 'Actualizar reseña',
      message: status === 'approved'
        ? `¿Deseas publicar la reseña de ${review.customer.name}?`
        : `¿Deseas devolver la reseña de ${review.customer.name} a revisión?`,
      actions: [
        { text: 'Cancelar', style: 'secondary' },
        {
          text: status === 'approved' ? 'Publicar' : 'Enviar a revisión',
          style: 'primary',
          callback: async () => {
            try {
              await catalogHttp.patch(`/admin/reviews/${review.id}`, { status })
              showSnackbar({ type: 'success', message: 'Reseña actualizada' })
              await loadReviews()
            } catch {
              showSnackbar({ type: 'error', message: 'Error actualizando la reseña' })
            }
          },
        },
      ],
    })
  }

  function toggleReviewVerified(review) {
    const targetValue = !review.is_verified

    showAlert({
      type: 'warning',
      title: targetValue ? 'Marcar compra verificada' : 'Quitar verificación',
      message: targetValue
        ? `¿Deseas marcar como verificada la reseña de ${review.customer.name}?`
        : `¿Deseas quitar la verificación de la reseña de ${review.customer.name}?`,
      actions: [
        { text: 'Cancelar', style: 'secondary' },
        {
          text: targetValue ? 'Verificar' : 'Quitar',
          style: 'primary',
          callback: async () => {
            try {
              await catalogHttp.patch(`/admin/reviews/${review.id}`, { is_verified: targetValue })
              showSnackbar({ type: 'success', message: 'Verificación actualizada' })
              await loadReviews()
            } catch {
              showSnackbar({ type: 'error', message: 'Error actualizando la verificación' })
            }
          },
        },
      ],
    })
  }

  function deleteReview(review) {
    showAlert({
      type: 'warning',
      title: 'Eliminar reseña',
      message: `¿Deseas eliminar la reseña de ${review.customer.name}? Esta acción no se puede deshacer.`,
      actions: [
        { text: 'Cancelar', style: 'secondary' },
        {
          text: 'Eliminar',
          style: 'primary',
          callback: async () => {
            try {
              await catalogHttp.delete(`/admin/reviews/${review.id}`)
              showSnackbar({ type: 'success', message: 'Reseña eliminada' })
              if (selectedReviewId.value === review.id) {
                closeReviewModal()
              }
              await loadReviews()
            } catch {
              showSnackbar({ type: 'error', message: 'Error eliminando la reseña' })
            }
          },
        },
      ],
    })
  }

  function buildReviewExportColumns() {
    return [
      {
        header: 'Avatar',
        includeInExcel: false,
        pdfImage: (review) => review.customer.image,
        fallbackType: 'avatar',
        pdfWidth: 18,
        pdfImageSize: 11,
      },
      { header: 'Cliente', value: (review) => review.customer.name },
      { header: 'Producto', value: (review) => review.product_name },
      { header: 'Rating', value: (review) => Number(review.rating || 0), excelType: 'number', align: 'center', width: 12 },
      { header: 'Estado', value: (review) => reviewStatusLabel(review.status), width: 14 },
      { header: 'Verificada', value: (review) => (review.is_verified ? 'Sí' : 'No'), width: 13 },
      { header: 'Fecha', value: (review) => formatDateTime(review.created_at), width: 18 },
      { header: 'Comentario', value: (review) => review.comment || 'Sin comentario', width: 32 },
    ]
  }

  // Exporta la bandeja actual desde la plantilla común para evitar otro CSV manual local.
  function exportReviews(format) {
    return exportData({
      format,
      fileBaseName: 'resenas-admin',
      sheetName: 'Reseñas',
      title: 'Reseñas',
      subtitle: 'Resumen exportado desde la bandeja de moderación de reseñas.',
      columns: buildReviewExportColumns(),
      rows: reviews.value,
      landscape: true,
      emptyMessage: 'No hay reseñas para exportar.',
    })
  }

  onMounted(loadReviews)

  return {
    activeFilterCount,
    clearAllFilters,
    closeReviewModal,
    confirmReviewStatus,
    debouncedLoadReviews,
    deleteReview,
    exportReviews,
    exportingFormat,
    filters,
    formatDate,
    formatDateTime,
    hasRatingChartData,
    highlightReviews,
    loadReviews,
    loading,
    openReviewModal,
    pagination,
    ratingChartDatasets,
    ratingChartLabels,
    ratingChartOptions,
    renderStars,
    reviews,
    reviewStats,
    reviewStatusLabel,
    selectedReview,
    showDetailModal,
    toggleReviewVerified,
  }
}
