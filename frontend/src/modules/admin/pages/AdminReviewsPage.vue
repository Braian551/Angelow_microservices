<template>
  <div class="admin-reviews-page">
    <AdminPageHeader
      icon="fas fa-star"
      title="Reseñas"
      subtitle="Modera la reputación y la visibilidad del catálogo con la misma experiencia del panel administrativo."
      :breadcrumbs="[{ label: 'Reseñas' }]"
    />

    <AdminStatsGrid :loading="loading" :count="5" :stats="reviewStats" />

    <section class="insights-grid">
      <AdminCard title="Distribución de rating" icon="fas fa-chart-column">
        <AdminChartPanel
          :has-data="hasRatingChartData"
          type="bar"
          :labels="ratingChartLabels"
          :datasets="ratingChartDatasets"
          :options="ratingChartOptions"
          empty-icon="fas fa-chart-column"
          empty-title="Sin datos para graficar"
          empty-description="No hay reseñas suficientes para dibujar la distribución de rating."
          :height="220"
        />
      </AdminCard>

      <AdminCard title="Últimas reseñas" icon="fas fa-comments">
        <div v-if="highlightReviews.length === 0" class="detail-empty">Sin actividad reciente.</div>
        <div v-else class="highlights-list">
          <button
            v-for="review in highlightReviews"
            :key="review.id"
            type="button"
            class="highlight-item"
            @click="openReviewModal(review)"
          >
            <div class="highlight-item__info">
              <strong class="highlight-item__name">{{ review.customer.name }}</strong>
              <span class="highlight-item__product">{{ review.product_name }}</span>
            </div>
            <div class="highlight-meta">
              <span class="stars-inline" v-html="renderStars(review.rating)"></span>
              <small>{{ formatDate(review.created_at) }}</small>
            </div>
          </button>
        </div>
      </AdminCard>
    </section>

    <AdminFilterCard
      v-model="filters.search"
      class="admin-insights-search-panel"
      icon="fas fa-filter"
      title="Bandeja de moderación"
      placeholder="Buscar por título, comentario o producto..."
      @search="loadReviews"
      @update:model-value="debouncedLoadReviews"
    >
      <template #advanced>
        <div class="admin-filters__row admin-filters__row--3">
          <div class="admin-filters__group">
            <label for="review-status"><i class="fas fa-flag"></i> Estado</label>
            <select id="review-status" v-model="filters.status" @change="loadReviews">
              <option value="all">Todas</option>
              <option value="pending">Pendientes</option>
              <option value="approved">Publicadas</option>
            </select>
          </div>

          <div class="admin-filters__group">
            <label for="review-rating"><i class="fas fa-star"></i> Rating</label>
            <select id="review-rating" v-model="filters.rating" @change="loadReviews">
              <option value="">Cualquiera</option>
              <option value="5">5 estrellas</option>
              <option value="4">4 estrellas</option>
              <option value="3">3 estrellas</option>
              <option value="2">2 estrellas</option>
              <option value="1">1 estrella</option>
            </select>
          </div>

          <div class="admin-filters__group">
            <label for="review-verified"><i class="fas fa-circle-check"></i> Compra verificada</label>
            <select id="review-verified" v-model="filters.verified" @change="loadReviews">
              <option value="all">Todas</option>
              <option value="verified">Solo verificadas</option>
              <option value="unverified">Sin verificar</option>
            </select>
          </div>
        </div>

        <div class="admin-filters__actions">
          <div class="admin-filters__active">
            <i class="fas fa-sliders-h"></i>
            <span>{{ activeFilterCount }} {{ activeFilterCount === 1 ? 'filtro activo' : 'filtros activos' }}</span>
          </div>
          <div class="admin-filters__actions-buttons">
            <button type="button" class="admin-filters__clear" @click="clearAllFilters">
              <i class="fas fa-times-circle"></i> Limpiar todo
            </button>
          </div>
        </div>
      </template>
    </AdminFilterCard>

    <AdminResultsBar :text="`Mostrando ${pagination.visibleCount} de ${pagination.totalItems} reseñas`">
      <template #actions>
        <AdminExportActions
          tone="results"
          :disabled="reviews.length === 0"
          :excel-loading="exportingFormat === 'excel'"
          :pdf-loading="exportingFormat === 'pdf'"
          @excel="exportReviews('excel')"
          @pdf="exportReviews('pdf')"
        />
      </template>
    </AdminResultsBar>

    <AdminCard :flush="true">
      <div v-if="loading" class="cards-loading">
        <AdminTableShimmer :rows="4" :columns="['line', 'line', 'line', 'pill']" />
      </div>
      <AdminEmptyState
        v-else-if="reviews.length === 0"
        icon="fas fa-star"
        title="Sin reseñas"
        description="No se encontraron reseñas con los filtros actuales."
      />
      <div v-else class="reviews-grid">
        <article v-for="review in pagination.paginatedItems" :key="review.id" class="review-card review-card--admin">
          <div class="review-card__header">
            <div class="review-customer">
              <img :src="avatarUrl(review.customer)" :alt="review.customer.name" @error="onAvatarError($event, review.customer.image)">
              <div>
                <strong>{{ review.customer.name }}</strong>
                <span>{{ review.product_name }}</span>
              </div>
            </div>
            <div class="review-badges">
              <span class="status-badge" :class="review.status">{{ reviewStatusLabel(review.status) }}</span>
              <span v-if="review.is_verified" class="status-badge active">Verificada</span>
            </div>
          </div>

          <div class="review-card__content" @click="openReviewModal(review)">
            <div class="review-card__rating" v-html="renderStars(review.rating)"></div>
            <h3>{{ review.title || 'Sin título' }}</h3>
            <p>{{ review.comment || 'Sin comentario' }}</p>
          </div>

          <div class="review-card__footer">
            <small>{{ formatDateTime(review.created_at) }}</small>
            <div class="admin-entity-actions">
              <button class="action-btn view" type="button" title="Ver detalle" @click="openReviewModal(review)">
                <i class="fas fa-eye"></i>
              </button>
              <button
                v-if="review.status !== 'approved'"
                class="action-btn edit"
                type="button"
                title="Publicar reseña"
                @click="confirmReviewStatus(review, 'approved')"
              >
                <i class="fas fa-check"></i>
              </button>
              <button
                v-else
                class="action-btn"
                type="button"
                title="Enviar a revisión"
                @click="confirmReviewStatus(review, 'pending')"
              >
                <i class="fas fa-rotate-left"></i>
              </button>
              <button
                class="action-btn"
                :class="review.is_verified ? 'edit' : 'view'"
                type="button"
                :title="review.is_verified ? 'Quitar verificación' : 'Marcar verificada'"
                @click="toggleReviewVerified(review)"
              >
                <i class="fas fa-circle-check"></i>
              </button>
              <button class="action-btn delete" type="button" title="Eliminar reseña" @click="deleteReview(review)">
                <i class="fas fa-trash"></i>
              </button>
            </div>
          </div>
        </article>
      </div>
    </AdminCard>

    <AdminPagination
      v-model:page="pagination.currentPage"
      v-model:page-size="pagination.pageSize"
      :total-items="pagination.totalItems"
      :page-size-options="pagination.pageSizeOptions"
    />

    <AdminModal :show="showDetailModal" :title="selectedReview ? selectedReview.title || 'Detalle de reseña' : 'Detalle de reseña'" max-width="980px" @close="closeReviewModal">
      <template v-if="selectedReview">
        <div class="admin-reviews-page admin-reviews-page--modal">
          <div class="review-detail-grid">
          <div>
            <AdminCard title="Contenido" icon="fas fa-message">
              <div class="review-customer review-customer--detail">
                <img :src="avatarUrl(selectedReview.customer)" :alt="selectedReview.customer.name" @error="onAvatarError($event, selectedReview.customer.image)">
                <div>
                  <strong>{{ selectedReview.customer.name }}</strong>
                  <span>{{ selectedReview.customer.email || `ID ${selectedReview.user_id}` }}</span>
                </div>
              </div>
              <div class="review-card__rating review-card__rating--detail" v-html="renderStars(selectedReview.rating)"></div>
              <p class="review-detail-title">{{ selectedReview.title || 'Sin título' }}</p>
              <p class="review-detail-comment">{{ selectedReview.comment || 'Sin comentario' }}</p>
            </AdminCard>
          </div>

          <div>
            <AdminCard title="Moderación" icon="fas fa-shield-halved">
              <div class="admin-detail-summary">
                <div class="admin-detail-summary__row"><span>Producto</span><strong>{{ selectedReview.product_name }}</strong></div>
                <div class="admin-detail-summary__row"><span>Estado</span><strong>{{ reviewStatusLabel(selectedReview.status) }}</strong></div>
                <div class="admin-detail-summary__row"><span>Verificación</span><strong>{{ selectedReview.is_verified ? 'Verificada' : 'Pendiente' }}</strong></div>
                <div class="admin-detail-summary__row"><span>Fecha</span><strong>{{ formatDateTime(selectedReview.created_at) }}</strong></div>
              </div>
            </AdminCard>

            <AdminCard title="Acciones" icon="fas fa-bolt" style="margin-top: 1.2rem;">
              <div class="modal-actions-grid">
                <button
                  class="modal-action-button modal-action-button--primary"
                  type="button"
                  @click="confirmReviewStatus(selectedReview, 'approved')"
                >
                  <i class="fas fa-check"></i>
                  <span class="modal-action-button__content">
                    <strong>Publicar</strong>
                    <small>Hace visible la reseña en el catálogo.</small>
                  </span>
                </button>
                <button
                  class="modal-action-button modal-action-button--neutral"
                  type="button"
                  @click="confirmReviewStatus(selectedReview, 'pending')"
                >
                  <i class="fas fa-rotate-left"></i>
                  <span class="modal-action-button__content">
                    <strong>Enviar a revisión</strong>
                    <small>La devuelve a moderación antes de mostrarla.</small>
                  </span>
                </button>
                <button
                  class="modal-action-button modal-action-button--soft"
                  type="button"
                  @click="toggleReviewVerified(selectedReview)"
                >
                  <i class="fas fa-circle-check"></i>
                  <span class="modal-action-button__content">
                    <strong>{{ selectedReview.is_verified ? 'Quitar verificación' : 'Marcar verificada' }}</strong>
                    <small>Actualiza la compra verificada del cliente.</small>
                  </span>
                </button>
                <button
                  class="modal-action-button modal-action-button--danger"
                  type="button"
                  @click="deleteReview(selectedReview)"
                >
                  <i class="fas fa-trash"></i>
                  <span class="modal-action-button__content">
                    <strong>Eliminar reseña</strong>
                    <small>Quita definitivamente este comentario del panel.</small>
                  </span>
                </button>
              </div>
            </AdminCard>
          </div>
          </div>
        </div>
      </template>

      <template #footer>
        <button class="btn btn-secondary" type="button" @click="closeReviewModal">Cerrar</button>
      </template>
    </AdminModal>
  </div>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue'
import '../views/AdminReviewsPage.css'
import { catalogHttp } from '../../../services/http'
import { useAlertSystem } from '../../../composables/useAlertSystem'
import { useSnackbarSystem } from '../../../composables/useSnackbarSystem'
import { handleMediaError, resolveMediaUrl } from '../../../utils/media'
import { loadAdminCustomerProfiles, resolveAdminCustomerProfile } from '../composables/useAdminCustomerProfiles'
import { useAdminDataExport } from '../composables/useAdminDataExport'
import { useAdminPagination } from '../composables/useAdminPagination'
import AdminCard from '../components/AdminCard.vue'
import AdminChartPanel from '../components/AdminChartPanel.vue'
import AdminEmptyState from '../components/AdminEmptyState.vue'
import AdminExportActions from '../components/AdminExportActions.vue'
import AdminFilterCard from '../components/AdminFilterCard.vue'
import AdminModal from '../components/AdminModal.vue'
import AdminPagination from '../components/AdminPagination.vue'
import AdminPageHeader from '../components/AdminPageHeader.vue'
import AdminResultsBar from '../components/AdminResultsBar.vue'
import AdminStatsGrid from '../components/AdminStatsGrid.vue'
import AdminTableShimmer from '../components/AdminTableShimmer.vue'

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
  if (filters.value.search.trim()) count++
  if (filters.value.status !== 'all') count++
  if (filters.value.rating) count++
  if (filters.value.verified !== 'all') count++
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

function avatarUrl(customer) {
  return resolveMediaUrl(customer?.image, 'avatar')
}

function onAvatarError(event, originalPath) {
  handleMediaError(event, originalPath, 'avatar')
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

// Reutiliza el mismo dataset moderado para Excel y PDF, agregando avatar solo en el PDF.
function buildReviewExportColumns() {
  return [
    {
      header: 'Avatar',
      includeInExcel: false,
      pdfImage: (review) => avatarUrl(review.customer),
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
</script>

